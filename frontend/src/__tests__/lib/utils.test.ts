import { cn } from '@/lib/utils';

describe('cn (classnames utility)', () => {
  it('should merge class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar');
  });

  it('should handle conditional classes with true', () => {
    const result = cn('foo', { bar: true });
    expect(result).toContain('foo');
    expect(result).toContain('bar');
  });

  it('should handle conditional classes with false', () => {
    const result = cn('foo', { bar: false });
    expect(result).toContain('foo');
    expect(result).not.toContain('bar');
  });

  it('should handle undefined values', () => {
    expect(cn('foo', undefined, 'bar')).toBe('foo bar');
  });

  it('should handle null values', () => {
    expect(cn('foo', null, 'bar')).toBe('foo bar');
  });

  it('should handle empty strings', () => {
    expect(cn('foo', '', 'bar')).toBe('foo bar');
  });

  it('should handle arrays', () => {
    expect(cn(['foo', 'bar'])).toBe('foo bar');
  });

  it('should merge conflicting Tailwind padding classes', () => {
    const result = cn('px-2', 'px-4');
    expect(result).toBe('px-4');
  });

  it('should merge conflicting Tailwind text color classes', () => {
    const result = cn('text-red-500', 'text-blue-500');
    expect(result).toBe('text-blue-500');
  });

  it('should merge conflicting Tailwind background classes', () => {
    const result = cn('bg-red-500', 'bg-blue-500');
    expect(result).toBe('bg-blue-500');
  });

  it('should handle complex class combinations', () => {
    const result = cn(
      'px-4 py-2',
      'text-white bg-blue-500',
      { 'hover:bg-blue-600': true, 'disabled': false }
    );
    expect(result).toContain('px-4');
    expect(result).toContain('py-2');
    expect(result).toContain('text-white');
    expect(result).toContain('bg-blue-500');
    expect(result).toContain('hover:bg-blue-600');
    expect(result).not.toContain('disabled');
  });

  it('should handle nested conditionals', () => {
    const isActive = true;
    const isDisabled = false;
    const result = cn(
      'base-class',
      isActive && 'active-class',
      isDisabled && 'disabled-class'
    );
    expect(result).toContain('base-class');
    expect(result).toContain('active-class');
    expect(result).not.toContain('disabled-class');
  });

  it('should handle empty input', () => {
    expect(cn()).toBe('');
  });

  it('should handle single class', () => {
    expect(cn('single')).toBe('single');
  });

  it('should handle Tailwind responsive prefixes', () => {
    const result = cn('md:px-4', 'lg:px-8');
    expect(result).toContain('md:px-4');
    expect(result).toContain('lg:px-8');
  });

  it('should handle Tailwind state variants', () => {
    const result = cn('hover:bg-blue-500', 'focus:ring-2');
    expect(result).toContain('hover:bg-blue-500');
    expect(result).toContain('focus:ring-2');
  });

  it('should handle boolean false values', () => {
    expect(cn(false, 'foo')).toBe('foo');
  });

  it('should handle zero values', () => {
    expect(cn(0, 'foo')).toBe('foo');
  });
});
