import { render, screen, fireEvent } from '@/__tests__/utils/test-utils';
import { Avatar } from '@/components/shared/Avatar';

// Mock next/image
jest.mock('next/image', () => ({
  __esModule: true,
  default: function MockImage(props: {
    src: string;
    alt: string;
    width: number;
    height: number;
    className?: string;
    loading?: string;
    onError?: () => void;
    unoptimized?: boolean;
  }) {
    // eslint-disable-next-line @next/next/no-img-element
    return (
      <img
        src={props.src}
        alt={props.alt}
        width={props.width}
        height={props.height}
        className={props.className}
        data-loading={props.loading}
        onError={props.onError}
      />
    );
  },
}));

describe('Avatar', () => {
  describe('with valid src', () => {
    it('renders an image when src is provided', () => {
      render(<Avatar src="/avatar.jpg" alt="Test User" />);

      const img = screen.getByRole('img');
      expect(img).toBeInTheDocument();
      expect(img).toHaveAttribute('src', '/avatar.jpg');
      expect(img).toHaveAttribute('alt', 'Test User');
    });

    it('applies lazy loading', () => {
      render(<Avatar src="/avatar.jpg" alt="Test User" />);

      const img = screen.getByRole('img');
      expect(img).toHaveAttribute('data-loading', 'lazy');
    });
  });

  describe('without src (fallback)', () => {
    it('renders initials when src is null', () => {
      render(<Avatar src={null} alt="Ahmed Mohamed" />);

      expect(screen.getByText('AM')).toBeInTheDocument();
    });

    it('renders initials when src is undefined', () => {
      render(<Avatar src={undefined} alt="John Doe" />);

      expect(screen.getByText('JD')).toBeInTheDocument();
    });

    it('renders single initial for single word name', () => {
      render(<Avatar src={null} alt="Admin" />);

      expect(screen.getByText('A')).toBeInTheDocument();
    });

    it('renders initials from first and last word only', () => {
      render(<Avatar src={null} alt="Mohamed Ahmed Hosny" />);

      // Should use first letter of first word and first letter of last word
      expect(screen.getByText('MH')).toBeInTheDocument();
    });

    it('handles empty string name gracefully', () => {
      render(<Avatar src={null} alt="" />);

      // Should render empty fallback
      const fallback = screen.getByLabelText('');
      expect(fallback).toBeInTheDocument();
    });
  });

  describe('sizes', () => {
    it('renders extra small size correctly', () => {
      const { container } = render(<Avatar src={null} alt="Test" size="xs" />);

      const avatar = container.firstChild as HTMLElement;
      expect(avatar).toHaveStyle({ width: '24px', height: '24px' });
    });

    it('renders small size correctly', () => {
      const { container } = render(<Avatar src={null} alt="Test" size="sm" />);

      const avatar = container.firstChild as HTMLElement;
      expect(avatar).toHaveStyle({ width: '32px', height: '32px' });
    });

    it('renders medium size (default) correctly', () => {
      const { container } = render(<Avatar src={null} alt="Test" />);

      const avatar = container.firstChild as HTMLElement;
      expect(avatar).toHaveStyle({ width: '40px', height: '40px' });
    });

    it('renders large size correctly', () => {
      const { container } = render(<Avatar src={null} alt="Test" size="lg" />);

      const avatar = container.firstChild as HTMLElement;
      expect(avatar).toHaveStyle({ width: '48px', height: '48px' });
    });

    it('renders extra large size correctly', () => {
      const { container } = render(<Avatar src={null} alt="Test" size="xl" />);

      const avatar = container.firstChild as HTMLElement;
      expect(avatar).toHaveStyle({ width: '64px', height: '64px' });
    });
  });

  describe('image error handling', () => {
    it('shows fallback when image fails to load', () => {
      render(<Avatar src="/broken-image.jpg" alt="John Doe" />);

      const img = screen.getByRole('img');
      fireEvent.error(img);

      // After error, should show initials fallback
      expect(screen.getByText('JD')).toBeInTheDocument();
    });
  });

  describe('custom className', () => {
    it('applies custom className', () => {
      const { container } = render(
        <Avatar src={null} alt="Test" className="custom-class" />
      );

      expect(container.firstChild).toHaveClass('custom-class');
    });

    it('applies fallbackClassName to fallback element', () => {
      const { container } = render(
        <Avatar src={null} alt="Test" fallbackClassName="fallback-style" />
      );

      expect(container.firstChild).toHaveClass('fallback-style');
    });
  });

  describe('accessibility', () => {
    it('has aria-label on fallback', () => {
      render(<Avatar src={null} alt="Test User" />);

      expect(screen.getByLabelText('Test User')).toBeInTheDocument();
    });
  });
});
