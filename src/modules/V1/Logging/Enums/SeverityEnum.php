<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Enums;

enum SeverityEnum: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SUCCESS = 'success';

    /**
     * Get severity color code for UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::INFO => '#3B82F6',      // Blue
            self::WARNING => '#F59E0B',   // Yellow/Orange
            self::ERROR => '#EF4444',     // Red
            self::SUCCESS => '#10B981',   // Green
        };
    }

    /**
     * Get severity icon
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::INFO => 'ℹ️',
            self::WARNING => '⚠️',
            self::ERROR => '🔴',
            self::SUCCESS => '✅',
        };
    }

    /**
     * Get severity label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
            self::SUCCESS => 'Success',
        };
    }

    /**
     * Auto-detect severity from event name
     *
     * ERROR (red):
     * - login_failed, authentication_failed, unauthorized_access
     * - stock_out_of_stock, stock_insufficient
     * - payment_failed, transaction_failed
     * - validation_error, api_error, database_error
     * - po_failed, delivery_failed
     *
     * WARNING (yellow):
     * - stock_low, stock_minimum_reached
     * - po_overdue, po_pending_confirmation
     * - supplier_late_confirmation, supplier_late_delivery
     * - high_return_rate
     * - invoice_due_soon, payment_overdue
     *
     * SUCCESS (green):
     * - login, login_success
     * - user_created, user_updated, user_deleted
     * - po_created, po_confirmed, po_completed
     * - stock_received, stock_added
     * - payment_success, payment_completed
     * - delivery_completed, order_delivered
     * - data_exported, data_imported
     *
     * INFO (blue):
     * - logout, logout_success
     * - data_viewed, page_viewed
     * - search_performed, filter_applied
     * - report_viewed, report_downloaded
     * - settings_updated, preferences_changed
     * - anything else
     */
    public static function fromEvent(string $event): self
    {
        $eventLower = strtolower($event);

        // ERROR - Critical failures
        if (
            str_contains($eventLower, 'error') ||
            str_contains($eventLower, 'failed') ||
            str_contains($eventLower, 'authentication_failed') ||
            str_contains($eventLower, 'login_failed') ||
            str_contains($eventLower, 'unauthorized') ||
            str_contains($eventLower, 'forbidden') ||
            str_contains($eventLower, 'out_of_stock') ||
            str_contains($eventLower, 'insufficient') ||
            str_contains($eventLower, 'validation_error') ||
            str_contains($eventLower, 'api_error') ||
            str_contains($eventLower, 'database_error') ||
            str_contains($eventLower, 'exception') ||
            str_contains($eventLower, 'crash')
        ) {
            return self::ERROR;
        }

        // WARNING - Potential issues
        if (
            str_contains($eventLower, 'warning') ||
            str_contains($eventLower, '_low') ||
            str_contains($eventLower, '_minimum') ||
            str_contains($eventLower, 'overdue') ||
            str_contains($eventLower, 'late') ||
            str_contains($eventLower, 'pending') ||
            str_contains($eventLower, 'expir') || // expired atau expiring
            str_contains($eventLower, 'due_soon') ||
            str_contains($eventLower, 'high_return') ||
            str_contains($eventLower, 'unusual_activity')
        ) {
            return self::WARNING;
        }

        // SUCCESS - Successful operations
        if (
            str_contains($eventLower, 'success') ||
            str_contains($eventLower, 'created') ||
            str_contains($eventLower, 'deleted') ||
            str_contains($eventLower, 'completed') ||
            str_contains($eventLower, 'confirmed') ||
            str_contains($eventLower, 'received') ||
            str_contains($eventLower, 'delivered') ||
            str_contains($eventLower, 'exported') ||
            str_contains($eventLower, 'imported') ||
            str_contains($eventLower, 'approved')
        ) {
            return self::SUCCESS;
        }

        // Special case: 'login' without 'failed' is success
        if ($eventLower === 'login' || str_contains($eventLower, 'login_success')) {
            return self::SUCCESS;
        }

        // INFO - Everything else (logout, viewed, search, etc.)
        return self::INFO;
    }
}
