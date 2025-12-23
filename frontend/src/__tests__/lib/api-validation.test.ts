import {
  validateResponse,
  validateResponseStrict,
  userSchema,
  appointmentSchema,
  apiResponseSchema,
  paginatedResponseSchema,
} from '@/lib/validations/api-responses';

describe('API Response Validation', () => {
  describe('userSchema', () => {
    const validUser = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      phone: '01234567890',
      role: 'patient' as const,
      avatar: null,
      is_active: true,
      date_of_birth: null,
      gender: null,
      address: null,
      phone_verified_at: null,
      created_at: '2025-01-01T00:00:00.000Z',
    };

    test('validates valid user', () => {
      const result = userSchema.safeParse(validUser);
      expect(result.success).toBe(true);
    });

    test('validates user with all optional fields', () => {
      const userWithOptional = {
        ...validUser,
        email: 'test@example.com',
        avatar: '/avatars/test.jpg',
        date_of_birth: '1990-01-01',
        gender: 'male' as const,
        address: '123 Test St',
        phone_verified_at: '2025-01-01T00:00:00.000Z',
      };
      const result = userSchema.safeParse(userWithOptional);
      expect(result.success).toBe(true);
    });

    test('rejects invalid role', () => {
      const invalidUser = { ...validUser, role: 'invalid' };
      const result = userSchema.safeParse(invalidUser);
      expect(result.success).toBe(false);
    });

    test('rejects missing required fields', () => {
      const { id, ...userWithoutId } = validUser;
      const result = userSchema.safeParse(userWithoutId);
      expect(result.success).toBe(false);
    });
  });

  describe('appointmentSchema', () => {
    const validAppointment = {
      id: 1,
      status: 'pending' as const,
      notes: null,
      admin_notes: null,
      cancellation_reason: null,
      cancelled_at: null,
      created_at: '2025-01-01T00:00:00.000Z',
    };

    test('validates valid appointment', () => {
      const result = appointmentSchema.safeParse(validAppointment);
      expect(result.success).toBe(true);
    });

    test('validates all appointment statuses', () => {
      const statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
      statuses.forEach((status) => {
        const appointment = { ...validAppointment, status };
        const result = appointmentSchema.safeParse(appointment);
        expect(result.success).toBe(true);
      });
    });
  });

  describe('apiResponseSchema', () => {
    test('validates valid API response', () => {
      const response = {
        success: true,
        message: 'Success',
        data: { id: 1, name: 'Test' },
      };

      const schema = apiResponseSchema(
        userSchema.pick({ id: true, name: true })
      );
      const result = schema.safeParse(response);
      expect(result.success).toBe(true);
    });

    test('validates response without optional message', () => {
      const response = {
        success: true,
        data: { id: 1, name: 'Test' },
      };

      const schema = apiResponseSchema(
        userSchema.pick({ id: true, name: true })
      );
      const result = schema.safeParse(response);
      expect(result.success).toBe(true);
    });
  });

  describe('paginatedResponseSchema', () => {
    test('validates valid paginated response', () => {
      const response = {
        success: true,
        data: [
          {
            id: 1,
            status: 'pending',
            notes: null,
            admin_notes: null,
            cancellation_reason: null,
            cancelled_at: null,
            created_at: '2025-01-01T00:00:00.000Z',
          },
        ],
        meta: {
          current_page: 1,
          from: 1,
          last_page: 1,
          per_page: 15,
          to: 1,
          total: 1,
        },
      };

      const schema = paginatedResponseSchema(appointmentSchema);
      const result = schema.safeParse(response);
      expect(result.success).toBe(true);
    });

    test('validates empty paginated response', () => {
      const response = {
        success: true,
        data: [],
        meta: {
          current_page: 1,
          from: null,
          last_page: 1,
          per_page: 15,
          to: null,
          total: 0,
        },
      };

      const schema = paginatedResponseSchema(appointmentSchema);
      const result = schema.safeParse(response);
      expect(result.success).toBe(true);
    });
  });

  describe('validateResponse', () => {
    test('returns data when valid', () => {
      const data = { id: 1, name: 'Test' };
      const schema = userSchema.pick({ id: true, name: true });
      const result = validateResponse(schema, data);
      expect(result).toEqual(data);
    });

    test('returns data even when invalid (with console error)', () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation();
      const data = { invalid: 'data' };
      const schema = userSchema.pick({ id: true, name: true });
      const result = validateResponse(schema, data);
      expect(result).toEqual(data);
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  describe('validateResponseStrict', () => {
    test('returns data when valid', () => {
      const data = { id: 1, name: 'Test' };
      const schema = userSchema.pick({ id: true, name: true });
      const result = validateResponseStrict(schema, data);
      expect(result).toEqual(data);
    });

    test('throws when invalid', () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation();
      const data = { invalid: 'data' };
      const schema = userSchema.pick({ id: true, name: true });
      expect(() => validateResponseStrict(schema, data)).toThrow(
        'Invalid API response format'
      );
      consoleSpy.mockRestore();
    });
  });
});
