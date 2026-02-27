// TypeScript Types & Interfaces for SIM-LKD API
// Generated: 2026-02-26

// ============================================
// COMMON TYPES
// ============================================

export interface ApiResponse<T = any> {
  status: 'success' | 'error';
  statusCode: number;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface ListRequest {
  search?: string;
  sortColumn?: string;
  sortColumnDir?: 'asc' | 'desc';
  pageNumber?: number;
  pageSize?: number;
}

export interface ListResponse<T> {
  status: 'success';
  statusCode: number;
  data: T[];
}

// ============================================
// AUTHENTICATION
// ============================================

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  status: 'success';
  statusCode: number;
  data: Admin;
  accessToken: string;
}

// ============================================
// ADMIN
// ============================================

export type AdminStatus = 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';

export interface Admin {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  status: AdminStatus;
  isSuperAdmin: boolean;
  createdAt: string;
}

export interface CreateAdminRequest {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  roles: number[];
}

export interface UpdateAdminRequest {
  first_name: string;
  last_name: string;
  email: string;
}

export interface ChangeAdminRoleRequest {
  roles: number[];
}

// ============================================
// SUPPLIER
// ============================================

export type SupplierStatus = 'ACTIVE' | 'INACTIVE';

export interface Supplier {
  id: string;
  code: string;
  name: string;
  contactPerson: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  status: SupplierStatus;
  createdAt: string;
}

export interface CreateSupplierRequest {
  name: string;
  contact_person?: string;
  phone?: string;
  email?: string;
  address?: string;
}

export interface UpdateSupplierRequest {
  name: string;
  contact_person?: string;
  phone?: string;
  email?: string;
  address?: string;
  status?: SupplierStatus;
}

// ============================================
// STOCK
// ============================================

export interface StockItem {
  id: string;
  code: string;
  name: string;
  category: string | null;
  unit: string | null;
  currentStock: number;
  minStock: number;
  buyPrice: number;
  sellPrice: number;
  createdAt: string;
}

export interface CreateStockItemRequest {
  name: string;
  category?: string;
  unit?: string;
  min_stock?: number;
  buy_price?: number;
  sell_price?: number;
}

export interface UpdateStockItemRequest {
  name: string;
  category?: string;
  unit?: string;
  min_stock?: number;
  buy_price?: number;
  sell_price?: number;
}

// ============================================
// PURCHASE ORDER
// ============================================

export type POStatus = 'DRAFT' | 'PENDING' | 'APPROVED' | 'RECEIVED' | 'CANCELLED';

export interface PurchaseOrder {
  id: string;
  poNumber: string;
  supplierId: string;
  supplier: {
    id: string;
    name: string;
  };
  status: POStatus;
  totalAmount: number;
  items: PurchaseOrderItem[];
  createdAt: string;
}

export interface PurchaseOrderItem {
  id: string;
  stockItemId: string;
  stockItem: {
    id: string;
    name: string;
  };
  quantity: number;
  unitPrice: number;
  totalPrice: number;
}

export interface CreatePORequest {
  supplier_id: string;
  items: {
    item_id: string;
    quantity: number;
    unit_price: number;
  }[];
}

// ============================================
// KITCHEN ORDER
// ============================================

export type KitchenOrderStatus = 'DRAFT' | 'SENT' | 'PROCESSING' | 'READY' | 'DELIVERED' | 'CANCELLED';

export interface KitchenOrder {
  id: string;
  orderNumber: string;
  dapurId: string;
  dapur: {
    id: string;
    name: string;
  };
  status: KitchenOrderStatus;
  notes: string | null;
  items: KitchenOrderItem[];
  createdAt: string;
}

export interface KitchenOrderItem {
  id: string;
  stockItemId: string;
  stockItem: {
    id: string;
    name: string;
  };
  quantity: number;
}

export interface CreateKitchenOrderRequest {
  dapur_id: string;
  items: {
    item_id: string;
    quantity: number;
  }[];
  notes?: string;
}

// ============================================
// FINANCE
// ============================================

export type TransactionType = 'INCOME' | 'EXPENSE';
export type TransactionCategory = 'SALES' | 'PURCHASE' | 'OPERATIONAL' | 'OTHER';
export type TransactionStatus = 'PENDING' | 'PAID' | 'CANCELLED';

export interface Transaction {
  id: string;
  date: string;
  type: TransactionType;
  category: TransactionCategory;
  description: string | null;
  reference: string | null;
  amount: number;
  status: TransactionStatus;
  paymentDate: string | null;
  createdAt: string;
}

export interface CreateTransactionRequest {
  date: string;
  type: TransactionType;
  category: TransactionCategory;
  description?: string;
  reference?: string;
  amount: number;
}

// ============================================
// QR CODE
// ============================================

export interface QRCode {
  id: string;
  code: string;
  referenceId: string;
  referenceType: string;
  imageUrl: string;
  createdAt: string;
}

export interface GenerateQRRequest {
  reference_id: string;
  reference_type: string;
  data?: string;
}

// ============================================
// ACTIVITY LOG
// ============================================

export interface ActivityLog {
  id: string;
  adminId: string;
  admin: {
    id: string;
    firstName: string;
    lastName: string;
    email: string;
  };
  action: string;
  modelType: string;
  modelId: string;
  changes: Record<string, any> | null;
  createdAt: string;
}

// ============================================
// API ENDPOINTS
// ============================================

export const API_ENDPOINTS = {
  // Auth
  LOGIN: '/admin/auth/login',

  // Admin
  ADMINS_LIST: '/admin/admins/list',
  ADMINS_CREATE: '/admin/admins/create',
  ADMINS_UPDATE: (id: string) => `/admin/admins/${id}/update`,
  ADMINS_CHANGE_ROLE: (id: string) => `/admin/admins/${id}/change-role`,

  // Suppliers
  SUPPLIERS_LIST: '/suppliers/list',
  SUPPLIERS_CREATE: '/suppliers/create',
  SUPPLIERS_UPDATE: (id: string) => `/suppliers/${id}/update`,
  SUPPLIERS_DELETE: (id: string) => `/suppliers/${id}/delete`,

  // Stock
  STOCK_LIST: '/stock/items/list',
  STOCK_DETAIL: (id: string) => `/stock/items/detail/${id}`,
  STOCK_CREATE: '/stock/items/create',
  STOCK_UPDATE: (id: string) => `/stock/items/${id}/update`,
  STOCK_DELETE: (id: string) => `/stock/items/${id}/delete`,

  // Purchase Orders
  PO_LIST: '/purchase-orders/list',
  PO_DETAIL: (id: string) => `/purchase-orders/${id}`,
  PO_CREATE: '/purchase-orders/create',
  PO_CANCEL: (id: string) => `/purchase-orders/${id}/cancel`,

  // Kitchen Orders
  KITCHEN_LIST: '/kitchen/list',
  KITCHEN_DETAIL: (id: string) => `/kitchen/detail/${id}`,
  KITCHEN_CREATE: '/kitchen/create',
  KITCHEN_SEND: (id: string) => `/kitchen/${id}/send`,
  KITCHEN_PROCESS: (id: string) => `/kitchen/${id}/process`,
  KITCHEN_DELIVER: (id: string) => `/kitchen/${id}/deliver`,

  // Finance
  FINANCE_LIST: '/finance/list',
  FINANCE_DETAIL: (id: string) => `/finance/detail/${id}`,
  FINANCE_MARK_PAID: (id: string) => `/finance/${id}/mark-paid`,

  // QR Codes
  QRCODE_GENERATE: '/qrcode/generate',
  QRCODE_VERIFY: '/qrcode/verify',

  // Reports
  REPORT_PROFIT_SUMMARY: '/finance/reports/profit-summary',
  REPORT_CASHFLOW: '/finance/reports/cashflow',
  REPORT_OMSET_DAPUR: '/finance/reports/omset-by-dapur',
  REPORT_OMSET_ITEM: '/finance/reports/omset-by-item',
} as const;

// ============================================
// AXIOS INSTANCE
// ============================================

import axios from 'axios';

export const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
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
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data);
  }
);

// ============================================
// API SERVICE
// ============================================

export const apiService = {
  // Auth
  login: (data: LoginRequest) =>
    api.post<LoginResponse>(API_ENDPOINTS.LOGIN, data),

  // Admin
  getAdmins: (data: ListRequest) =>
    api.post<ListResponse<Admin>>(API_ENDPOINTS.ADMINS_LIST, data),
  createAdmin: (data: CreateAdminRequest) =>
    api.post<ApiResponse<Admin>>(API_ENDPOINTS.ADMINS_CREATE, data),
  updateAdmin: (id: string, data: UpdateAdminRequest) =>
    api.post<ApiResponse<Admin>>(API_ENDPOINTS.ADMINS_UPDATE(id), data),
  changeAdminRole: (id: string, data: ChangeAdminRoleRequest) =>
    api.post<ApiResponse<Admin>>(API_ENDPOINTS.ADMINS_CHANGE_ROLE(id), data),

  // Suppliers
  getSuppliers: (data: ListRequest) =>
    api.post<ListResponse<Supplier>>(API_ENDPOINTS.SUPPLIERS_LIST, data),
  createSupplier: (data: CreateSupplierRequest) =>
    api.post<ApiResponse<Supplier>>(API_ENDPOINTS.SUPPLIERS_CREATE, data),

  // Stock
  getStockItems: (data: ListRequest) =>
    api.post<ListResponse<StockItem>>(API_ENDPOINTS.STOCK_LIST, data),
  getStockDetail: (id: string) =>
    api.post<ApiResponse<StockItem>>(API_ENDPOINTS.STOCK_DETAIL(id), {}),

  // Purchase Orders
  getPurchaseOrders: (data: ListRequest) =>
    api.post<ListResponse<PurchaseOrder>>(API_ENDPOINTS.PO_LIST, data),

  // Kitchen Orders
  getKitchenOrders: (data: ListRequest) =>
    api.post<ListResponse<KitchenOrder>>(API_ENDPOINTS.KITCHEN_LIST, data),

  // Finance
  getTransactions: (data: ListRequest) =>
    api.post<ListResponse<Transaction>>(API_ENDPOINTS.FINANCE_LIST, data),
};

export default api;
