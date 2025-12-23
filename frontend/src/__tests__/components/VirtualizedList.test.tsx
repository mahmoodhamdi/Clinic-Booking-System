import { render, screen, fireEvent } from '@/__tests__/utils/test-utils';
import { VirtualizedList } from '@/components/shared/VirtualizedList';

describe('VirtualizedList', () => {
  const mockItems = Array.from({ length: 100 }, (_, i) => ({
    id: i + 1,
    name: `Item ${i + 1}`,
  }));

  const renderItem = (item: { id: number; name: string }, index: number) => (
    <div data-testid={`item-${item.id}`}>{item.name}</div>
  );

  describe('rendering', () => {
    it('renders visible items', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
        />
      );

      // Should render first items (with overscan)
      // Container is 200px, items are 50px each = 4 visible + 3 overscan each way
      expect(screen.getByTestId('item-1')).toBeInTheDocument();
      expect(screen.getByTestId('item-2')).toBeInTheDocument();
    });

    it('does not render items beyond the visible range plus overscan', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
          overscan={2}
        />
      );

      // With containerHeight 200, itemHeight 50, overscan 2:
      // visibleCount = ceil(200/50) + 2*2 = 4 + 4 = 8
      // startIndex = max(0, 0 - 2) = 0
      // endIndex = min(99, 0 + 8) = 8
      // So items 1-9 should be rendered
      expect(screen.getByTestId('item-1')).toBeInTheDocument();
      expect(screen.queryByTestId('item-20')).not.toBeInTheDocument();
    });

    it('calculates total height based on items', () => {
      const { container } = render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
        />
      );

      // Total height = 100 items * 50px = 5000px
      const innerContainer = container.querySelector('[style*="height: 5000px"]');
      expect(innerContainer).toBeInTheDocument();
    });
  });

  describe('empty state', () => {
    it('shows empty message when items array is empty', () => {
      render(
        <VirtualizedList
          items={[]}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
        />
      );

      expect(screen.getByText('No items')).toBeInTheDocument();
    });

    it('shows custom empty message', () => {
      render(
        <VirtualizedList
          items={[]}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
          emptyMessage="No data available"
        />
      );

      expect(screen.getByText('No data available')).toBeInTheDocument();
    });

    it('applies containerHeight to empty state', () => {
      const { container } = render(
        <VirtualizedList
          items={[]}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={300}
        />
      );

      const emptyContainer = container.firstChild as HTMLElement;
      expect(emptyContainer).toHaveStyle({ height: '300px' });
    });
  });

  describe('scrolling', () => {
    it('positions items absolutely based on index', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
        />
      );

      // Check that items are positioned correctly
      const item1 = screen.getByTestId('item-1').parentElement;
      expect(item1).toHaveStyle({ top: '0px', height: '50px' });

      const item2 = screen.getByTestId('item-2').parentElement;
      expect(item2).toHaveStyle({ top: '50px', height: '50px' });
    });

    it('updates visible items on scroll', () => {
      const { container } = render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
          overscan={0}
        />
      );

      const scrollContainer = container.querySelector('.overflow-auto') as HTMLElement;

      // Simulate scrolling down
      Object.defineProperty(scrollContainer, 'scrollTop', {
        writable: true,
        value: 500, // Scroll to position where items 10+ should be visible
      });

      fireEvent.scroll(scrollContainer);

      // After scrolling 500px with 50px items:
      // startIndex = floor(500/50) - 0 = 10
      // So item 11 should be visible (0-indexed item 10)
      expect(screen.getByTestId('item-11')).toBeInTheDocument();
    });
  });

  describe('custom className', () => {
    it('applies custom className', () => {
      const { container } = render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={200}
          className="custom-list"
        />
      );

      expect(container.firstChild).toHaveClass('custom-list');
    });
  });

  describe('overscan', () => {
    it('respects custom overscan value', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={100}
          overscan={5}
        />
      );

      // With containerHeight 100, itemHeight 50, overscan 5:
      // visibleCount = ceil(100/50) + 2*5 = 2 + 10 = 12
      // Items 1-13 should be rendered (startIndex 0 to endIndex 12)
      expect(screen.getByTestId('item-1')).toBeInTheDocument();
      expect(screen.getByTestId('item-10')).toBeInTheDocument();
    });

    it('uses default overscan of 3', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={50}
          containerHeight={100}
        />
      );

      // With default overscan 3:
      // visibleCount = ceil(100/50) + 2*3 = 2 + 6 = 8
      // Items 1-9 should be rendered
      expect(screen.getByTestId('item-1')).toBeInTheDocument();
      expect(screen.getByTestId('item-8')).toBeInTheDocument();
    });
  });

  describe('item height', () => {
    it('uses different item heights', () => {
      render(
        <VirtualizedList
          items={mockItems}
          renderItem={renderItem}
          itemHeight={100}
          containerHeight={200}
        />
      );

      const item1 = screen.getByTestId('item-1').parentElement;
      expect(item1).toHaveStyle({ height: '100px' });

      const item2 = screen.getByTestId('item-2').parentElement;
      expect(item2).toHaveStyle({ top: '100px' });
    });
  });

  describe('renderItem', () => {
    it('passes item and index to renderItem', () => {
      const mockRenderItem = jest.fn((item, index) => (
        <span data-testid={`rendered-${index}`}>{item.name}</span>
      ));

      render(
        <VirtualizedList
          items={mockItems.slice(0, 5)}
          renderItem={mockRenderItem}
          itemHeight={50}
          containerHeight={300}
        />
      );

      expect(mockRenderItem).toHaveBeenCalledWith(
        expect.objectContaining({ id: 1, name: 'Item 1' }),
        0
      );
      expect(mockRenderItem).toHaveBeenCalledWith(
        expect.objectContaining({ id: 2, name: 'Item 2' }),
        1
      );
    });
  });
});
