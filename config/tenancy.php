<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used for tenants.
    |
    */

    'tenant_model' => \App\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Domain Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used for domains.
    |
    */

    'domain_model' => \App\Models\Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | These domains will be used for the central application.
    | All other domains will be treated as tenant domains.
    |
    */

    'central_domains' => [
        'localhost',
        '127.0.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the database connection that will be used for tenants.
    |
    */

    'database_connection_name' => env('TENANCY_DB_CONNECTION', 'tenant'),

    /*
    |--------------------------------------------------------------------------
    | Central Connection Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the database connection that will be used for central operations.
    |
    */
  
      'database' => [
          'central_connection' => env('DB_CONNECTION', 'mysql'),
          'template_tenant_connection' => env('DB_CONNECTION', 'mysql'),
          'managers' => [
              'mysql' => \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
              'pgsql' => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
              'sqlite' => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
              'sqlsrv' => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
          ],
      ],

    /*
    |--------------------------------------------------------------------------
    | Database Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used for tenant databases.
    |
    */

    'database_prefix' => env('TENANCY_DB_PREFIX', 'tenant_'),

    /*
    |--------------------------------------------------------------------------
    | Database Name Generator
    |--------------------------------------------------------------------------
    |
    | This function will be used to generate database names for tenants.
    |
    */

    'database_name_generator' => function (string $tenantId): string {
        return 'tenant_' . $tenantId;
    },

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Config
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the tenancy bootstrap process.
    |
    */

    'bootstrap' => [
        'database' => true,
        'cache' => true,
        'filesystem' => true,
        'queue' => true,
        'redis' => true,
        'routes' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenancy bootstrappers.
    |
    */

    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant migrations.
    |
    */

    'migrations' => [
        'path' => database_path('migrations/tenant'),
        'separator' => '_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant seeding.
    |
    */

    'seeder' => [
        'class' => \Database\Seeders\DatabaseSeeder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant queues.
    |
    */

    'queue' => [
        'connection' => env('TENANCY_QUEUE_CONNECTION', 'database'),
        'queue' => env('TENANCY_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant cache.
    |
    */

    'cache' => [
        'tag_base' => 'tenant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant filesystems.
    |
    */

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant Redis.
    |
    */

    'redis' => [
        'prefix_base' => 'tenant',
        'connections' => [
            'default',
            'cache',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenancy features.
    |
    */

    'features' => [
        Stancl\Tenancy\Features\TenantConfig::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant routes.
    |
    */

    'routes' => [
        'path' => base_path('routes/tenant.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | This is the configuration for tenant middleware.
    |
    */

    'middleware' => [
        'prevent_access_from_central' => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        'initialize' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    ],

];
