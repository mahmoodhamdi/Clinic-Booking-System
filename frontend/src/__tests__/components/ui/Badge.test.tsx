import { render, screen } from '@/__tests__/utils/test-utils';
import { Badge } from '@/components/ui/badge';

describe('Badge', () => {
  describe('rendering', () => {
    it('renders children', () => {
      render(<Badge>New</Badge>);

      expect(screen.getByText('New')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<Badge data-testid="badge">Badge</Badge>);

      expect(screen.getByTestId('badge')).toHaveAttribute('data-slot', 'badge');
    });

    it('renders as span by default', () => {
      render(<Badge data-testid="badge">Badge</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge.tagName).toBe('SPAN');
    });
  });

  describe('variants', () => {
    it('renders default variant', () => {
      render(<Badge data-testid="badge">Default</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('bg-primary', 'text-primary-foreground');
    });

    it('renders secondary variant', () => {
      render(<Badge variant="secondary" data-testid="badge">Secondary</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('bg-secondary', 'text-secondary-foreground');
    });

    it('renders destructive variant', () => {
      render(<Badge variant="destructive" data-testid="badge">Destructive</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('bg-destructive');
    });

    it('renders outline variant', () => {
      render(<Badge variant="outline" data-testid="badge">Outline</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('text-foreground');
    });
  });

  describe('styling', () => {
    it('has base styling classes', () => {
      render(<Badge data-testid="badge">Badge</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('inline-flex', 'items-center', 'rounded-full');
    });

    it('has text styling', () => {
      render(<Badge data-testid="badge">Badge</Badge>);

      const badge = screen.getByTestId('badge');
      expect(badge).toHaveClass('text-xs', 'font-medium');
    });

    it('applies custom className', () => {
      render(<Badge className="custom-badge" data-testid="badge">Badge</Badge>);

      expect(screen.getByTestId('badge')).toHaveClass('custom-badge');
    });
  });

  describe('asChild prop', () => {
    it('renders as child element when asChild is true', () => {
      render(
        <Badge asChild>
          <a href="/notifications">Notifications</a>
        </Badge>
      );

      const link = screen.getByRole('link', { name: 'Notifications' });
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('href', '/notifications');
    });
  });

  describe('with icons', () => {
    it('renders with icon children', () => {
      render(
        <Badge>
          <svg data-testid="icon" />
          <span>With Icon</span>
        </Badge>
      );

      expect(screen.getByTestId('icon')).toBeInTheDocument();
      expect(screen.getByText('With Icon')).toBeInTheDocument();
    });
  });

  describe('accessibility', () => {
    it('accepts aria attributes', () => {
      render(
        <Badge aria-label="3 unread notifications" data-testid="badge">
          3
        </Badge>
      );

      expect(screen.getByTestId('badge')).toHaveAttribute(
        'aria-label',
        '3 unread notifications'
      );
    });
  });

  describe('use cases', () => {
    it('renders status badge', () => {
      render(<Badge variant="secondary">Active</Badge>);

      expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it('renders count badge', () => {
      render(<Badge>5</Badge>);

      expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('renders error badge', () => {
      render(<Badge variant="destructive">Error</Badge>);

      expect(screen.getByText('Error')).toBeInTheDocument();
    });
  });
});
