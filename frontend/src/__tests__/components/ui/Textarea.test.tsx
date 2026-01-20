import { render, screen, fireEvent } from '@testing-library/react';
import { Textarea } from '@/components/ui/textarea';

describe('Textarea', () => {
  it('renders correctly', () => {
    render(<Textarea data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toBeInTheDocument();
  });

  it('renders with placeholder', () => {
    render(<Textarea placeholder="Enter text here" />);
    expect(screen.getByPlaceholderText('Enter text here')).toBeInTheDocument();
  });

  it('handles value changes', () => {
    const handleChange = jest.fn();
    render(<Textarea onChange={handleChange} data-testid="textarea" />);

    const textarea = screen.getByTestId('textarea');
    fireEvent.change(textarea, { target: { value: 'New text' } });

    expect(handleChange).toHaveBeenCalled();
  });

  it('can be disabled', () => {
    render(<Textarea disabled data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toBeDisabled();
  });

  it('applies custom className', () => {
    render(<Textarea className="custom-class" data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveClass('custom-class');
  });

  it('has default styling classes', () => {
    render(<Textarea data-testid="textarea" />);
    const textarea = screen.getByTestId('textarea');
    expect(textarea).toHaveClass('border');
    expect(textarea).toHaveClass('rounded-md');
  });

  it('supports rows attribute', () => {
    render(<Textarea rows={5} data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveAttribute('rows', '5');
  });

  it('supports maxLength attribute', () => {
    render(<Textarea maxLength={100} data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveAttribute('maxlength', '100');
  });

  it('supports required attribute', () => {
    render(<Textarea required data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toBeRequired();
  });

  it('supports name attribute', () => {
    render(<Textarea name="description" data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveAttribute('name', 'description');
  });

  it('can be readonly', () => {
    render(<Textarea readOnly data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveAttribute('readonly');
  });

  it('displays default value', () => {
    render(<Textarea defaultValue="Default text" data-testid="textarea" />);
    expect(screen.getByTestId('textarea')).toHaveValue('Default text');
  });
});
