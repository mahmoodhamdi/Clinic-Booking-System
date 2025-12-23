import { render, screen } from '@/__tests__/utils/test-utils';
import { AuthLayout } from '@/components/layouts/AuthLayout';

// Mock next-intl
jest.mock('next-intl', () => ({
  useTranslations: () => (key: string) => key,
}));

describe('AuthLayout', () => {
  it('renders the title', () => {
    render(
      <AuthLayout title="Login">
        <div>Form content</div>
      </AuthLayout>
    );

    expect(screen.getByRole('heading', { name: 'Login' })).toBeInTheDocument();
  });

  it('renders the subtitle when provided', () => {
    render(
      <AuthLayout title="Login" subtitle="Enter your credentials">
        <div>Form content</div>
      </AuthLayout>
    );

    expect(screen.getByText('Enter your credentials')).toBeInTheDocument();
  });

  it('does not render subtitle when not provided', () => {
    render(
      <AuthLayout title="Login">
        <div>Form content</div>
      </AuthLayout>
    );

    // Should only have the title, not an extra subtitle paragraph
    const paragraphs = screen.queryAllByText(/Enter/);
    expect(paragraphs).toHaveLength(0);
  });

  it('renders children content', () => {
    render(
      <AuthLayout title="Login">
        <form data-testid="login-form">
          <input type="text" name="phone" />
          <button type="submit">Submit</button>
        </form>
      </AuthLayout>
    );

    expect(screen.getByTestId('login-form')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Submit' })).toBeInTheDocument();
  });

  it('renders the app name in header', () => {
    render(
      <AuthLayout title="Login">
        <div>Content</div>
      </AuthLayout>
    );

    // The app name should be rendered using the translation key
    expect(screen.getByText('appName')).toBeInTheDocument();
  });

  it('renders the language switcher', () => {
    render(
      <AuthLayout title="Login">
        <div>Content</div>
      </AuthLayout>
    );

    // LanguageSwitcher should be in the document
    expect(screen.getByRole('button')).toBeInTheDocument();
  });

  it('renders the footer with copyright and app name', () => {
    render(
      <AuthLayout title="Login">
        <div>Content</div>
      </AuthLayout>
    );

    const currentYear = new Date().getFullYear();
    const footer = screen.getByRole('contentinfo');
    expect(footer).toHaveTextContent(currentYear.toString());
    expect(footer).toHaveTextContent('appName');
  });

  it('applies proper styling classes', () => {
    const { container } = render(
      <AuthLayout title="Login">
        <div>Content</div>
      </AuthLayout>
    );

    // Check for gradient background class
    expect(container.firstChild).toHaveClass('min-h-screen');
    expect(container.firstChild).toHaveClass('bg-gradient-to-br');
  });
});
