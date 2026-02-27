<?php

declare(strict_types=1);

namespace Modules\V1\Finance\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
        };
    }
}
