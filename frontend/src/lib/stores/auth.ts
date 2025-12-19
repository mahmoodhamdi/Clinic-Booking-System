import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { User } from '@/types';
import { authApi, LoginCredentials, RegisterData } from '@/lib/api/auth';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  // Actions
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setUser: (user: User | null) => void;
  setToken: (token: string | null) => void;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,

      login: async (credentials: LoginCredentials) => {
        set({ isLoading: true, error: null });
        try {
          const response = await authApi.login(credentials);
          const { user, token } = response.data;

          // Store token in localStorage for API client
          localStorage.setItem('token', token);

          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
        } catch (error: unknown) {
          const message = error instanceof Error ? error.message : 'Login failed';
          const axiosError = error as { response?: { data?: { message?: string } } };
          set({
            isLoading: false,
            error: axiosError.response?.data?.message || message,
          });
          throw error;
        }
      },

      register: async (data: RegisterData) => {
        set({ isLoading: true, error: null });
        try {
          const response = await authApi.register(data);
          const { user, token } = response.data;

          // Store token in localStorage for API client
          localStorage.setItem('token', token);

          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
        } catch (error: unknown) {
          const message = error instanceof Error ? error.message : 'Registration failed';
          const axiosError = error as { response?: { data?: { message?: string } } };
          set({
            isLoading: false,
            error: axiosError.response?.data?.message || message,
          });
          throw error;
        }
      },

      logout: async () => {
        set({ isLoading: true });
        try {
          await authApi.logout();
        } catch {
          // Ignore logout errors
        } finally {
          localStorage.removeItem('token');
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
            error: null,
          });
        }
      },

      fetchUser: async () => {
        const token = get().token;
        if (!token) return;

        set({ isLoading: true });
        try {
          const response = await authApi.me();
          set({
            user: response.data,
            isAuthenticated: true,
            isLoading: false,
          });
        } catch {
          // Token invalid, clear auth state
          localStorage.removeItem('token');
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      setUser: (user: User | null) => set({ user }),
      setToken: (token: string | null) => {
        if (token) {
          localStorage.setItem('token', token);
        } else {
          localStorage.removeItem('token');
        }
        set({ token, isAuthenticated: !!token });
      },
      clearError: () => set({ error: null }),
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
