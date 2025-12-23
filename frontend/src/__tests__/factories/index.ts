import type {
  User,
  Appointment,
  MedicalRecord,
  Payment,
  Prescription,
  PrescriptionItem,
  PatientProfile,
  Schedule,
  Vacation,
  Notification,
  ClinicSettings,
  Slot,
  DashboardStats,
} from '@/types';

let idCounter = 1;

// Reset counter between tests
export const resetFactoryCounter = () => {
  idCounter = 1;
};

// User factories
export const createUser = (overrides: Partial<User> = {}): User => ({
  id: idCounter++,
  name: 'Test User',
  email: 'test@example.com',
  phone: '01234567890',
  role: 'patient',
  avatar: null,
  is_active: true,
  date_of_birth: null,
  gender: null,
  address: null,
  phone_verified_at: null,
  created_at: '2025-01-01T00:00:00.000Z',
  ...overrides,
});

export const createAdmin = (overrides: Partial<User> = {}): User =>
  createUser({
    name: 'Admin User',
    phone: '01000000000',
    role: 'admin',
    ...overrides,
  });

export const createSecretary = (overrides: Partial<User> = {}): User =>
  createUser({
    name: 'Secretary User',
    phone: '01100000000',
    role: 'secretary',
    ...overrides,
  });

export const createPatient = (overrides: Partial<User> = {}): User =>
  createUser({
    name: 'Patient User',
    role: 'patient',
    ...overrides,
  });

// Appointment factories
export const createAppointment = (overrides: Partial<Appointment> = {}): Appointment => ({
  id: idCounter++,
  user_id: 1,
  patient_id: 1,
  appointment_date: '2025-01-15',
  date: '2025-01-15',
  slot_time: '10:00',
  appointment_time: '10:00:00',
  end_time: '10:30:00',
  status: 'pending',
  reason: null,
  notes: null,
  admin_notes: null,
  cancellation_reason: null,
  cancelled_at: null,
  cancelled_by: null,
  confirmed_at: null,
  completed_at: null,
  created_at: '2025-01-01T00:00:00.000Z',
  updated_at: '2025-01-01T00:00:00.000Z',
  ...overrides,
});

export const createPendingAppointment = (overrides: Partial<Appointment> = {}): Appointment =>
  createAppointment({ status: 'pending', ...overrides });

export const createConfirmedAppointment = (overrides: Partial<Appointment> = {}): Appointment =>
  createAppointment({
    status: 'confirmed',
    confirmed_at: '2025-01-01T00:00:00.000Z',
    ...overrides,
  });

export const createCompletedAppointment = (overrides: Partial<Appointment> = {}): Appointment =>
  createAppointment({
    status: 'completed',
    confirmed_at: '2025-01-01T00:00:00.000Z',
    completed_at: '2025-01-15T11:00:00.000Z',
    ...overrides,
  });

export const createCancelledAppointment = (overrides: Partial<Appointment> = {}): Appointment =>
  createAppointment({
    status: 'cancelled',
    cancellation_reason: 'Test cancellation reason',
    cancelled_at: '2025-01-10T00:00:00.000Z',
    cancelled_by: 'patient',
    ...overrides,
  });

// Medical record factories
export const createMedicalRecord = (overrides: Partial<MedicalRecord> = {}): MedicalRecord => ({
  id: idCounter++,
  patient_id: 1,
  appointment_id: 1,
  diagnosis: 'Test diagnosis',
  symptoms: 'Test symptoms',
  notes: null,
  examination_notes: null,
  treatment_plan: 'Test treatment plan',
  blood_pressure_systolic: 120,
  blood_pressure_diastolic: 80,
  heart_rate: 72,
  temperature: 37,
  weight: 70,
  height: 170,
  vital_signs: {
    blood_pressure: '120/80',
    heart_rate: 72,
    temperature: 37,
    weight: 70,
    height: 170,
  },
  follow_up_date: null,
  follow_up_notes: null,
  created_at: '2025-01-15T10:00:00.000Z',
  attachments: [],
  prescriptions: [],
  ...overrides,
});

// Prescription factories
export const createPrescriptionItem = (overrides: Partial<PrescriptionItem> = {}): PrescriptionItem => ({
  id: idCounter++,
  prescription_id: 1,
  medication_name: 'Test Medication',
  dosage: '500mg',
  frequency: 'مرتين يومياً',
  duration: '7 أيام',
  instructions: 'بعد الأكل',
  quantity: 14,
  ...overrides,
});

export const createPrescription = (overrides: Partial<Prescription> = {}): Prescription => ({
  id: idCounter++,
  patient_id: 1,
  medical_record_id: 1,
  diagnosis: 'Test diagnosis',
  notes: null,
  valid_until: '2025-02-15',
  is_dispensed: false,
  dispensed_at: null,
  created_at: '2025-01-15T10:00:00.000Z',
  items: [createPrescriptionItem()],
  ...overrides,
});

// Payment factories
export const createPayment = (overrides: Partial<Payment> = {}): Payment => ({
  id: idCounter++,
  appointment_id: 1,
  amount: 100,
  discount: 0,
  final_amount: 100,
  total: 100,
  payment_method: 'cash',
  method: 'cash',
  status: 'pending',
  transaction_id: null,
  notes: null,
  paid_at: null,
  refunded_at: null,
  created_at: '2025-01-15T10:00:00.000Z',
  ...overrides,
});

export const createPaidPayment = (overrides: Partial<Payment> = {}): Payment =>
  createPayment({
    status: 'paid',
    paid_at: '2025-01-15T10:30:00.000Z',
    ...overrides,
  });

// Patient profile factories
export const createPatientProfile = (overrides: Partial<PatientProfile> = {}): PatientProfile => ({
  id: idCounter++,
  user_id: 1,
  blood_type: null,
  allergies: null,
  chronic_diseases: null,
  current_medications: null,
  emergency_contact_name: null,
  emergency_contact_phone: null,
  emergency_contact_relationship: null,
  insurance_provider: null,
  insurance_policy_number: null,
  insurance_number: null,
  notes: null,
  medical_notes: null,
  created_at: '2025-01-01T00:00:00.000Z',
  ...overrides,
});

// Schedule factories
export const createSchedule = (overrides: Partial<Schedule> = {}): Schedule => ({
  id: idCounter++,
  day_of_week: 0,
  start_time: '09:00',
  end_time: '17:00',
  break_start: '13:00',
  break_end: '14:00',
  is_active: true,
  ...overrides,
});

// Vacation factories
export const createVacation = (overrides: Partial<Vacation> = {}): Vacation => ({
  id: idCounter++,
  title: 'Test Vacation',
  start_date: '2025-02-01',
  end_date: '2025-02-03',
  reason: 'Test reason',
  ...overrides,
});

// Notification factories
export const createNotification = (overrides: Partial<Notification> = {}): Notification => ({
  id: idCounter++,
  user_id: 1,
  title: 'Test Notification',
  body: 'Test notification body',
  type: 'appointment_reminder',
  data: null,
  read_at: null,
  created_at: '2025-01-01T00:00:00.000Z',
  ...overrides,
});

// Slot factories
export const createSlot = (overrides: Partial<Slot> = {}): Slot => ({
  time: '09:00',
  end_time: '09:30',
  available: true,
  remaining: 1,
  ...overrides,
});

// Dashboard stats factories
export const createDashboardStats = (overrides: Partial<DashboardStats> = {}): DashboardStats => ({
  total_patients: 100,
  total_appointments: 250,
  today_appointments: 15,
  pending_appointments: 5,
  completed_appointments: 200,
  total_revenue: 50000,
  today_revenue: 5000,
  ...overrides,
});

// Clinic settings factories
export const createClinicSettings = (overrides: Partial<ClinicSettings> = {}): ClinicSettings => ({
  id: 1,
  clinic_name: 'عيادة الاختبار',
  doctor_name: 'دكتور اختبار',
  specialization: 'طب عام',
  phone: '01000000000',
  email: 'clinic@test.com',
  address: 'شارع الاختبار',
  logo: null,
  slot_duration: 30,
  max_patients_per_slot: 1,
  advance_booking_days: 30,
  cancellation_hours: 24,
  ...overrides,
});

// Helper to create multiple items
export const createMany = <T>(factory: () => T, count: number): T[] => {
  return Array.from({ length: count }, () => factory());
};

// API Response wrappers
export const wrapInApiResponse = <T>(data: T, message?: string) => ({
  success: true,
  message,
  data,
});

export const wrapInPaginatedResponse = <T>(
  data: T[],
  page = 1,
  perPage = 15,
  total?: number
) => ({
  success: true,
  data,
  meta: {
    current_page: page,
    from: data.length > 0 ? (page - 1) * perPage + 1 : null,
    last_page: Math.ceil((total ?? data.length) / perPage),
    per_page: perPage,
    to: data.length > 0 ? (page - 1) * perPage + data.length : null,
    total: total ?? data.length,
  },
});
