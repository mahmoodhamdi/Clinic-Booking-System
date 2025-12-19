import { z } from 'zod';

export const loginSchema = z.object({
  phone: z
    .string()
    .min(1, 'Phone number is required')
    .regex(/^01[0-9]{9}$/, 'Invalid phone number format'),
  password: z.string().min(1, 'Password is required'),
});

export const registerSchema = z
  .object({
    name: z.string().min(1, 'Name is required').min(2, 'Name must be at least 2 characters'),
    phone: z
      .string()
      .min(1, 'Phone number is required')
      .regex(/^01[0-9]{9}$/, 'Invalid phone number format'),
    email: z.string().email('Invalid email').optional().or(z.literal('')),
    password: z.string().min(6, 'Password must be at least 6 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

export const forgotPasswordSchema = z.object({
  phone: z
    .string()
    .min(1, 'Phone number is required')
    .regex(/^01[0-9]{9}$/, 'Invalid phone number format'),
});

export const verifyOtpSchema = z.object({
  phone: z.string(),
  otp: z.string().length(6, 'OTP must be 6 digits'),
});

export const resetPasswordSchema = z
  .object({
    phone: z.string(),
    otp: z.string(),
    password: z.string().min(6, 'Password must be at least 6 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

export const changePasswordSchema = z
  .object({
    current_password: z.string().min(1, 'Current password is required'),
    password: z.string().min(6, 'Password must be at least 6 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

export type LoginFormData = z.infer<typeof loginSchema>;
export type RegisterFormData = z.infer<typeof registerSchema>;
export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;
export type VerifyOtpFormData = z.infer<typeof verifyOtpSchema>;
export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;
export type ChangePasswordFormData = z.infer<typeof changePasswordSchema>;
