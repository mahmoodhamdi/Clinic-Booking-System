import api from './client';
import { AuthResponse, ApiResponse, User } from '@/types';

export interface LoginCredentials {
  phone: string;
  password: string;
}

export interface RegisterData {
  name: string;
  phone: string;
  password: string;
  password_confirmation: string;
  email?: string;
}

export interface ForgotPasswordData {
  phone: string;
}

export interface VerifyOtpData {
  phone: string;
  otp: string;
}

export interface ResetPasswordData {
  phone: string;
  otp: string;
  password: string;
  password_confirmation: string;
}

export interface ChangePasswordData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfileData {
  name?: string;
  email?: string;
  date_of_birth?: string;
  gender?: 'male' | 'female';
  address?: string;
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const response = await api.post<AuthResponse>('/auth/login', credentials);
    return response.data;
  },

  register: async (data: RegisterData): Promise<AuthResponse> => {
    const response = await api.post<AuthResponse>('/auth/register', data);
    return response.data;
  },

  logout: async (): Promise<ApiResponse<null>> => {
    const response = await api.post<ApiResponse<null>>('/auth/logout');
    return response.data;
  },

  me: async (): Promise<ApiResponse<User>> => {
    const response = await api.get<ApiResponse<User>>('/auth/me');
    return response.data;
  },

  forgotPassword: async (data: ForgotPasswordData): Promise<ApiResponse<null>> => {
    const response = await api.post<ApiResponse<null>>('/auth/forgot-password', data);
    return response.data;
  },

  verifyOtp: async (data: VerifyOtpData): Promise<ApiResponse<{ verified: boolean }>> => {
    const response = await api.post<ApiResponse<{ verified: boolean }>>('/auth/verify-otp', data);
    return response.data;
  },

  resetPassword: async (data: ResetPasswordData): Promise<ApiResponse<null>> => {
    const response = await api.post<ApiResponse<null>>('/auth/reset-password', data);
    return response.data;
  },

  changePassword: async (data: ChangePasswordData): Promise<ApiResponse<null>> => {
    const response = await api.post<ApiResponse<null>>('/auth/change-password', data);
    return response.data;
  },

  updateProfile: async (data: UpdateProfileData): Promise<ApiResponse<User>> => {
    const response = await api.put<ApiResponse<User>>('/auth/profile', data);
    return response.data;
  },

  uploadAvatar: async (file: File): Promise<ApiResponse<{ avatar: string }>> => {
    const formData = new FormData();
    formData.append('avatar', file);
    const response = await api.post<ApiResponse<{ avatar: string }>>('/auth/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  deleteAccount: async (): Promise<ApiResponse<null>> => {
    const response = await api.delete<ApiResponse<null>>('/auth/account');
    return response.data;
  },
};
