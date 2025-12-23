import axios, { AxiosError, AxiosResponse, InternalAxiosRequestConfig } from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:9000/api';

// Custom error class for API errors
export class ApiError extends Error {
  public status: number;
  public code: string;
  public details: Record<string, string[]> | null;

  constructor(
    message: string,
    status: number,
    code: string = 'UNKNOWN_ERROR',
    details: Record<string, string[]> | null = null
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

// Configuration
const DEFAULT_TIMEOUT = 30000; // 30 seconds
const MAX_RETRIES = 3;
const RETRY_DELAY = 1000; // 1 second base delay

// Retryable HTTP methods (idempotent)
const RETRYABLE_METHODS = ['get', 'head', 'options', 'put', 'delete'];

// Retryable status codes (server errors and specific client errors)
const RETRYABLE_STATUS_CODES = [408, 429, 500, 502, 503, 504];

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // IMPORTANT: Send cookies with requests
  timeout: DEFAULT_TIMEOUT,
});

// Sleep helper for retry delay
const sleep = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

// Calculate exponential backoff delay
const getRetryDelay = (retryCount: number): number => {
  return RETRY_DELAY * Math.pow(2, retryCount);
};

// Check if request should be retried
const shouldRetry = (error: AxiosError, retryCount: number): boolean => {
  if (retryCount >= MAX_RETRIES) return false;

  const method = error.config?.method?.toLowerCase();
  if (!method || !RETRYABLE_METHODS.includes(method)) return false;

  // Network errors (no response)
  if (!error.response) return true;

  // Specific status codes
  return RETRYABLE_STATUS_CODES.includes(error.response.status);
};

// Request interceptor - add locale (token is now handled via HttpOnly cookie)
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    if (typeof window !== 'undefined') {
      // Add locale from localStorage (safe, not sensitive)
      const locale = localStorage.getItem('locale') || 'ar';
      config.headers['Accept-Language'] = locale;
    }

    // Don't set Content-Type for FormData - let browser set it with boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
    }

    // Initialize retry count
    if (config.headers['x-retry-count'] === undefined) {
      config.headers['x-retry-count'] = '0';
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - handle errors with retry logic
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError) => {
    const config = error.config;

    if (!config) {
      return Promise.reject(new ApiError(
        'Request configuration error',
        0,
        'CONFIG_ERROR'
      ));
    }

    const retryCount = parseInt(config.headers['x-retry-count'] as string || '0', 10);

    // Retry logic
    if (shouldRetry(error, retryCount)) {
      const delay = getRetryDelay(retryCount);
      await sleep(delay);

      config.headers['x-retry-count'] = String(retryCount + 1);
      return api(config);
    }

    // Handle specific error cases
    if (error.response) {
      const { status, data } = error.response as { status: number; data: Record<string, unknown> };

      // Unauthorized - redirect to login
      if (status === 401) {
        if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
          window.location.href = '/login';
        }
        return Promise.reject(new ApiError(
          'Session expired. Please login again.',
          401,
          'UNAUTHORIZED'
        ));
      }

      // Forbidden - insufficient permissions
      if (status === 403) {
        return Promise.reject(new ApiError(
          (data?.message as string) || 'You do not have permission to perform this action.',
          403,
          'FORBIDDEN'
        ));
      }

      // Not Found
      if (status === 404) {
        return Promise.reject(new ApiError(
          (data?.message as string) || 'The requested resource was not found.',
          404,
          'NOT_FOUND'
        ));
      }

      // Validation errors
      if (status === 422) {
        const errors = data?.errors as Record<string, string[]> | undefined;
        const message = (data?.message as string) || 'Validation failed. Please check your input.';
        return Promise.reject(new ApiError(
          message,
          422,
          'VALIDATION_ERROR',
          errors || null
        ));
      }

      // Rate limiting
      if (status === 429) {
        return Promise.reject(new ApiError(
          'Too many requests. Please try again later.',
          429,
          'RATE_LIMITED'
        ));
      }

      // Server errors
      if (status >= 500) {
        return Promise.reject(new ApiError(
          'Server error. Please try again later.',
          status,
          'SERVER_ERROR'
        ));
      }

      // Generic error with server message
      return Promise.reject(new ApiError(
        (data?.message as string) || 'An error occurred.',
        status,
        'API_ERROR',
        (data?.errors as Record<string, string[]>) || null
      ));
    }

    // Network errors
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      return Promise.reject(new ApiError(
        'Request timed out. Please check your connection and try again.',
        0,
        'TIMEOUT'
      ));
    }

    return Promise.reject(new ApiError(
      'Network error. Please check your connection.',
      0,
      'NETWORK_ERROR'
    ));
  }
);

// Helper to check if an error is an ApiError
export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError;
}

// Helper to get user-friendly error message
export function getErrorMessage(error: unknown): string {
  if (isApiError(error)) {
    return error.message;
  }
  if (error instanceof Error) {
    return error.message;
  }
  return 'An unexpected error occurred.';
}

// Helper to get validation errors from ApiError
export function getValidationErrors(error: unknown): Record<string, string[]> | null {
  if (isApiError(error) && error.code === 'VALIDATION_ERROR') {
    return error.details;
  }
  return null;
}

export default api;
