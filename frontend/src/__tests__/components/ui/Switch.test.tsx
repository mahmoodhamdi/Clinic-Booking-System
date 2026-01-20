import { render, screen, fireEvent } from '@testing-library/react';
import { Switch } from '@/components/ui/switch';

describe('Switch', () => {
  it('renders correctly', () => {
    render(<Switch data-testid="switch" />);
    expect(screen.getByTestId('switch')).toBeInTheDocument();
  });

  it('has correct role', () => {
    render(<Switch data-testid="switch" />);
    expect(screen.getByRole('switch')).toBeInTheDocument();
  });

  it('can be toggled', () => {
    const handleChange = jest.fn();
    render(<Switch onCheckedChange={handleChange} data-testid="switch" />);

    const switchElement = screen.getByRole('switch');
    fireEvent.click(switchElement);

    expect(handleChange).toHaveBeenCalled();
  });

  it('can be disabled', () => {
    render(<Switch disabled data-testid="switch" />);
    expect(screen.getByRole('switch')).toBeDisabled();
  });

  it('applies custom className', () => {
    render(<Switch className="custom-class" data-testid="switch" />);
    expect(screen.getByTestId('switch')).toHaveClass('custom-class');
  });

  it('can be checked by default', () => {
    render(<Switch defaultChecked data-testid="switch" />);
    const switchElement = screen.getByRole('switch');
    expect(switchElement).toHaveAttribute('data-state', 'checked');
  });

  it('is unchecked by default', () => {
    render(<Switch data-testid="switch" />);
    const switchElement = screen.getByRole('switch');
    expect(switchElement).toHaveAttribute('data-state', 'unchecked');
  });

  it('supports controlled mode', () => {
    const { rerender } = render(<Switch checked={false} data-testid="switch" />);
    expect(screen.getByRole('switch')).toHaveAttribute('data-state', 'unchecked');

    rerender(<Switch checked={true} data-testid="switch" />);
    expect(screen.getByRole('switch')).toHaveAttribute('data-state', 'checked');
  });

  it('has thumb element', () => {
    render(<Switch data-testid="switch" />);
    const switchElement = screen.getByTestId('switch');
    expect(switchElement.querySelector('span')).toBeInTheDocument();
  });

  it('has rounded corners', () => {
    render(<Switch data-testid="switch" />);
    expect(screen.getByTestId('switch')).toHaveClass('rounded-full');
  });
});
