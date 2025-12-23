import api from './client';
import type {
  ApiResponse,
  PaginatedResponse,
  Appointment,
  User,
  MedicalRecord,
  Prescription,
  Payment,
  Schedule,
  Vacation,
  ClinicSettings,
  DashboardStats,
  AppointmentsReport,
  RevenueReport,
  PatientsReport,
  Activity,
  PatientProfile,
  Attachment,
} from '@/types';

// Request parameter types
interface AppointmentListParams {
  status?: string;
  date?: string;
  patient_id?: number;
  page?: number;
  per_page?: number;
}

interface PatientListParams {
  search?: string;
  page?: number;
  per_page?: number;
}

interface MedicalRecordListParams {
  patient_id?: number;
  page?: number;
  per_page?: number;
}

interface PrescriptionListParams {
  patient_id?: number;
  dispensed?: boolean;
  page?: number;
  per_page?: number;
}

interface PaymentListParams {
  status?: string;
  patient_id?: number;
  from_date?: string;
  to_date?: string;
  page?: number;
  per_page?: number;
}

interface ReportParams {
  from_date?: string;
  to_date?: string;
  status?: string;
}

interface CreateMedicalRecordData {
  patient_id: number;
  appointment_id?: number;
  diagnosis: string;
  symptoms?: string;
  notes?: string;
  examination_notes?: string;
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

interface CreatePrescriptionData {
  patient_id: number;
  medical_record_id?: number;
  diagnosis: string;
  notes?: string;
  valid_until?: string;
  items: Array<{
    medication_name: string;
    dosage: string;
    frequency: string;
    duration: string;
    instructions?: string;
    quantity?: number;
  }>;
}

interface CreatePaymentData {
  appointment_id: number;
  amount: number;
  discount?: number;
  payment_method?: string;
  notes?: string;
}

interface ClinicSettingsData {
  clinic_name?: string;
  clinic_address?: string;
  clinic_phone?: string;
  clinic_email?: string;
  consultation_fee?: number;
  slot_duration?: number;
  max_patients_per_slot?: number;
  advance_booking_days?: number;
  cancellation_hours?: number;
}

interface CreateScheduleData {
  day_of_week: number;
  start_time: string;
  end_time: string;
  break_start?: string;
  break_end?: string;
  is_active?: boolean;
}

interface UpdateScheduleData {
  start_time?: string;
  end_time?: string;
  break_start?: string;
  break_end?: string;
  is_active?: boolean;
}

interface CreateVacationData {
  title?: string;
  start_date: string;
  end_date: string;
  reason?: string;
}

interface UpdateVacationData {
  title?: string;
  start_date?: string;
  end_date?: string;
  reason?: string;
}

interface UpdatePatientProfileData {
  blood_type?: string;
  allergies?: string;
  chronic_diseases?: string;
  current_medications?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  emergency_contact_relationship?: string;
  insurance_provider?: string;
  insurance_policy_number?: string;
  notes?: string;
}

export const adminApi = {
  // Dashboard
  getDashboardStats: async (): Promise<ApiResponse<DashboardStats>> => {
    const response = await api.get<ApiResponse<DashboardStats>>('/admin/dashboard/stats');
    return response.data;
  },

  getTodayAppointments: async (): Promise<ApiResponse<Appointment[]>> => {
    const response = await api.get<ApiResponse<Appointment[]>>('/admin/dashboard/today');
    return response.data;
  },

  getRecentActivity: async (): Promise<ApiResponse<Activity[]>> => {
    const response = await api.get<ApiResponse<Activity[]>>('/admin/dashboard/recent-activity');
    return response.data;
  },

  getUpcomingAppointments: async (): Promise<ApiResponse<Appointment[]>> => {
    const response = await api.get<ApiResponse<Appointment[]>>('/admin/dashboard/upcoming-appointments');
    return response.data;
  },

  // Appointments
  getAppointments: async (params?: AppointmentListParams): Promise<PaginatedResponse<Appointment>> => {
    const response = await api.get<PaginatedResponse<Appointment>>('/admin/appointments', { params });
    return response.data;
  },

  getAppointment: async (id: number): Promise<ApiResponse<Appointment>> => {
    const response = await api.get<ApiResponse<Appointment>>(`/admin/appointments/${id}`);
    return response.data;
  },

  confirmAppointment: async (id: number): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/admin/appointments/${id}/confirm`);
    return response.data;
  },

  completeAppointment: async (id: number): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/admin/appointments/${id}/complete`);
    return response.data;
  },

  cancelAppointment: async (id: number, reason?: string): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/admin/appointments/${id}/cancel`, {
      cancellation_reason: reason,
    });
    return response.data;
  },

  markNoShow: async (id: number): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/admin/appointments/${id}/no-show`);
    return response.data;
  },

  updateAppointmentNotes: async (id: number, notes: string, adminNotes?: string): Promise<ApiResponse<Appointment>> => {
    const response = await api.put<ApiResponse<Appointment>>(`/admin/appointments/${id}/notes`, {
      notes,
      admin_notes: adminNotes,
    });
    return response.data;
  },

  rescheduleAppointment: async (id: number, date: string, slotTime: string): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/admin/appointments/${id}/reschedule`, {
      date,
      slot_time: slotTime,
    });
    return response.data;
  },

  // Patients
  getPatients: async (params?: PatientListParams): Promise<PaginatedResponse<User>> => {
    const response = await api.get<PaginatedResponse<User>>('/admin/patients', { params });
    return response.data;
  },

  getPatient: async (id: number): Promise<ApiResponse<User & { profile?: PatientProfile }>> => {
    const response = await api.get<ApiResponse<User & { profile?: PatientProfile }>>(`/admin/patients/${id}`);
    return response.data;
  },

  getPatientAppointments: async (id: number, page?: number): Promise<PaginatedResponse<Appointment>> => {
    const response = await api.get<PaginatedResponse<Appointment>>(`/admin/patients/${id}/appointments`, {
      params: { page },
    });
    return response.data;
  },

  getPatientMedicalRecords: async (id: number, page?: number): Promise<PaginatedResponse<MedicalRecord>> => {
    const response = await api.get<PaginatedResponse<MedicalRecord>>(`/admin/patients/${id}/medical-records`, {
      params: { page },
    });
    return response.data;
  },

  getPatientPrescriptions: async (id: number, page?: number): Promise<PaginatedResponse<Prescription>> => {
    const response = await api.get<PaginatedResponse<Prescription>>(`/admin/patients/${id}/prescriptions`, {
      params: { page },
    });
    return response.data;
  },

  updatePatientProfile: async (id: number, data: UpdatePatientProfileData): Promise<ApiResponse<PatientProfile>> => {
    const response = await api.put<ApiResponse<PatientProfile>>(`/admin/patients/${id}/profile`, data);
    return response.data;
  },

  createPatient: async (data: {
    name: string;
    phone: string;
    email?: string;
    date_of_birth?: string;
    gender?: string;
    address?: string;
  }): Promise<ApiResponse<User>> => {
    const response = await api.post<ApiResponse<User>>('/admin/patients', data);
    return response.data;
  },

  // Medical Records
  getMedicalRecords: async (params?: MedicalRecordListParams): Promise<PaginatedResponse<MedicalRecord>> => {
    const response = await api.get<PaginatedResponse<MedicalRecord>>('/admin/medical-records', { params });
    return response.data;
  },

  getMedicalRecord: async (id: number): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.get<ApiResponse<MedicalRecord>>(`/admin/medical-records/${id}`);
    return response.data;
  },

  createMedicalRecord: async (data: CreateMedicalRecordData): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.post<ApiResponse<MedicalRecord>>('/admin/medical-records', data);
    return response.data;
  },

  updateMedicalRecord: async (id: number, data: Partial<CreateMedicalRecordData>): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.put<ApiResponse<MedicalRecord>>(`/admin/medical-records/${id}`, data);
    return response.data;
  },

  deleteMedicalRecord: async (id: number): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/admin/medical-records/${id}`);
    return response.data;
  },

  // Medical Record Attachments
  uploadAttachment: async (recordId: number, file: File, description?: string): Promise<ApiResponse<Attachment>> => {
    const formData = new FormData();
    formData.append('file', file);
    if (description) {
      formData.append('description', description);
    }
    // Note: Content-Type is handled by request interceptor for FormData
    const response = await api.post<ApiResponse<Attachment>>(`/admin/medical-records/${recordId}/attachments`, formData);
    return response.data;
  },

  deleteAttachment: async (recordId: number, attachmentId: number): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/admin/medical-records/${recordId}/attachments/${attachmentId}`);
    return response.data;
  },

  downloadAttachment: async (recordId: number, attachmentId: number): Promise<Blob> => {
    const response = await api.get<Blob>(`/admin/medical-records/${recordId}/attachments/${attachmentId}/download`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Prescriptions
  getPrescriptions: async (params?: PrescriptionListParams): Promise<PaginatedResponse<Prescription>> => {
    const response = await api.get<PaginatedResponse<Prescription>>('/admin/prescriptions', { params });
    return response.data;
  },

  getPrescription: async (id: number): Promise<ApiResponse<Prescription>> => {
    const response = await api.get<ApiResponse<Prescription>>(`/admin/prescriptions/${id}`);
    return response.data;
  },

  createPrescription: async (data: CreatePrescriptionData): Promise<ApiResponse<Prescription>> => {
    const response = await api.post<ApiResponse<Prescription>>('/admin/prescriptions', data);
    return response.data;
  },

  updatePrescription: async (id: number, data: Partial<CreatePrescriptionData>): Promise<ApiResponse<Prescription>> => {
    const response = await api.put<ApiResponse<Prescription>>(`/admin/prescriptions/${id}`, data);
    return response.data;
  },

  deletePrescription: async (id: number): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/admin/prescriptions/${id}`);
    return response.data;
  },

  dispensePrescription: async (id: number): Promise<ApiResponse<Prescription>> => {
    const response = await api.post<ApiResponse<Prescription>>(`/admin/prescriptions/${id}/dispense`);
    return response.data;
  },

  downloadPrescriptionPdf: async (id: number): Promise<Blob> => {
    const response = await api.get<Blob>(`/admin/prescriptions/${id}/download`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Payments
  getPayments: async (params?: PaymentListParams): Promise<PaginatedResponse<Payment>> => {
    const response = await api.get<PaginatedResponse<Payment>>('/admin/payments', { params });
    return response.data;
  },

  getPayment: async (id: number): Promise<ApiResponse<Payment>> => {
    const response = await api.get<ApiResponse<Payment>>(`/admin/payments/${id}`);
    return response.data;
  },

  getPaymentStatistics: async (): Promise<ApiResponse<{
    total_revenue: number;
    total_pending: number;
    total_paid: number;
    total_refunded: number;
    today_revenue: number;
    this_month_revenue: number;
  }>> => {
    const response = await api.get('/admin/payments/statistics');
    return response.data;
  },

  createPayment: async (data: CreatePaymentData): Promise<ApiResponse<Payment>> => {
    const response = await api.post<ApiResponse<Payment>>('/admin/payments', data);
    return response.data;
  },

  markPaymentPaid: async (id: number, paymentMethod?: string): Promise<ApiResponse<Payment>> => {
    const response = await api.post<ApiResponse<Payment>>(`/admin/payments/${id}/mark-paid`, {
      payment_method: paymentMethod,
    });
    return response.data;
  },

  refundPayment: async (id: number, reason?: string): Promise<ApiResponse<Payment>> => {
    const response = await api.post<ApiResponse<Payment>>(`/admin/payments/${id}/refund`, { reason });
    return response.data;
  },

  // Settings
  getClinicSettings: async (): Promise<ApiResponse<ClinicSettings>> => {
    const response = await api.get<ApiResponse<ClinicSettings>>('/admin/settings');
    return response.data;
  },

  updateClinicSettings: async (data: ClinicSettingsData): Promise<ApiResponse<ClinicSettings>> => {
    const response = await api.put<ApiResponse<ClinicSettings>>('/admin/settings', data);
    return response.data;
  },

  uploadClinicLogo: async (file: File): Promise<ApiResponse<{ logo: string }>> => {
    const formData = new FormData();
    formData.append('logo', file);
    // Note: Content-Type is handled by request interceptor for FormData
    const response = await api.post<ApiResponse<{ logo: string }>>('/admin/settings/logo', formData);
    return response.data;
  },

  deleteClinicLogo: async (): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>('/admin/settings/logo');
    return response.data;
  },

  // Schedules
  getSchedules: async (): Promise<ApiResponse<Schedule[]>> => {
    const response = await api.get<ApiResponse<Schedule[]>>('/admin/schedules');
    return response.data;
  },

  getSchedule: async (id: number): Promise<ApiResponse<Schedule>> => {
    const response = await api.get<ApiResponse<Schedule>>(`/admin/schedules/${id}`);
    return response.data;
  },

  createSchedule: async (data: CreateScheduleData): Promise<ApiResponse<Schedule>> => {
    const response = await api.post<ApiResponse<Schedule>>('/admin/schedules', data);
    return response.data;
  },

  updateSchedule: async (id: number, data: UpdateScheduleData): Promise<ApiResponse<Schedule>> => {
    const response = await api.put<ApiResponse<Schedule>>(`/admin/schedules/${id}`, data);
    return response.data;
  },

  toggleSchedule: async (id: number): Promise<ApiResponse<Schedule>> => {
    const response = await api.patch<ApiResponse<Schedule>>(`/admin/schedules/${id}/toggle`);
    return response.data;
  },

  deleteSchedule: async (id: number): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/admin/schedules/${id}`);
    return response.data;
  },

  // Vacations
  getVacations: async (): Promise<ApiResponse<Vacation[]>> => {
    const response = await api.get<ApiResponse<Vacation[]>>('/admin/vacations');
    return response.data;
  },

  getVacation: async (id: number): Promise<ApiResponse<Vacation>> => {
    const response = await api.get<ApiResponse<Vacation>>(`/admin/vacations/${id}`);
    return response.data;
  },

  createVacation: async (data: CreateVacationData): Promise<ApiResponse<Vacation>> => {
    const response = await api.post<ApiResponse<Vacation>>('/admin/vacations', data);
    return response.data;
  },

  updateVacation: async (id: number, data: UpdateVacationData): Promise<ApiResponse<Vacation>> => {
    const response = await api.put<ApiResponse<Vacation>>(`/admin/vacations/${id}`, data);
    return response.data;
  },

  deleteVacation: async (id: number): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/admin/vacations/${id}`);
    return response.data;
  },

  // Reports - Fixed parameter naming (from_date/to_date instead of from/to)
  getAppointmentsReport: async (params?: ReportParams): Promise<ApiResponse<AppointmentsReport>> => {
    const response = await api.get<ApiResponse<AppointmentsReport>>('/admin/reports/appointments', { params });
    return response.data;
  },

  getRevenueReport: async (params?: ReportParams): Promise<ApiResponse<RevenueReport>> => {
    const response = await api.get<ApiResponse<RevenueReport>>('/admin/reports/revenue', { params });
    return response.data;
  },

  getPatientsReport: async (params?: ReportParams): Promise<ApiResponse<PatientsReport>> => {
    const response = await api.get<ApiResponse<PatientsReport>>('/admin/reports/patients', { params });
    return response.data;
  },

  exportAppointmentsReport: async (params?: ReportParams): Promise<Blob> => {
    const response = await api.get<Blob>('/admin/reports/appointments/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  exportRevenueReport: async (params?: ReportParams): Promise<Blob> => {
    const response = await api.get<Blob>('/admin/reports/revenue/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  exportPatientsReport: async (params?: ReportParams): Promise<Blob> => {
    const response = await api.get<Blob>('/admin/reports/patients/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },
};
