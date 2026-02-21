import { ApiError, isApiError, getErrorMessage, getValidationErrors } from '@/lib/api/client';

describe('API Client Utilities', () => {
  describe('ApiError Class', () => {
    it('creates error with all properties', () => {
      const error = new ApiError('Test error', 400, 'TEST_ERROR', {
        field: ['error'],
      });

      expect(error.message).toBe('Test error');
      expect(error.status).toBe(400);
      expect(error.code).toBe('TEST_ERROR');
      expect(error.details).toEqual({ field: ['error'] });
      expect(error.name).toBe('ApiError');
    });

    it('creates error with default code', () => {
      const error = new ApiError('Test error', 500);

      expect(error.code).toBe('UNKNOWN_ERROR');
      expect(error.details).toBeNull();
    });

    it('is instance of Error', () => {
      const error = new ApiError('Test error', 500);
      expect(error instanceof Error).toBe(true);
    });

    it('preserves stack trace', () => {
      const error = new ApiError('Test error', 500);
      expect(error.stack).toBeDefined();
    });

    it('creates validation error with details', () => {
      const validationDetails = {
        email: ['Invalid email format'],
        password: ['Too short'],
      };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', validationDetails);

      expect(error.status).toBe(422);
      expect(error.code).toBe('VALIDATION_ERROR');
      expect(error.details).toEqual(validationDetails);
    });
  });

  describe('isApiError', () => {
    it('returns true for ApiError', () => {
      const error = new ApiError('Test', 500);
      expect(isApiError(error)).toBe(true);
    });

    it('returns false for regular Error', () => {
      const error = new Error('Test');
      expect(isApiError(error)).toBe(false);
    });

    it('returns false for non-error objects', () => {
      expect(isApiError({ message: 'test' })).toBe(false);
      expect(isApiError(null)).toBe(false);
      expect(isApiError(undefined)).toBe(false);
      expect(isApiError('string')).toBe(false);
    });

    it('returns false for axios error that is not ApiError', () => {
      const error = new Error('Axios error');
      expect(isApiError(error)).toBe(false);
    });
  });

  describe('getErrorMessage', () => {
    it('returns message from ApiError', () => {
      const error = new ApiError('API error message', 500);
      expect(getErrorMessage(error)).toBe('API error message');
    });

    it('returns message from regular Error', () => {
      const error = new Error('Regular error message');
      expect(getErrorMessage(error)).toBe('Regular error message');
    });

    it('returns default message for unknown types', () => {
      expect(getErrorMessage(null)).toBe('An unexpected error occurred.');
      expect(getErrorMessage(undefined)).toBe('An unexpected error occurred.');
      expect(getErrorMessage('string')).toBe('An unexpected error occurred.');
      expect(getErrorMessage({})).toBe('An unexpected error occurred.');
    });

    it('handles empty error message', () => {
      const error = new ApiError('', 500);
      expect(getErrorMessage(error)).toBe('');
    });

    it('handles complex error objects', () => {
      const error = { message: 'test' } as unknown;
      expect(getErrorMessage(error)).toBe('An unexpected error occurred.');
    });
  });

  describe('getValidationErrors', () => {
    it('returns validation errors from ApiError with VALIDATION_ERROR code', () => {
      const details = { email: ['Invalid email'], password: ['Too short'] };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', details);
      expect(getValidationErrors(error)).toEqual(details);
    });

    it('returns null for ApiError with different code', () => {
      const error = new ApiError('Server error', 500, 'SERVER_ERROR', null);
      expect(getValidationErrors(error)).toBeNull();
    });

    it('returns null when no details present', () => {
      const error = new ApiError('Validation error', 422, 'VALIDATION_ERROR', null);
      expect(getValidationErrors(error)).toBeNull();
    });

    it('returns null for regular Error', () => {
      const error = new Error('Test');
      expect(getValidationErrors(error)).toBeNull();
    });

    it('returns null for non-errors', () => {
      expect(getValidationErrors(null)).toBeNull();
      expect(getValidationErrors(undefined)).toBeNull();
      expect(getValidationErrors('string')).toBeNull();
    });

    it('handles validation error with multiple field errors', () => {
      const details = {
        email: ['Invalid email format'],
        password: ['Too short', 'Must contain uppercase'],
        phone: ['Invalid format'],
      };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', details);
      const result = getValidationErrors(error);

      expect(result).toEqual(details);
      expect(result?.email).toEqual(['Invalid email format']);
      expect(result?.password).toEqual(['Too short', 'Must contain uppercase']);
    });
  });

  describe('API Error Codes', () => {
    it('defines VALIDATION_ERROR code', () => {
      const error = new ApiError('Validation error', 422, 'VALIDATION_ERROR');
      expect(error.code).toBe('VALIDATION_ERROR');
    });

    it('defines UNAUTHORIZED code', () => {
      const error = new ApiError('Unauthorized', 401, 'UNAUTHORIZED');
      expect(error.code).toBe('UNAUTHORIZED');
    });

    it('defines FORBIDDEN code', () => {
      const error = new ApiError('Forbidden', 403, 'FORBIDDEN');
      expect(error.code).toBe('FORBIDDEN');
    });

    it('defines NOT_FOUND code', () => {
      const error = new ApiError('Not found', 404, 'NOT_FOUND');
      expect(error.code).toBe('NOT_FOUND');
    });

    it('defines RATE_LIMITED code', () => {
      const error = new ApiError('Rate limited', 429, 'RATE_LIMITED');
      expect(error.code).toBe('RATE_LIMITED');
    });

    it('defines SERVER_ERROR code', () => {
      const error = new ApiError('Server error', 500, 'SERVER_ERROR');
      expect(error.code).toBe('SERVER_ERROR');
    });

    it('defines TIMEOUT code', () => {
      const error = new ApiError('Timeout', 0, 'TIMEOUT');
      expect(error.code).toBe('TIMEOUT');
    });

    it('defines NETWORK_ERROR code', () => {
      const error = new ApiError('Network error', 0, 'NETWORK_ERROR');
      expect(error.code).toBe('NETWORK_ERROR');
    });

    it('defines CONFIG_ERROR code', () => {
      const error = new ApiError('Config error', 0, 'CONFIG_ERROR');
      expect(error.code).toBe('CONFIG_ERROR');
    });
  });

  describe('HTTP Status Codes', () => {
    it('handles 400 Bad Request', () => {
      const error = new ApiError('Bad request', 400, 'BAD_REQUEST');
      expect(error.status).toBe(400);
    });

    it('handles 401 Unauthorized', () => {
      const error = new ApiError('Unauthorized', 401, 'UNAUTHORIZED');
      expect(error.status).toBe(401);
    });

    it('handles 403 Forbidden', () => {
      const error = new ApiError('Forbidden', 403, 'FORBIDDEN');
      expect(error.status).toBe(403);
    });

    it('handles 404 Not Found', () => {
      const error = new ApiError('Not found', 404, 'NOT_FOUND');
      expect(error.status).toBe(404);
    });

    it('handles 422 Unprocessable Entity', () => {
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR');
      expect(error.status).toBe(422);
    });

    it('handles 429 Too Many Requests', () => {
      const error = new ApiError('Rate limited', 429, 'RATE_LIMITED');
      expect(error.status).toBe(429);
    });

    it('handles 500 Internal Server Error', () => {
      const error = new ApiError('Server error', 500, 'SERVER_ERROR');
      expect(error.status).toBe(500);
    });

    it('handles 503 Service Unavailable', () => {
      const error = new ApiError('Service unavailable', 503, 'SERVER_ERROR');
      expect(error.status).toBe(503);
    });
  });

  describe('Error Details Structure', () => {
    it('stores error details as record of string arrays', () => {
      const details = {
        name: ['Name is required'],
        email: ['Invalid email format'],
      };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', details);

      expect(error.details).toEqual(details);
      expect(Array.isArray(error.details?.name)).toBe(true);
      expect(Array.isArray(error.details?.email)).toBe(true);
    });

    it('handles null details gracefully', () => {
      const error = new ApiError('Server error', 500, 'SERVER_ERROR', null);
      expect(error.details).toBeNull();
    });

    it('preserves details through error creation', () => {
      const originalDetails = {
        phone: ['Invalid format', 'Must start with 01'],
      };
      const error = new ApiError('Validation failed', 422, 'VALIDATION_ERROR', originalDetails);
      const result = getValidationErrors(error);

      expect(result).toEqual(originalDetails);
      expect(result?.phone).toEqual(['Invalid format', 'Must start with 01']);
    });
  });
});
