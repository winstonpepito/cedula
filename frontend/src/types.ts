export type ApplicantType = 'individual' | 'corporation'
export type DeliveryMode = 'soft_copy' | 'pickup' | 'delivery'

export interface BarangayDeliveryFeeInfo {
  fee: number | string
  is_active?: boolean
}

export interface Barangay {
  id: number
  name: string
  code?: string
  /** Flat delivery fee amount (public + admin list/update). */
  delivery_fee?: number | string
  is_active?: boolean
  deliveryFee?: BarangayDeliveryFeeInfo
}

export interface Breakdown {
  base_tax: number
  additional_tax: number
  community_tax_total: number
  interest_months: number
  interest_amount: number
  delivery_fee: number
  convenience_fee: number
  server_fee: number
  payment_processor_fee: number
  total_due: number
  annual_income?: number
  details?: Record<string, number>
}

export interface Application {
  tracking_number: string
  public_token: string
  applicant_type: ApplicantType
  applicant_name: string
  email: string
  phone?: string
  address_line: string
  city?: string
  province?: string
  barangay?: Barangay
  delivery_mode: DeliveryMode
  status: string
  breakdown?: Breakdown
  base_tax: number | string
  additional_tax: number | string
  interest_amount: number | string
  interest_months: number
  community_tax_total: number | string
  delivery_fee: number | string
  convenience_fee: number | string
  server_fee: number | string
  payment_processor_fee: number | string
  total_due: number | string
  paid_at?: string
  created_at?: string
  can_download_soft_copy: boolean
  is_paid: boolean
  documents: Array<{
    id: number
    type: string
    download_url: string
    is_uploaded?: boolean
    original_name?: string | null
  }>
  status_logs: Array<{
    id: number
    from_status?: string
    to_status: string
    note?: string
    actor_type?: string
    created_at: string
  }>
  payment_proofs?: Array<{
    id: number
    status: string
    original_name?: string
    notes?: string
    created_at: string
  }>
  track_url: string
  receipt_url: string
  first_name?: string
  last_name?: string
  corporation_name?: string
  payments?: Array<{
    id: number
    method: string
    status: string
    amount: number | string
    checkout_url?: string
  }>
}

export interface TaxSettings {
  individual_base_tax: number | string
  individual_rate_amount: number | string
  individual_rate_per: number | string
  individual_additional_cap: number | string
  corporation_base_tax: number | string
  corporation_rate_amount: number | string
  corporation_rate_per: number | string
  corporation_additional_cap: number | string
  interest_rate_percent: number | string
  deadline_month: number
  deadline_day: number
  interest_counts_from_january: boolean
  convenience_fee: number | string
  server_fee: number | string
  payment_processor_fee: number | string
  default_city: string
  default_province: string
  default_barangay_id?: number | null
  manual_payment_only: boolean
  gcash_number?: string | null
}

export interface PublicSettings {
  default_city: string
  default_province: string
  default_barangay_id?: number | null
  manual_payment_only: boolean
  gcash_number?: string | null
}

export interface StaffUser {
  id: number
  name: string
  email: string
  role: 'admin' | 'delivery'
}
