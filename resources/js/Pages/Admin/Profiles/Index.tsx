import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

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

interface Role {
    id: string;
    slug: string;
    name: string;
    description: string | null;
    color: string;
    is_system: boolean;
    page_permissions: Record<string, PagePermission>;
    special_permissions: SpecialPermissions;
    users_count: number;
}

interface Props {
    roles: Role[];
    availablePages: Record<string, string>;
    availableSpecialPermissions: Record<string, string>;
    availableColors: Record<string, string>;
}

const colorClasses: Record<string, { bg: string; text: string; border: string; dot: string }> = {
    purple: { bg: 'bg-purple-100', text: 'text-purple-700', border: 'border-purple-200', dot: 'bg-purple-500' },
    blue: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
    emerald: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    amber: { bg: 'bg-amber-100', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
    indigo: { bg: 'bg-indigo-100', text: 'text-indigo-700', border: 'border-indigo-200', dot: 'bg-indigo-500' },
    gray: { bg: 'bg-gray-100', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500' },
    red: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
    pink: { bg: 'bg-pink-100', text: 'text-pink-700', border: 'border-pink-200', dot: 'bg-pink-500' },
    cyan: { bg: 'bg-cyan-100', text: 'text-cyan-700', border: 'border-cyan-200', dot: 'bg-cyan-500' },
    teal: { bg: 'bg-teal-100', text: 'text-teal-700', border: 'border-teal-200', dot: 'bg-teal-500' },
};

const PermissionBadge = ({ allowed }: { allowed: boolean }) => (
    <span className={`inline-flex items-center justify-center w-6 h-6 rounded-lg ${allowed ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400'}`}>
        {allowed ? (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        ) : (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        )}
    </span>
);

const PermissionCheckbox = ({ 
    checked, 
    onChange, 
    disabled = false 
}: { 
    checked: boolean; 
    onChange: (checked: boolean) => void; 
    disabled?: boolean;
}) => (
    <label className={`inline-flex items-center justify-center w-6 h-6 rounded-lg cursor-pointer transition-all ${disabled ? 'cursor-not-allowed opacity-50' : ''} ${checked ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}`}>
        <input
            type="checkbox"
            checked={checked}
            onChange={(e) => onChange(e.target.checked)}
            disabled={disabled}
            className="sr-only"
        />
        {checked ? (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        ) : (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        )}
    </label>
);

// Default permissions for new roles
const getDefaultPagePermissions = (pages: Record<string, string>): Record<string, PagePermission> => {
    const permissions: Record<string, PagePermission> = {};
    for (const key of Object.keys(pages)) {
        permissions[key] = { access: false, view: false, create: false, edit: false, delete: false };
    }
    return permissions;
};

const getDefaultSpecialPermissions = (): SpecialPermissions => ({
    canValidateInvoices: false,
    canImportXML: false,
    canExportData: true,
    canViewFinancialKPIs: false,
    canManageOwnProfile: true,
});

export default function ProfilesIndex({ roles, availablePages, availableSpecialPermissions, availableColors }: Props) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingRole, setEditingRole] = useState<Role | null>(null);
    const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);

    // Create form
    const createForm = useForm({
        name: '',
        slug: '',
        description: '',
        color: 'blue',
        page_permissions: getDefaultPagePermissions(availablePages),
        special_permissions: getDefaultSpecialPermissions(),
    });

    // Edit form
    const editForm = useForm({
        name: '',
        description: '',
        color: '',
        page_permissions: {} as Record<string, PagePermission>,
        special_permissions: {} as SpecialPermissions,
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/profiles', {
            onSuccess: () => {
                setShowCreateModal(false);
                createForm.reset();
            },
        });
    };

    const handleEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingRole) return;
        editForm.put(`/admin/profiles/${editingRole.id}`, {
            onSuccess: () => {
                setEditingRole(null);
                editForm.reset();
            },
        });
    };

    const handleDelete = (roleId: string) => {
        router.delete(`/admin/profiles/${roleId}`, {
            onSuccess: () => setDeleteConfirm(null),
        });
    };

    const openEditModal = (role: Role) => {
        setEditingRole(role);
        editForm.setData({
            name: role.name,
            description: role.description || '',
            color: role.color,
            page_permissions: { ...role.page_permissions },
            special_permissions: { ...role.special_permissions },
        });
    };

    const updateCreatePagePermission = (page: string, perm: keyof PagePermission, value: boolean) => {
        const newPerms = { ...createForm.data.page_permissions };
        newPerms[page] = { ...newPerms[page], [perm]: value };
        // If access is toggled off, disable all other permissions
        if (perm === 'access' && !value) {
            newPerms[page] = { access: false, view: false, create: false, edit: false, delete: false };
        }
        // If any other permission is turned on, ensure access and view are on
        if (perm !== 'access' && value) {
            newPerms[page].access = true;
            newPerms[page].view = true;
        }
        createForm.setData('page_permissions', newPerms);
    };

    const updateEditPagePermission = (page: string, perm: keyof PagePermission, value: boolean) => {
        const newPerms = { ...editForm.data.page_permissions };
        newPerms[page] = { ...newPerms[page], [perm]: value };
        if (perm === 'access' && !value) {
            newPerms[page] = { access: false, view: false, create: false, edit: false, delete: false };
        }
        if (perm !== 'access' && value) {
            newPerms[page].access = true;
            newPerms[page].view = true;
        }
        editForm.setData('page_permissions', newPerms);
    };

    const generateSlug = (name: string) => {
        return name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Profile Manager" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-gradient-to-br from-user-600 to-indigo-500 text-white shadow-lg shadow-user-500/25">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Profile Manager</h1>
                            <p className="text-gray-500">Manage role permissions across the system</p>
                        </div>
                    </div>
                    <button
                        onClick={() => setShowCreateModal(true)}
                        className="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-user-600 to-user-500 text-white text-sm font-semibold rounded-xl hover:from-user-700 hover:to-user-600 transition-all shadow-lg shadow-user-500/25 hover:shadow-xl hover:shadow-user-500/30 hover:-translate-y-0.5"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create New Role
                    </button>
                </div>

                {/* Role Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {roles.map((role) => {
                        const colors = colorClasses[role.color] || colorClasses.gray;
                        return (
                            <div key={role.id} className={`bg-white rounded-xl border ${colors.border} p-5 shadow-soft`}>
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-10 h-10 rounded-xl ${colors.bg} flex items-center justify-center`}>
                                            <span className={`w-3 h-3 rounded-full ${colors.dot}`} />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-gray-900">{role.name}</h3>
                                            <p className="text-xs text-gray-500 font-mono">{role.slug}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <button
                                            onClick={() => openEditModal(role)}
                                            className="p-2 rounded-lg text-gray-500 hover:text-user-600 hover:bg-user-50 transition-all"
                                            title="Edit Permissions"
                                        >
                                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        {!role.is_system && (
                                            <button
                                                onClick={() => setDeleteConfirm(role.id)}
                                                className="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-all"
                                                title="Delete Role"
                                            >
                                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <p className="text-sm text-gray-600 mb-3">{role.description}</p>
                                <div className="flex items-center justify-between text-xs">
                                    <span className={`inline-flex items-center px-2 py-1 rounded-lg ${role.is_system ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'}`}>
                                        {role.is_system ? 'System Role' : 'Custom Role'}
                                    </span>
                                    <span className="text-gray-500">{role.users_count} user(s)</span>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Permissions Matrix */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100">
                        <h2 className="text-lg font-semibold text-gray-900">Permissions Matrix</h2>
                        <p className="text-sm text-gray-500 mt-1">Overview of permissions for each role across all pages</p>
                    </div>
                    
                    <div className="overflow-x-auto">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-gradient-to-r from-gray-50 to-gray-50/50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                        Page / Module
                                    </th>
                                    <th className="px-4 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Permission
                                    </th>
                                    {roles.map((role) => {
                                        const colors = colorClasses[role.color] || colorClasses.gray;
                                        return (
                                            <th key={role.id} className="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg ${colors.bg} ${colors.text}`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${colors.dot}`} />
                                                    {role.name}
                                                </span>
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {Object.entries(availablePages).map(([pageKey, pageLabel], pageIdx) => {
                                    const permissionTypes = ['access', 'view', 'create', 'edit', 'delete'] as const;
                                    
                                    return permissionTypes.map((permType, permIdx) => (
                                        <tr 
                                            key={`${pageKey}-${permType}`} 
                                            className={`transition-colors hover:bg-user-50/30 ${pageIdx % 2 === 0 ? '' : 'bg-gray-50/30'}`}
                                        >
                                            {permIdx === 0 ? (
                                                <td 
                                                    rowSpan={permissionTypes.length} 
                                                    className="px-6 py-4 sticky left-0 bg-white z-10 border-r border-gray-100"
                                                >
                                                    <span className="font-semibold text-gray-900">{pageLabel}</span>
                                                </td>
                                            ) : null}
                                            <td className="px-4 py-3 text-center">
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-xs font-medium text-gray-600 capitalize">
                                                    {permType}
                                                </span>
                                            </td>
                                            {roles.map((role) => (
                                                <td key={role.id} className="px-4 py-3 text-center">
                                                    <PermissionBadge allowed={role.page_permissions[pageKey]?.[permType] ?? false} />
                                                </td>
                                            ))}
                                        </tr>
                                    ));
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Special Permissions */}
                <div className="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100">
                        <h2 className="text-lg font-semibold text-gray-900">Special Permissions</h2>
                        <p className="text-sm text-gray-500 mt-1">Additional permissions for specific features</p>
                    </div>
                    
                    <div className="overflow-x-auto">
                        <table className="min-w-full">
                            <thead>
                                <tr className="bg-gradient-to-r from-gray-50 to-gray-50/50">
                                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Permission
                                    </th>
                                    {roles.map((role) => {
                                        const colors = colorClasses[role.color] || colorClasses.gray;
                                        return (
                                            <th key={role.id} className="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg ${colors.bg} ${colors.text}`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${colors.dot}`} />
                                                    {role.name}
                                                </span>
                                            </th>
                                        );
                                    })}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {Object.entries(availableSpecialPermissions).map(([permKey, permLabel], idx) => (
                                    <tr key={permKey} className={`transition-colors hover:bg-user-50/30 ${idx % 2 === 0 ? '' : 'bg-gray-50/30'}`}>
                                        <td className="px-6 py-4 font-medium text-gray-900">{permLabel}</td>
                                        {roles.map((role) => (
                                            <td key={role.id} className="px-4 py-4 text-center">
                                                <PermissionBadge allowed={role.special_permissions[permKey as keyof SpecialPermissions] ?? false} />
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Legend */}
                <div className="bg-white rounded-xl border border-gray-100 p-5 shadow-soft">
                    <h3 className="text-sm font-semibold text-gray-900 mb-3">Legend</h3>
                    <div className="flex flex-wrap gap-6 text-sm">
                        <div className="flex items-center gap-2">
                            <PermissionBadge allowed={true} />
                            <span className="text-gray-600">Permission granted</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <PermissionBadge allowed={false} />
                            <span className="text-gray-600">Permission denied</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Create Role Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
                        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-gray-900">Create New Role</h2>
                            <button onClick={() => setShowCreateModal(false)} className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg className="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <form onSubmit={handleCreate} className="overflow-y-auto flex-1 p-3">
                            <div className="space-y-3">
                                {/* Basic Info */}
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Role Name *</label>
                                        <input
                                            type="text"
                                            value={createForm.data.name}
                                            onChange={(e) => {
                                                createForm.setData('name', e.target.value);
                                                if (!createForm.data.slug || createForm.data.slug === generateSlug(createForm.data.name.slice(0, -1))) {
                                                    createForm.setData('slug', generateSlug(e.target.value));
                                                }
                                            }}
                                            className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                            placeholder="e.g., Sales Manager"
                                        />
                                        {createForm.errors.name && <p className="text-red-500 text-xs mt-0.5">{createForm.errors.name}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Slug *</label>
                                        <input
                                            type="text"
                                            value={createForm.data.slug}
                                            onChange={(e) => createForm.setData('slug', e.target.value.toLowerCase().replace(/[^a-z_]/g, ''))}
                                            className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all font-mono"
                                            placeholder="e.g., sales_manager"
                                        />
                                        {createForm.errors.slug && <p className="text-red-500 text-xs mt-0.5">{createForm.errors.slug}</p>}
                                    </div>
                                </div>
                                
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                        <input
                                            type="text"
                                            value={createForm.data.description}
                                            onChange={(e) => createForm.setData('description', e.target.value)}
                                            className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                            placeholder="Brief description of this role"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Color *</label>
                                        <select
                                            value={createForm.data.color}
                                            onChange={(e) => createForm.setData('color', e.target.value)}
                                            className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                        >
                                            {Object.entries(availableColors).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                {/* Page Permissions */}
                                <div>
                                    <h3 className="text-xs font-semibold text-gray-900 mb-1">Page Permissions</h3>
                                    <div className="border border-gray-200 rounded-lg overflow-hidden max-h-40 overflow-y-auto">
                                        <table className="min-w-full text-xs">
                                            <thead className="sticky top-0 bg-gray-50">
                                                <tr>
                                                    <th className="px-2 py-1 text-left font-semibold text-gray-600">Page</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Access</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">View</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Create</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Edit</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {Object.entries(availablePages).map(([key, label]) => (
                                                    <tr key={key}>
                                                        <td className="px-2 py-0.5 text-gray-900">{label}</td>
                                                        {(['access', 'view', 'create', 'edit', 'delete'] as const).map((perm) => (
                                                            <td key={perm} className="px-1 py-0.5 text-center">
                                                                <PermissionCheckbox
                                                                    checked={createForm.data.page_permissions[key]?.[perm] ?? false}
                                                                    onChange={(v) => updateCreatePagePermission(key, perm, v)}
                                                                />
                                                            </td>
                                                        ))}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Special Permissions */}
                                <div>
                                    <h3 className="text-xs font-semibold text-gray-900 mb-1">Special Permissions</h3>
                                    <div className="grid gap-1.5 sm:grid-cols-2">
                                        {Object.entries(availableSpecialPermissions).map(([key, label]) => (
                                            <label key={key} className="flex items-center gap-2 px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                                <input
                                                    type="checkbox"
                                                    checked={createForm.data.special_permissions[key as keyof SpecialPermissions] ?? false}
                                                    onChange={(e) => createForm.setData('special_permissions', {
                                                        ...createForm.data.special_permissions,
                                                        [key]: e.target.checked,
                                                    })}
                                                    className="w-3 h-3 rounded border-gray-300 text-user-600 focus:ring-user-500"
                                                />
                                                <span className="text-xs text-gray-700">{label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div className="px-4 py-2 border-t border-gray-100 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowCreateModal(false)}
                                className="px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleCreate}
                                disabled={createForm.processing}
                                className="px-4 py-1.5 bg-gradient-to-r from-user-600 to-user-500 text-white text-xs font-semibold rounded-lg hover:from-user-700 hover:to-user-600 transition-all disabled:opacity-50"
                            >
                                {createForm.processing ? 'Creating...' : 'Create Role'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Edit Role Modal */}
            {editingRole && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[70vh] overflow-hidden flex flex-col">
                        <div className="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900">Edit Role: {editingRole.name}</h2>
                                {editingRole.is_system && (
                                    <p className="text-xs text-amber-600">System roles have limited editing - only permissions can be changed</p>
                                )}
                            </div>
                            <button onClick={() => setEditingRole(null)} className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg className="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <form onSubmit={handleEdit} className="overflow-y-auto flex-1 p-3">
                            <div className="space-y-3">
                                {/* Basic Info - only for non-system roles */}
                                {!editingRole.is_system && (
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 mb-1">Role Name *</label>
                                            <input
                                                type="text"
                                                value={editForm.data.name}
                                                onChange={(e) => editForm.setData('name', e.target.value)}
                                                className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                            <input
                                                type="text"
                                                value={editForm.data.description}
                                                onChange={(e) => editForm.setData('description', e.target.value)}
                                                className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-medium text-gray-700 mb-1">Color</label>
                                            <select
                                                value={editForm.data.color}
                                                onChange={(e) => editForm.setData('color', e.target.value)}
                                                className="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:border-user-500 focus:ring-2 focus:ring-user-500/20 transition-all"
                                            >
                                                {Object.entries(availableColors).map(([value, label]) => (
                                                    <option key={value} value={value}>{label}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                )}

                                {/* Page Permissions */}
                                <div>
                                    <h3 className="text-xs font-semibold text-gray-900 mb-1">Page Permissions</h3>
                                    <div className="border border-gray-200 rounded-lg overflow-hidden max-h-40 overflow-y-auto">
                                        <table className="min-w-full text-xs">
                                            <thead className="sticky top-0 bg-gray-50">
                                                <tr>
                                                    <th className="px-2 py-1 text-left font-semibold text-gray-600">Page</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Access</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">View</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Create</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Edit</th>
                                                    <th className="px-1 py-1 text-center font-semibold text-gray-600">Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100">
                                                {Object.entries(availablePages).map(([key, label]) => (
                                                    <tr key={key}>
                                                        <td className="px-2 py-0.5 text-gray-900">{label}</td>
                                                        {(['access', 'view', 'create', 'edit', 'delete'] as const).map((perm) => (
                                                            <td key={perm} className="px-1 py-0.5 text-center">
                                                                <PermissionCheckbox
                                                                    checked={editForm.data.page_permissions[key]?.[perm] ?? false}
                                                                    onChange={(v) => updateEditPagePermission(key, perm, v)}
                                                                />
                                                            </td>
                                                        ))}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Special Permissions */}
                                <div>
                                    <h3 className="text-xs font-semibold text-gray-900 mb-1">Special Permissions</h3>
                                    <div className="grid gap-1.5 sm:grid-cols-2">
                                        {Object.entries(availableSpecialPermissions).map(([key, label]) => (
                                            <label key={key} className="flex items-center gap-2 px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                                <input
                                                    type="checkbox"
                                                    checked={editForm.data.special_permissions[key as keyof SpecialPermissions] ?? false}
                                                    onChange={(e) => editForm.setData('special_permissions', {
                                                        ...editForm.data.special_permissions,
                                                        [key]: e.target.checked,
                                                    })}
                                                    className="w-3 h-3 rounded border-gray-300 text-user-600 focus:ring-user-500"
                                                />
                                                <span className="text-xs text-gray-700">{label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div className="px-4 py-2 border-t border-gray-100 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setEditingRole(null)}
                                className="px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleEdit}
                                disabled={editForm.processing}
                                className="px-4 py-1.5 bg-gradient-to-r from-user-600 to-user-500 text-white text-xs font-semibold rounded-lg hover:from-user-700 hover:to-user-600 transition-all disabled:opacity-50"
                            >
                                {editForm.processing ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {deleteConfirm && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl max-w-sm w-full p-4">
                        <div className="flex items-center gap-3 mb-3">
                            <div className="p-2 rounded-lg bg-red-100">
                                <svg className="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900">Delete Role?</h3>
                                <p className="text-xs text-gray-500">This action cannot be undone.</p>
                            </div>
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                onClick={() => setDeleteConfirm(null)}
                                className="px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={() => handleDelete(deleteConfirm)}
                                className="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Delete Role
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
