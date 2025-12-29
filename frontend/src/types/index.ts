// ============ Base Enums & Types ============

export type UserRole = 'admin' | 'secretary' | 'patient';
export type Gender = 'male' | 'female';
export type BloodType = 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';
export type CancelledBy = 'patient' | 'admin' | 'system';
export type DayOfWeek = 0 | 1 | 2 | 3 | 4 | 5 | 6;

// ============ User Types ============

export interface User {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  role: UserRole;
  date_of_birth: string | null;
  gender: Gender | null;
  address: string | null;
  avatar: string | null;
  avatar_url?: string | null;
  is_active: boolean;
  phone_verified_at: string | null;
  created_at: string;
  profile?: PatientProfile | null;
}

export interface PatientWithProfile extends User {
  profile: PatientProfile | null;
  statistics?: PatientStatistics;
}

export interface PatientStatistics {
  total_appointments: number;
  completed_appointments: number;
  cancelled_appointments: number;
  no_show_count: number;
  upcoming_appointments: number;
  last_visit: string | null;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
    token: string;
  };
}

// Appointment types
export type AppointmentStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';

export interface Appointment {
  id: number;
  patient_id: number;
  date: string;
  slot_time: string;
  end_time: string;
  status: AppointmentStatus;
  reason: string | null;
  notes: string | null;
  admin_notes: string | null;
  cancellation_reason: string | null;
  cancelled_at: string | null;
  confirmed_at: string | null;
  completed_at: string | null;
  created_at: string;
  patient?: User;
}

// ============ Patient Profile Types ============

export interface PatientProfile {
  id: number;
  user_id: number;
  blood_type: BloodType | null;
  allergies: string | null;
  chronic_diseases: string | null;
  current_medications: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  emergency_contact_relationship: string | null;
  insurance_provider: string | null;
  insurance_policy_number: string | null;
  notes: string | null;
  created_at: string;
}

// Medical Record types
export interface MedicalRecord {
  id: number;
  patient_id: number;
  appointment_id: number | null;
  diagnosis: string;
  notes: string | null;
  treatment_plan: string | null;
  blood_pressure_systolic: number | null;
  blood_pressure_diastolic: number | null;
  heart_rate: number | null;
  temperature: number | null;
  weight: number | null;
  height: number | null;
  follow_up_date: string | null;
  follow_up_notes: string | null;
  created_at: string;
  attachments?: Attachment[];
}

// Attachment types
export interface Attachment {
  id: number;
  medical_record_id: number;
  file_name: string;
  file_path: string;
  file_type: string;
  file_size: number;
  description: string | null;
  created_at: string;
}

// Prescription types
export interface Prescription {
  id: number;
  patient_id: number;
  medical_record_id: number | null;
  diagnosis: string;
  notes: string | null;
  is_dispensed: boolean;
  dispensed_at: string | null;
  created_at: string;
  items?: PrescriptionItem[];
}

export interface PrescriptionItem {
  id: number;
  prescription_id: number;
  medication_name: string;
  dosage: string;
  frequency: string;
  duration: string;
  instructions: string | null;
}

// Payment types
export type PaymentStatus = 'pending' | 'paid' | 'refunded';
export type PaymentMethod = 'cash' | 'card' | 'wallet';

export interface Payment {
  id: number;
  appointment_id: number;
  amount: number;
  discount: number;
  final_amount: number;
  payment_method: PaymentMethod | null;
  status: PaymentStatus;
  notes: string | null;
  paid_at: string | null;
  refunded_at: string | null;
  created_at: string;
}

// Notification types
export interface Notification {
  id: number;
  user_id: number;
  title: string;
  body: string;
  type: string;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
}

// Slot types
export interface Slot {
  time: string;
  end_time: string;
  available: boolean;
  remaining: number;
}

export interface AvailableDate {
  date: string;
  day_name: string;
  slots_count: number;
}

// Clinic Settings
export interface ClinicSettings {
  id: number;
  clinic_name: string;
  clinic_phone: string | null;
  clinic_email: string | null;
  clinic_address: string | null;
  logo: string | null;
  slot_duration: number;
  max_patients_per_slot: number;
  advance_booking_days: number;
  cancellation_hours: number;
  consultation_fee: number;
}

// Schedule types
export interface Schedule {
  id: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  is_active: boolean;
}

// Vacation types
export interface Vacation {
  id: number;
  start_date: string;
  end_date: string;
  reason: string | null;
}

// Dashboard types
export interface DashboardStats {
  total_patients: number;
  total_appointments: number;
  today_appointments: number;
  pending_appointments: number;
  completed_appointments: number;
  total_revenue: number;
  today_revenue: number;
}

// Report types
export interface AppointmentsReport {
  total: number;
  by_status: Record<AppointmentStatus, number>;
  by_day: Array<{ date: string; count: number }>;
  completion_rate: number;
  cancellation_rate: number;
  no_show_rate: number;
}

export interface RevenueReport {
  total_revenue: number;
  total_paid: number;
  total_pending: number;
  total_refunded: number;
  by_method: Record<PaymentMethod, number>;
  by_day: Array<{ date: string; amount: number }>;
  average_per_appointment: number;
}

export interface PatientsReport {
  total_patients: number;
  new_patients: number;
  returning_patients: number;
  by_gender: Record<string, number>;
  by_age_group: Record<string, number>;
  most_frequent: Array<{ patient_id: number; name: string; visit_count: number }>;
}

// Activity types
export interface Activity {
  id: number;
  type: 'appointment' | 'payment' | 'medical_record' | 'prescription';
  description: string;
  user_name: string;
  created_at: string;
}

export interface PatientDashboard {
  upcoming_appointments: Appointment[];
  recent_records: MedicalRecord[];
  recent_prescriptions: Prescription[];
  unread_notifications: number;
  statistics: {
    total_appointments: number;
    completed_appointments: number;
    cancelled_appointments: number;
  };
}

// API Response types
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

// ============ Form Data Types ============

export interface LoginFormData {
  phone: string;
  password: string;
}

export interface RegisterFormData {
  name: string;
  phone: string;
  email?: string;
  password: string;
  password_confirmation: string;
}

export interface BookingFormData {
  date: string;
  time: string;
  notes?: string;
}

export interface ProfileFormData {
  name: string;
  email?: string;
  date_of_birth?: string;
  gender?: Gender;
  address?: string;
}

export interface MedicalInfoFormData {
  blood_type?: BloodType;
  allergies?: string;
  chronic_diseases?: string;
  current_medications?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  emergency_contact_relationship?: string;
  insurance_provider?: string;
  insurance_policy_number?: string;
}

export interface PasswordChangeFormData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface AppointmentFormData {
  patient_id?: number;
  date: string;
  slot_time: string;
  reason?: string;
  notes?: string;
}

export interface MedicalRecordFormData {
  patient_id: number;
  appointment_id?: number;
  diagnosis: string;
  notes?: string;
  treatment_plan?: string;
  blood_pressure_systolic?: number;
  blood_pressure_diastolic?: number;
  heart_rate?: number;
  temperature?: number;
  weight?: number;
  height?: number;
  follow_up_date?: string;
  follow_up_notes?: string;
}

export interface PrescriptionFormData {
  medical_record_id: number;
  diagnosis: string;
  notes?: string;
  items: PrescriptionItemFormData[];
}

export interface PrescriptionItemFormData {
  medication_name: string;
  dosage: string;
  frequency: string;
  duration: string;
  instructions?: string;
}

export interface PaymentFormData {
  appointment_id: number;
  amount: number;
  discount?: number;
  payment_method: PaymentMethod;
  notes?: string;
}

export interface ScheduleFormData {
  day_of_week: DayOfWeek;
  start_time: string;
  end_time: string;
  is_active: boolean;
}

export interface VacationFormData {
  start_date: string;
  end_date: string;
  reason?: string;
}

export interface ClinicSettingsFormData {
  clinic_name: string;
  clinic_phone?: string;
  clinic_email?: string;
  clinic_address?: string;
  slot_duration: number;
  max_patients_per_slot: number;
  advance_booking_days: number;
  cancellation_hours: number;
  consultation_fee: number;
}

// ============ Filter Types ============

export interface AppointmentFilters {
  status?: AppointmentStatus;
  date?: string;
  from_date?: string;
  to_date?: string;
  patient_id?: number;
  page?: number;
  per_page?: number;
}

export interface PatientFilters {
  search?: string;
  page?: number;
  per_page?: number;
}

export interface ReportFilters {
  from_date: string;
  to_date: string;
  group_by?: 'day' | 'week' | 'month';
}
