import { usePage } from '@inertiajs/react';
import type { User } from '@/types';

type PageName = string;

interface PagePermission {
  access: boolean;
  view: boolean;
  create: boolean;
  edit: boolean;
  delete: boolean;
}

interface SpecialPermissions {
  canValidateInvoices: boolean;
  canImportXML: boolean;
  canExportData: boolean;
  canViewFinancialKPIs: boolean;
  canManageOwnProfile: boolean;
}

interface UserPermissions {
  page_permissions: Record<string, PagePermission>;
  special_permissions: SpecialPermissions;
}

interface UsePermissionsReturn {
  role: string;
  isViewer: boolean;
  isAdmin: boolean;
  canModify: boolean;
  pagePermissions: (page: PageName) => PagePermission;
  specialPermissions: SpecialPermissions;
  canAccess: (page: PageName) => boolean;
  canCreate: (page: PageName) => boolean;
  canEdit: (page: PageName) => boolean;
  canDelete: (page: PageName) => boolean;
  canView: (page: PageName) => boolean;
  // Special permissions exposed directly
  canViewFinancials: boolean;
  canImportXML: boolean;
  canExportData: boolean;
  canValidateInvoices: boolean;
}

const defaultPagePermission: PagePermission = {
  access: false,
  view: false,
  create: false,
  edit: false,
  delete: false,
};

const defaultSpecialPermissions: SpecialPermissions = {
  canValidateInvoices: false,
  canImportXML: false,
  canExportData: false,
  canViewFinancialKPIs: false,
  canManageOwnProfile: true,
};

/**
 * Hook for accessing role-based permissions in React components
 * Now uses database permissions passed from the server
 */
export function usePermissions(): UsePermissionsReturn {
  const { auth } = usePage<{ auth: { user: User & { permissions?: UserPermissions } } }>().props;
  const role = auth?.user?.role ?? 'viewer';
  const permissions = auth?.user?.permissions;

  // Get page permissions from database
  const getPagePermissions = (page: PageName): PagePermission => {
    if (!permissions?.page_permissions) return defaultPagePermission;
    return permissions.page_permissions[page] || defaultPagePermission;
  };

  // Get special permissions from database
  const special: SpecialPermissions = permissions?.special_permissions || defaultSpecialPermissions;

  // Determine admin status from role
  const isAdmin = role === 'admin' || role === 'super_admin';

  // Determine viewer status - check if user has any create/edit/delete permissions
  const hasAnyModifyPermission = (): boolean => {
    if (!permissions?.page_permissions) return false;
    return Object.values(permissions.page_permissions).some(
      p => p.create || p.edit || p.delete
    );
  };

  const isViewer = !hasAnyModifyPermission() && !isAdmin;

  return {
    role,
    isViewer,
    isAdmin,
    canModify: !isViewer,
    pagePermissions: getPagePermissions,
    specialPermissions: special,
    canAccess: (page: PageName) => getPagePermissions(page).access,
    canView: (page: PageName) => getPagePermissions(page).view,
    canCreate: (page: PageName) => getPagePermissions(page).create,
    canEdit: (page: PageName) => getPagePermissions(page).edit,
    canDelete: (page: PageName) => getPagePermissions(page).delete,
    // Special permissions exposed directly
    canViewFinancials: special.canViewFinancialKPIs,
    canImportXML: special.canImportXML,
    canExportData: special.canExportData,
    canValidateInvoices: special.canValidateInvoices,
  };
}
