import { render, screen } from '@testing-library/react';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';

describe('LanguageSwitcher', () => {
  it('renders without crashing', () => {
    render(<LanguageSwitcher />);
    // Just check the component renders
    expect(document.body).toBeInTheDocument();
  });

  it('renders a clickable element', () => {
    const { container } = render(<LanguageSwitcher />);
    const button = container.querySelector('button');
    expect(button).toBeInTheDocument();
  });
});
