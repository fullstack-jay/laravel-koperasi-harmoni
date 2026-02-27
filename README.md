<div align="center">
  <h2><b>🕹️🕹️ Laravel API - SIM-LKD (Sistem Informasi Manajemen Koperasi)🕹️🕹️</b></h2>
  <br/>
</div>

<a name="readme-top"></a>

<!-- TABLE OF CONTENTS -->

# 📗 Table of Contents

- [📖 About the Project](#about-project)
    - [🛠 Built With](#built-with)
        - [Tech Stack](#tech-stack)
    - [🚀 Links](#api-docs)
    - [Features](#features)
- [📁 Project Structure](#project-structure)
    - [Directory Overview](#directory-overview)
    - [Module Structure](#module-structure)
    - [Shared Components](#shared-components)
    - [Route Organization](#route-organization)
- [🔄 Code Flow & Logic](#code-flow)
    - [Request/Response Lifecycle](#request-response-lifecycle)
    - [Authentication Flow](#authentication-flow)
    - [Business Logic Flows](#business-logic-flows)
    - [Design Patterns](#design-patterns)
    - [Coding Standards](#coding-standards)
- [💻 Getting Started](#getting-started)
    - [Setup](#setup)
    - [Prerequisites](#prerequisites)
    - [Usage](#usage)
- [🤝 Contributing](#contributing)

<!-- PROJECT DESCRIPTION -->

# 📖  API Backend - SIM-LKD <a name="about-project"></a>

Backend API for Sistem Informasi Manajemen Koperasi (SIM-LKD) - A comprehensive cooperative management system built with Laravel 11 using Domain-Driven Design (DDD) approach. This system handles purchase orders, stock management, supplier relationships, kitchen orders, and financial tracking.

### Features

- **Multi-Role Authentication**: Role-based access for SUPER_ADMIN, KOPERASI, SUPPLIER, DAPUR, and KEUANGAN
- **Purchase Order Management**: Complete PO lifecycle from draft to completion
- **Stock Management**: FEFO (First Expired First Out) inventory tracking with batch management
- **Supplier Portal**: Dedicated supplier interface for PO management
- **Kitchen Orders**: Raw material request system from kitchen to cooperative
- **QR Code Tracking**: Delivery verification with QR code generation and scanning
- **Financial Management**: Automatic transaction recording, profit calculation, cashflow tracking
- **Domain-Driven Architecture**: Modular design with clear separation of concerns
- **API Versioning**: Support for multiple API versions
- **Rate Limiting**: API throttling to prevent abuse
- **Comprehensive Logging**: Activity logging and audit trails

### Business Overview

The SIM-LKD system manages the flow of goods from Suppliers → Koperasi → Dapur (Kitchen), with automatic financial tracking at each stage.

**Key Business Flows:**
1. **Purchase Order (PO)**: Koperasi creates PO → Supplier confirms with actual prices → Goods received → Stock updated → Transaction recorded
2. **Kitchen Orders**: Dapur requests materials → Koperasi approves → Stock allocated (FEFO) → Delivered with QR code → Verified by scan
3. **Stock Management**: Batch-based tracking with expiry dates, automatic alerts for low stock and expiring items
4. **Financial Tracking**: Automatic recording of purchases (when PO received) and sales (when goods delivered to kitchen)

### Architecture Overview
#### Domain-Driven Design (DDD)
The project structure is organized to separate concerns:

`src/modules`: Contains feature-specific modules (PurchaseOrder, Stock, Supplier, Kitchen, Finance, Auth)
`src/shared`: Shared resources like helpers, enums, and base classes

#### Versioning
Version-specific modules and routes are located in the `V1` directory for flexibility.

### Tech Stack <a name="tech-stack"></a>

- <a href="https://www.php.net/">PHP</a> 8.2+
- <a href="https://laravel.com/">Laravel</a> 11
- <a href="https://www.postgresql.org/">PostgreSQL</a> 14+

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- PROJECT STRUCTURE -->

# 📁 Project Structure <a name="project-structure"></a>

## Directory Overview <a name="directory-overview"></a>

The project follows a **Domain-Driven Design (DDD)** architecture with clear separation of concerns:

```
laravel-koperasi-harmoni/
├── app/                          # Laravel core application files
├── bootstrap/                    # Framework bootstrap files
├── config/                       # Application configuration files
├── database/                     # Database migrations, seeders, and factories
│   ├── migrations/              # Database schema migrations
│   ├── seeders/                 # Initial data seeding
│   └── factories/               # Model factories for testing
├── public/                       # Public entry point
├── resources/                    # Views and raw assets
├── routes/                       # API route definitions
│   ├── api.php                  # Main API entry point
│   └── v1/                      # Version 1 routes
│       ├── api.php              # V1 main router
│       ├── auth.php             # Authentication endpoints
│       ├── purchase-orders.php  # PO endpoints
│       ├── stock.php            # Stock management endpoints
│       ├── suppliers.php        # Supplier endpoints
│       ├── kitchen.php          # Kitchen order endpoints
│       ├── finance.php          # Financial endpoints
│       └── admin/               # Admin-specific routes
│           ├── api.php          # Admin profile & management
│           ├── users.php        # User management
│           └── logs.php         # Activity logging
├── src/                          # Custom application code (DDD structure)
│   ├── modules/                  # Feature-specific modules (Domain Layer)
│   │   └── V1/                  # Version 1 modules
│   │       ├── Auth/            # Authentication domain
│   │       ├── PurchaseOrder/   # Purchase Order management domain
│   │       ├── Stock/           # Stock management domain
│   │       ├── Supplier/        # Supplier management domain
│   │       ├── Kitchen/         # Kitchen order domain
│   │       ├── Finance/         # Financial management domain
│   │       ├── QRCode/          # QR Code generation domain
│   │       └── Admin/           # Admin management domain
│   └── shared/                   # Shared utilities (Cross-cutting concerns)
│       ├── Enums/               # Shared enumerations
│       ├── Helpers/             # Utility helper classes
│       ├── Models/              # Base model classes
│       ├── Providers/           # Service providers
│       ├── Services/            # Shared services
│       └── Traits/              # Reusable traits
├── storage/                      # Application storage (logs, cache, etc.)
├── tests/                        # Automated tests
└── vendor/                       # Composer dependencies
```

### Key Design Principles

1. **Modular Structure**: Each module is self-contained with its own controllers, models, services
2. **Separation of Concerns**: Business logic separated from presentation and data layers
3. **Reusability**: Shared components avoid code duplication
4. **Scalability**: Easy to add new modules or API versions
5. **Business Logic Centralization**: Complex operations (FEFO, profit calculation) in dedicated services

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Module Structure <a name="module-structure"></a>

Each module in `src/modules/V1/` follows a consistent structure:

### Auth Module (`src/modules/V1/Auth/`)
Handles authentication and authorization for all user types.

```
Auth/
├── Controllers/
│   ├── AuthController.php              # Main auth operations
│   ├── LoginController.php             # Login handler
│   ├── LogoutController.php            # Logout handler
│   └── RefreshTokenController.php      # Token refresh handler
├── Models/
│   ├── User.php                        # User model with role relationships
│   └── AccessToken.php                 # Token management
├── Requests/
│   ├── LoginRequest.php                # Login validation
│   └── RefreshTokenRequest.php         # Token refresh validation
├── Services/
│   └── AuthService.php                 # Core auth logic
└── Enums/
    └── RoleEnum.php                    # Role definitions
```

**Key Features:**
- JWT-based authentication with access and refresh tokens
- Role-based access control (RBAC)
- Multi-user type support (5 roles)
- Token refresh mechanism
- Session management

### PurchaseOrder Module (`src/modules/V1/PurchaseOrder/`)
Manages the complete PO lifecycle from creation to completion.

```
PurchaseOrder/
├── Controllers/
│   ├── POController.php                # Main PO operations
│   ├── POCreateController.php          # Create new PO
│   ├── POUpdateController.php          # Update existing PO
│   ├── POSupplierController.php        # Supplier operations (confirm/reject)
│   ├── POKoperasiController.php        # Koperasi operations (confirm/receive)
│   └── POCancelController.php          # Cancel PO
├── Models/
│   ├── PurchaseOrder.php               # PO model with relationships
│   ├── PurchaseOrderItem.php           # PO items model
│   └── POStatusHistory.php             # Status change history
├── Requests/
│   ├── CreatePORequest.php             # PO creation validation
│   ├── UpdatePORequest.php             # PO update validation
│   ├── ConfirmPORequest.php            # Supplier confirmation validation
│   ├── RejectPORequest.php             # PO rejection validation
│   └── ReceiveGoodsRequest.php         # Goods receipt validation
├── Services/
│   ├── POService.php                   # Core PO logic
│   ├── POStatusService.php             # Status transition logic
│   ├── POValidationService.php         # Business rule validation
│   └── POCalculationService.php        # Total calculations
├── Resources/
│   ├── POResource.php                  # PO data transformation
│   └── POItemResource.php              # PO item transformation
└── Enums/
    └── POStatusEnum.php                # PO status constants
```

**Key Features:**
- Draft PO creation with estimated prices
- Send to supplier with notification
- Supplier price confirmation/rejection
- Koperasi confirmation of supplier response
- Goods receipt with batch creation
- Automatic transaction recording
- Status history tracking
- Multiple price revisions support

### Stock Module (`src/modules/V1/Stock/`)
Manages inventory with FEFO (First Expired First Out) logic.

```
Stock/
├── Controllers/
│   ├── StockItemController.php         # Stock item CRUD
│   ├── StockBatchController.php        # Batch management
│   ├── StockAdjustmentController.php   # Stock adjustments
│   ├── StockAlertController.php        # Stock alerts
│   └── StockOpnameController.php       # Stock opname
├── Models/
│   ├── StockItem.php                   # Stock item master
│   ├── StockBatch.php                  # Batch tracking
│   ├── StockCard.php                   # Stock movement ledger
│   ├── StockAlert.php                  # Alert records
│   └── StockOpname.php                 # Stock opname records
├── Requests/
│   ├── CreateStockItemRequest.php      # Item creation validation
│   ├── AdjustStockRequest.php          # Stock adjustment validation
│   └── CreateOpnameRequest.php         # Opname validation
├── Services/
│   ├── StockService.php                # Core stock logic
│   ├── FEFOService.php                 # FEFO allocation logic
│   ├── StockCalculationService.php     # Stock calculations
│   ├── StockAlertService.php           # Alert generation
│   └── BatchManagementService.php      # Batch operations
├── Resources/
│   ├── StockItemResource.php           # Item data transformation
│   ├── StockBatchResource.php          # Batch data transformation
│   └── StockAlertResource.php          # Alert transformation
└── Enums/
    ├── StockStatusEnum.php             # Batch status constants
    └── AlertTypeEnum.php               # Alert type constants
```

**Key Features:**
- FEFO (First Expired First Out) stock allocation
- Batch tracking with expiry dates
- Automatic stock alerts (low quantity, expiring)
- Stock card (ledger) for all movements
- Stock opname (physical count) support
- Weighted average price calculation
- Multi-location support

### Supplier Module (`src/modules/V1/Supplier/`)
Manages supplier data and relationships.

```
Supplier/
├── Controllers/
│   ├── SupplierController.php          # Supplier CRUD
│   └── SupplierPOController.php        # Supplier's PO view
├── Models/
│   ├── Supplier.php                    # Supplier model
│   └── SupplierContact.php             # Supplier contacts
├── Requests/
│   └── CreateSupplierRequest.php       # Supplier creation validation
├── Services/
│   └── SupplierService.php             # Core supplier logic
├── Resources/
│   └── SupplierResource.php            # Supplier data transformation
└── Enums/
    └── SupplierStatusEnum.php          # Supplier status constants
```

**Key Features:**
- Supplier master data management
- PO assignment to suppliers
- Supplier performance tracking
- Contact management

### Kitchen Module (`src/modules/V1/Kitchen/`)
Handles kitchen (dapur) orders for raw materials.

```
Kitchen/
├── Controllers/
│   ├── KitchenOrderController.php      # Main order operations
│   ├── KitchenCreateController.php     # Create order
│   ├── KitchenKoperasiController.php   # Koperasi operations
│   └── KitchenDeliveryController.php   # Delivery with QR
├── Models/
│   ├── KitchenOrder.php                # Kitchen order model
│   ├── KitchenOrderItem.php            # Order items
│   └── SuratJalan.php                  # Delivery document
├── Requests/
│   ├── CreateOrderRequest.php          # Order creation validation
│   ├── ProcessOrderRequest.php         # Order processing validation
│   └── DeliveryRequest.php             # Delivery validation
├── Services/
│   ├── KitchenOrderService.php         # Core order logic
│   ├── OrderProcessingService.php      # Order processing logic
│   ├── StockAllocationService.php      # FEFO stock allocation
│   └── SuratJalanService.php           # Delivery document logic
├── Resources/
│   ├── KitchenOrderResource.php        # Order data transformation
│   └── SuratJalanResource.php          # Surat Jalan transformation
└── Enums/
    └── OrderStatusEnum.php             # Order status constants
```

**Key Features:**
- Dapur creates material requests
- Koperasi approves and allocates stock (FEFO)
- QR code generation for delivery
- Delivery verification with QR scan
- Automatic stock reduction on delivery
- Surat Jalan generation

### Finance Module (`src/modules/V1/Finance/`)
Handles financial transactions and reporting.

```
Finance/
├── Controllers/
│   ├── TransactionController.php       # Transaction CRUD
│   ├── CashflowController.php          # Cashflow reports
│   ├── ProfitController.php            # Profit reports
│   └── OmsetController.php             # Omset reports
├── Models/
│   ├── Transaction.php                 # Transaction model
│   └── TransactionItem.php             # Transaction items
├── Requests/
│   └── TransactionFilterRequest.php    # Filter validation
├── Services/
│   ├── FinanceService.php              # Core finance logic
│   ├── TransactionService.php          # Transaction operations
│   ├── ProfitCalculationService.php    # Profit calculations
│   ├── CashflowService.php             # Cashflow tracking
│   └── ReportService.php               # Report generation
├── Resources/
│   ├── TransactionResource.php         # Transaction transformation
│   └── ProfitReportResource.php        # Profit report transformation
└── Enums/
    └── TransactionTypeEnum.php         # Transaction types
```

**Key Features:**
- Automatic transaction recording on PO completion
- Profit calculation by item and dapur
- Cashflow tracking (in/out)
- Daily, weekly, monthly reports
- Export to Excel/PDF
- Transaction history with filters

### QRCode Module (`src/modules/V1/QRCode/`)
Generates and verifies QR codes for delivery tracking.

```
QRCode/
├── Controllers/
│   ├── QRCodeController.php            # QR code operations
│   └── QRCodeVerifyController.php      # QR verification
├── Services/
│   ├── QRCodeService.php               # QR generation logic
│   └── QRVerificationService.php       # QR verification logic
├── Resources/
│   └── QRCodeResource.php              # QR data transformation
└── Enums/
    └── QRCodeTypeEnum.php              # QR code types
```

**Key Features:**
- QR code generation for deliveries
- QR code scanning/verification
- QR data encoding/decoding
- QR image storage
- Scan tracking

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Shared Components <a name="shared-components"></a>

### Enums (`src/shared/Enums/`)
Centralized enumerations for consistency across the application.

- **RoleEnum**: User roles (SUPER_ADMIN, KOPERASI, SUPPLIER, DAPUR, KEUANGAN)
- **POStatusEnum**: Purchase Order statuses
- **OrderStatusEnum**: Kitchen Order statuses
- **StockStatusEnum**: Batch statuses (AVAILABLE, ALLOCATED, EXPIRED)
- **TransactionTypeEnum**: Transaction types (PURCHASE, SALES)
- **LogEventEnum**: Activity event types

### Helpers (`src/shared/Helpers/`)
Utility classes for common operations.

```php
// ResponseHelper: Standardized JSON responses
ResponseHelper::success($data, $message, $statusCode);
ResponseHelper::error($message, $statusCode);

// DocumentHelper: Document number generation
DocumentHelper::generatePONumber($date, $sequence);
DocumentHelper::generateSuratJalanNumber($date, $sequence);

// CalculationHelper: Financial calculations
CalculationHelper::calculateProfit($sales, $purchases);
CalculationHelper::calculateMargin($profit, $revenue);

// ValidationHelper: Business rule validation
ValidationHelper::validateStockAvailability($items, $batches);
ValidationHelper::checkExpiryDate($expiryDate);
```

### Services (`src/shared/Services/`)
Core services used across modules.

- **FEFOAllocationService**: First Expired First Out stock allocation
- **BatchManagementService**: Batch creation and tracking
- **NotificationService**: Email and in-app notifications
- **AuditLogService**: Activity logging

### Traits (`src/shared/Traits/`)
Reusable behavior patterns.

```php
// HasAuditColumns: Automatic audit fields
trait HasAuditColumns
{
    protected static function bootHasAuditColumns()
    {
        // Automatically set created_by, updated_by, deleted_by
    }
}

// HasStatusHistory: Track status changes
trait HasStatusHistory
{
    // Automatically log status transitions
}

// SoftDeletesWithReason: Soft delete with reason
trait SoftDeletesWithReason
{
    // Add deletion reason tracking
}
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Route Organization <a name="route-organization"></a>

### Route Hierarchy

```
routes/
├── api.php                      # Main entry point
│   └── Routes to v1/api.php
│
└── v1/                          # Version 1 routes
    ├── api.php                  # V1 main router
    │   ├── Auth routes (public)
    │   ├── User routes (auth:sanctum)
    │   ├── PO routes (auth:sanctum + role)
    │   ├── Stock routes (auth:sanctum + role)
    │   ├── Kitchen routes (auth:sanctum + role)
    │   ├── Finance routes (auth:sanctum + role)
    │   └── Admin routes (auth:sanctum + SUPER_ADMIN)
    │
    ├── auth.php                 # Authentication endpoints
    │   ├── POST /auth/login
    │   ├── POST /auth/refresh
    │   ├── POST /auth/logout
    │   └── POST /auth/me
    │
    ├── purchase-orders.php      # PO endpoints
    │   ├── POST /po/list
    │   ├── POST /po/create
    │   ├── POST /po/update
    │   ├── POST /po/send
    │   ├── POST /po/supplier/confirm
    │   ├── POST /po/supplier/reject
    │   ├── POST /po/koperasi/confirm
    │   ├── POST /po/receive
    │   ├── POST /po/cancel
    │   └── POST /po/detail
    │
    ├── stock.php                # Stock endpoints
    │   ├── POST /stock/items/list
    │   ├── POST /stock/items/create
    │   ├── POST /stock/items/update
    │   ├── POST /stock/batches/list
    │   ├── POST /stock/adjust
    │   ├── POST /stock/alerts
    │   └── POST /stock/opname
    │
    ├── suppliers.php            # Supplier endpoints
    │   ├── POST /suppliers/list
    │   ├── POST /suppliers/create
    │   ├── POST /suppliers/update
    │   └── POST /suppliers/{id}/pos
    │
    ├── kitchen.php              # Kitchen order endpoints
    │   ├── POST /kitchen/orders/list
    │   ├── POST /kitchen/orders/create
    │   ├── POST /kitchen/orders/send
    │   ├── POST /kitchen/orders/process
    │   ├── POST /kitchen/orders/deliver
    │   ├── POST /kitchen/orders/verify
    │   └── POST /kitchen/orders/detail
    │
    ├── finance.php              # Financial endpoints
    │   ├── POST /finance/transactions/list
    │   ├── POST /finance/transactions/summary
    │   ├── POST /finance/cashflow
    │   ├── POST /finance/profit
    │   └── POST /finance/omset
    │
    ├── qrcode.php               # QR Code endpoints
    │   ├── POST /qrcode/generate
    │   └── POST /qrcode/verify
    │
    └── admin/                   # Admin-specific routes
        ├── users.php            # User management
        │   ├── POST /admin/users/list
        │   ├── POST /admin/users/create
        │   └── POST /admin/users/{id}/update
        │
        └── logs.php             # Activity logging
            └── POST /admin/logs/list
```

### Route Protection

| Route Type | Middleware | Purpose |
|------------|-----------|---------|
| Auth Routes | `guest` | Only for non-authenticated users |
| User Routes | `auth:sanctum` | Requires valid token |
| PO Routes | `auth:sanctum` + `role:KOPERASI,SUPPLIER` | Role-based access |
| Stock Routes | `auth:sanctum` + `role:KOPERASI` | Koperasi only |
| Kitchen Routes | `auth:sanctum` + `role:KOPERASI,DAPUR` | Koperasi & Dapur |
| Finance Routes | `auth:sanctum` + `role:KEUANGAN,SUPER_ADMIN` | Read-only finance |
| Admin Routes | `auth:sanctum` + `role:SUPER_ADMIN` | Super admin only |

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- CODE FLOW & LOGIC -->

# 🔄 Code Flow & Logic <a name="code-flow"></a>

## Request/Response Lifecycle <a name="request-response-lifecycle"></a>

### Standard Request Flow

```
┌─────────────────┐
│  HTTP Request   │
│  POST /api/v1/..│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Middleware    │
│ - auth:sanctum  │ ← Token validation
│ - role:check    │ ← Role verification
│ - throttle      │ ← Rate limiting
│ - json          │ ← JSON response
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     Route       │
│  Match Route    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Controller    │
│  - Validate     │ ← Form Request validation
│  - Authorize    │ ← Permission check
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Service Layer │
│  - Business     │ ← Core business logic
│    Logic        │
│  - Database     │ ← Data operations
│    Operations   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Resource      │
│  - Transform    │ ← Data formatting
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  ResponseHelper │
│  - Standardize  │ ← Consistent JSON format
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  JSON Response  │
│  { status,      │
│    statusCode,  │
│    message,     │
│    data }       │
└─────────────────┘
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Authentication Flow <a name="authentication-flow"></a>

### Login Flow

```
User submits credentials
        │
        ▼
LoginRequest validates username/password
        │
        ▼
AuthController@login
        │
        ├─→ Check credentials (Auth::attempt())
        │
        ├─→ Check user status
        │
        ├─→ Generate JWT access token (15 min expiry)
        │
        ├─→ Generate refresh token (7 days expiry)
        │
        ├─→ Log activity (Activity::log)
        │
        └─→ Return response with tokens
```

### Token Refresh Flow

```
Client sends refresh token
        │
        ▼
RefreshTokenRequest validates token
        │
        ▼
AuthController@refresh
        │
        ├─→ Validate refresh token
        │
        ├─→ Check if token is expired
        │
        ├─→ Generate new access token
        │
        ├─→ Optionally generate new refresh token
        │
        └─→ Return new tokens
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Business Logic Flows <a name="business-logic-flows"></a>

### Purchase Order Flow

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: KOPERASI creates PO (DRAFT)                        │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/po/create                                     │
│                                                             │
│ Request:                                                    │
│ {                                                           │
│   supplierId: "SUP-001",                                   │
│   poDate: "2025-02-22",                                    │
│   items: [                                                 │
│     {                                                       │
│       itemId: "STK-001",                                   │
│       itemName: "Beras Premium 25kg",                       │
│       estimatedQty: 100,                                   │
│       unit: "karung",                                      │
│       estimatedPrice: 150000                               │
│     }                                                       │
│   ],                                                        │
│   estimatedDeliveryDate: "2025-02-25",                     │
│   notes: "Untuk stok bulan depan"                           │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate supplier exists and active                     │
│ 2. Validate items exist in stock_items                     │
│ 3. Calculate estimatedTotal = Σ(estimatedQty × price)      │
│ 4. Generate PO number: PO-20250222-GDG-001                 │
│ 5. Create PO with status: DRAFT                            │
│ 6. Create PO items                                         │
│ 7. Log activity                                            │
│                                                             │
│ Response:                                                   │
│ {                                                           │
│   status: "success",                                       │
│   statusCode: 201,                                         │
│   message: "PO berhasil dibuat",                           │
│   data: { po: {...} }                                      │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: KOPERASI sends PO to supplier (TERKIRIM)           │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/po/send                                       │
│ { poId: "PO-001" }                                          │
│                                                             │
│ Process:                                                    │
│ 1. Validate PO exists and status is DRAFT                  │
│ 2. Update status: TERKIRIM                                 │
│ 3. Send notification to supplier                           │
│ 4. Log status change                                       │
│                                                             │
│ Response:                                                   │
│ { status: "success", message: "PO dikirim ke supplier" }   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 3a: SUPPLIER confirms with actual prices              │
│         (DIKONFIRMASI_SUPPLIER)                            │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/po/supplier/confirm                           │
│ {                                                           │
│   poId: "PO-001",                                          │
│   items: [                                                 │
│     { itemId: "STK-001", actualPrice: 155000 }            │
│   ],                                                        │
│   invoiceNumber: "INV-2025-001"                            │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate PO status is TERKIRIM                          │
│ 2. Validate actual prices provided                         │
│ 3. Calculate actualTotal = Σ(actualPrice × estimatedQty)   │
│ 4. Update PO items with actual prices                      │
│ 5. Update status: DIKONFIRMASI_SUPPLIER                    │
│ 6. Store invoice number                                   │
│ 7. Set confirmedBySupplierAt timestamp                     │
│ 8. Send notification to Koperasi                           │
│ 9. Log activity                                            │
│                                                             │
│ Response:                                                   │
│ { status: "success", message: "PO dikonfirmasi" }          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: KOPERASI confirms supplier response                │
│         (DIKONFIRMASI_KOPERASI)                            │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/po/koperasi/confirm                           │
│ { poId: "PO-001" }                                          │
│                                                             │
│ Process:                                                    │
│ 1. Validate PO status is DIKONFIRMASI_SUPPLIER             │
│ 2. Update status: DIKONFIRMASI_KOPERASI                    │
│ 3. Set confirmedByKoperasiAt timestamp                     │
│ 4. Log activity                                            │
│                                                             │
│ Response:                                                   │
│ { status: "success", message: "Konfirmasi berhasil" }      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: KOPERASI receives goods (SELESAI)                  │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/po/receive                                    │
│ {                                                           │
│   poId: "PO-001",                                          │
│   actualDeliveryDate: "2025-02-23",                        │
│   items: [                                                 │
│     {                                                       │
│       poItemId: "POI-001",                                 │
│       receivedQty: 100,                                    │
│       batchNumber: "BATCH-2025-022",                       │
│       expiryDate: "2025-12-31",                            │
│       location: "Gudang A-Rak 1"                           │
│     }                                                       │
│   ]                                                         │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate PO status is DIKONFIRMASI_KOPERASI             │
│ 2. For each item:                                          │
│    a. Create stock_batch:                                  │
│       - batchNumber: BATCH-2025-022                        │
│       - quantity: receivedQty                              │
│       - remainingQty: receivedQty                          │
│       - buyPrice: actualPrice                              │
│       - expiryDate: from input                             │
│       - status: AVAILABLE                                  │
│    b. Update stock_item.current_stock += receivedQty       │
│    c. Update stock_item.buy_price = actualPrice            │
│    d. Create stock_card entry (IN)                         │
│ 3. Create transaction (PURCHASE):                          │
│    - type: PURCHASE                                        │
│    - category: PO                                          │
│    - amount: actualTotal                                   │
│    - reference: PO number                                  │
│    - items: JSON array of items                            │
│ 4. Update PO status: SELESAI                               │
│ 5. Set receivedDate timestamp                             │
│ 6. Log activity                                            │
│                                                             │
│ Response:                                                   │
│ {                                                           │
│   status: "success",                                       │
│   message: "Barang diterima. Stok ditambahkan. Transaksi ││
│             tercatat di keuangan.",                        │
│   data: {                                                  │
│     po: {...},                                             │
│     stockUpdated: [...],                                   │
│     transaction: {...}                                     │
│   }                                                        │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
```

### Kitchen Order Flow with FEFO

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: DAPUR creates material request (DRAFT)             │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/kitchen/orders/create                         │
│ {                                                           │
│   dapurId: "DAP-001",                                      │
│   neededDate: "2025-02-25",                                │
│   items: [                                                 │
│     {                                                       │
│       itemId: "STK-001",                                   │
│       itemName: "Beras Premium 25kg",                       │
│       requestedQty: 10,                                    │
│       unit: "karung"                                       │
│     }                                                       │
│   ]                                                         │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate dapur exists and active                        │
│ 2. Validate items exist in stock_items                     │
│ 3. Generate order number: ORD-20250222-001                 │
│ 4. Create order with status: DRAFT                         │
│ 5. Check stock availability (warning only)                │
│ 6. Log activity                                            │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: DAPUR sends request to KOPERASI (TERKIRIM)         │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/kitchen/orders/send                           │
│ { orderId: "ORD-001" }                                      │
│                                                             │
│ Process:                                                    │
│ 1. Update status: TERKIRIM                                 │
│ 2. Send notification to Koperasi                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: KOPERASI processes request (DIPROSES)              │
│         with FEFO allocation                               │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/kitchen/orders/process                        │
│ {                                                           │
│   orderId: "ORD-001",                                      │
│   items: [                                                 │
│     {                                                       │
│       itemId: "STK-001",                                   │
│       approvedQty: 10                                      │
│     }                                                       │
│   ]                                                         │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate order status is TERKIRIM                       │
│ 2. For each item:                                          │
│    a. Get available stock from stock_batches:             │
│       WHERE itemId = ?                                      │
│       AND status = 'AVAILABLE'                             │
│       AND expiryDate > NOW()                               │
│    b. Check if current_stock >= approvedQty                │
│    c. If insufficient, return error with shortage info     │
│    d. Allocate using FEFO:                                 │
│       - SORT BY expiryDate ASC (earliest first)           │
│       - Select batches until approvedQty met               │
│       - Store allocation details                           │
│ 3. Update order status: DIPROSES                           │
│ 4. Store FEFO allocation in order items                   │
│ 5. Set processedAt timestamp                             │
│                                                             │
│ FEFO Allocation Example:                                   │
│ ──────────────────────────────                            │
│ Request: 10 karung Beras                                  │
│                                                             │
│ Available Batches (sorted by expiry):                     │
│ ┌──────────────────────────────────────────────┐          │
│ │ Batch    │ Expiry     │ Qty │ Remaining │   │          │
│ ├──────────────────────────────────────────────┤          │
│ │ BATCH-001 │ 2025-03-15 │ 5   │ 5         │   │          │
│ │ BATCH-002 │ 2025-04-20 │ 8   │ 8         │   │          │
│ │ BATCH-003 │ 2025-06-10 │ 15  │ 15        │   │          │
│ └──────────────────────────────────────────────┘          │
│                                                             │
│ FEFO Allocation (take from nearest expiry first):         │
│ 1. Take 5 from BATCH-001 (earliest)                      │
│ 2. Take 5 from BATCH-002 (next earliest)                 │
│ 3. Total: 10 karung allocated                            │
│ 4. BATCH-003 untouched (not needed)                      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: KOPERASI delivers to DAPUR (DITERIMA_DAPUR)        │
│         with QR Code generation                            │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/kitchen/orders/deliver                        │
│ {                                                           │
│   orderId: "ORD-001",                                      │
│   notes: "Barang dikirim kurir internal"                   │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate order status is DIPROSES                       │
│ 2. For each FEFO allocation from step 3:                  │
│    a. Reduce batch.remainingQty -= allocatedQty            │
│    b. Update batch.status if remainingQty = 0             │
│    c. Update stock_item.current_stock -= approvedQty       │
│    d. Create stock_card entry (OUT)                        │
│ 3. Generate QR Code:                                       │
│    - Format: ORD-20250222-001-QR-{timestamp}              │
│    - Encode: order info, items, delivery details           │
│    - Generate PNG image                                    │
│    - Save to storage                                       │
│ 4. Create transaction (SALES):                             │
│    - type: SALES                                           │
│    - category: KITCHEN_ORDER                               │
│    - amount: Σ(sellPrice × approvedQty)                    │
│    - profit: Σ((sellPrice - buyPrice) × approvedQty)       │
│    - items: JSON array with batch info                     │
│ 5. Create Surat Jalan:                                     │
│    - Generate number: SJ-20250222-001                      │
│    - Include all items with batch numbers                 │
│ 6. Update order status: DITERIMA_DAPUR                     │
│ 7. Store QR code data                                     │
│ 8. Set sentAt timestamp                                  │
│ 9. Send notification to Dapur with QR                     │
│                                                             │
│ Response:                                                   │
│ {                                                           │
│   status: "success",                                       │
│   message: "Barang dikirim ke Dapur. QR Code digenerate.",│
│   data: {                                                  │
│     order: {...},                                          │
│     qrCode: {                                              │
│       data: "ORD-20250222-001-QR-1234567890",             │
│       imageUrl: "https://api.com/qr-codes/...",           │
│       scanUrl: "https://koperasi.com/scan/..."            │
│     },                                                      │
│     suratJalan: {...},                                     │
│     stockReduced: [...]                                    │
│   }                                                        │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: DAPUR verifies delivery (QR Scan)                  │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/qrcode/verify                                 │
│ { qrCodeData: "ORD-20250222-001-QR-1234567890" }           │
│                                                             │
│ Process:                                                    │
│ 1. Parse QR code data                                      │
│ 2. Find order by QR code                                   │
│ 3. Validate order status is DITERIMA_DAPUR                 │
│ 4. Return order details with items                         │
│ 5. Update receivedByDapurAt timestamp                     │
│ 6. Log scan activity                                       │
│                                                             │
│ Response:                                                   │
│ {                                                           │
│   status: "success",                                       │
│   message: "QR Code valid. Pengiriman diverifikasi.",     │
│   data: {                                                  │
│     order: {                                               │
│       orderNumber: "ORD-20250222-001",                     │
│       items: [...]                                        │
│     }                                                       │
│   }                                                        │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
```

### FEFO Algorithm Implementation

```php
// src/modules/V1/Stock/Services/FEFOService.php

class FEFOService
{
    /**
     * Allocate stock using FEFO (First Expired First Out)
     *
     * @param string $itemId
     * @param int $requiredQty
     * @return array|null Selected batches or null if insufficient
     */
    public function allocateStock(string $itemId, int $requiredQty): ?array
    {
        // Get available, non-expired batches for this item
        $batches = StockBatch::where('itemId', $itemId)
            ->where('status', 'AVAILABLE')
            ->where('expiryDate', '>', now())
            ->orderBy('expiryDate', 'asc')  // FEFO: Earliest expiry first
            ->get();

        if ($batches->isEmpty()) {
            return null;
        }

        $selectedBatches = [];
        $allocatedQty = 0;

        // Allocate from batches starting with earliest expiry
        foreach ($batches as $batch) {
            if ($allocatedQty >= $requiredQty) {
                break;
            }

            $remainingNeeded = $requiredQty - $allocatedQty;
            $qtyFromBatch = min($batch->remainingQty, $remainingNeeded);

            $selectedBatches[] = [
                'batchId' => $batch->id,
                'batchNumber' => $batch->batchNumber,
                'qty' => $qtyFromBatch,
                'buyPrice' => $batch->buyPrice,
                'expiryDate' => $batch->expiryDate
            ];

            $allocatedQty += $qtyFromBatch;
        }

        // Check if we have enough stock
        if ($allocatedQty < $requiredQty) {
            return null;  // Insufficient stock
        }

        return $selectedBatches;
    }

    /**
     * Get total available quantity for an item
     */
    public function getAvailableStock(string $itemId): int
    {
        return StockBatch::where('itemId', $itemId)
            ->where('status', 'AVAILABLE')
            ->where('expiryDate', '>', now())
            ->sum('remainingQty');
    }
}
```

### Stock Alert Logic

```php
// src/modules/V1/Stock/Services/StockAlertService.php

class StockAlertService
{
    /**
     * Generate stock alerts for all items
     */
    public function generateAlerts(): Collection
    {
        $alerts = collect();

        $items = StockItem::all();

        foreach ($items as $item) {
            $availableQty = $this->getAvailableStock($item->id);
            $batches = $this->getBatches($item->id);

            // Check quantity alerts
            if ($availableQty === 0) {
                $alerts->push($this->createAlert($item, 'OUT_OF_STOCK'));
            } elseif ($availableQty <= $item->minStock) {
                $alerts->push($this->createAlert($item, 'LOW_STOCK'));
            }

            // Check expiry alerts
            foreach ($batches as $batch) {
                $daysToExpiry = now()->diffInDays($batch->expiryDate, false);

                if ($daysToExpiry <= 0) {
                    $alerts->push($this->createExpiryAlert($batch, 'EXPIRED'));
                } elseif ($daysToExpiry <= 7) {
                    $alerts->push($this->createExpiryAlert($batch, 'CRITICAL'));
                } elseif ($daysToExpiry <= 30) {
                    $alerts->push($this->createExpiryAlert($batch, 'WARNING'));
                }
            }
        }

        return $alerts;
    }

    private function createAlert(StockItem $item, string $type): array
    {
        return [
            'type' => $type,
            'itemId' => $item->id,
            'itemName' => $item->name,
            'severity' => $this->getSeverity($type),
            'message' => $this->getMessage($type, $item),
            'currentQty' => $this->getAvailableStock($item->id),
            'minStock' => $item->minStock,
            'createdAt' => now()->toIso8601String()
        ];
    }
}
```

### Financial Transaction Flow

```php
// Transaction recording happens automatically in two scenarios:
// 1. When Purchase Order is received (PURCHASE transaction)
// 2. When Kitchen Order is delivered (SALES transaction)

// src/modules/V1/Finance/Services/TransactionService.php

class TransactionService
{
    /**
     * Record purchase transaction when PO is received
     *
     * @param PurchaseOrder $po
     * @return Transaction
     */
    public function recordPurchase(PurchaseOrder $po): Transaction
    {
        DB::beginTransaction();

        try {
            // Calculate total amount
            $totalAmount = $po->items->sum(function ($item) {
                return $item->actual_price * $item->actual_qty;
            });

            // Create transaction
            $transaction = Transaction::create([
                'date' => $po->received_date ?? now(),
                'type' => 'PURCHASE',
                'category' => 'PO',
                'amount' => $totalAmount,
                'reference' => $po->po_number,
                'reference_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'items' => $po->items->map(function ($item) {
                    return [
                        'itemId' => $item->item_id,
                        'itemName' => $item->item_name,
                        'qty' => $item->actual_qty,
                        'price' => $item->actual_price,
                        'subtotal' => $item->actual_price * $item->actual_qty
                    ];
                })->toJson(),
                'created_by' => auth()->id()
            ]);

            // Create transaction items for detailed tracking
            foreach ($po->items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_name,
                    'qty' => $item->actual_qty,
                    'buy_price' => $item->actual_price,
                    'subtotal' => $item->actual_price * $item->actual_qty
                ]);
            }

            DB::commit();

            Activity::log('transaction_created', "Purchase transaction recorded: {$transaction->reference}");

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to record purchase transaction', [
                'po_id' => $po->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Record sales transaction when Kitchen Order is delivered
     *
     * @param KitchenOrder $order
     * @param array $fefoAllocations
     * @return Transaction
     */
    public function recordSales(KitchenOrder $order, array $fefoAllocations): Transaction
    {
        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $totalProfit = 0;
            $transactionItems = [];

            // Calculate amount and profit for each item
            foreach ($order->items as $item) {
                $stockItem = StockItem::find($item->item_id);

                // Get FEFO allocations for this item
                $allocations = collect($fefoAllocations)
                    ->where('itemId', $item->item_id)
                    ->first();

                $itemBuyPrice = 0;
                $itemSellAmount = $stockItem->sell_price * $item->approved_qty;

                // Calculate weighted average buy price from allocations
                foreach ($allocations['batches'] as $batch) {
                    $itemBuyPrice += $batch['buyPrice'] * $batch['qty'];
                }

                $itemProfit = $itemSellAmount - $itemBuyPrice;

                $totalAmount += $itemSellAmount;
                $totalProfit += $itemProfit;

                $transactionItems[] = [
                    'item_id' => $item->item_id,
                    'item_name' => $stockItem->name,
                    'qty' => $item->approved_qty,
                    'buy_price' => $itemBuyPrice / $item->approved_qty, // Weighted average
                    'sell_price' => $stockItem->sell_price,
                    'subtotal' => $itemSellAmount,
                    'profit' => $itemProfit,
                    'batches' => $allocations['batches']
                ];
            }

            // Create transaction
            $transaction = Transaction::create([
                'date' => $order->sent_at ?? now(),
                'type' => 'SALES',
                'category' => 'KITCHEN_ORDER',
                'amount' => $totalAmount,
                'profit' => $totalProfit,
                'margin' => $totalAmount > 0 ? ($totalProfit / $totalAmount) * 100 : 0,
                'reference' => $order->order_number,
                'reference_id' => $order->id,
                'dapur_id' => $order->dapur_id,
                'items' => json_encode($transactionItems),
                'created_by' => auth()->id()
            ]);

            // Create transaction items
            foreach ($transactionItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'qty' => $item['qty'],
                    'buy_price' => $item['buy_price'],
                    'sell_price' => $item['sell_price'],
                    'subtotal' => $item['subtotal'],
                    'profit' => $item['profit'],
                    'batch_info' => json_encode($item['batches'])
                ]);
            }

            DB::commit();

            Activity::log('transaction_created', "Sales transaction recorded: {$transaction->reference}");

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to record sales transaction', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get profit summary by period
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProfitSummary(string $startDate, string $endDate): array
    {
        $transactions = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'SALES')
            ->get();

        return [
            'totalRevenue' => $transactions->sum('amount'),
            'totalProfit' => $transactions->sum('profit'),
            'averageMargin' => $transactions->avg('margin'),
            'transactionCount' => $transactions->count(),
            'profitByDapur' => $transactions->groupBy('dapur_id')->map(function ($items) {
                return [
                    'revenue' => $items->sum('amount'),
                    'profit' => $items->sum('profit'),
                    'transactions' => $items->count()
                ];
            }),
            'profitByItem' => $this->getProfitByItem($transactions),
            'dailyProfit' => $transactions->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            })->map(function ($items) {
                return [
                    'revenue' => $items->sum('amount'),
                    'profit' => $items->sum('profit')
                ];
            })
        ];
    }

    /**
     * Get profit breakdown by item
     */
    private function getProfitByItem(Collection $transactions): array
    {
        $itemProfits = [];

        foreach ($transactions as $transaction) {
            $items = json_decode($transaction->items, true);

            foreach ($items as $item) {
                $itemId = $item['item_id'];

                if (!isset($itemProfits[$itemId])) {
                    $itemProfits[$itemId] = [
                        'itemName' => $item['item_name'],
                        'totalQty' => 0,
                        'revenue' => 0,
                        'profit' => 0
                    ];
                }

                $itemProfits[$itemId]['totalQty'] += $item['qty'];
                $itemProfits[$itemId]['revenue'] += $item['subtotal'];
                $itemProfits[$itemId]['profit'] += $item['profit'];
            }
        }

        return $itemProfits;
    }
}
```

### Stock Adjustment Flow

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: KOPERASI initiates stock adjustment                 │
├─────────────────────────────────────────────────────────────┤
│ POST /api/v1/stock/adjust                                   │
│ {                                                           │
│   adjustmentDate: "2025-02-26",                            │
│   type: "ADDITION", // or "REDUCTION", "CORRECTION"        │
│   reason: "Barang rusak ditemukan di gudang",              │
│   items: [                                                 │
│     {                                                       │
│       itemId: "STK-001",                                   │
│       batchNumber: "BATCH-2025-022",                       │
│       adjustmentQty: -5, // negative for reduction          │
│       notes: "Kemasan rusak"                               │
│     }                                                       │
│   ]                                                         │
│ }                                                           │
│                                                             │
│ Process:                                                    │
│ 1. Validate adjustment type                                │
│ 2. For each item:                                          │
│    a. Validate batch exists                                │
│    b. Calculate new qty:                                   │
│       - ADDITION: batch.remainingQty + adjustmentQty       │
│       - REDUCTION: batch.remainingQty - abs(adjustmentQty)  │
│       - CORRECTION: Set to adjustmentQty (new actual)      │
│    c. If REDUCTION, check if sufficient stock              │
│    d. Update batch.remainingQty                            │
│    e. Update batch.status if needed                        │
│    f. Update stock_item.current_stock                      │
│    g. Create stock_card entry:                             │
│       - type: ADJUSTMENT                                   │
│       - qty_in (for ADDITION) or qty_out (for REDUCTION)   │
│       - reference: adjustment note                         │
│ 3. Create adjustment record for audit                      │
│ 4. Send notification if critical change                    │
│ 5. Log activity                                            │
│                                                             │
│ Response:                                                   │
│ {                                                           │
│   status: "success",                                       │
│   message: "Stok berhasil diadjust",                       │
│   data: {                                                  │
│     adjustments: [...],                                    │
│     stockCards: [...],                                     │
│     currentStock: {...}                                    │
│   }                                                        │
│ }                                                           │
└─────────────────────────────────────────────────────────────┘
```

### Profit Calculation Flow

```php
// src/modules/V1/Finance/Services/ProfitCalculationService.php

class ProfitCalculationService
{
    /**
     * Calculate profit for a single item
     *
     * @param float $sellPrice
     * @param float $buyPrice
     * @param int $qty
     * @return array
     */
    public function calculateItemProfit(float $sellPrice, float $buyPrice, int $qty): array
    {
        $revenue = $sellPrice * $qty;
        $cost = $buyPrice * $qty;
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $margin,
            'marginPercentage' => round($margin, 2) . '%'
        ];
    }

    /**
     * Calculate weighted average buy price from multiple batches
     *
     * Used when selling from multiple batches (FEFO allocation)
     *
     * @param array $batches
     * @return float
     */
    public function calculateWeightedAverageBuyPrice(array $batches): float
    {
        $totalCost = 0;
        $totalQty = 0;

        foreach ($batches as $batch) {
            $totalCost += $batch['buyPrice'] * $batch['qty'];
            $totalQty += $batch['qty'];
        }

        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }

    /**
     * Calculate profit for Kitchen Order delivery
     *
     * @param KitchenOrder $order
     * @param array $fefoAllocations
     * @return array
     */
    public function calculateOrderProfit(KitchenOrder $order, array $fefoAllocations): array
    {
        $items = [];
        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($order->items as $orderItem) {
            $stockItem = StockItem::find($orderItem->item_id);

            // Get FEFO allocations for this item
            $allocations = collect($fefoAllocations)
                ->where('itemId', $orderItem->item_id)
                ->first();

            if (!$allocations) {
                continue;
            }

            // Calculate weighted average buy price
            $buyPrice = $this->calculateWeightedAverageBuyPrice($allocations['batches']);

            // Calculate item profit
            $itemProfit = $this->calculateItemProfit(
                $stockItem->sell_price,
                $buyPrice,
                $orderItem->approved_qty
            );

            $items[] = [
                'itemId' => $orderItem->item_id,
                'itemName' => $stockItem->name,
                'qty' => $orderItem->approved_qty,
                'buyPrice' => $buyPrice,
                'sellPrice' => $stockItem->sell_price,
                ...$itemProfit,
                'batches' => $allocations['batches']
            ];

            $totalRevenue += $itemProfit['revenue'];
            $totalCost += $itemProfit['cost'];
        }

        $totalProfit = $totalRevenue - $totalCost;
        $overallMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'items' => $items,
            'summary' => [
                'totalRevenue' => $totalRevenue,
                'totalCost' => $totalCost,
                'totalProfit' => $totalProfit,
                'overallMargin' => $overallMargin,
                'overallMarginPercentage' => round($overallMargin, 2) . '%'
            ]
        ];
    }

    /**
     * Generate profit report by period
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $groupBy 'day', 'week', 'month', 'dapur', 'item'
     * @return array
     */
    public function generateProfitReport(
        string $startDate,
        string $endDate,
        string $groupBy = 'day'
    ): array {
        $transactions = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'SALES')
            ->get();

        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'summary' => [
                'totalRevenue' => $transactions->sum('amount'),
                'totalCost' => $transactions->sum('amount') - $transactions->sum('profit'),
                'totalProfit' => $transactions->sum('profit'),
                'averageMargin' => $transactions->avg('margin'),
                'transactionCount' => $transactions->count()
            ]
        ];

        switch ($groupBy) {
            case 'day':
                $report['breakdown'] = $transactions->groupBy(function ($t) {
                    return Carbon::parse($t->date)->format('Y-m-d');
                })->map(function ($items, $date) {
                    return [
                        'date' => $date,
                        'revenue' => $items->sum('amount'),
                        'profit' => $items->sum('profit'),
                        'margin' => $items->avg('margin'),
                        'transactions' => $items->count()
                    ];
                })->values();
                break;

            case 'week':
                $report['breakdown'] = $transactions->groupBy(function ($t) {
                    return Carbon::parse($t->date)->format('Y-W');
                })->map(function ($items, $week) {
                    return [
                        'week' => $week,
                        'revenue' => $items->sum('amount'),
                        'profit' => $items->sum('profit'),
                        'margin' => $items->avg('margin'),
                        'transactions' => $items->count()
                    ];
                })->values();
                break;

            case 'month':
                $report['breakdown'] = $transactions->groupBy(function ($t) {
                    return Carbon::parse($t->date)->format('Y-m');
                })->map(function ($items, $month) {
                    return [
                        'month' => $month,
                        'revenue' => $items->sum('amount'),
                        'profit' => $items->sum('profit'),
                        'margin' => $items->avg('margin'),
                        'transactions' => $items->count()
                    ];
                })->values();
                break;

            case 'dapur':
                $report['breakdown'] = $transactions->groupBy('dapur_id')
                    ->map(function ($items, $dapurId) {
                        $dapur = Dapur::find($dapurId);
                        return [
                            'dapurId' => $dapurId,
                            'dapurName' => $dapur ? $dapur->name : 'Unknown',
                            'revenue' => $items->sum('amount'),
                            'profit' => $items->sum('profit'),
                            'margin' => $items->avg('margin'),
                            'transactions' => $items->count()
                        ];
                    })->values();
                break;

            case 'item':
                $itemStats = [];
                foreach ($transactions as $transaction) {
                    $items = json_decode($transaction->items, true);
                    foreach ($items as $item) {
                        $itemId = $item['item_id'];
                        if (!isset($itemStats[$itemId])) {
                            $itemStats[$itemId] = [
                                'itemId' => $itemId,
                                'itemName' => $item['item_name'],
                                'qty' => 0,
                                'revenue' => 0,
                                'cost' => 0,
                                'profit' => 0
                            ];
                        }
                        $itemStats[$itemId]['qty'] += $item['qty'];
                        $itemStats[$itemId]['revenue'] += $item['subtotal'];
                        $itemStats[$itemId]['cost'] += $item['buy_price'] * $item['qty'];
                        $itemStats[$itemId]['profit'] += $item['profit'];
                    }
                }
                $report['breakdown'] = array_values($itemStats);
                break;
        }

        return $report;
    }
}
```

### QR Code Generation & Verification Flow

```php
// src/modules/V1/QRCode/Services/QRCodeService.php

class QRCodeService
{
    /**
     * Generate QR Code for Kitchen Order delivery
     *
     * @param KitchenOrder $order
     * @return array
     */
    public function generateDeliveryQR(KitchenOrder $order): array
    {
        // Prepare QR data
        $qrData = [
            'type' => 'KITCHEN_DELIVERY',
            'reference' => $order->order_number,
            'orderId' => $order->id,
            'dapurId' => $order->dapur_id,
            'generatedAt' => now()->toIso8601String(),
            'items' => $order->items->map(function ($item) {
                return [
                    'itemId' => $item->item_id,
                    'itemName' => $item->item_name,
                    'qty' => $item->approved_qty,
                    'batches' => json_decode($item->batch_allocations, true)
                ];
            })->toArray()
        ];

        // Generate unique QR string
        $qrString = $order->order_number . '-QR-' . time() . '-' . Str::random(8);

        // Encode QR data
        $encodedData = json_encode($qrData);

        // Generate QR code image using simple-qrcode or similar
        $qrImage = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($encodedData);

        // Store QR image
        $fileName = "qr-codes/{$qrString}.png";
        Storage::disk('public')->put($fileName, $qrImage);
        $imageUrl = Storage::disk('public')->url($fileName);

        // Save QR record to database
        $qrCode = QRCode::create([
            'type' => 'KITCHEN_DELIVERY',
            'reference_id' => $order->id,
            'data' => $encodedData,
            'qr_string' => $qrString,
            'image_url' => $imageUrl,
            'status' => 'ACTIVE',
            'created_by' => auth()->id()
        ]);

        // Update order with QR code
        $order->update([
            'qr_code_id' => $qrCode->id,
            'qr_code_data' => $qrString
        ]);

        Activity::log('qr_generated', "QR Code generated for order: {$order->order_number}");

        return [
            'qrString' => $qrString,
            'imageUrl' => $imageUrl,
            'scanUrl' => config('app.url') . "/api/v1/qrcode/verify/{$qrString}",
            'data' => $qrData
        ];
    }

    /**
     * Verify QR Code and return order details
     *
     * @param string $qrString
     * @return array
     */
    public function verifyQR(string $qrString): array
    {
        // Find QR code record
        $qrCode = QRCode::where('qr_string', $qrString)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$qrCode) {
            return [
                'valid' => false,
                'message' => 'QR Code tidak valid atau sudah kadaluarsa'
            ];
        }

        // Decode QR data
        $qrData = json_decode($qrCode->data, true);

        // Get order details
        $order = KitchenOrder::with(['items', 'dapur'])
            ->find($qrData['orderId']);

        if (!$order) {
            return [
                'valid' => false,
                'message' => 'Order tidak ditemukan'
            ];
        }

        // Update scan tracking
        $qrCode->increment('scan_count');
        $qrCode->update([
            'last_scanned_at' => now()
        ]);

        // Update order if not yet received
        if (!$order->received_by_dapur_at) {
            $order->update([
                'received_by_dapur_at' => now(),
                'received_by' => auth()->id()
            ]);
        }

        Activity::log('qr_scanned', "QR Code scanned for order: {$order->order_number}");

        return [
            'valid' => true,
            'message' => 'QR Code valid. Pengiriman diverifikasi.',
            'data' => [
                'order' => new KitchenOrderResource($order),
                'qrData' => $qrData,
                'scanCount' => $qrCode->scan_count,
                'firstScannedAt' => $qrCode->created_at,
                'lastScannedAt' => $qrCode->last_scanned_at
            ]
        ];
    }
}
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Design Patterns <a name="design-patterns"></a>

### 1. Domain-Driven Design (DDD)
- **Modules** represent bounded contexts
- **Shared** components contain cross-cutting concerns
- Clear separation between business logic and infrastructure

### 2. Service Layer Pattern
```php
// Controller handles HTTP
public function store(CreatePORequest $request)
{
    return $this->poService->createPO($request->validated());
}

// Service handles business logic
class POService
{
    public function createPO(array $data): PurchaseOrder
    {
        // Validation, PO creation, notifications, etc.
    }
}
```

### 3. Repository Pattern (via Services)
```php
// Models accessed through services
class StockService
{
    public function findByItem(string $itemId): Collection
    {
        return StockBatch::where('itemId', $itemId)->get();
    }
}
```

### 4. Strategy Pattern
```php
// Stock allocation strategies
interface StockAllocationStrategy
{
    public function allocate(string $itemId, int $qty): array;
}

class FEFOStrategy implements StockAllocationStrategy
{
    public function allocate(string $itemId, int $qty): array
    {
        // FEFO implementation
    }
}
```

### 5. Observer Pattern
```php
// Activity logging on model events
class PurchaseOrder extends Model
{
    protected static function boot()
    {
        static::updated(function ($po) {
            if ($po->isDirty('status')) {
                ActivityLog::create([
                    'entity' => 'PO',
                    'entityId' => $po->id,
                    'action' => 'STATUS_CHANGE',
                    'oldValue' => $po->getOriginal('status'),
                    'newValue' => $po->status
                ]);
            }
        });
    }
}
```

### 6. Facade Pattern
```php
// Simplified access to complex subsystems
Activity::log('po_created', 'Purchase Order created');
FEFO::allocateStock($itemId, $qty);

// Behind the scenes:
Activity → ActivityLogger Service
FEFO → StockAllocation Service
```

### 7. Data Transfer Object (DTO)
```php
// Clean data transfer between layers
class POCreationData
{
    public function __construct(
        public readonly string $supplierId,
        public readonly array $items,
        public readonly string $poDate,
        public readonly ?string $notes = null
    ) {}
}
```

### 8. Factory Pattern
```php
// QR Code generation
$qrCode = QRCodeFactory::create('KITCHEN_DELIVERY', $orderData);
```

### 9. Dependency Injection
```php
// Constructor injection
class POController extends Controller
{
    public function __construct(
        private POService $poService,
        private FEFOService $fefoService
    ) {}
}
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Coding Standards <a name="coding-standards"></a>

### PHP Standards

1. **Strict Types**: All files use `declare(strict_types=1);`
2. **Type Hints**: All methods have return types and parameter types
3. **Readonly Properties**: Use `public readonly` for immutable data
4. **Named Arguments**: Use named arguments for better readability
5. **Constructor Property Promotion**: Modern PHP 8+ syntax

### Code Style

```php
// ✅ GOOD - Modern, type-safe
final class POService
{
    public function __construct(
        private PORepository $repository,
        private FEFOService $fefoService
    ) {}

    public function createPO(CreatePOData $data): PurchaseOrder
    {
        return $this->repository->create($data);
    }
}

// ❌ AVOID - Old style
class POService
{
    private $repository;

    public function __construct(PORepository $repository)
    {
        $this->repository = $repository;
    }
}
```

### Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| Classes | PascalCase | `PurchaseOrder`, `FEFOService` |
| Methods | camelCase | `createPO()`, `allocateStock()` |
| Variables | camelCase | `$poId`, `$approvedQty` |
| Constants | UPPER_SNAKE_CASE | `MAX_PO_ITEMS`, `FEFO_PRIORITY` |
| Database Tables | snake_case | `purchase_orders`, `stock_batches` |
| API Routes | kebab-case | `/api/v1/purchase-orders/create` |

### Response Standards

All API responses follow this format:

```php
// Success Response
{
    "status": "success",
    "statusCode": 200,
    "message": "Operation completed successfully",
    "data": {
        "id": "PO-001",
        "poNumber": "PO-20250222-GDG-001"
    }
}

// Error Response
{
    "status": "error",
    "statusCode": 422,
    "message": "Validation failed",
    "errors": {
        "items": ["Minimal 1 item harus ditambahkan"]
    }
}
```

### Error Handling

```php
// Use try-catch for expected exceptions
try {
    $po = $this->poService->createPO($data);
    return ResponseHelper::success($po);
} catch (InsufficientStockException $e) {
    return ResponseHelper::error(
        $e->getMessage(),
        422,
        ['shortage' => $e->getShortageDetails()]
    );
} catch (Exception $e) {
    Log::error($e);
    return ResponseHelper::error('Internal server error', 500);
}
```

### Security Best Practices

1. **Never store passwords in plain text** - always use `Hash::make()`
2. **Validate all input** - use Form Requests
3. **Use prepared statements** - Eloquent ORM handles this
4. **Sanitize output** - use Resources for data transformation
5. **Log security events** - login attempts, permission changes
6. **Use HTTPS** in production
7. **Implement rate limiting** to prevent abuse
8. **Never expose internal details** in error messages
9. **Role-based access control** - check permissions for every action
10. **SQL injection prevention** - use parameterized queries

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- Link to Api Documentation -->

## 🚀 Links <a name="api-docs"></a>

To access the documentation goto the below link

- Link to api routes
```
http://localhost:8000/api/v1
```
- Link to documentation (Swagger/OpenAPI)
```
http://localhost:8000/api/documentation
```

<br/>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- GETTING STARTED -->

## 💻 Getting Started <a name="getting-started"></a>

To get a local copy up and running, follow these steps.

### Prerequisites

In order to run this project, you need:

1. PHP ^8.2 <br>
   use the following link to setup `PHP` if you dont have it already installed on your computer
<p align="left">(<a href="https://www.php.net/manual/en/install.php">install PHP</a>)</p>

2. Composer <br>
   use the following link to Download `Composer` if you dont have it already installed on your computer
<p align="left">(<a href="https://getcomposer.org/download/">install Composer</a>)</p>

3. PostgreSQL 14+ <br>
   use the following link to setup `PostgreSQL` if you dont have it already installed on your computer
<p align="left">(<a href="https://www.postgresql.org/download/">install PostgreSQL</a>)</p>

## Install

Clone the repository:
```
git clone git@github.com:your-org/laravel-koperasi-harmoni.git
```

Install dependencies:

```
composer install
```

## Setup

Create your database.

Create .env file, change using the .env.example file and update the Database credentials:
```
cp .env.example .env
```

Generate keys, Run the migration and seed data:

```
php artisan key:generate
php artisan migrate --seed
```

### Usage

The following command can be used to run the application.

```sh
php artisan serve
```

The API will be available at `http://localhost:8000`

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📊 Database Schema Overview

### Key Tables

```sql
-- Users & Authentication
users (id, username, password, name, role, email, supplier_id, ...)
personal_access_tokens (id, tokenable_id, token, abilities, ...)

-- Purchase Orders
purchase_orders (id, po_number, po_date, supplier_id, status, ...)
purchase_order_items (id, po_id, item_id, estimated_qty, actual_qty, ...)

-- Stock Management
stock_items (id, code, name, category, unit, min_stock, buy_price, sell_price, ...)
stock_batches (id, item_id, batch_number, quantity, remaining_qty, buy_price, expiry_date, ...)
stock_cards (id, item_id, date, type, batch_number, qty_in, qty_out, balance, ...)

-- Kitchen Orders
kitchen_orders (id, order_number, dapur_id, request_date, status, ...)
kitchen_order_items (id, order_id, item_id, requested_qty, approved_qty, ...)

-- Financial
transactions (id, date, type, category, amount, reference, items, profit, ...)

-- QR Codes
qr_codes (id, type, reference_id, data, image_url, created_at, ...)

-- Suppliers
suppliers (id, code, name, contact, phone, email, address, ...)

-- Activity Logs
activity_logs (id, user_id, action, entity_type, entity_id, old_values, new_values, ...)
```

## 🔄 Status Transitions

### Purchase Order Status Flow
```
DRAFT → TERKIRIM → PERUBAHAN_HARGA → DIKONFIRMASI_SUPPLIER → DIKONFIRMASI_KOPERASI → SELESAI
                   ↓
                   └→ DIBATALKAN (can be cancelled at various stages)
```

### Kitchen Order Status Flow
```
DRAFT → TERKIRIM → DIPROSES → DITERIMA_DAPUR
```

### Stock Batch Status Flow
```
AVAILABLE → ALLOCATED → (when qty becomes 0)
            ↓
         EXPIRED (when expiry date passed)
```

## Contributing
Feel free to fork the repository, make changes, and submit pull requests. Feedback is always welcome!

## License
This project is licensed under the MIT License.

---

*Document Version: 1.0*
*Last Updated: 2025-02-26*
*System: SIM-LKD (Sistem Informasi Manajemen Koperasi)*
*Backend: Laravel 11, PostgreSQL 14+*
*Frontend: Next.js 15, TypeScript, Zustand, Tailwind CSS*
*Architecture: Domain-Driven Design (DDD)*
*Stock Allocation: FEFO (First Expired First Out)*
