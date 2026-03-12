<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait to automatically log model activity to audit_logs table.
 * 
 * Usage: Add `use LogsActivity;` to any model you want to audit.
 */
trait LogsActivity
{
    /**
     * Boot the trait and register model event listeners.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', null, $model->toArray());
        });

        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();
            
            // Remove timestamps from changes for cleaner logs
            unset($changes['updated_at']);
            
            if (!empty($changes)) {
                // Get only the original values for changed fields
                $oldValues = array_intersect_key($original, $changes);
                $model->logActivity('updated', $oldValues, $changes);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->toArray(), null);
        });
    }

    /**
     * Log an activity for this model.
     */
    protected function logActivity(string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => get_class($this),
                'auditable_id' => $this->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't break the main operation
            // You could log this to error log if needed
            \Log::warning('Failed to create audit log: ' . $e->getMessage());
        }
    }
}
