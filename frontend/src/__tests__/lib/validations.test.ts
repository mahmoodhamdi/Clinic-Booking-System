import { loginSchema, registerSchema, forgotPasswordSchema, verifyOtpSchema } from '@/lib/validations/auth';

describe('Auth Validations', () => {
  describe('loginSchema', () => {
    it('should validate correct login data', () => {
      const validData = {
        phone: '01234567890',
        password: 'password123',
      };

      const result = loginSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject empty phone', () => {
      const invalidData = {
        phone: '',
        password: 'password123',
      };

      const result = loginSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject invalid phone format', () => {
      const invalidData = {
        phone: '12345', // not Egyptian format
        password: 'password123',
      };

      const result = loginSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject empty password', () => {
      const invalidData = {
        phone: '01234567890',
        password: '',
      };

      const result = loginSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept any non-empty password (no minimum length for login)', () => {
      const validData = {
        phone: '01234567890',
        password: '123', // short but allowed for login
      };

      const result = loginSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });
  });

  describe('registerSchema', () => {
    it('should validate correct registration data', () => {
      const validData = {
        name: 'Test User',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject short name (less than 2 chars)', () => {
      const invalidData = {
        name: 'A',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject short password (less than 6 chars)', () => {
      const invalidData = {
        name: 'Test User',
        phone: '01234567890',
        password: '12345',
        password_confirmation: '12345',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject mismatched passwords', () => {
      const invalidData = {
        name: 'Test User',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'different123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject empty fields', () => {
      const invalidData = {
        name: '',
        phone: '',
        password: '',
        password_confirmation: '',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept optional email', () => {
      const validData = {
        name: 'Test User',
        phone: '01234567890',
        email: '',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should validate email format when provided', () => {
      const invalidData = {
        name: 'Test User',
        phone: '01234567890',
        email: 'invalid-email',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });
  });

  describe('forgotPasswordSchema', () => {
    it('should validate correct phone', () => {
      const validData = {
        phone: '01234567890',
      };

      const result = forgotPasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject invalid phone format', () => {
      const invalidData = {
        phone: '12345',
      };

      const result = forgotPasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });
  });

  describe('verifyOtpSchema', () => {
    it('should validate correct OTP', () => {
      const validData = {
        phone: '01234567890',
        otp: '123456',
      };

      const result = verifyOtpSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject OTP with wrong length', () => {
      const invalidData = {
        phone: '01234567890',
        otp: '12345', // 5 digits instead of 6
      };

      const result = verifyOtpSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });
  });
});
