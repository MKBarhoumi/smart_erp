export interface User {
  id: string;
  name: string;
  email: string;
  role: 'super_admin' | 'admin' | 'accountant' | 'sales' | 'inventory_manager' | 'viewer';
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface Customer {
  [key: string]: unknown;
  id: string;
  identifier_type: 'I-01' | 'I-02' | 'I-03' | 'I-04';
  identifier_value: string;
  name: string;
  matricule_fiscal: string | null;
  category_type: string | null;
  person_type: 'P' | 'M' | null;
  tax_office: string | null;
  registre_commerce: string | null;
  legal_form: string | null;
  address_description: string | null;
  street: string | null;
  city: string | null;
  postal_code: string | null;
  country_code: string;
  phone: string | null;
  fax: string | null;
  email: string | null;
  website: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface Product {
  [key: string]: unknown;
  id: string;
  code: string;
  name: string;
  description: string | null;
  item_lang: string;
  unit_of_measure: string;
  unit_price: string;
  tva_rate: string;
  is_subject_to_timbre: boolean;
  track_inventory: boolean;
  current_stock: string;
  min_stock_alert: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface OldInvoiceLine {
  id: string;
  oldinvoice_id: string;
  line_number: number;
  product_id: string | null;
  item_code: string;
  item_description: string;
  item_lang: string;
  quantity: string;
  unit_of_measure: string;
  unit_price: string;
  line_net_amount: string;
  line_total: string;
  tva_rate: string;
  tva_amount: string;
  discount_rate: string;
  discount_amount: string;
  // helper properties used in the UI
  line_total_ht?: string;
  line_total_ttc?: string;
}

export interface OldInvoiceTaxLine {
  id: string;
  oldinvoice_id: string;
  tax_type_code: string;
  tax_type_name: string;
  tax_rate: string;
  taxable_amount: string;
  tax_amount: string;
}

export interface OldInvoice {
  id: string;
  oldinvoice_number: string;
  document_identifier: string | null;
  document_type_code: 'I-11' | 'I-12' | 'I-13' | 'I-14' | 'I-15' | 'I-16';
  parent_oldinvoice_id: string | null;
  oldinvoice_date: string;
  due_date: string | null;
  billing_period_start: string | null;
  billing_period_end: string | null;
  customer_id: string;
  created_by: string;
  customer?: Customer;
  total_gross: string;
  total_discount: string;
  total_net_before_disc: string;
  total_ht: string;
  total_tva: string;
  timbre_fiscal: string;
  total_ttc: string;
  status: 'draft' | 'validated' | 'signed' | 'submitted' | 'accepted' | 'rejected' | 'archived';
  ref_ttn_val: string | null;
  cev_qr_content: string | null;
  signed_xml: string | null;
  submitted_at: string | null;
  accepted_at: string | null;
  rejection_reason: string | null;
  notes: string | null;
  lines?: OldInvoiceLine[];
  tax_lines?: OldInvoiceTaxLine[];
  payments?: Payment[];
  created_at: string;
  updated_at: string;
}

export interface Payment {
  id: string;
  oldinvoice_id: string;
  created_by: string;
  payment_date: string;
  amount: string;
  method: 'cash' | 'bank_transfer' | 'cheque' | 'effect';
  reference: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

export interface CompanySettings {
  id: string;
  company_name: string;
  matricule_fiscal: string;
  // aliases provided by model accessors
  matricule_fiscale?: string;
  category_type: string;
  person_type: string;
  tax_office: string | null;
  registre_commerce: string | null;
  legal_form: string | null;
  address_description: string | null;
  street: string | null;
  address_street?: string;
  city: string;
  address_city?: string;
  postal_code: string | null;
  address_postal_code?: string;
  country_code: string;
  phone: string | null;
  fax: string | null;
  email: string | null;
  website: string | null;
  logo_path: string | null;
  bank_rib: string | null;
  bank_name: string | null;
  bank_branch_code: string | null;
  postal_account: string | null;
  certificate_file: string | null;
  certificate_passphrase: string | null;
  certificate_expires_at: string | null;
  oldinvoice_prefix: string;
  oldinvoice_number_format: string;
  next_oldinvoice_counter: number;
  default_timbre_fiscal: string;
  // extra fields used by the settings form that come from backend or aliases
  tax_category_code?: string;
  secondary_establishment?: string;
}

export interface Plan {
  id: string;
  name: string;
  max_oldinvoices_per_month: number | null;
  max_users: number | null;
  max_products: number | null;
  has_ttn_integration: boolean;
  price_monthly: string;
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface PageProps {
  [key: string]: any;
  auth: {
    user: User;
  };
  flash: {
    success?: string;
    error?: string;
  };
  company?: CompanySettings;
}

export interface DashboardData {
  revenue_month: string;
  revenue_year: string;
  outstanding_receivables: string;
  overdue_oldinvoices_count: number;
  overdue_oldinvoices_total: string;
  recent_oldinvoices: OldInvoice[];
  low_stock_alerts: Product[];
  ttn_summary: {
    pending: number;
    accepted: number;
    rejected: number;
  };
  monthly_revenue: Array<{ month: string; amount: number }>;
}
