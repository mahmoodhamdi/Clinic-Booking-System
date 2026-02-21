import { render, screen } from '@/__tests__/utils/test-utils';
import userEvent from '@testing-library/user-event';
import { Pagination } from '@/components/shared/Pagination';

// Mock next-intl
jest.mock('next-intl', () => ({
  useTranslations: () => (key: string) => {
    const translations: Record<string, string> = {
      'common.pagination': 'Pagination',
      'common.previous': 'Previous',
      'common.next': 'Next',
      'common.page': 'Page',
    };
    return translations[key] || key;
  },
}));

describe('Pagination', () => {
  const mockOnPageChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders correct number of page buttons', () => {
    render(
      <Pagination
        currentPage={1}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    // With 5 pages and current on 1, should show: Previous, 1, 2, 3, 4, 5, Next
    const pageButtons = screen.getAllByRole('button').filter((btn) => {
      const text = btn.textContent?.trim();
      return text === '1' || text === '2' || text === '3' || text === '4' || text === '5';
    });

    expect(pageButtons).toHaveLength(5);
  });

  it('highlights current page', () => {
    render(
      <Pagination
        currentPage={2}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const currentPageButton = screen.getByRole('button', { name: /Page 2/i });
    expect(currentPageButton).toHaveAttribute('aria-current', 'page');
  });

  it('calls onPageChange when page clicked', async () => {
    const user = userEvent.setup();
    render(
      <Pagination
        currentPage={1}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const pageButton = screen.getByRole('button', { name: /Page 2/i });
    await user.click(pageButton);

    expect(mockOnPageChange).toHaveBeenCalledWith(2);
  });

  it('disables prev on first page', () => {
    render(
      <Pagination
        currentPage={1}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const prevButton = screen.getByRole('button', { name: /Previous/i });
    expect(prevButton).toBeDisabled();
  });

  it('disables next on last page', () => {
    render(
      <Pagination
        currentPage={5}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const nextButton = screen.getByRole('button', { name: /Next/i });
    expect(nextButton).toBeDisabled();
  });

  it('enables prev and next buttons on middle page', () => {
    render(
      <Pagination
        currentPage={3}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const prevButton = screen.getByRole('button', { name: /Previous/i });
    const nextButton = screen.getByRole('button', { name: /Next/i });

    expect(prevButton).not.toBeDisabled();
    expect(nextButton).not.toBeDisabled();
  });

  it('handles single page (no pagination needed)', () => {
    const { container } = render(
      <Pagination
        currentPage={1}
        lastPage={1}
        onPageChange={mockOnPageChange}
      />
    );

    // Should not render pagination when only 1 page exists
    expect(container.firstChild).toBeNull();
  });

  it('handles zero or negative last page', () => {
    const { container } = render(
      <Pagination
        currentPage={1}
        lastPage={0}
        onPageChange={mockOnPageChange}
      />
    );

    expect(container.firstChild).toBeNull();
  });

  it('calls onPageChange for previous button', async () => {
    const user = userEvent.setup();
    render(
      <Pagination
        currentPage={3}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const prevButton = screen.getByRole('button', { name: /Previous/i });
    await user.click(prevButton);

    expect(mockOnPageChange).toHaveBeenCalledWith(2);
  });

  it('calls onPageChange for next button', async () => {
    const user = userEvent.setup();
    render(
      <Pagination
        currentPage={2}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const nextButton = screen.getByRole('button', { name: /Next/i });
    await user.click(nextButton);

    expect(mockOnPageChange).toHaveBeenCalledWith(3);
  });

  it('renders ellipsis for large page ranges', () => {
    render(
      <Pagination
        currentPage={1}
        lastPage={20}
        onPageChange={mockOnPageChange}
      />
    );

    // With 20 pages, should show ellipsis
    const ellipsis = screen.getAllByText('…');
    expect(ellipsis.length).toBeGreaterThan(0);
  });

  it('always shows first and last page', () => {
    render(
      <Pagination
        currentPage={10}
        lastPage={20}
        onPageChange={mockOnPageChange}
      />
    );

    // Get all buttons and filter to find pages
    const buttons = screen.getAllByRole('button');
    const pageButtons = buttons.map((btn) => btn.textContent?.trim()).filter((text) => /^\d+$/.test(text || ''));

    // Should include first page (1) and last page (20)
    expect(pageButtons).toContain('1');
    expect(pageButtons).toContain('20');
  });

  it('applies custom className', () => {
    const { container } = render(
      <Pagination
        currentPage={1}
        lastPage={5}
        onPageChange={mockOnPageChange}
        className="custom-class"
      />
    );

    const nav = container.querySelector('[role="navigation"]');
    expect(nav).toHaveClass('custom-class');
  });

  it('has proper aria navigation label', () => {
    render(
      <Pagination
        currentPage={1}
        lastPage={5}
        onPageChange={mockOnPageChange}
      />
    );

    const nav = screen.getByRole('navigation', { name: /Pagination/i });
    expect(nav).toBeInTheDocument();
  });
});
