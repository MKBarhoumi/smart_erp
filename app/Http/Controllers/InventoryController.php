<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('track_inventory', true)
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $lowStockCount = Product::where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'min_stock_alert')
            ->where('min_stock_alert', '>', 0)
            ->count();

        $recentMovements = StockMovement::with('product:id,code,name')
            ->latest('created_at')
            ->take(20)
            ->get();

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'lowStockCount' => $lowStockCount,
            'recentMovements' => $recentMovements,
            'filters' => request()->only('search'),
        ]);
    }

    public function history(Request $request): Response
    {
        $movements = StockMovement::with([
            'product:id,code,name',
            'performer:id,name',
        ])
            ->when($request->input('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('date_from'), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $products = Product::where('track_inventory', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Inventory/History', [
            'movements' => $movements,
            'products' => $products,
            'filters' => $request->only(['product_id', 'type', 'date_from', 'date_to']),
        ]);
    }

    public function adjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'type' => ['required', 'string', 'in:in,out,adjustment'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            if (!$product->track_inventory) {
                return back()->with('error', 'Le suivi de stock n\'est pas activé pour ce produit.');
            }

            $stockBefore = $product->current_stock;

            $newStock = match ($validated['type']) {
                'in' => bcadd($stockBefore, $validated['quantity'], 3),
                'out' => bcsub($stockBefore, $validated['quantity'], 3),
                'adjustment' => number_format((float) $validated['quantity'], 3, '.', ''),
            };

            if (bccomp($newStock, '0', 3) < 0) {
                return back()->with('error', 'Le stock ne peut pas être négatif.');
            }

            $product->update(['current_stock' => $newStock]);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'reason' => $validated['reason'] ?? null,
                'performed_by' => $request->user()->id,
            ]);

            return back()->with('success', 'Mouvement de stock enregistré.');
        });
    }
}
