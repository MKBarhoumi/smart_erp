<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Tenancy;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register tenancy event listeners
        $this->registerEventListeners();

        // Register tenancy middleware
        $this->registerMiddleware();
    }

    /**
     * Register tenancy event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Tenant initialized event
        Event::listen(Events\TenancyInitialized::class, function ($event) {
            // This will be called when a tenant is initialized
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the initialized tenant
        });

        // Tenant ended event
        Event::listen(Events\TenancyEnded::class, function ($event) {
            // This will be called when a tenant context ends
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the tenant that was ended
        });

        // Tenant creating event
        Event::listen(Events\CreatingTenant::class, function ($event) {
            // This will be called before a tenant is created
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the tenant being created
        });

        // Tenant created event
        Event::listen(Events\TenantCreated::class, function ($event) {
            // This will be called after a tenant is created
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the created tenant
        });

        // Tenant updating event
        Event::listen(Events\UpdatingTenant::class, function ($event) {
            // This will be called before a tenant is updated
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the tenant being updated
        });

        // Tenant updated event
        Event::listen(Events\TenantUpdated::class, function ($event) {
            // This will be called after a tenant is updated
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the updated tenant
        });

        // Tenant deleting event
        Event::listen(Events\DeletingTenant::class, function ($event) {
            // This will be called before a tenant is deleted
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the tenant being deleted
        });

        // Tenant deleted event
        Event::listen(Events\TenantDeleted::class, function ($event) {
            // This will be called after a tenant is deleted
            // We can add custom logic here for Phase 1B+
            // $event->tenant contains the deleted tenant
        });

        // Domain created event
        Event::listen(Events\DomainCreated::class, function ($event) {
            // This will be called after a domain is created
            // We can add custom logic here for Phase 1B+
            // $event->domain contains the created domain
        });

        // Domain updated event
        Event::listen(Events\DomainUpdated::class, function ($event) {
            // This will be called after a domain is updated
            // We can add custom logic here for Phase 1B+
            // $event->domain contains the updated domain
        });

        // Domain deleted event
        Event::listen(Events\DomainDeleted::class, function ($event) {
            // This will be called after a domain is deleted
            // We can add custom logic here for Phase 1B+
            // $event->domain contains the deleted domain
        });
    }

    /**
     * Register tenancy middleware.
     */
    protected function registerMiddleware(): void
    {
        // Custom middleware will be registered here in Phase 1B+
        // For now, we're using the default stancl/tenancy middleware
    }
}
