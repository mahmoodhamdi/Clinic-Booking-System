import {
  resetPasswordSchema,
  changePasswordSchema,
  registerSchema,
} from '@/lib/validations/auth';

describe('Auth Validations Extended', () => {
  describe('resetPasswordSchema', () => {
    it('should validate correct reset password data', () => {
      const validData = {
        phone: '01234567890',
        otp: '123456',
        password: 'newPassword123',
        password_confirmation: 'newPassword123',
      };

      const result = resetPasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject when passwords do not match', () => {
      const invalidData = {
        phone: '01234567890',
        otp: '123456',
        password: 'newPassword123',
        password_confirmation: 'differentPassword123',
      };

      const result = resetPasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
      if (!result.success) {
        const errors = result.error.flatten();
        expect(errors.formErrors?.[0] || Object.values(errors.fieldErrors).flat()[0]).toBeDefined();
      }
    });

    it('should reject password less than 6 characters', () => {
      const invalidData = {
        phone: '01234567890',
        otp: '123456',
        password: '12345',
        password_confirmation: '12345',
      };

      const result = resetPasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should require password_confirmation field', () => {
      const invalidData = {
        phone: '01234567890',
        otp: '123456',
        password: 'newPassword123',
      };

      const result = resetPasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept exactly 6 character password', () => {
      const validData = {
        phone: '01234567890',
        otp: '123456',
        password: '123456',
        password_confirmation: '123456',
      };

      const result = resetPasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should accept long passwords', () => {
      const validData = {
        phone: '01234567890',
        otp: '123456',
        password: 'veryLongPassword123WithManyCharacters!@#$%^&*()',
        password_confirmation: 'veryLongPassword123WithManyCharacters!@#$%^&*()',
      };

      const result = resetPasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });
  });

  describe('changePasswordSchema', () => {
    it('should validate correct change password data', () => {
      const validData = {
        current_password: 'oldPassword123',
        password: 'newPassword123',
        password_confirmation: 'newPassword123',
      };

      const result = changePasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should reject when passwords do not match', () => {
      const invalidData = {
        current_password: 'oldPassword123',
        password: 'newPassword123',
        password_confirmation: 'differentPassword123',
      };

      const result = changePasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
      if (!result.success) {
        const errors = result.error.flatten();
        expect(errors.formErrors?.[0] || Object.values(errors.fieldErrors).flat()[0]).toBeDefined();
      }
    });

    it('should require all fields', () => {
      const invalidData = {
        current_password: 'oldPassword123',
        password: 'newPassword123',
        // missing password_confirmation
      };

      const result = changePasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should require current_password', () => {
      const invalidData = {
        current_password: '',
        password: 'newPassword123',
        password_confirmation: 'newPassword123',
      };

      const result = changePasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should reject new password less than 6 characters', () => {
      const invalidData = {
        current_password: 'oldPassword123',
        password: '12345',
        password_confirmation: '12345',
      };

      const result = changePasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept exactly 6 character new password', () => {
      const validData = {
        current_password: 'oldPassword123',
        password: '123456',
        password_confirmation: '123456',
      };

      const result = changePasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should allow any non-empty current_password', () => {
      const validData = {
        current_password: '123',
        password: 'newPassword123',
        password_confirmation: 'newPassword123',
      };

      const result = changePasswordSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should require password_confirmation field', () => {
      const invalidData = {
        current_password: 'oldPassword123',
        password: 'newPassword123',
        password_confirmation: '',
      };

      const result = changePasswordSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });
  });

  describe('registerSchema extended validation', () => {
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

    it('should require phone number', () => {
      const invalidData = {
        name: 'Test User',
        phone: '',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should validate password confirmation matches', () => {
      const invalidData = {
        name: 'Test User',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'differentPassword',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
      if (!result.success) {
        const errors = result.error.flatten();
        expect(errors.formErrors?.[0] || Object.values(errors.fieldErrors).flat()[0]).toBeDefined();
      }
    });

    it('should accept optional email field', () => {
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
        email: 'not-an-email',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept valid email', () => {
      const validData = {
        name: 'Test User',
        phone: '01234567890',
        email: 'test@example.com',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should require name with minimum 2 characters', () => {
      const invalidData = {
        name: 'A',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept exactly 2 character name', () => {
      const validData = {
        name: 'Ab',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should require password with minimum 6 characters', () => {
      const invalidData = {
        name: 'Test User',
        phone: '01234567890',
        password: '12345',
        password_confirmation: '12345',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept exactly 6 character password', () => {
      const validData = {
        name: 'Test User',
        phone: '01234567890',
        password: '123456',
        password_confirmation: '123456',
      };

      const result = registerSchema.safeParse(validData);
      expect(result.success).toBe(true);
    });

    it('should validate Egyptian phone format', () => {
      const invalidData = {
        name: 'Test User',
        phone: '12345', // not Egyptian format
        password: 'password123',
        password_confirmation: 'password123',
      };

      const result = registerSchema.safeParse(invalidData);
      expect(result.success).toBe(false);
    });

    it('should accept valid Egyptian phone formats', () => {
      const validPhoneNumbers = [
        '01000000000',
        '01100000000',
        '01200000000',
        '01111234567',
        '01999999999',
      ];

      validPhoneNumbers.forEach((phone) => {
        const validData = {
          name: 'Test User',
          phone,
          password: 'password123',
          password_confirmation: 'password123',
        };

        const result = registerSchema.safeParse(validData);
        expect(result.success).toBe(true);
      });
    });
  });
});
