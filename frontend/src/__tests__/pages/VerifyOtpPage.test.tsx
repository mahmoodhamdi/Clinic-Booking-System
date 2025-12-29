import { render, screen, fireEvent, waitFor } from '@/__tests__/utils/test-utils';
import userEvent from '@testing-library/user-event';
import VerifyOtpPage from '@/app/(auth)/verify-otp/page';
import { authApi } from '@/lib/api/auth';

// Mock next/navigation
const mockPush = jest.fn();
const mockBack = jest.fn();
jest.mock('next/navigation', () => ({
  useRouter: () => ({
    push: mockPush,
    back: mockBack,
  }),
}));

// Mock next-intl
jest.mock('next-intl', () => ({
  useTranslations: () => (key: string) => key,
}));

// Mock the auth API
jest.mock('@/lib/api/auth', () => ({
  authApi: {
    verifyOtp: jest.fn(),
    forgotPassword: jest.fn(),
  },
}));

// Mock sonner toast
jest.mock('sonner', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}));

describe('VerifyOtpPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Set up session storage with phone number
    sessionStorage.setItem('reset_phone', '01012345678');
  });

  afterEach(() => {
    sessionStorage.clear();
  });

  it('redirects to forgot-password if no phone in session', async () => {
    sessionStorage.clear();
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith('/forgot-password');
    });
  });

  it('renders 6 OTP input fields', async () => {
    render(<VerifyOtpPage />);

    await waitFor(() => {
      const inputs = screen.getAllByRole('textbox');
      expect(inputs).toHaveLength(6);
    });
  });

  it('displays masked phone number', async () => {
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getByText('010****678')).toBeInTheDocument();
    });
  });

  it('auto-focuses next input on entry', async () => {
    const user = userEvent.setup();
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getAllByRole('textbox')).toHaveLength(6);
    });

    const inputs = screen.getAllByRole('textbox');
    await user.type(inputs[0], '1');

    // After typing, the value should be set and focus should move
    await waitFor(() => {
      expect(inputs[0]).toHaveValue('1');
    });
  });

  it('only allows numeric input', async () => {
    const user = userEvent.setup();
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getAllByRole('textbox')).toHaveLength(6);
    });

    const inputs = screen.getAllByRole('textbox');
    await user.type(inputs[0], 'a');

    expect(inputs[0]).toHaveValue('');
  });

  it('handles backspace to focus previous input', async () => {
    const user = userEvent.setup();
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getAllByRole('textbox')).toHaveLength(6);
    });

    const inputs = screen.getAllByRole('textbox');
    await user.type(inputs[0], '1');
    await user.type(inputs[1], '2');
    await user.keyboard('{Backspace}');
    await user.keyboard('{Backspace}');

    expect(inputs[0]).toHaveFocus();
  });

  it('auto-submits when 6 digits are entered', async () => {
    const user = userEvent.setup();
    (authApi.verifyOtp as jest.Mock).mockResolvedValue({
      data: { verified: true },
    });

    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getAllByRole('textbox')).toHaveLength(6);
    });

    const inputs = screen.getAllByRole('textbox');
    for (let i = 0; i < 6; i++) {
      await user.type(inputs[i], String(i + 1));
    }

    await waitFor(() => {
      expect(authApi.verifyOtp).toHaveBeenCalledWith({
        phone: '01012345678',
        otp: '123456',
      });
    });
  });

  it('handles paste of full OTP', async () => {
    const user = userEvent.setup();
    (authApi.verifyOtp as jest.Mock).mockResolvedValue({
      data: { verified: true },
    });

    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getAllByRole('textbox')).toHaveLength(6);
    });

    const inputs = screen.getAllByRole('textbox');
    await user.click(inputs[0]);

    // Simulate paste
    fireEvent.paste(inputs[0].parentElement!, {
      clipboardData: {
        getData: () => '123456',
      },
    });

    await waitFor(() => {
      expect(inputs[0]).toHaveValue('1');
      expect(inputs[5]).toHaveValue('6');
    });
  });

  it('shows resend button after countdown', async () => {
    render(<VerifyOtpPage />);

    await waitFor(() => {
      expect(screen.getByText('resendOtp')).toBeInTheDocument();
    });
  });

  it('has a back link to forgot-password', async () => {
    render(<VerifyOtpPage />);

    await waitFor(() => {
      const backLink = screen.getByRole('link', { name: /back/i });
      expect(backLink).toHaveAttribute('href', '/forgot-password');
    });
  });

  it('disables submit button when OTP is incomplete', async () => {
    render(<VerifyOtpPage />);

    await waitFor(() => {
      const submitButton = screen.getByRole('button', { name: 'verifyOtp' });
      expect(submitButton).toBeDisabled();
    });
  });
});
