import { ApiError, isApiError, getErrorMessage, getValidationErrors } from '@/lib/api/client';

describe('API Client', () => {
  describe('ApiError', () => {
    test('creates error with all properties', () => {
      const error = new ApiError('Test error', 400, 'TEST_ERROR', { field: ['error'] });

      expect(error.message).toBe('Test error');
      expect(error.status).toBe(400);
      expect(error.code).toBe('TEST_ERROR');
      expect(error.details).toEqual({ field: ['error'] });
      expect(error.name).toBe('ApiError');
    });

    test('creates error with defaults', () => {
      const error = new ApiError('Test error', 500);

      expect(error.message).toBe('Test error');
      expect(error.status).toBe(500);
      expect(error.code).toBe('UNKNOWN_ERROR');
      expect(error.details).toBeNull();
    });

    test('is instance of Error', () => {
      const error = new ApiError('Test error', 500);
      expect(error instanceof Error).toBe(true);
    });
  });

  describe('isApiError', () => {
    test('returns true for ApiError', () => {
      const error = new ApiError('Test', 500);
      expect(isApiError(error)).toBe(true);
    });

    test('returns false for regular Error', () => {
      const error = new Error('Test');
      expect(isApiError(error)).toBe(false);
    });

    test('returns false for non-error objects', () => {
      expect(isApiError({ message: 'test' })).toBe(false);
      expect(isApiError(null)).toBe(false);
      expect(isApiError(undefined)).toBe(false);
      expect(isApiError('string')).toBe(false);
    });
  });

  describe('getErrorMessage', () => {
    test('returns message from ApiError', () => {
      const error = new ApiError('API error message', 500);
      expect(getErrorMessage(error)).toBe('API error message');
    });

    test('returns message from regular Error', () => {
      const error = new Error('Regular error message');
      expect(getErrorMessage(error)).toBe('Regular error message');
    });

    test('returns default message for unknown types', () => {
      expect(getErrorMessage(null)).toBe('An unexpected error occurred.');
      expect(getErrorMessage(undefined)).toBe('An unexpected error occurred.');
      expect(getErrorMessage('string')).toBe('An unexpected error occurred.');
      expect(getErrorMessage({})).toBe('An unexpected error occurred.');
    });
  });

  describe('getValidationErrors', () => {
    test('returns validation errors from ApiError with VALIDATION_ERROR code', () => {
      const details = { email: ['Invalid email'], password: ['Too short'] };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', details);
      expect(getValidationErrors(error)).toEqual(details);
    });

    test('returns null for ApiError with different code', () => {
      const error = new ApiError('Server error', 500, 'SERVER_ERROR', null);
      expect(getValidationErrors(error)).toBeNull();
    });

    test('returns null for regular Error', () => {
      const error = new Error('Test');
      expect(getValidationErrors(error)).toBeNull();
    });

    test('returns null for non-errors', () => {
      expect(getValidationErrors(null)).toBeNull();
      expect(getValidationErrors(undefined)).toBeNull();
      expect(getValidationErrors('string')).toBeNull();
    });
  });
});

describe('API Error Codes', () => {
  test('defines common error codes', () => {
    // Test that common error scenarios produce expected codes
    const unauthorized = new ApiError('Unauthorized', 401, 'UNAUTHORIZED');
    expect(unauthorized.code).toBe('UNAUTHORIZED');

    const forbidden = new ApiError('Forbidden', 403, 'FORBIDDEN');
    expect(forbidden.code).toBe('FORBIDDEN');

    const notFound = new ApiError('Not found', 404, 'NOT_FOUND');
    expect(notFound.code).toBe('NOT_FOUND');

    const validation = new ApiError('Validation failed', 422, 'VALIDATION_ERROR');
    expect(validation.code).toBe('VALIDATION_ERROR');

    const rateLimit = new ApiError('Rate limited', 429, 'RATE_LIMITED');
    expect(rateLimit.code).toBe('RATE_LIMITED');

    const serverError = new ApiError('Server error', 500, 'SERVER_ERROR');
    expect(serverError.code).toBe('SERVER_ERROR');

    const timeout = new ApiError('Timeout', 0, 'TIMEOUT');
    expect(timeout.code).toBe('TIMEOUT');

    const network = new ApiError('Network error', 0, 'NETWORK_ERROR');
    expect(network.code).toBe('NETWORK_ERROR');
  });
});
