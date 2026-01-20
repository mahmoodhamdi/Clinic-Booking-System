import { render, screen } from '@testing-library/react';
import { Label } from '@/components/ui/label';

describe('Label', () => {
  it('renders correctly', () => {
    render(<Label>Test Label</Label>);
    expect(screen.getByText('Test Label')).toBeInTheDocument();
  });

  it('renders with htmlFor attribute', () => {
    render(<Label htmlFor="test-input">Label for input</Label>);
    const label = screen.getByText('Label for input');
    expect(label).toHaveAttribute('for', 'test-input');
  });

  it('applies custom className', () => {
    render(<Label className="custom-class">Custom Label</Label>);
    expect(screen.getByText('Custom Label')).toHaveClass('custom-class');
  });

  it('has default styling classes', () => {
    render(<Label>Styled Label</Label>);
    const label = screen.getByText('Styled Label');
    expect(label).toHaveClass('text-sm');
    expect(label).toHaveClass('font-medium');
  });

  it('can contain complex children', () => {
    render(
      <Label>
        <span data-testid="child">Child element</span>
      </Label>
    );
    expect(screen.getByTestId('child')).toBeInTheDocument();
  });

  it('applies peer-disabled styles', () => {
    render(<Label>Peer Label</Label>);
    const label = screen.getByText('Peer Label');
    expect(label).toHaveClass('peer-disabled:opacity-50');
  });

  it('has data-slot attribute', () => {
    render(<Label>Data Slot Label</Label>);
    const label = screen.getByText('Data Slot Label');
    expect(label).toHaveAttribute('data-slot', 'label');
  });

  it('uses flex layout', () => {
    render(<Label>Flex Label</Label>);
    const label = screen.getByText('Flex Label');
    expect(label).toHaveClass('flex');
    expect(label).toHaveClass('items-center');
  });

  it('has proper gap for children', () => {
    render(<Label>Gap Label</Label>);
    const label = screen.getByText('Gap Label');
    expect(label).toHaveClass('gap-2');
  });

  it('has select-none for better UX', () => {
    render(<Label>No Select Label</Label>);
    const label = screen.getByText('No Select Label');
    expect(label).toHaveClass('select-none');
  });
});
