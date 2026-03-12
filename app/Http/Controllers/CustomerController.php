<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\MatriculeFiscaleValidator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly MatriculeFiscaleValidator $mfValidator,
    ) {
    }

    public function index(): Response
    {
        $customers = Customer::query()
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('identifier_value', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(request('identifier_type'), function ($query, $type) {
                $query->where('identifier_type', $type);
            })
            ->when(request('city'), function ($query, $city) {
                $query->where('city', 'like', "%{$city}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => request()->only('search', 'identifier_type', 'city'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Customer::class);
        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);
        $data = $request->validated();

        // Validate MF if present
        if (!empty($data['matricule_fiscal'])) {
            $mfResult = $this->mfValidator->validateMatriculeFiscale($data['matricule_fiscal']);
            if (!$mfResult['valid']) {
                return back()->withErrors(['matricule_fiscal' => $mfResult['message']]);
            }
        }

        Customer::create($data);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['oldinvoices' => function ($query) {
            $query->latest('oldinvoice_date')->take(20);
        }]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('update', $customer);
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);
        $data = $request->validated();

        if (!empty($data['matricule_fiscal'])) {
            $mfResult = $this->mfValidator->validateMatriculeFiscale($data['matricule_fiscal']);
            if (!$mfResult['valid']) {
                return back()->withErrors(['matricule_fiscal' => $mfResult['message']]);
            }
        }

        $customer->update($data);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);
        if ($customer->oldinvoices()->exists()) {
            return back()->with('error', 'Cannot delete a customer with existing oldinvoices.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
