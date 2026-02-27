<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Enums;

enum LogEventEnum: string {
    // General Events
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
    case EXPORT = 'export';
    case RESTORE = 'restore';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case LOGIN_FAILED = 'login_failed';

    // Purchase Order Events
    case PO_CREATED = 'po_created';
    case PO_UPDATED = 'po_updated';
    case PO_SENT = 'po_sent';
    case PO_CANCELLED = 'po_cancelled';
    case PO_CONFIRMED_SUPPLIER = 'po_confirmed_supplier';
    case PO_CONFIRMED_KOPERASI = 'po_confirmed_koperasi';
    case PO_RECEIVED = 'po_received';
    case PO_REJECTED = 'po_rejected';

    // Stock Events
    case STOCK_ITEM_CREATED = 'stock_item_created';
    case STOCK_ITEM_UPDATED = 'stock_item_updated';
    case STOCK_BATCH_CREATED = 'stock_batch_created';
    case STOCK_ADDED = 'stock_added';
    case STOCK_REDUCED = 'stock_reduced';
    case STOCK_ADJUSTED = 'stock_adjusted';
    case STOCK_LOW = 'stock_low';
    case STOCK_OUT = 'stock_out';
    case STOCK_EXPIRED = 'stock_expired';
    case STOCK_OPNAME = 'stock_opname';

    // Kitchen Order Events
    case ORDER_CREATED = 'order_created';
    case ORDER_UPDATED = 'order_updated';
    case ORDER_SENT = 'order_sent';
    case ORDER_PROCESSED = 'order_processed';
    case ORDER_DELIVERED = 'order_delivered';
    case ORDER_RECEIVED = 'order_received';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_VERIFIED = 'order_verified';

    // Finance Events
    case TRANSACTION_RECORDED = 'transaction_recorded';
    case TRANSACTION_PURCHASE = 'transaction_purchase';
    case TRANSACTION_SALES = 'transaction_sales';
    case PROFIT_CALCULATED = 'profit_calculated';

    // QR Code Events
    case QR_GENERATED = 'qr_generated';
    case QR_SCANNED = 'qr_scanned';
    case QR_VERIFIED = 'qr_verified';

    // Supplier Events
    case SUPPLIER_CREATED = 'supplier_created';
    case SUPPLIER_UPDATED = 'supplier_updated';
    case SUPPLIER_DELETED = 'supplier_deleted';

    // Dapur Events
    case DAPUR_CREATED = 'dapur_created';
    case DAPUR_UPDATED = 'dapur_updated';
}
