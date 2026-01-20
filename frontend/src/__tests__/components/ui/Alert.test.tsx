import { render, screen } from '@testing-library/react';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';

describe('Alert', () => {
  it('renders correctly', () => {
    render(<Alert data-testid="alert">Alert content</Alert>);
    expect(screen.getByTestId('alert')).toBeInTheDocument();
  });

  it('has correct role', () => {
    render(<Alert data-testid="alert">Alert content</Alert>);
    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  it('renders children', () => {
    render(<Alert>Alert message</Alert>);
    expect(screen.getByText('Alert message')).toBeInTheDocument();
  });

  it('applies custom className', () => {
    render(<Alert className="custom-alert" data-testid="alert">Content</Alert>);
    expect(screen.getByTestId('alert')).toHaveClass('custom-alert');
  });

  it('has default styling', () => {
    render(<Alert data-testid="alert">Content</Alert>);
    const alert = screen.getByTestId('alert');
    expect(alert).toHaveClass('relative');
    expect(alert).toHaveClass('rounded-lg');
    expect(alert).toHaveClass('border');
  });

  it('supports destructive variant', () => {
    render(<Alert variant="destructive" data-testid="alert">Error</Alert>);
    const alert = screen.getByTestId('alert');
    expect(alert).toHaveClass('text-destructive');
  });

  it('has grid layout', () => {
    render(<Alert data-testid="alert">Content</Alert>);
    const alert = screen.getByTestId('alert');
    expect(alert).toHaveClass('grid');
  });
});

describe('AlertTitle', () => {
  it('renders correctly', () => {
    render(<AlertTitle>Title</AlertTitle>);
    expect(screen.getByText('Title')).toBeInTheDocument();
  });

  it('applies custom className', () => {
    render(<AlertTitle className="custom-title">Title</AlertTitle>);
    expect(screen.getByText('Title')).toHaveClass('custom-title');
  });

  it('has heading styles', () => {
    render(<AlertTitle data-testid="title">Title</AlertTitle>);
    const title = screen.getByTestId('title');
    expect(title).toHaveClass('font-medium');
  });
});

describe('AlertDescription', () => {
  it('renders correctly', () => {
    render(<AlertDescription>Description</AlertDescription>);
    expect(screen.getByText('Description')).toBeInTheDocument();
  });

  it('applies custom className', () => {
    render(<AlertDescription className="custom-desc">Description</AlertDescription>);
    expect(screen.getByText('Description')).toHaveClass('custom-desc');
  });

  it('has muted text style', () => {
    render(<AlertDescription data-testid="desc">Description</AlertDescription>);
    const desc = screen.getByTestId('desc');
    expect(desc).toHaveClass('text-sm');
  });

  it('has data-slot attribute', () => {
    render(<AlertDescription data-testid="desc">Description</AlertDescription>);
    const desc = screen.getByTestId('desc');
    expect(desc).toHaveAttribute('data-slot', 'alert-description');
  });
});

describe('Alert with Title and Description', () => {
  it('renders complete alert', () => {
    render(
      <Alert>
        <AlertTitle>Alert Title</AlertTitle>
        <AlertDescription>Alert Description</AlertDescription>
      </Alert>
    );

    expect(screen.getByText('Alert Title')).toBeInTheDocument();
    expect(screen.getByText('Alert Description')).toBeInTheDocument();
  });

  it('renders with icon', () => {
    render(
      <Alert>
        <span data-testid="icon">⚠️</span>
        <AlertTitle>Warning</AlertTitle>
        <AlertDescription>This is a warning</AlertDescription>
      </Alert>
    );

    expect(screen.getByTestId('icon')).toBeInTheDocument();
    expect(screen.getByText('Warning')).toBeInTheDocument();
  });
});
