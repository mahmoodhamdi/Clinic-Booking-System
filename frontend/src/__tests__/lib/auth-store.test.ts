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
    token: null,
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

    it('should have null token initially', () => {
      const { token } = useAuthStore.getState();
      expect(token).toBeNull();
    });

    it('should not be authenticated initially', () => {
      const { isAuthenticated } = useAuthStore.getState();
      expect(isAuthenticated).toBe(false);
    });

    it('should not be loading initially', () => {
      const { isLoading } = useAuthStore.getState();
      expect(isLoading).toBe(false);
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

      const { user } = useAuthStore.getState();
      expect(user).toEqual(mockUser);
    });

    it('should clear user when passed null', () => {
      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
      });

      useAuthStore.getState().setUser(null);

      const { user } = useAuthStore.getState();
      expect(user).toBeNull();
    });
  });

  describe('setToken', () => {
    it('should set token and isAuthenticated correctly', () => {
      const mockToken = 'test-token-123';

      useAuthStore.getState().setToken(mockToken);

      const { token, isAuthenticated } = useAuthStore.getState();
      expect(token).toBe(mockToken);
      expect(isAuthenticated).toBe(true);
    });

    it('should store token in localStorage', () => {
      const mockToken = 'test-token-123';

      useAuthStore.getState().setToken(mockToken);

      expect(localStorage.setItem).toHaveBeenCalledWith('token', mockToken);
    });

    it('should clear authentication when token is null', () => {
      useAuthStore.setState({ token: 'test-token', isAuthenticated: true });

      useAuthStore.getState().setToken(null);

      const { token, isAuthenticated } = useAuthStore.getState();
      expect(token).toBeNull();
      expect(isAuthenticated).toBe(false);
    });
  });

  describe('logout', () => {
    it('should clear user and token on logout', async () => {
      // Set initial state
      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        token: 'test-token',
        isAuthenticated: true,
      });

      await useAuthStore.getState().logout();

      const { user, token, isAuthenticated } = useAuthStore.getState();
      expect(user).toBeNull();
      expect(token).toBeNull();
      expect(isAuthenticated).toBe(false);
    });

    it('should remove token from localStorage on logout', async () => {
      useAuthStore.setState({
        user: { id: 1, name: 'Test', phone: '123', role: 'patient' },
        token: 'test-token',
        isAuthenticated: true,
      });

      await useAuthStore.getState().logout();

      expect(localStorage.removeItem).toHaveBeenCalledWith('token');
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
