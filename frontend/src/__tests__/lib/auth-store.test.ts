import { useAuthStore } from '@/lib/stores/auth';

// Mock the auth API
jest.mock('@/lib/api/auth', () => ({
  authApi: {
    login: jest.fn(),
    register: jest.fn(),
    logout: jest.fn().mockResolvedValue({}),
    me: jest.fn(),
  },
}));

// Reset the store before each test
beforeEach(() => {
  useAuthStore.setState({
    user: null,
    isAuthenticated: false,
    isLoading: false,
    error: null,
  });
  jest.clearAllMocks();
});

describe('Auth Store', () => {
  describe('Initial State', () => {
    it('should have null user initially', () => {
      const { user } = useAuthStore.getState();
      expect(user).toBeNull();
    });

    it('should not be authenticated initially', () => {
      const { isAuthenticated } = useAuthStore.getState();
      expect(isAuthenticated).toBe(false);
    });

    it('should not be loading initially', () => {
      const { isLoading } = useAuthStore.getState();
      expect(isLoading).toBe(false);
    });

    it('should have no error initially', () => {
      const { error } = useAuthStore.getState();
      expect(error).toBeNull();
    });
  });

  describe('setUser', () => {
    it('should set user correctly', () => {
      const mockUser = {
        id: 1,
        name: 'Test User',
        phone: '01234567890',
        role: 'patient' as const,
      };

      useAuthStore.getState().setUser(mockUser);

      const { user, isAuthenticated } = useAuthStore.getState();
      expect(user).toEqual(mockUser);
      expect(isAuthenticated).toBe(true);
    });

    it('should clear user and authentication when passed null', () => {
      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        isAuthenticated: true,
      });

      useAuthStore.getState().setUser(null);

      const { user, isAuthenticated } = useAuthStore.getState();
      expect(user).toBeNull();
      expect(isAuthenticated).toBe(false);
    });
  });

  describe('logout', () => {
    it('should clear user on logout', async () => {
      // Set initial state
      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        isAuthenticated: true,
      });

      await useAuthStore.getState().logout();

      const { user, isAuthenticated } = useAuthStore.getState();
      expect(user).toBeNull();
      expect(isAuthenticated).toBe(false);
    });

    it('should call auth API logout', async () => {
      const { authApi } = require('@/lib/api/auth');

      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        isAuthenticated: true,
      });

      await useAuthStore.getState().logout();

      expect(authApi.logout).toHaveBeenCalled();
    });

    it('should handle logout errors gracefully', async () => {
      const { authApi } = require('@/lib/api/auth');
      authApi.logout.mockRejectedValueOnce(new Error('Logout failed'));

      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        isAuthenticated: true,
      });

      // Should not throw
      await useAuthStore.getState().logout();

      const { user, isAuthenticated } = useAuthStore.getState();
      expect(user).toBeNull();
      expect(isAuthenticated).toBe(false);
    });
  });

  describe('clearError', () => {
    it('should clear error state', () => {
      useAuthStore.setState({ error: 'Some error' });

      useAuthStore.getState().clearError();

      const { error } = useAuthStore.getState();
      expect(error).toBeNull();
    });
  });
});
