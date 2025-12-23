import { z } from 'zod';

// Base schemas
export const userRoleSchema = z.enum(['admin', 'secretary', 'patient']);
export const genderSchema = z.enum(['male', 'female']);
export const appointmentStatusSchema = z.enum(['pending', 'confirmed', 'completed', 'cancelled', 'no_show']);
export const paymentStatusSchema = z.enum(['pending', 'paid', 'refunded']);
export const paymentMethodSchema = z.enum(['cash', 'card', 'wallet']);

// User schema
export const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string().email().nullable(),
  phone: z.string(),
  role: userRoleSchema,
  avatar: z.string().nullable(),
  is_active: z.boolean(),
  date_of_birth: z.string().nullable(),
  gender: genderSchema.nullable(),
  address: z.string().nullable(),
  phone_verified_at: z.string().nullable(),
  created_at: z.string(),
});

// Appointment schema
export const appointmentSchema = z.object({
  id: z.number(),
  patient_id: z.number().optional(),
  user_id: z.number().optional(),
  date: z.string().optional(),
  appointment_date: z.string().optional(),
  slot_time: z.string().optional(),
  appointment_time: z.string().optional(),
  end_time: z.string().optional(),
  status: appointmentStatusSchema,
  reason: z.string().nullable().optional(),
  notes: z.string().nullable(),
  admin_notes: z.string().nullable(),
  cancellation_reason: z.string().nullable(),
  cancelled_at: z.string().nullable(),
  cancelled_by: z.enum(['patient', 'admin']).nullable().optional(),
  confirmed_at: z.string().nullable().optional(),
  completed_at: z.string().nullable().optional(),
  created_at: z.string(),
  updated_at: z.string().optional(),
  patient: userSchema.optional(),
});

// Patient profile schema
export const patientProfileSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  blood_type: z.string().nullable(),
  allergies: z.string().nullable(),
  chronic_diseases: z.string().nullable(),
  current_medications: z.string().nullable(),
  emergency_contact_name: z.string().nullable(),
  emergency_contact_phone: z.string().nullable(),
  emergency_contact_relationship: z.string().nullable().optional(),
  insurance_provider: z.string().nullable(),
  insurance_policy_number: z.string().nullable().optional(),
  insurance_number: z.string().nullable().optional(),
  notes: z.string().nullable().optional(),
  medical_notes: z.string().nullable().optional(),
  created_at: z.string(),
});

// Attachment schema
export const attachmentSchema = z.object({
  id: z.number(),
  medical_record_id: z.number().optional(),
  file_name: z.string(),
  file_path: z.string(),
  file_type: z.string(),
  file_size: z.number(),
  description: z.string().nullable().optional(),
  full_url: z.string().optional(),
  uploaded_by: z.number().optional(),
  created_at: z.string(),
});

// Prescription item schema
export const prescriptionItemSchema = z.object({
  id: z.number(),
  prescription_id: z.number(),
  medication_name: z.string(),
  dosage: z.string(),
  frequency: z.string(),
  duration: z.string(),
  instructions: z.string().nullable(),
  quantity: z.number().nullable().optional(),
});

// Prescription schema
export const prescriptionSchema = z.object({
  id: z.number(),
  patient_id: z.number().optional(),
  medical_record_id: z.number().nullable(),
  diagnosis: z.string().optional(),
  notes: z.string().nullable(),
  valid_until: z.string().nullable().optional(),
  is_dispensed: z.boolean(),
  dispensed_at: z.string().nullable(),
  created_at: z.string(),
  items: z.array(prescriptionItemSchema).optional(),
});

// Medical record schema
export const medicalRecordSchema = z.object({
  id: z.number(),
  patient_id: z.number().optional(),
  appointment_id: z.number().nullable(),
  diagnosis: z.string(),
  symptoms: z.string().nullable().optional(),
  notes: z.string().nullable().optional(),
  examination_notes: z.string().nullable().optional(),
  treatment_plan: z.string().nullable(),
  blood_pressure_systolic: z.number().nullable().optional(),
  blood_pressure_diastolic: z.number().nullable().optional(),
  heart_rate: z.number().nullable().optional(),
  temperature: z.number().nullable().optional(),
  weight: z.number().nullable().optional(),
  height: z.number().nullable().optional(),
  vital_signs: z.object({
    blood_pressure: z.string().optional(),
    heart_rate: z.number().optional(),
    temperature: z.number().optional(),
    weight: z.number().optional(),
    height: z.number().optional(),
  }).nullable().optional(),
  follow_up_date: z.string().nullable(),
  follow_up_notes: z.string().nullable().optional(),
  created_at: z.string(),
  attachments: z.array(attachmentSchema).optional(),
  prescriptions: z.array(prescriptionSchema).optional(),
});

// Payment schema
export const paymentSchema = z.object({
  id: z.number(),
  appointment_id: z.number(),
  amount: z.number(),
  discount: z.number(),
  final_amount: z.number().optional(),
  total: z.number().optional(),
  payment_method: paymentMethodSchema.nullable().optional(),
  method: paymentMethodSchema.optional(),
  status: paymentStatusSchema,
  transaction_id: z.string().nullable().optional(),
  notes: z.string().nullable(),
  paid_at: z.string().nullable(),
  refunded_at: z.string().nullable().optional(),
  created_at: z.string(),
  appointment: appointmentSchema.optional(),
});

// Notification schema
export const notificationSchema = z.object({
  id: z.union([z.number(), z.string()]),
  user_id: z.number().optional(),
  title: z.string().optional(),
  body: z.string().optional(),
  type: z.string(),
  data: z.record(z.string(), z.unknown()).nullable(),
  read_at: z.string().nullable(),
  created_at: z.string(),
});

// Schedule schema
export const scheduleSchema = z.object({
  id: z.number(),
  day_of_week: z.number(),
  start_time: z.string(),
  end_time: z.string(),
  break_start: z.string().nullable().optional(),
  break_end: z.string().nullable().optional(),
  is_active: z.boolean(),
});

// Vacation schema
export const vacationSchema = z.object({
  id: z.number(),
  title: z.string().optional(),
  start_date: z.string(),
  end_date: z.string(),
  reason: z.string().nullable(),
});

// Slot schema
export const slotSchema = z.object({
  time: z.string(),
  end_time: z.string(),
  available: z.boolean(),
  remaining: z.number(),
});

// Dashboard stats schema
export const dashboardStatsSchema = z.object({
  total_patients: z.number(),
  total_appointments: z.number(),
  today_appointments: z.number(),
  pending_appointments: z.number(),
  completed_appointments: z.number(),
  total_revenue: z.number(),
  today_revenue: z.number(),
});

// Generic API response schemas
export function apiResponseSchema<T extends z.ZodType>(dataSchema: T) {
  return z.object({
    success: z.boolean(),
    message: z.string().optional(),
    data: dataSchema,
  });
}

export function paginatedResponseSchema<T extends z.ZodType>(itemSchema: T) {
  return z.object({
    success: z.boolean(),
    message: z.string().optional(),
    data: z.array(itemSchema),
    meta: z.object({
      current_page: z.number(),
      from: z.number().nullable(),
      last_page: z.number(),
      per_page: z.number(),
      to: z.number().nullable(),
      total: z.number(),
    }),
  });
}

// Validation helper
export function validateResponse<T>(schema: z.ZodType<T>, data: unknown): T {
  const result = schema.safeParse(data);
  if (!result.success) {
    console.error('API Response validation failed:', result.error.issues);
    // In production, we might want to report this to an error tracking service
    // For now, we'll still return the data but log the error
    return data as T;
  }
  return result.data;
}

// Strict validation helper (throws on invalid data)
export function validateResponseStrict<T>(schema: z.ZodType<T>, data: unknown): T {
  const result = schema.safeParse(data);
  if (!result.success) {
    console.error('API Response validation failed:', result.error.issues);
    throw new Error('Invalid API response format');
  }
  return result.data;
}

// Type exports from schemas
export type UserFromSchema = z.infer<typeof userSchema>;
export type AppointmentFromSchema = z.infer<typeof appointmentSchema>;
export type MedicalRecordFromSchema = z.infer<typeof medicalRecordSchema>;
export type PaymentFromSchema = z.infer<typeof paymentSchema>;
export type PrescriptionFromSchema = z.infer<typeof prescriptionSchema>;
