import api from './client';

export const adminApi = {
  // Dashboard
  getDashboardStats: async () => {
    const response = await api.get('/admin/dashboard/stats');
    return response.data;
  },

  getTodayAppointments: async () => {
    const response = await api.get('/admin/dashboard/today');
    return response.data;
  },

  getRecentActivity: async () => {
    const response = await api.get('/admin/dashboard/recent-activity');
    return response.data;
  },

  getUpcomingAppointments: async () => {
    const response = await api.get('/admin/dashboard/upcoming-appointments');
    return response.data;
  },

  // Appointments
  getAppointments: async (params?: {
    status?: string;
    date?: string;
    patient_id?: number;
    page?: number;
  }) => {
    const response = await api.get('/admin/appointments', { params });
    return response.data;
  },

  getAppointment: async (id: number) => {
    const response = await api.get(`/admin/appointments/${id}`);
    return response.data;
  },

  confirmAppointment: async (id: number) => {
    const response = await api.post(`/admin/appointments/${id}/confirm`);
    return response.data;
  },

  completeAppointment: async (id: number) => {
    const response = await api.post(`/admin/appointments/${id}/complete`);
    return response.data;
  },

  cancelAppointment: async (id: number, reason?: string) => {
    const response = await api.post(`/admin/appointments/${id}/cancel`, {
      cancellation_reason: reason
    });
    return response.data;
  },

  markNoShow: async (id: number) => {
    const response = await api.post(`/admin/appointments/${id}/no-show`);
    return response.data;
  },

  updateAppointmentNotes: async (id: number, notes: string) => {
    const response = await api.put(`/admin/appointments/${id}/notes`, { notes });
    return response.data;
  },

  // Patients
  getPatients: async (params?: { search?: string; page?: number }) => {
    const response = await api.get('/admin/patients', { params });
    return response.data;
  },

  getPatient: async (id: number) => {
    const response = await api.get(`/admin/patients/${id}`);
    return response.data;
  },

  getPatientAppointments: async (id: number) => {
    const response = await api.get(`/admin/patients/${id}/appointments`);
    return response.data;
  },

  getPatientMedicalRecords: async (id: number) => {
    const response = await api.get(`/admin/patients/${id}/medical-records`);
    return response.data;
  },

  getPatientPrescriptions: async (id: number) => {
    const response = await api.get(`/admin/patients/${id}/prescriptions`);
    return response.data;
  },

  updatePatientProfile: async (id: number, data: object) => {
    const response = await api.put(`/admin/patients/${id}/profile`, data);
    return response.data;
  },

  // Medical Records
  getMedicalRecords: async (params?: { patient_id?: number; page?: number }) => {
    const response = await api.get('/admin/medical-records', { params });
    return response.data;
  },

  getMedicalRecord: async (id: number) => {
    const response = await api.get(`/admin/medical-records/${id}`);
    return response.data;
  },

  createMedicalRecord: async (data: {
    patient_id: number;
    appointment_id?: number;
    diagnosis: string;
    notes?: string;
    treatment_plan?: string;
    follow_up_date?: string;
  }) => {
    const response = await api.post('/admin/medical-records', data);
    return response.data;
  },

  updateMedicalRecord: async (id: number, data: object) => {
    const response = await api.put(`/admin/medical-records/${id}`, data);
    return response.data;
  },

  deleteMedicalRecord: async (id: number) => {
    const response = await api.delete(`/admin/medical-records/${id}`);
    return response.data;
  },

  // Medical Record Attachments
  uploadAttachment: async (recordId: number, file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await api.post(`/admin/medical-records/${recordId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  deleteAttachment: async (recordId: number, attachmentId: number) => {
    const response = await api.delete(`/admin/medical-records/${recordId}/attachments/${attachmentId}`);
    return response.data;
  },

  // Prescriptions
  getPrescriptions: async (params?: { patient_id?: number; dispensed?: boolean; page?: number }) => {
    const response = await api.get('/admin/prescriptions', { params });
    return response.data;
  },

  getPrescription: async (id: number) => {
    const response = await api.get(`/admin/prescriptions/${id}`);
    return response.data;
  },

  createPrescription: async (data: {
    patient_id: number;
    appointment_id?: number;
    diagnosis: string;
    notes?: string;
    items: Array<{
      medication_name: string;
      dosage: string;
      frequency: string;
      duration: string;
      instructions?: string;
    }>;
  }) => {
    const response = await api.post('/admin/prescriptions', data);
    return response.data;
  },

  updatePrescription: async (id: number, data: object) => {
    const response = await api.put(`/admin/prescriptions/${id}`, data);
    return response.data;
  },

  deletePrescription: async (id: number) => {
    const response = await api.delete(`/admin/prescriptions/${id}`);
    return response.data;
  },

  dispensePrescription: async (id: number) => {
    const response = await api.post(`/admin/prescriptions/${id}/dispense`);
    return response.data;
  },

  downloadPrescriptionPdf: async (id: number) => {
    const response = await api.get(`/admin/prescriptions/${id}/download`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Payments
  getPayments: async (params?: { status?: string; patient_id?: number; date_from?: string; date_to?: string; page?: number }) => {
    const response = await api.get('/admin/payments', { params });
    return response.data;
  },

  getPayment: async (id: number) => {
    const response = await api.get(`/admin/payments/${id}`);
    return response.data;
  },

  getPaymentStatistics: async () => {
    const response = await api.get('/admin/payments/statistics');
    return response.data;
  },

  recordPayment: async (data: {
    patient_id: number;
    appointment_id?: number;
    amount: number;
    payment_method: string;
    notes?: string;
  }) => {
    const response = await api.post('/admin/payments', data);
    return response.data;
  },

  markPaymentPaid: async (id: number) => {
    const response = await api.post(`/admin/payments/${id}/mark-paid`);
    return response.data;
  },

  refundPayment: async (id: number, reason?: string) => {
    const response = await api.post(`/admin/payments/${id}/refund`, { reason });
    return response.data;
  },

  // Settings
  getClinicSettings: async () => {
    const response = await api.get('/admin/settings');
    return response.data;
  },

  updateClinicSettings: async (data: {
    clinic_name?: string;
    address?: string;
    phone?: string;
    email?: string;
    consultation_fee?: number;
    currency?: string;
  }) => {
    const response = await api.put('/admin/settings', data);
    return response.data;
  },

  uploadClinicLogo: async (file: File) => {
    const formData = new FormData();
    formData.append('logo', file);
    const response = await api.post('/admin/settings/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  deleteClinicLogo: async () => {
    const response = await api.delete('/admin/settings/logo');
    return response.data;
  },

  // Schedules
  getSchedules: async () => {
    const response = await api.get('/admin/schedules');
    return response.data;
  },

  getSchedule: async (id: number) => {
    const response = await api.get(`/admin/schedules/${id}`);
    return response.data;
  },

  createSchedule: async (data: {
    day_of_week: number;
    start_time: string;
    end_time: string;
    slot_duration: number;
    is_working: boolean;
  }) => {
    const response = await api.post('/admin/schedules', data);
    return response.data;
  },

  updateSchedule: async (id: number, data: {
    start_time?: string;
    end_time?: string;
    slot_duration?: number;
    is_working?: boolean;
  }) => {
    const response = await api.put(`/admin/schedules/${id}`, data);
    return response.data;
  },

  toggleSchedule: async (id: number) => {
    const response = await api.put(`/admin/schedules/${id}/toggle`);
    return response.data;
  },

  deleteSchedule: async (id: number) => {
    const response = await api.delete(`/admin/schedules/${id}`);
    return response.data;
  },

  // Vacations
  getVacations: async () => {
    const response = await api.get('/admin/vacations');
    return response.data;
  },

  getVacation: async (id: number) => {
    const response = await api.get(`/admin/vacations/${id}`);
    return response.data;
  },

  createVacation: async (data: {
    start_date: string;
    end_date: string;
    reason?: string;
  }) => {
    const response = await api.post('/admin/vacations', data);
    return response.data;
  },

  updateVacation: async (id: number, data: {
    start_date?: string;
    end_date?: string;
    reason?: string;
  }) => {
    const response = await api.put(`/admin/vacations/${id}`, data);
    return response.data;
  },

  deleteVacation: async (id: number) => {
    const response = await api.delete(`/admin/vacations/${id}`);
    return response.data;
  },

  // Reports
  getAppointmentsReport: async (params?: { from?: string; to?: string }) => {
    const response = await api.get('/admin/reports/appointments', { params });
    return response.data;
  },

  getRevenueReport: async (params?: { from?: string; to?: string }) => {
    const response = await api.get('/admin/reports/revenue', { params });
    return response.data;
  },

  getPatientsReport: async (params?: { from?: string; to?: string }) => {
    const response = await api.get('/admin/reports/patients', { params });
    return response.data;
  },

  exportAppointmentsReport: async (params?: { from?: string; to?: string }) => {
    const response = await api.get('/admin/reports/appointments/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },

  exportRevenueReport: async (params?: { from?: string; to?: string }) => {
    const response = await api.get('/admin/reports/revenue/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },
};
