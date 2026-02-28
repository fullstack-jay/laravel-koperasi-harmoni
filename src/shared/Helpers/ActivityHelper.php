<?php

declare(strict_types=1);

namespace Shared\Helpers;

use Illuminate\Database\Eloquent\Model;
use Modules\V1\Logging\Facades\Activity;
use Modules\V1\Logging\Enums\LogEventEnum;

class ActivityHelper
{
    /**
     * Log user creation
     */
    public static function logUserCreated(Model $user, array $extra = []): void
    {
        $userName = $user->full_name ?? $user->name ?? 'Unknown';

        Activity::causedBy(auth()->user())
            ->performedOn($user)
            ->event(LogEventEnum::CREATE)
            ->withProperties(array_merge([
                'user_id' => $user->getKey(),
                'user_name' => $userName,
                'user_email' => $user->email ?? null,
                'user_role' => $user->role ?? null,
            ], $extra))
            ->log("New user created: {$userName}");
    }

    /**
     * Log user update
     */
    public static function logUserUpdated(Model $user, array $changes = [], array $extra = []): void
    {
        $userName = $user->full_name ?? $user->name ?? 'Unknown';

        Activity::causedBy(auth()->user())
            ->performedOn($user)
            ->event(LogEventEnum::UPDATE)
            ->withProperties(array_merge([
                'user_id' => $user->getKey(),
                'user_name' => $userName,
                'changes' => $changes,
            ], $extra))
            ->log("User updated: {$userName}");
    }

    /**
     * Log user deletion
     */
    public static function logUserDeleted(Model $user, array $extra = []): void
    {
        $userName = $user->full_name ?? $user->name ?? 'Unknown';
        $deletedBy = auth()->user()?->full_name ?? auth()->user()?->name ?? 'Unknown';

        Activity::causedBy(auth()->user())
            ->event(LogEventEnum::DELETE)
            ->withProperties(array_merge([
                'user_id' => $user->getKey(),
                'user_name' => $userName,
                'user_email' => $user->email ?? null,
                'deleted_by' => $deletedBy,
            ], $extra))
            ->log("User deleted: {$userName}");
    }

    /**
     * Log user view
     */
    public static function logUserViewed(Model $user, array $extra = []): void
    {
        $userName = $user->full_name ?? $user->name ?? 'Unknown';

        Activity::causedBy(auth()->user())
            ->performedOn($user)
            ->event(LogEventEnum::VIEW)
            ->withProperties(array_merge([
                'viewed_user_id' => $user->getKey(),
                'viewed_user_name' => $userName,
            ], $extra))
            ->log("Viewed user profile: {$userName}");
    }

    /**
     * Log supplier creation
     */
    public static function logSupplierCreated(Model $supplier, array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->performedOn($supplier)
            ->event(LogEventEnum::SUPPLIER_CREATED->value)
            ->withProperties(array_merge([
                'supplier_id' => $supplier->getKey(),
                'supplier_code' => $supplier->code ?? null,
                'supplier_name' => $supplier->name ?? null,
            ], $extra))
            ->log("Supplier created: {$supplier->name}");
    }

    /**
     * Log supplier update
     */
    public static function logSupplierUpdated(Model $supplier, array $changes = [], array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->performedOn($supplier)
            ->event(LogEventEnum::SUPPLIER_UPDATED->value)
            ->withProperties(array_merge([
                'supplier_id' => $supplier->getKey(),
                'supplier_code' => $supplier->code ?? null,
                'changes' => $changes,
            ], $extra))
            ->log("Supplier updated: {$supplier->name}");
    }

    /**
     * Log dapur creation
     */
    public static function logDapurCreated(Model $dapur, array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->performedOn($dapur)
            ->event(LogEventEnum::DAPUR_CREATED->value)
            ->withProperties(array_merge([
                'dapur_id' => $dapur->getKey(),
                'dapur_code' => $dapur->code ?? null,
                'dapur_name' => $dapur->name ?? null,
            ], $extra))
            ->log("Dapur created: {$dapur->name}");
    }

    /**
     * Log dapur update
     */
    public static function logDapurUpdated(Model $dapur, array $changes = [], array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->performedOn($dapur)
            ->event(LogEventEnum::DAPUR_UPDATED->value)
            ->withProperties(array_merge([
                'dapur_id' => $dapur->getKey(),
                'dapur_code' => $dapur->code ?? null,
                'changes' => $changes,
            ], $extra))
            ->log("Dapur updated: {$dapur->name}");
    }

    /**
     * Log login
     */
    public static function logLogin(array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->event(LogEventEnum::LOGIN)
            ->withProperties(array_merge([
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $extra))
            ->log('User logged in successfully');
    }

    /**
     * Log logout
     */
    public static function logLogout(array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->event(LogEventEnum::LOGOUT)
            ->withProperties(array_merge([
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
            ], $extra))
            ->log('User logged out');
    }

    /**
     * Log failed login
     */
    public static function logFailedLogin(string $email, string $reason = 'Invalid credentials', array $extra = []): void
    {
        Activity::withProperties(array_merge([
            'email' => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
        ], $extra))
            ->log('Login failed attempt');
    }

    /**
     * Log export activity
     */
    public static function logExport(string $type, string $format, int $recordCount, array $extra = []): void
    {
        Activity::causedBy(auth()->user())
            ->event(LogEventEnum::EXPORT)
            ->withProperties(array_merge([
                'export_type' => $type,
                'format' => $format,
                'records_count' => $recordCount,
                'ip_address' => request()->ip(),
            ], $extra))
            ->log("Exported {$type} to {$format} format ({$recordCount} records)");
    }

    /**
     * Log general activity
     */
    public static function log(string $event, string $description, array $properties = []): void
    {
        Activity::causedBy(auth()->user())
            ->event($event)
            ->withProperties(array_merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $properties))
            ->log($description);
    }
}
