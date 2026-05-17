import { z } from 'zod';

type AuthMsg =
  | 'phoneRequired'
  | 'phoneInvalid'
  | 'passwordRequired'
  | 'passwordMinLength'
  | 'nameRequired'
  | 'nameMinLength'
  | 'emailInvalid'
  | 'passwordsDoNotMatch'
  | 'confirmPasswordRequired'
  | 'currentPasswordRequired'
  | 'otpDigits';

type Translator = (key: AuthMsg) => string;

const fallback: Record<AuthMsg, string> = {
  phoneRequired: 'Phone number is required',
  phoneInvalid: 'Invalid phone number format',
  passwordRequired: 'Password is required',
  passwordMinLength: 'Password must be at least 8 characters',
  nameRequired: 'Name is required',
  nameMinLength: 'Name must be at least 2 characters',
  emailInvalid: 'Invalid email',
  passwordsDoNotMatch: 'Passwords do not match',
  confirmPasswordRequired: 'Please confirm your password',
  currentPasswordRequired: 'Current password is required',
  otpDigits: 'OTP must be 6 digits',
};

const tr = (t?: Translator): Translator => t ?? ((k: AuthMsg) => fallback[k]);

const PHONE_REGEX = /^01[0-9]{9}$/;

export const createLoginSchema = (t?: Translator) => {
  const tx = tr(t);
  return z.object({
    phone: z
      .string()
      .min(1, tx('phoneRequired'))
      .regex(PHONE_REGEX, tx('phoneInvalid')),
    password: z.string().min(1, tx('passwordRequired')),
  });
};

export const createRegisterSchema = (t?: Translator) => {
  const tx = tr(t);
  return z
    .object({
      name: z.string().min(1, tx('nameRequired')).min(2, tx('nameMinLength')),
      phone: z
        .string()
        .min(1, tx('phoneRequired'))
        .regex(PHONE_REGEX, tx('phoneInvalid')),
      email: z.string().email(tx('emailInvalid')).optional().or(z.literal('')),
      password: z.string().min(8, tx('passwordMinLength')),
      password_confirmation: z.string().min(1, tx('confirmPasswordRequired')),
    })
    .refine((data) => data.password === data.password_confirmation, {
      message: tx('passwordsDoNotMatch'),
      path: ['password_confirmation'],
    });
};

export const createForgotPasswordSchema = (t?: Translator) => {
  const tx = tr(t);
  return z.object({
    phone: z
      .string()
      .min(1, tx('phoneRequired'))
      .regex(PHONE_REGEX, tx('phoneInvalid')),
  });
};

export const createVerifyOtpSchema = (t?: Translator) => {
  const tx = tr(t);
  return z.object({
    phone: z.string(),
    otp: z.string().length(6, tx('otpDigits')),
  });
};

export const createResetPasswordSchema = (t?: Translator) => {
  const tx = tr(t);
  return z
    .object({
      phone: z.string(),
      otp: z.string(),
      password: z.string().min(8, tx('passwordMinLength')),
      password_confirmation: z.string().min(1, tx('confirmPasswordRequired')),
    })
    .refine((data) => data.password === data.password_confirmation, {
      message: tx('passwordsDoNotMatch'),
      path: ['password_confirmation'],
    });
};

export const createChangePasswordSchema = (t?: Translator) => {
  const tx = tr(t);
  return z
    .object({
      current_password: z.string().min(1, tx('currentPasswordRequired')),
      password: z.string().min(8, tx('passwordMinLength')),
      password_confirmation: z.string().min(1, tx('confirmPasswordRequired')),
    })
    .refine((data) => data.password === data.password_confirmation, {
      message: tx('passwordsDoNotMatch'),
      path: ['password_confirmation'],
    });
};

// Static fallback schemas (English) — used by unit tests that import the
// schema directly without a translator. Keep them in sync with the factories.
export const loginSchema = createLoginSchema();
export const registerSchema = createRegisterSchema();
export const forgotPasswordSchema = createForgotPasswordSchema();
export const verifyOtpSchema = createVerifyOtpSchema();
export const resetPasswordSchema = createResetPasswordSchema();
export const changePasswordSchema = createChangePasswordSchema();

export type LoginFormData = z.infer<ReturnType<typeof createLoginSchema>>;
export type RegisterFormData = z.infer<ReturnType<typeof createRegisterSchema>>;
export type ForgotPasswordFormData = z.infer<ReturnType<typeof createForgotPasswordSchema>>;
export type VerifyOtpFormData = z.infer<ReturnType<typeof createVerifyOtpSchema>>;
export type ResetPasswordFormData = z.infer<ReturnType<typeof createResetPasswordSchema>>;
export type ChangePasswordFormData = z.infer<ReturnType<typeof createChangePasswordSchema>>;
