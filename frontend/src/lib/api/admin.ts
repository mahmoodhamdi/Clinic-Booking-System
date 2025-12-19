import api from './client';

export const adminApi = {
  // Dashboard
  getDashboardStats: async () => {
    const response = await api.get('/admin/dashboard/stats');
    return response.data;
  },

  // Appointments
  getAppointments: async (params?: {
    status?: string;
    date?: string;
    patient_id?: number;
    page?: number;
  }) => {
    const response = await api.get('/appointments', { params });
    return response.data;
  },

  updateAppointmentStatus: async (id: number, status: string, notes?: string) => {
    const response = await api.patch(`/appointments/${id}/status`, { status, notes });
    return response.data;
  },

  // Patients
  getPatients: async (params?: { search?: string; page?: number }) => {
    const response = await api.get('/patients', { params });
    return response.data;
  },

  getPatient: async (id: number) => {
    const response = await api.get(`/patients/${id}`);
    return response.data;
  },

  // Medical Records
  getMedicalRecords: async (params?: { patient_id?: number; page?: number }) => {
    const response = await api.get('/admin/medical-records', { params });
    return response.data;
  },

  createMedicalRecord: async (data: {
    patient_id: number;
    appointment_id?: number;
    diagnosis: string;
    notes?: string;
    attachments?: File[];
  }) => {
    const formData = new FormData();
    formData.append('patient_id', data.patient_id.toString());
    if (data.appointment_id) formData.append('appointment_id', data.appointment_id.toString());
    formData.append('diagnosis', data.diagnosis);
    if (data.notes) formData.append('notes', data.notes);
    if (data.attachments) {
      data.attachments.forEach((file) => formData.append('attachments[]', file));
    }
    const response = await api.post('/medical-records', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  // Prescriptions
  getPrescriptions: async (params?: { patient_id?: number; page?: number }) => {
    const response = await api.get('/admin/prescriptions', { params });
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
    const response = await api.post('/prescriptions', data);
    return response.data;
  },

  dispensePrescription: async (id: number) => {
    const response = await api.post(`/prescriptions/${id}/dispense`);
    return response.data;
  },

  // Payments
  getPayments: async (params?: { status?: string; patient_id?: number; page?: number }) => {
    const response = await api.get('/payments', { params });
    return response.data;
  },

  recordPayment: async (data: {
    patient_id: number;
    appointment_id?: number;
    amount: number;
    payment_method: string;
    notes?: string;
  }) => {
    const response = await api.post('/payments', data);
    return response.data;
  },

  // Settings
  getClinicSettings: async () => {
    const response = await api.get('/settings');
    return response.data;
  },

  updateClinicSettings: async (data: {
    clinic_name?: string;
    address?: string;
    phone?: string;
    email?: string;
    working_hours?: object;
  }) => {
    const response = await api.put('/settings', data);
    return response.data;
  },

  // Schedules
  getSchedules: async () => {
    const response = await api.get('/schedules');
    return response.data;
  },

  updateSchedule: async (dayOfWeek: number, data: {
    is_working: boolean;
    start_time?: string;
    end_time?: string;
    slot_duration?: number;
  }) => {
    const response = await api.put(`/schedules/${dayOfWeek}`, data);
    return response.data;
  },

  // Vacations
  getVacations: async () => {
    const response = await api.get('/vacations');
    return response.data;
  },

  createVacation: async (data: {
    start_date: string;
    end_date: string;
    reason?: string;
  }) => {
    const response = await api.post('/vacations', data);
    return response.data;
  },

  deleteVacation: async (id: number) => {
    const response = await api.delete(`/vacations/${id}`);
    return response.data;
  },
};
