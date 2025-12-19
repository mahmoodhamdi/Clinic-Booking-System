import api from './client';
import { ApiResponse, PaginatedResponse, MedicalRecord, Prescription, Notification, PatientProfile, PatientDashboard } from '@/types';

export const patientApi = {
  // Dashboard
  getDashboard: async (): Promise<ApiResponse<PatientDashboard>> => {
    const response = await api.get<ApiResponse<PatientDashboard>>('/patient/dashboard');
    return response.data;
  },

  // Profile
  getProfile: async (): Promise<ApiResponse<PatientProfile>> => {
    const response = await api.get<ApiResponse<PatientProfile>>('/patient/profile');
    return response.data;
  },

  createProfile: async (data: {
    date_of_birth?: string;
    gender?: 'male' | 'female';
    blood_type?: string;
    allergies?: string;
    chronic_conditions?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;
  }): Promise<ApiResponse<PatientProfile>> => {
    const response = await api.post<ApiResponse<PatientProfile>>('/patient/profile', data);
    return response.data;
  },

  updateProfile: async (data: {
    date_of_birth?: string;
    gender?: 'male' | 'female';
    blood_type?: string;
    allergies?: string;
    chronic_conditions?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;
  }): Promise<ApiResponse<PatientProfile>> => {
    const response = await api.put<ApiResponse<PatientProfile>>('/patient/profile', data);
    return response.data;
  },

  // History
  getHistory: async (): Promise<ApiResponse<unknown>> => {
    const response = await api.get<ApiResponse<unknown>>('/patient/history');
    return response.data;
  },

  // Statistics
  getStatistics: async (): Promise<ApiResponse<unknown>> => {
    const response = await api.get<ApiResponse<unknown>>('/patient/statistics');
    return response.data;
  },

  // Medical Records
  getMedicalRecords: async (params?: { page?: number }): Promise<PaginatedResponse<MedicalRecord>> => {
    const response = await api.get<PaginatedResponse<MedicalRecord>>('/medical-records', { params });
    return response.data;
  },

  getMedicalRecord: async (id: number): Promise<ApiResponse<MedicalRecord>> => {
    const response = await api.get<ApiResponse<MedicalRecord>>(`/medical-records/${id}`);
    return response.data;
  },

  // Prescriptions
  getPrescriptions: async (params?: { page?: number }): Promise<PaginatedResponse<Prescription>> => {
    const response = await api.get<PaginatedResponse<Prescription>>('/prescriptions', { params });
    return response.data;
  },

  getPrescription: async (id: number): Promise<ApiResponse<Prescription>> => {
    const response = await api.get<ApiResponse<Prescription>>(`/prescriptions/${id}`);
    return response.data;
  },

  // Notifications
  getNotifications: async (params?: { page?: number }): Promise<PaginatedResponse<Notification>> => {
    const response = await api.get<PaginatedResponse<Notification>>('/notifications', { params });
    return response.data;
  },

  getUnreadCount: async (): Promise<ApiResponse<{ count: number }>> => {
    const response = await api.get<ApiResponse<{ count: number }>>('/notifications/unread-count');
    return response.data;
  },

  markAsRead: async (id: string): Promise<ApiResponse<Notification>> => {
    const response = await api.post<ApiResponse<Notification>>(`/notifications/${id}/read`);
    return response.data;
  },

  markAllAsRead: async (): Promise<ApiResponse<null>> => {
    const response = await api.post<ApiResponse<null>>('/notifications/read-all');
    return response.data;
  },

  deleteNotification: async (id: string): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>(`/notifications/${id}`);
    return response.data;
  },
};
