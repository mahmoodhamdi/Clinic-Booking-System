import { render, screen } from '@testing-library/react';
import { LoadingSpinner, LoadingPage } from '@/components/shared/LoadingSpinner';

describe('LoadingSpinner', () => {
  it('renders without crashing', () => {
    const { container } = render(<LoadingSpinner />);
    const spinner = container.querySelector('svg');
    expect(spinner).toBeInTheDocument();
  });

  it('applies animate-spin class', () => {
    const { container } = render(<LoadingSpinner />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('animate-spin');
  });

  it('applies custom className', () => {
    const { container } = render(<LoadingSpinner className="custom-class" />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('custom-class');
  });

  it('applies correct size class for sm', () => {
    const { container } = render(<LoadingSpinner size="sm" />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('h-4', 'w-4');
  });

  it('applies correct size class for md (default)', () => {
    const { container } = render(<LoadingSpinner />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('h-6', 'w-6');
  });

  it('applies correct size class for lg', () => {
    const { container } = render(<LoadingSpinner size="lg" />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('h-10', 'w-10');
  });
});

describe('LoadingPage', () => {
  it('renders full page loading spinner', () => {
    const { container } = render(<LoadingPage />);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('min-h-screen', 'flex', 'items-center', 'justify-center');
  });

  it('renders large spinner', () => {
    const { container } = render(<LoadingPage />);
    const spinner = container.querySelector('svg');
    expect(spinner).toHaveClass('h-10', 'w-10');
  });
});
