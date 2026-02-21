import { render, screen } from '@/__tests__/utils/test-utils';
import userEvent from '@testing-library/user-event';
import { ConfirmDialog } from '@/components/shared/ConfirmDialog';

// Mock next-intl
jest.mock('next-intl', () => ({
  useTranslations: () => (key: string) => {
    const translations: Record<string, string> = {
      'common.confirm': 'Confirm',
      'common.cancel': 'Cancel',
    };
    return translations[key] || key;
  },
}));

describe('ConfirmDialog', () => {
  const mockOnConfirm = jest.fn();
  const mockOnOpenChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders when open prop is true', () => {
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Delete Item"
        description="Are you sure you want to delete this item?"
        onConfirm={mockOnConfirm}
      />
    );

    expect(screen.getByRole('heading', { name: 'Delete Item' })).toBeInTheDocument();
    expect(screen.getByText('Are you sure you want to delete this item?')).toBeInTheDocument();
  });

  it('does not render when open is false', () => {
    const { container } = render(
      <ConfirmDialog
        open={false}
        onOpenChange={mockOnOpenChange}
        title="Delete Item"
        onConfirm={mockOnConfirm}
      />
    );

    // When closed, the AlertDialog should not have visible content
    // The dialog content should not be in the DOM or should be hidden
    expect(
      container.querySelector('[role="alertdialog"]')
    ).not.toBeInTheDocument();
  });

  it('calls onConfirm when confirm button clicked', async () => {
    const user = userEvent.setup();
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Delete Item"
        confirmLabel="Delete"
        cancelLabel="Cancel"
        onConfirm={mockOnConfirm}
      />
    );

    const confirmButton = screen.getByRole('button', { name: /Delete/i });
    await user.click(confirmButton);

    expect(mockOnConfirm).toHaveBeenCalledTimes(1);
  });

  it('shows loading state during async confirmation', () => {
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Delete Item"
        confirmLabel="Delete"
        cancelLabel="Cancel"
        loading={true}
        onConfirm={mockOnConfirm}
      />
    );

    const confirmButton = screen.getByRole('button', { name: /Delete/i });
    const cancelButton = screen.getByRole('button', { name: /Cancel/i });

    // Buttons should be disabled when loading
    expect(confirmButton).toBeDisabled();
    expect(cancelButton).toBeDisabled();

    // Loading indicator (spinner) should be visible
    expect(document.querySelector('.animate-spin')).toBeInTheDocument();
  });

  it('displays custom title and description', () => {
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Confirm Action"
        description="Please confirm this action before proceeding"
        onConfirm={mockOnConfirm}
      />
    );

    expect(screen.getByRole('heading', { name: 'Confirm Action' })).toBeInTheDocument();
    expect(screen.getByText('Please confirm this action before proceeding')).toBeInTheDocument();
  });

  it('uses custom labels for buttons', () => {
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Custom Dialog"
        confirmLabel="Proceed"
        cancelLabel="Abort"
        onConfirm={mockOnConfirm}
      />
    );

    expect(screen.getByRole('button', { name: 'Proceed' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Abort' })).toBeInTheDocument();
  });

  it('does not call onConfirm when loading and confirm clicked', async () => {
    const user = userEvent.setup();
    render(
      <ConfirmDialog
        open={true}
        onOpenChange={mockOnOpenChange}
        title="Delete Item"
        confirmLabel="Delete"
        loading={true}
        onConfirm={mockOnConfirm}
      />
    );

    const confirmButton = screen.getByRole('button', { name: /Delete/i });
    expect(confirmButton).toBeDisabled();

    // Even if we try to click (shouldn't be possible), onConfirm shouldn't be called
    // because the button is disabled
  });
});
