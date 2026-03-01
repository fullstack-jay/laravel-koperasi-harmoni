<?php

declare(strict_types=1);

namespace Modules\V1\PurchaseOrder\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\V1\PurchaseOrder\Models\PurchaseOrder;

final class PurchaseOrderSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Purchase Order Baru - ' . $this->purchaseOrder->po_number)
            ->markdown(
                'emails.purchase-orders.sent',
                [
                    'poNumber' => $this->purchaseOrder->po_number,
                    'poDate' => $this->purchaseOrder->po_date?->format('d/m/Y'),
                    'estimatedTotal' => number_format($this->purchaseOrder->estimated_total, 0, ',', '.'),
                    'estimatedDeliveryDate' => $this->purchaseOrder->estimated_delivery_date?->format('d/m/Y'),
                    'notes' => $this->purchaseOrder->notes,
                    'itemsCount' => $this->purchaseOrder->items->count(),
                ]
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'PurchaseOrder',
            'title' => 'Purchase Order Baru Diterima',
            'message' => "PO {$this->purchaseOrder->po_number} dengan total Rp " . number_format($this->purchaseOrder->estimated_total, 0, ',', '.'),
            'poNumber' => $this->purchaseOrder->po_number,
            'poId' => $this->purchaseOrder->id,
            'estimatedTotal' => $this->purchaseOrder->estimated_total,
            'estimatedDeliveryDate' => $this->purchaseOrder->estimated_delivery_date?->format('Y-m-d'),
            'url' => '/supplier/purchase-orders/' . $this->purchaseOrder->id,
        ];
    }
}
