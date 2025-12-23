import { render, screen } from '@/__tests__/utils/test-utils';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
  CardAction,
} from '@/components/ui/card';

describe('Card', () => {
  describe('Card component', () => {
    it('renders children', () => {
      render(
        <Card>
          <div data-testid="child">Content</div>
        </Card>
      );

      expect(screen.getByTestId('child')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<Card data-testid="card">Content</Card>);

      expect(screen.getByTestId('card')).toHaveAttribute('data-slot', 'card');
    });

    it('applies custom className', () => {
      render(<Card className="custom-card" data-testid="card">Content</Card>);

      expect(screen.getByTestId('card')).toHaveClass('custom-card');
    });

    it('has rounded border styling', () => {
      render(<Card data-testid="card">Content</Card>);

      expect(screen.getByTestId('card')).toHaveClass('rounded-xl', 'border');
    });
  });

  describe('CardHeader component', () => {
    it('renders children', () => {
      render(
        <CardHeader>
          <span data-testid="header-child">Header Content</span>
        </CardHeader>
      );

      expect(screen.getByTestId('header-child')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardHeader data-testid="header">Header</CardHeader>);

      expect(screen.getByTestId('header')).toHaveAttribute('data-slot', 'card-header');
    });

    it('applies custom className', () => {
      render(<CardHeader className="custom-header" data-testid="header">Header</CardHeader>);

      expect(screen.getByTestId('header')).toHaveClass('custom-header');
    });
  });

  describe('CardTitle component', () => {
    it('renders children', () => {
      render(<CardTitle>My Title</CardTitle>);

      expect(screen.getByText('My Title')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardTitle data-testid="title">Title</CardTitle>);

      expect(screen.getByTestId('title')).toHaveAttribute('data-slot', 'card-title');
    });

    it('has font styling', () => {
      render(<CardTitle data-testid="title">Title</CardTitle>);

      expect(screen.getByTestId('title')).toHaveClass('font-semibold');
    });

    it('applies custom className', () => {
      render(<CardTitle className="text-2xl" data-testid="title">Title</CardTitle>);

      expect(screen.getByTestId('title')).toHaveClass('text-2xl');
    });
  });

  describe('CardDescription component', () => {
    it('renders children', () => {
      render(<CardDescription>My description text</CardDescription>);

      expect(screen.getByText('My description text')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardDescription data-testid="desc">Description</CardDescription>);

      expect(screen.getByTestId('desc')).toHaveAttribute('data-slot', 'card-description');
    });

    it('has muted text styling', () => {
      render(<CardDescription data-testid="desc">Description</CardDescription>);

      expect(screen.getByTestId('desc')).toHaveClass('text-muted-foreground', 'text-sm');
    });
  });

  describe('CardContent component', () => {
    it('renders children', () => {
      render(
        <CardContent>
          <p data-testid="content">Main content here</p>
        </CardContent>
      );

      expect(screen.getByTestId('content')).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardContent data-testid="content">Content</CardContent>);

      expect(screen.getByTestId('content')).toHaveAttribute('data-slot', 'card-content');
    });

    it('has padding styling', () => {
      render(<CardContent data-testid="content">Content</CardContent>);

      expect(screen.getByTestId('content')).toHaveClass('px-6');
    });
  });

  describe('CardFooter component', () => {
    it('renders children', () => {
      render(
        <CardFooter>
          <button>Submit</button>
        </CardFooter>
      );

      expect(screen.getByRole('button', { name: 'Submit' })).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardFooter data-testid="footer">Footer</CardFooter>);

      expect(screen.getByTestId('footer')).toHaveAttribute('data-slot', 'card-footer');
    });

    it('has flex styling', () => {
      render(<CardFooter data-testid="footer">Footer</CardFooter>);

      expect(screen.getByTestId('footer')).toHaveClass('flex', 'items-center');
    });
  });

  describe('CardAction component', () => {
    it('renders children', () => {
      render(
        <CardAction>
          <button>Action</button>
        </CardAction>
      );

      expect(screen.getByRole('button', { name: 'Action' })).toBeInTheDocument();
    });

    it('has data-slot attribute', () => {
      render(<CardAction data-testid="action">Action</CardAction>);

      expect(screen.getByTestId('action')).toHaveAttribute('data-slot', 'card-action');
    });
  });

  describe('full card composition', () => {
    it('renders a complete card with all sections', () => {
      render(
        <Card>
          <CardHeader>
            <CardTitle>Card Title</CardTitle>
            <CardDescription>Card description text</CardDescription>
          </CardHeader>
          <CardContent>
            <p>Main card content goes here</p>
          </CardContent>
          <CardFooter>
            <button>Save</button>
          </CardFooter>
        </Card>
      );

      expect(screen.getByText('Card Title')).toBeInTheDocument();
      expect(screen.getByText('Card description text')).toBeInTheDocument();
      expect(screen.getByText('Main card content goes here')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Save' })).toBeInTheDocument();
    });
  });
});
