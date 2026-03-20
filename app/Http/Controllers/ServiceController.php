<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $services = Service::query()
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when(request('category') !== null && request('category') !== '', function ($query) {
                $query->where('category', request('category'));
            })
            ->when(request('status') !== null && request('status') !== '', function ($query) {
                $query->where('is_active', request('status') === 'active');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => request()->only('search', 'category', 'status'),
            'categories' => Service::categories(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Service::class);
        return Inertia::render('Services/Create', [
            'categories' => Service::categories(),
            'billingUnits' => Service::billingUnits(),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Service::class);
        Service::create($request->validated());

        return redirect()->route('services.index')
            ->with('success', 'Service created successfully.');
    }

    public function show(Service $service): Response
    {
        return Inertia::render('Services/Show', [
            'service' => $service,
        ]);
    }

    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);
        return Inertia::render('Services/Edit', [
            'service' => $service,
            'categories' => Service::categories(),
            'billingUnits' => Service::billingUnits(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);
        $service->update($request->validated());

        return redirect()->route('services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);
        
        // Check if service is used in any invoices
        if ($service->invoiceLines()->exists()) {
            return back()->with('error', 'Cannot delete a service that has been used in invoices.');
        }

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
