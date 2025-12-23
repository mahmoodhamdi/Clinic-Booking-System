import { authApi } from '@/lib/api/auth';
import api from '@/lib/api/client';
import { createUser, wrapInApiResponse } from '@/__tests__/factories';

// Mock the API client
jest.mock('@/lib/api/client', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    delete: jest.fn(),
  },
}));

const mockApi = api as jest.Mocked<typeof api>;

describe('authApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('login', () => {
    it('should call POST /auth/login with credentials', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'Login successful',
          data: {
            user: createUser(),
            token: 'test-token',
          },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const credentials = { phone: '01234567890', password: 'password123' };
      const result = await authApi.login(credentials);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/login', credentials);
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle login failure', async () => {
      const error = new Error('Invalid credentials');
      mockApi.post.mockRejectedValueOnce(error);

      const credentials = { phone: '01234567890', password: 'wrong' };
      await expect(authApi.login(credentials)).rejects.toThrow('Invalid credentials');
    });
  });

  describe('register', () => {
    it('should call POST /auth/register with registration data', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'Registration successful',
          data: {
            user: createUser(),
            token: 'test-token',
          },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const registerData = {
        name: 'Test User',
        phone: '01234567890',
        password: 'password123',
        password_confirmation: 'password123',
        email: 'test@example.com',
      };
      const result = await authApi.register(registerData);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/register', registerData);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('logout', () => {
    it('should call POST /auth/logout', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'Logged out successfully'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await authApi.logout();

      expect(mockApi.post).toHaveBeenCalledWith('/auth/logout');
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('me', () => {
    it('should call GET /auth/me', async () => {
      const user = createUser();
      const mockResponse = {
        data: wrapInApiResponse(user),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await authApi.me();

      expect(mockApi.get).toHaveBeenCalledWith('/auth/me');
      expect(result.data).toEqual(user);
    });
  });

  describe('forgotPassword', () => {
    it('should call POST /auth/forgot-password', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'OTP sent'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await authApi.forgotPassword({ phone: '01234567890' });

      expect(mockApi.post).toHaveBeenCalledWith('/auth/forgot-password', { phone: '01234567890' });
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('verifyOtp', () => {
    it('should call POST /auth/verify-otp', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ verified: true }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const data = { phone: '01234567890', otp: '123456' };
      const result = await authApi.verifyOtp(data);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/verify-otp', data);
      expect(result.data?.verified).toBe(true);
    });

    it('should handle invalid OTP', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ verified: false }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const data = { phone: '01234567890', otp: 'wrong' };
      const result = await authApi.verifyOtp(data);

      expect(result.data?.verified).toBe(false);
    });
  });

  describe('resetPassword', () => {
    it('should call POST /auth/reset-password', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'Password reset successful'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const data = {
        phone: '01234567890',
        otp: '123456',
        password: 'newpassword123',
        password_confirmation: 'newpassword123',
      };
      const result = await authApi.resetPassword(data);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/reset-password', data);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('changePassword', () => {
    it('should call POST /auth/change-password', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'Password changed'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const data = {
        current_password: 'oldpassword',
        password: 'newpassword123',
        password_confirmation: 'newpassword123',
      };
      const result = await authApi.changePassword(data);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/change-password', data);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('updateProfile', () => {
    it('should call PUT /auth/profile', async () => {
      const updatedUser = createUser({ name: 'Updated Name' });
      const mockResponse = {
        data: wrapInApiResponse(updatedUser),
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const data = { name: 'Updated Name', email: 'new@example.com' };
      const result = await authApi.updateProfile(data);

      expect(mockApi.put).toHaveBeenCalledWith('/auth/profile', data);
      expect(result.data?.name).toBe('Updated Name');
    });
  });

  describe('uploadAvatar', () => {
    it('should call POST /auth/avatar with FormData', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ avatar: 'https://example.com/avatar.jpg' }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const file = new File(['test'], 'avatar.jpg', { type: 'image/jpeg' });
      const result = await authApi.uploadAvatar(file);

      expect(mockApi.post).toHaveBeenCalledWith('/auth/avatar', expect.any(FormData));
      expect(result.data?.avatar).toBe('https://example.com/avatar.jpg');
    });

    it('should append file to FormData correctly', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ avatar: 'https://example.com/avatar.jpg' }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const file = new File(['test'], 'avatar.jpg', { type: 'image/jpeg' });
      await authApi.uploadAvatar(file);

      const calledFormData = mockApi.post.mock.calls[0][1] as FormData;
      expect(calledFormData.get('avatar')).toBe(file);
    });
  });

  describe('deleteAccount', () => {
    it('should call DELETE /auth/account', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'Account deleted'),
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      const result = await authApi.deleteAccount();

      expect(mockApi.delete).toHaveBeenCalledWith('/auth/account');
      expect(result).toEqual(mockResponse.data);
    });
  });
});
