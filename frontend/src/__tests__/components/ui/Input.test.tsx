import { render, screen, fireEvent } from '@/__tests__/utils/test-utils';
import { Input } from '@/components/ui/input';

describe('Input', () => {
  describe('rendering', () => {
    it('renders an input element', () => {
      render(<Input />);

      expect(screen.getByRole('textbox')).toBeInTheDocument();
    });

    it('renders with correct type', () => {
      render(<Input type="email" />);

      const input = screen.getByRole('textbox');
      expect(input).toHaveAttribute('type', 'email');
    });

    it('renders password type input', () => {
      render(<Input type="password" data-testid="password-input" />);

      const input = screen.getByTestId('password-input');
      expect(input).toHaveAttribute('type', 'password');
    });

    it('has data-slot attribute', () => {
      render(<Input data-testid="input" />);

      const input = screen.getByTestId('input');
      expect(input).toHaveAttribute('data-slot', 'input');
    });
  });

  describe('props', () => {
    it('applies placeholder', () => {
      render(<Input placeholder="Enter your name" />);

      expect(screen.getByPlaceholderText('Enter your name')).toBeInTheDocument();
    });

    it('applies disabled state', () => {
      render(<Input disabled />);

      expect(screen.getByRole('textbox')).toBeDisabled();
    });

    it('applies required attribute', () => {
      render(<Input required />);

      expect(screen.getByRole('textbox')).toBeRequired();
    });

    it('applies custom className', () => {
      render(<Input className="custom-class" data-testid="input" />);

      expect(screen.getByTestId('input')).toHaveClass('custom-class');
    });

    it('forwards ref correctly', () => {
      const ref = { current: null };
      render(<Input ref={ref} />);

      expect(ref.current).toBeInstanceOf(HTMLInputElement);
    });
  });

  describe('interaction', () => {
    it('handles value change', () => {
      const handleChange = jest.fn();
      render(<Input onChange={handleChange} />);

      const input = screen.getByRole('textbox');
      fireEvent.change(input, { target: { value: 'test value' } });

      expect(handleChange).toHaveBeenCalled();
    });

    it('handles focus event', () => {
      const handleFocus = jest.fn();
      render(<Input onFocus={handleFocus} />);

      const input = screen.getByRole('textbox');
      fireEvent.focus(input);

      expect(handleFocus).toHaveBeenCalled();
    });

    it('handles blur event', () => {
      const handleBlur = jest.fn();
      render(<Input onBlur={handleBlur} />);

      const input = screen.getByRole('textbox');
      fireEvent.blur(input);

      expect(handleBlur).toHaveBeenCalled();
    });
  });

  describe('aria attributes', () => {
    it('applies aria-invalid attribute', () => {
      render(<Input aria-invalid="true" data-testid="input" />);

      expect(screen.getByTestId('input')).toHaveAttribute('aria-invalid', 'true');
    });

    it('applies aria-describedby', () => {
      render(<Input aria-describedby="error-message" data-testid="input" />);

      expect(screen.getByTestId('input')).toHaveAttribute('aria-describedby', 'error-message');
    });

    it('applies aria-label', () => {
      render(<Input aria-label="Phone number" />);

      expect(screen.getByLabelText('Phone number')).toBeInTheDocument();
    });
  });

  describe('styling', () => {
    it('has base styling classes', () => {
      render(<Input data-testid="input" />);

      const input = screen.getByTestId('input');
      expect(input).toHaveClass('rounded-md', 'border');
    });

    it('applies disabled styling when disabled', () => {
      render(<Input disabled data-testid="input" />);

      const input = screen.getByTestId('input');
      expect(input).toHaveClass('disabled:opacity-50');
    });
  });
});
