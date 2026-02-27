# API Documentation - SIM-LKD Backend

**Base URL:** `http://127.0.0.1:8000/api/v1`

**Authentication:** Bearer Token (dapatkan dari login endpoint)

---

## Table of Contents

1. [Authentication](#authentication)
2. [Admin Management](#admin-management)
3. [Suppliers](#suppliers)
4. [Stock Management](#stock-management)
5. [Purchase Orders](#purchase-orders)
6. [Kitchen Orders](#kitchen-orders)
7. [Finance](#finance)
8. [QR Codes](#qr-codes)

---

## Authentication

### 1. Admin Login
**Endpoint:** `POST /admin/auth/login`
**Auth Required:** No

**Request Body:**
```json
{
  "email": "admin@gmail.com",
  "password": "admin123"
}
```

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": {
    "id": "uuid",
    "firstName": "Super",
    "lastName": "Admin",
    "email": "admin@gmail.com",
    "status": "ACTIVE",
    "isSuperAdmin": true,
    "createdAt": "2026-02-26 07:29:26"
  },
  "accessToken": "4|randomtokenstring"
}
```

**Usage:** Copy `accessToken` and add to headers:
```
Authorization: Bearer 4|randomtokenstring
```

---

## Admin Management

### Get All Admins
**Endpoint:** `POST /admin/admins/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "john",
  "sortColumn": "created_at",
  "sortColumnDir": "desc",
  "pageNumber": 1,
  "pageSize": 15
}
```

**Fields:**
- `search` (optional): Search in first_name, last_name, email
- `sortColumn` (optional): Column to sort by (default: created_at)
- `sortColumnDir` (optional): "asc" or "desc" (default: desc)
- `pageNumber` (optional): Page number (default: 1)
- `pageSize` (optional): Items per page (default: 15)

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "firstName": "Super",
      "lastName": "Admin",
      "email": "admin@gmail.com",
      "status": "ACTIVE",
      "isSuperAdmin": true,
      "createdAt": "2026-02-26 07:29:26"
    }
  ]
}
```

### Create Admin
**Endpoint:** `POST /admin/admins/create`
**Auth Required:** Yes

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "roles": [1]
}
```

**Fields:**
- `first_name` (required): string, max 255
- `last_name` (required): string, max 255
- `email` (required): valid email
- `password` (required): string
- `roles` (required): array of role IDs

### Update Admin
**Endpoint:** `POST /admin/admins/{admin_id}/update`
**Auth Required:** Yes

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.new@example.com"
}
```

### Change Admin Role
**Endpoint:** `POST /admin/admins/{admin_id}/change-role`
**Auth Required:** Yes

**Request Body:**
```json
{
  "roles": [1, 2, 3]
}
```

---

## Suppliers

### Get All Suppliers
**Endpoint:** `POST /suppliers/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "beras",
  "sortDirColumn": "created_at",
  "sortDir": "desc",
  "pageNumber": 1,
  "pageSize": 15
}
```

**Fields:**
- `search` (optional): Search in name, code, contact_person
- `sortDirColumn` (optional): Column to sort by
- `sortDir` (optional): "asc" or "desc"
- `pageNumber` (optional): Page number
- `pageSize` (optional): Items per page

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "code": "SUP-001",
      "name": "PT. Beras Jaya",
      "contactPerson": "Budi Santoso",
      "phone": "08123456789",
      "email": "contact@berasjaya.com",
      "address": "Jl. Padi No. 123",
      "status": "ACTIVE",
      "createdAt": "2026-02-26 10:00:00"
    }
  ]
}
```

### Create Supplier
**Endpoint:** `POST /suppliers/create`
**Auth Required:** Yes

**Request Body:**
```json
{
  "name": "PT. Beras Jaya",
  "contact_person": "Budi Santoso",
  "phone": "08123456789",
  "email": "contact@berasjaya.com",
  "address": "Jl. Padi No. 123"
}
```

---

## Stock Management

### Get All Stock Items
**Endpoint:** `POST /stock/items/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "beras",
  "sortDirColumn": "created_at",
  "sortDir": "desc",
  "pageNumber": 1,
  "pageSize": 15
}
```

**Fields:**
- `search` (optional): Search in name, code, category
- `sortDirColumn` (optional): Column to sort by
- `sortDir` (optional): "asc" or "desc"
- `pageNumber` (optional): Page number
- `pageSize` (optional): Items per page

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "code": "STK-001",
      "name": "Beras Premium",
      "category": "Sembako",
      "unit": "kg",
      "currentStock": 100,
      "minStock": 10,
      "buyPrice": 12000,
      "sellPrice": 15000,
      "createdAt": "2026-02-26 10:00:00"
    }
  ]
}
```

### Get Stock Item Detail
**Endpoint:** `POST /stock/items/detail/{id}`
**Auth Required:** Yes

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": {
    "id": "uuid",
    "code": "STK-001",
    "name": "Beras Premium",
    "category": "Sembako",
    "unit": "kg",
    "currentStock": 100,
    "minStock": 10,
    "buyPrice": 12000,
    "sellPrice": 15000
  }
}
```

---

## Purchase Orders

### Get All Purchase Orders
**Endpoint:** `POST /purchase-orders/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "PO-001",
  "sortDirColumn": "created_at",
  "sortDir": "desc",
  "pageNumber": 1,
  "pageSize": 15,
  "status": "PENDING",
  "supplier_id": "uuid"
}
```

**Fields:**
- `search` (optional): Search in po_number
- `sortDirColumn` (optional): Column to sort by
- `sortDir` (optional): "asc" or "desc"
- `pageNumber` (optional): Page number
- `pageSize` (optional): Items per page
- `status` (optional): Filter by status
- `supplier_id` (optional): Filter by supplier

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "poNumber": "PO-2026-001",
      "supplierId": "uuid",
      "supplier": {
        "id": "uuid",
        "name": "PT. Beras Jaya"
      },
      "status": "PENDING",
      "totalAmount": 1500000,
      "items": [
        {
          "id": "uuid",
          "stockItemId": "uuid",
          "stockItem": {
            "name": "Beras Premium"
          },
          "quantity": 100,
          "unitPrice": 15000,
          "totalPrice": 1500000
        }
      ],
      "createdAt": "2026-02-26 10:00:00"
    }
  ]
}
```

---

## Kitchen Orders

### Get All Kitchen Orders
**Endpoint:** `POST /kitchen/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "ORDER-001",
  "sortDirColumn": "created_at",
  "sortDir": "desc",
  "pageNumber": 1,
  "pageSize": 15,
  "status": "PENDING",
  "dapur_id": "uuid"
}
```

**Fields:**
- `search` (optional): Search in order_number
- `sortDirColumn` (optional): Column to sort by
- `sortDir` (optional): "asc" or "desc"
- `pageNumber` (optional): Page number
- `pageSize` (optional): Items per page
- `status` (optional): Filter by status
- `dapur_id` (optional): Filter by dapur

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "orderNumber": "ORDER-2026-001",
      "dapurId": "uuid",
      "dapur": {
        "id": "uuid",
        "name": "Dapur Utama"
      },
      "status": "PENDING",
      "notes": "Pesanan khusus",
      "items": [
        {
          "id": "uuid",
          "stockItemId": "uuid",
          "stockItem": {
            "name": "Beras Premium"
          },
          "quantity": 50
        }
      ],
      "createdAt": "2026-02-26 10:00:00"
    }
  ]
}
```

---

## Finance

### Get All Transactions
**Endpoint:** `POST /finance/list`
**Auth Required:** Yes

**Request Body:**
```json
{
  "search": "PURCHASE",
  "sortDirColumn": "date",
  "sortDir": "desc",
  "pageNumber": 1,
  "pageSize": 15,
  "type": "INCOME",
  "category": "SALES",
  "start_date": "2026-01-01",
  "end_date": "2026-01-31"
}
```

**Fields:**
- `search` (optional): Search in description, reference
- `sortDirColumn` (optional): Column to sort by
- `sortDir` (optional): "asc" or "desc"
- `pageNumber` (optional): Page number
- `pageSize` (optional): Items per page
- `type` (optional): Filter by type (INCOME/EXPENSE)
- `category` (optional): Filter by category
- `start_date` (optional): Filter start date
- `end_date` (optional): Filter end date

**Response (200):**
```json
{
  "status": "success",
  "statusCode": 200,
  "data": [
    {
      "id": "uuid",
      "date": "2026-02-26",
      "type": "INCOME",
      "category": "SALES",
      "description": "Penjualan Harian",
      "reference": "TRX-2026-001",
      "amount": 1500000,
      "status": "PAID",
      "createdAt": "2026-02-26 10:00:00"
    }
  ]
}
```

---

## QR Codes

### Generate QR Code
**Endpoint:** `POST /qrcode/generate`
**Auth Required:** Yes

**Request Body:**
```json
{
  "reference_id": "uuid",
  "reference_type": "KitchenOrder",
  "data": "Custom data for QR"
}
```

---

## Common Response Structure

### Success Response (200/201)
```json
{
  "status": "success",
  "statusCode": 200,
  "data": { ... },
  "message": "Operation successful"
}
```

### Error Response (4xx/5xx)
```json
{
  "status": "error",
  "statusCode": 422,
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Common Error Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 201  | Created |
| 401  | Unauthorized - Token missing or invalid |
| 403  | Forbidden - Permission denied |
| 404  | Not Found |
| 422  | Validation Error |
| 500  | Internal Server Error |

---

## Common Data Types

### UUID Format
All IDs use UUID v4 format: `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`

### Date/Time Format
Unix timestamp: `"2026-02-26 10:00:00"`

### Enum Values

**User/Admin Status:**
- `ACTIVE`
- `INACTIVE`
- `SUSPENDED`

**Transaction Type:**
- `INCOME`
- `EXPENSE`

**Transaction Category:**
- `SALES`
- `PURCHASE`
- `OPERATIONAL`
- `OTHER`

---

## Pagination & Sorting Parameters

### Standard Request Parameters (for all list endpoints)

```typescript
interface ListRequest {
  search?: string;           // Global search
  sortColumn?: string;       // Column to sort by
  sortColumnDir?: string;    // "asc" | "desc"
  pageNumber?: number;       // Page number (default: 1)
  pageSize?: number;         // Items per page (default: 15)
}
```

### TypeScript Interface Example

```typescript
interface ApiResponse<T> {
  status: 'success' | 'error';
  statusCode: number;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

interface Admin {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
  isSuperAdmin: boolean;
  createdAt: string;
}

interface ListResponse<T> {
  status: 'success';
  statusCode: number;
  data: T[];
}
```

---

## Frontend Integration Example

### React/TypeScript Hook

```typescript
import axios from 'axios';

const API_BASE_URL = 'http://127.0.0.1:8000/api/v1';

// Login
const login = async (email: string, password: string) => {
  const response = await axios.post(`${API_BASE_URL}/admin/auth/login`, {
    email,
    password
  });

  const token = response.data.data.accessToken;
  localStorage.setItem('token', token);
  return response.data;
};

// Get all admins
const getAdmins = async (params: ListRequest) => {
  const token = localStorage.getItem('token');
  const response = await axios.post(
    `${API_BASE_URL}/admin/admins/list`,
    params,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
  return response.data;
};
```

### Axios Instance Setup

```typescript
// api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api/v1',
  headers: {
    'Content-Type': 'application/json'
  }
});

// Request interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to login
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data);
  }
);

export default api;
```

---

## Testing with cURL

### Login
```bash
curl -X POST http://127.0.0.1:8000/api/v1/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gmail.com",
    "password": "admin123"
  }'
```

### Get All Admins
```bash
curl -X POST http://127.0.0.1:8000/api/v1/admin/admins/list \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "pageNumber": 1,
    "pageSize": 10,
    "search": "admin"
  }'
```

---

## Notes

- All endpoints use **POST** method for consistency
- All timestamps are in **Unix timestamp** format
- All IDs use **UUID v4**
- Default pagination: 15 items per page
- Always send `Authorization` header for authenticated endpoints
- Token expiration is configured in Laravel Sanctum settings

---

## Swagger Documentation

Interactive API documentation available at:
```
http://127.0.0.1:8000/api/v1/documentation
```

---

**Last Updated:** 2026-02-26
**Version:** 1.0.0
