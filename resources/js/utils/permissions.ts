/**
 * Role-based permissions utility for NovERP
 * Implements the permission matrices defined in the specification
 */

export type UserRole = 'super_admin' | 'admin' | 'accountant' | 'sales' | 'inventory_manager' | 'viewer';

export type PermissionLevel = 'none' | 'view' | 'view_edit' | 'full';

export type PageName = 
  | 'dashboard'
  | 'invoices'
  | 'oldinvoices'
  | 'customers'
  | 'products'
  | 'services'
  | 'payments'
  | 'inventory'
  | 'reports'
  | 'settings'
  | 'admin_users'
  | 'admin_audit'
  | 'admin_profiles';

export interface PagePermission {
  access: boolean;
  view: boolean;
  create: boolean;
  edit: boolean;
  delete: boolean;
}

export interface SpecialPermissions {
  canValidateInvoices: boolean;
  canImportXML: boolean;
  canExportData: boolean;
  canViewFinancialKPIs: boolean;
  canManageOwnProfile: boolean;
}

/**
 * Permission matrices for each role based on the specification
 */
const rolePermissions: Record<UserRole, Record<PageName, PagePermission>> = {
  super_admin: {
    dashboard: { access: true, view: true, create: true, edit: true, delete: true },
    invoices: { access: true, view: true, create: true, edit: true, delete: true },
    oldinvoices: { access: true, view: true, create: true, edit: true, delete: true },
    customers: { access: true, view: true, create: true, edit: true, delete: true },
    products: { access: true, view: true, create: true, edit: true, delete: true },
    services: { access: true, view: true, create: true, edit: true, delete: true },
    payments: { access: true, view: true, create: true, edit: true, delete: true },
    inventory: { access: true, view: true, create: true, edit: true, delete: true },
    reports: { access: true, view: true, create: false, edit: false, delete: false },
    settings: { access: true, view: true, create: true, edit: true, delete: true },
    admin_users: { access: true, view: true, create: true, edit: true, delete: true },
    admin_audit: { access: true, view: true, create: false, edit: false, delete: false },
    admin_profiles: { access: true, view: true, create: true, edit: true, delete: true },
  },
  admin: {
    dashboard: { access: true, view: true, create: true, edit: true, delete: true },
    invoices: { access: true, view: true, create: true, edit: true, delete: true },
    oldinvoices: { access: true, view: true, create: true, edit: true, delete: true },
    customers: { access: true, view: true, create: true, edit: true, delete: true },
    products: { access: true, view: true, create: true, edit: true, delete: true },
    services: { access: true, view: true, create: true, edit: true, delete: true },
    payments: { access: true, view: true, create: true, edit: true, delete: true },
    inventory: { access: true, view: true, create: true, edit: true, delete: true },
    reports: { access: true, view: true, create: false, edit: false, delete: false },
    settings: { access: true, view: true, create: true, edit: true, delete: true },
    admin_users: { access: true, view: true, create: true, edit: true, delete: true },
    admin_audit: { access: true, view: true, create: false, edit: false, delete: false },
    admin_profiles: { access: true, view: true, create: true, edit: true, delete: true },
  },
  accountant: {
    dashboard: { access: true, view: true, create: false, edit: false, delete: false },
    invoices: { access: true, view: true, create: true, edit: true, delete: false },
    oldinvoices: { access: true, view: true, create: true, edit: true, delete: false },
    customers: { access: true, view: true, create: true, edit: true, delete: false },
    products: { access: true, view: true, create: true, edit: true, delete: false },
    services: { access: true, view: true, create: true, edit: true, delete: false },
    payments: { access: true, view: true, create: true, edit: true, delete: false },
    inventory: { access: false, view: false, create: false, edit: false, delete: false },
    reports: { access: true, view: true, create: false, edit: false, delete: false },
    settings: { access: false, view: false, create: false, edit: false, delete: false },
    admin_users: { access: false, view: false, create: false, edit: false, delete: false },
    admin_audit: { access: false, view: false, create: false, edit: false, delete: false },
    admin_profiles: { access: false, view: false, create: false, edit: false, delete: false },
  },
  sales: {
    dashboard: { access: true, view: true, create: false, edit: false, delete: false },
    invoices: { access: true, view: true, create: true, edit: true, delete: false }, // edit own drafts only
    oldinvoices: { access: true, view: true, create: false, edit: false, delete: false },
    customers: { access: true, view: true, create: true, edit: true, delete: false },
    products: { access: true, view: true, create: false, edit: false, delete: false },
    services: { access: true, view: true, create: false, edit: false, delete: false },
    payments: { access: true, view: true, create: false, edit: false, delete: false },
    inventory: { access: false, view: false, create: false, edit: false, delete: false },
    reports: { access: true, view: true, create: false, edit: false, delete: false }, // sales reports only
    settings: { access: false, view: false, create: false, edit: false, delete: false },
    admin_users: { access: false, view: false, create: false, edit: false, delete: false },
    admin_audit: { access: false, view: false, create: false, edit: false, delete: false },
    admin_profiles: { access: false, view: false, create: false, edit: false, delete: false },
  },
  inventory_manager: {
    dashboard: { access: true, view: true, create: false, edit: false, delete: false }, // inventory widgets only
    invoices: { access: false, view: false, create: false, edit: false, delete: false },
    oldinvoices: { access: false, view: false, create: false, edit: false, delete: false },
    customers: { access: false, view: false, create: false, edit: false, delete: false },
    products: { access: true, view: true, create: true, edit: true, delete: true },
    services: { access: false, view: false, create: false, edit: false, delete: false },
    payments: { access: false, view: false, create: false, edit: false, delete: false },
    inventory: { access: true, view: true, create: true, edit: true, delete: true },
    reports: { access: true, view: true, create: false, edit: false, delete: false }, // inventory reports only
    settings: { access: false, view: false, create: false, edit: false, delete: false },
    admin_users: { access: false, view: false, create: false, edit: false, delete: false },
    admin_audit: { access: false, view: false, create: false, edit: false, delete: false },
    admin_profiles: { access: false, view: false, create: false, edit: false, delete: false },
  },
  viewer: {
    dashboard: { access: true, view: true, create: false, edit: false, delete: false },
    invoices: { access: true, view: true, create: false, edit: false, delete: false },
    oldinvoices: { access: true, view: true, create: false, edit: false, delete: false },
    customers: { access: true, view: true, create: false, edit: false, delete: false },
    products: { access: true, view: true, create: false, edit: false, delete: false },
    services: { access: true, view: true, create: false, edit: false, delete: false },
    payments: { access: true, view: true, create: false, edit: false, delete: false },
    inventory: { access: true, view: true, create: false, edit: false, delete: false },
    reports: { access: true, view: true, create: false, edit: false, delete: false },
    settings: { access: false, view: false, create: false, edit: false, delete: false },
    admin_users: { access: false, view: false, create: false, edit: false, delete: false },
    admin_audit: { access: false, view: false, create: false, edit: false, delete: false },
    admin_profiles: { access: false, view: false, create: false, edit: false, delete: false },
  },
};

/**
 * Special permissions for each role
 */
const specialPermissions: Record<UserRole, SpecialPermissions> = {
  super_admin: {
    canValidateInvoices: true,
    canImportXML: true,
    canExportData: true,
    canViewFinancialKPIs: true,
    canManageOwnProfile: true,
  },
  admin: {
    canValidateInvoices: true,
    canImportXML: true,
    canExportData: true,
    canViewFinancialKPIs: true,
    canManageOwnProfile: true,
  },
  accountant: {
    canValidateInvoices: true,
    canImportXML: true,
    canExportData: true,
    canViewFinancialKPIs: true,
    canManageOwnProfile: true,
  },
  sales: {
    canValidateInvoices: false,
    canImportXML: true,
    canExportData: true,
    canViewFinancialKPIs: false, // Cannot see revenue/profit KPIs
    canManageOwnProfile: true,
  },
  inventory_manager: {
    canValidateInvoices: false,
    canImportXML: false,
    canExportData: true,
    canViewFinancialKPIs: false,
    canManageOwnProfile: true,
  },
  viewer: {
    canValidateInvoices: false,
    canImportXML: false,
    canExportData: true, // Can export/download if export buttons are present
    canViewFinancialKPIs: true,
    canManageOwnProfile: true,
  },
};

/**
 * Get page permissions for a specific role and page
 */
export function getPagePermissions(role: UserRole, page: PageName): PagePermission {
  return rolePermissions[role]?.[page] ?? { access: false, view: false, create: false, edit: false, delete: false };
}

/**
 * Get special permissions for a role
 */
export function getSpecialPermissions(role: UserRole): SpecialPermissions {
  return specialPermissions[role] ?? {
    canValidateInvoices: false,
    canImportXML: false,
    canExportData: false,
    canViewFinancialKPIs: false,
    canManageOwnProfile: false,
  };
}

/**
 * Check if user can access a page
 */
export function canAccessPage(role: UserRole, page: PageName): boolean {
  return getPagePermissions(role, page).access;
}

/**
 * Check if user can view a page
 */
export function canViewPage(role: UserRole, page: PageName): boolean {
  const perms = getPagePermissions(role, page);
  return perms.access && perms.view;
}

/**
 * Check if user can create on a page
 */
export function canCreateOnPage(role: UserRole, page: PageName): boolean {
  const perms = getPagePermissions(role, page);
  return perms.access && perms.create;
}

/**
 * Check if user can edit on a page
 */
export function canEditOnPage(role: UserRole, page: PageName): boolean {
  const perms = getPagePermissions(role, page);
  return perms.access && perms.edit;
}

/**
 * Check if user can delete on a page
 */
export function canDeleteOnPage(role: UserRole, page: PageName): boolean {
  const perms = getPagePermissions(role, page);
  return perms.access && perms.delete;
}

/**
 * Check if user is a viewer (read-only)
 */
export function isViewer(role: UserRole): boolean {
  return role === 'viewer';
}

/**
 * Check if user is an admin
 */
export function isAdmin(role: UserRole): boolean {
  return role === 'admin' || role === 'super_admin';
}

/**
 * Get visible navigation items for a role
 */
export function getVisibleNavItems(role: UserRole): PageName[] {
  const allPages: PageName[] = [
    'dashboard', 'invoices', 'oldinvoices', 'customers', 'products',
    'services', 'payments', 'inventory', 'reports', 'settings',
    'admin_users', 'admin_audit', 'admin_profiles'
  ];
  
  return allPages.filter(page => canAccessPage(role, page));
}
