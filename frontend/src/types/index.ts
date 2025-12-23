// User types
export type UserRole = 'admin' | 'secretary' | 'patient';

export interface User {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  role: UserRole;
  date_of_birth: string | null;
  gender: 'male' | 'female' | null;
  address: string | null;
  avatar: string | null;
  is_active: boolean;
  phone_verified_at: string | null;
  created_at: string;
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

// Patient Profile types
export interface PatientProfile {
  id: number;
  user_id: number;
  blood_type: string | null;
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
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
}
