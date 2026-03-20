import { usePage } from '@inertiajs/react';
import type { User } from '@/types';
import type { PageName, UserRole, PagePermission, SpecialPermissions } from '@/utils/permissions';
import { 
  getPagePermissions, 
  getSpecialPermissions, 
  canAccessPage, 
  canCreateOnPage, 
  canEditOnPage, 
  canDeleteOnPage,
  isViewer as checkIsViewer,
  isAdmin as checkIsAdmin,
} from '@/utils/permissions';

interface UsePermissionsReturn {
  role: UserRole;
  isViewer: boolean;
  isAdmin: boolean;
  canModify: boolean;
  pagePermissions: (page: PageName) => PagePermission;
  specialPermissions: SpecialPermissions;
  canAccess: (page: PageName) => boolean;
  canCreate: (page: PageName) => boolean;
  canEdit: (page: PageName) => boolean;
  canDelete: (page: PageName) => boolean;
  // Special permissions exposed directly
  canViewFinancials: boolean;
  canImportXML: boolean;
  canExportData: boolean;
  canValidateInvoices: boolean;
}

/**
 * Hook for accessing role-based permissions in React components
 */
export function usePermissions(): UsePermissionsReturn {
  const { auth } = usePage<{ auth: { user: User } }>().props;
  const role = (auth?.user?.role ?? 'viewer') as UserRole;
  const special = getSpecialPermissions(role);

  return {
    role,
    isViewer: checkIsViewer(role),
    isAdmin: checkIsAdmin(role),
    canModify: !checkIsViewer(role),
    pagePermissions: (page: PageName) => getPagePermissions(role, page),
    specialPermissions: special,
    canAccess: (page: PageName) => canAccessPage(role, page),
    canCreate: (page: PageName) => canCreateOnPage(role, page),
    canEdit: (page: PageName) => canEditOnPage(role, page),
    canDelete: (page: PageName) => canDeleteOnPage(role, page),
    // Special permissions exposed directly
    canViewFinancials: special.canViewFinancialKPIs,
    canImportXML: special.canImportXML,
    canExportData: special.canExportData,
    canValidateInvoices: special.canValidateInvoices,
  };
}
