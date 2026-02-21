import { render, screen } from '@/__tests__/utils/test-utils';
import userEvent from '@testing-library/user-event';
import { EmptyState } from '@/components/shared/EmptyState';
import { Package } from 'lucide-react';

describe('EmptyState', () => {
  const mockOnClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders icon, title, and description', () => {
    render(
      <EmptyState
        icon={Package}
        title="No Items"
        description="You don't have any items yet"
      />
    );

    expect(screen.getByRole('heading', { name: 'No Items' })).toBeInTheDocument();
    expect(screen.getByText("You don't have any items yet")).toBeInTheDocument();

    // Icon should be rendered (check for SVG)
    const svg = document.querySelector('svg');
    expect(svg).toBeInTheDocument();
  });

  it('renders action button when provided', () => {
    render(
      <EmptyState
        icon={Package}
        title="No Items"
        description="You don't have any items yet"
        action={{ label: 'Create Item', onClick: mockOnClick }}
      />
    );

    const button = screen.getByRole('button', { name: 'Create Item' });
    expect(button).toBeInTheDocument();
  });

  it('calls action onClick when button clicked', async () => {
    const user = userEvent.setup();
    render(
      <EmptyState
        icon={Package}
        title="No Items"
        action={{ label: 'Create Item', onClick: mockOnClick }}
      />
    );

    const button = screen.getByRole('button', { name: 'Create Item' });
    await user.click(button);

    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  it('renders without action button when not provided', () => {
    render(
      <EmptyState
        icon={Package}
        title="No Items"
        description="You don't have any items yet"
      />
    );

    // Should not have any buttons
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('renders title without description', () => {
    render(
      <EmptyState
        icon={Package}
        title="No Items"
      />
    );

    expect(screen.getByRole('heading', { name: 'No Items' })).toBeInTheDocument();
  });

  it('renders with custom className', () => {
    const { container } = render(
      <EmptyState
        icon={Package}
        title="No Items"
        className="custom-empty-state"
      />
    );

    const emptyStateDiv = container.querySelector('.custom-empty-state');
    expect(emptyStateDiv).toBeInTheDocument();
  });

  it('displays only title when neither description nor action provided', () => {
    render(
      <EmptyState
        icon={Package}
        title="No Items"
      />
    );

    expect(screen.getByRole('heading', { name: 'No Items' })).toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('renders icon with proper styling', () => {
    const { container } = render(
      <EmptyState
        icon={Package}
        title="No Items"
      />
    );

    // Icon should be inside a circular container
    const iconContainer = container.querySelector('.h-16.w-16');
    expect(iconContainer).toBeInTheDocument();
    expect(iconContainer).toHaveClass('rounded-full');
  });

  it('renders with both description and action', async () => {
    const user = userEvent.setup();
    render(
      <EmptyState
        icon={Package}
        title="No Items"
        description="You don't have any items yet"
        action={{ label: 'Create Item', onClick: mockOnClick }}
      />
    );

    expect(screen.getByText("You don't have any items yet")).toBeInTheDocument();

    const button = screen.getByRole('button', { name: 'Create Item' });
    expect(button).toBeInTheDocument();

    await user.click(button);
    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  it('renders heading with proper text styling', () => {
    const { container } = render(
      <EmptyState
        icon={Package}
        title="No Items"
      />
    );

    const heading = screen.getByRole('heading', { name: 'No Items' });
    expect(heading).toHaveClass('text-lg');
    expect(heading).toHaveClass('font-semibold');
  });
});
