import api from './client';
import { ApiResponse, PaginatedResponse, Appointment, Slot, AvailableDate } from '@/types';

export interface BookAppointmentData {
  date: string;
  slot_time: string;
  reason?: string;
}

export const appointmentsApi = {
  // Get available dates for booking
  getAvailableDates: async (): Promise<ApiResponse<AvailableDate[]>> => {
    const response = await api.get<ApiResponse<AvailableDate[]>>('/slots/dates');
    return response.data;
  },

  // Get available slots for a specific date
  getSlots: async (date: string): Promise<ApiResponse<Slot[]>> => {
    const response = await api.get<ApiResponse<Slot[]>>(`/slots/${date}`);
    return response.data;
  },

  // Check slot availability
  checkSlot: async (date: string, time: string): Promise<ApiResponse<{ available: boolean }>> => {
    const response = await api.post<ApiResponse<{ available: boolean }>>('/slots/check', {
      date,
      slot_time: time,
    });
    return response.data;
  },

  // Book an appointment
  book: async (data: BookAppointmentData): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>('/appointments', data);
    return response.data;
  },

  // Get my appointments
  getMyAppointments: async (params?: {
    status?: string;
    page?: number;
  }): Promise<PaginatedResponse<Appointment>> => {
    const response = await api.get<PaginatedResponse<Appointment>>('/appointments', { params });
    return response.data;
  },

  // Get upcoming appointments
  getUpcoming: async (): Promise<ApiResponse<Appointment[]>> => {
    const response = await api.get<ApiResponse<Appointment[]>>('/appointments/upcoming');
    return response.data;
  },

  // Get single appointment
  getById: async (id: number): Promise<ApiResponse<Appointment>> => {
    const response = await api.get<ApiResponse<Appointment>>(`/appointments/${id}`);
    return response.data;
  },

  // Cancel appointment
  cancel: async (id: number, reason?: string): Promise<ApiResponse<Appointment>> => {
    const response = await api.post<ApiResponse<Appointment>>(`/appointments/${id}/cancel`, {
      cancellation_reason: reason,
    });
    return response.data;
  },
};
