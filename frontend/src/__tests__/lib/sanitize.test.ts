/**
 * @jest-environment jsdom
 */
import {
  sanitizeHtml,
  sanitizeText,
  escapeHtml,
  sanitizeUrl,
  sanitizePhone,
  sanitizeEmail,
  sanitizeObject,
} from '@/lib/utils/sanitize';

describe('Sanitization Utilities', () => {
  describe('sanitizeHtml', () => {
    test('allows safe HTML tags', () => {
      const input = '<b>Bold</b> and <i>italic</i>';
      const result = sanitizeHtml(input);
      expect(result).toContain('<b>');
      expect(result).toContain('<i>');
    });

    test('removes script tags', () => {
      const input = '<script>alert("XSS")</script>Hello';
      const result = sanitizeHtml(input);
      expect(result).not.toContain('<script>');
      expect(result).not.toContain('alert');
    });

    test('removes onclick handlers', () => {
      const input = '<button onclick="alert(\'XSS\')">Click</button>';
      const result = sanitizeHtml(input);
      expect(result).not.toContain('onclick');
    });

    test('removes img onerror', () => {
      const input = '<img src="x" onerror="alert(\'XSS\')">';
      const result = sanitizeHtml(input);
      expect(result).not.toContain('onerror');
    });
  });

  describe('sanitizeText', () => {
    test('removes all HTML tags', () => {
      const input = '<b>Bold</b> and <script>evil()</script>';
      const result = sanitizeText(input);
      expect(result).toBe('Bold and ');
    });

    test('keeps plain text', () => {
      const input = 'Plain text without HTML';
      const result = sanitizeText(input);
      expect(result).toBe('Plain text without HTML');
    });
  });

  describe('escapeHtml', () => {
    test('escapes HTML entities', () => {
      const input = '<script>alert("test")</script>';
      const result = escapeHtml(input);
      expect(result).toBe('&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;');
    });

    test('escapes ampersand', () => {
      const input = 'Tom & Jerry';
      const result = escapeHtml(input);
      expect(result).toBe('Tom &amp; Jerry');
    });

    test('escapes single quotes', () => {
      const input = "It's a test";
      const result = escapeHtml(input);
      expect(result).toBe('It&#039;s a test');
    });
  });

  describe('sanitizeUrl', () => {
    test('allows https URLs', () => {
      const input = 'https://example.com/path?query=1';
      expect(sanitizeUrl(input)).toBe(input);
    });

    test('allows http URLs', () => {
      const input = 'http://example.com/path';
      expect(sanitizeUrl(input)).toBe(input);
    });

    test('allows relative URLs', () => {
      expect(sanitizeUrl('/path/to/resource')).toBe('/path/to/resource');
      expect(sanitizeUrl('#anchor')).toBe('#anchor');
      expect(sanitizeUrl('?query=1')).toBe('?query=1');
    });

    test('allows mailto links', () => {
      const input = 'mailto:test@example.com';
      expect(sanitizeUrl(input)).toBe(input);
    });

    test('allows tel links', () => {
      const input = 'tel:+1234567890';
      expect(sanitizeUrl(input)).toBe(input);
    });

    test('blocks javascript: URLs', () => {
      expect(sanitizeUrl('javascript:alert(1)')).toBe('');
      expect(sanitizeUrl('JAVASCRIPT:alert(1)')).toBe('');
      expect(sanitizeUrl('  javascript:alert(1)  ')).toBe('');
    });

    test('blocks data: URLs', () => {
      expect(sanitizeUrl('data:text/html,<script>alert(1)</script>')).toBe('');
    });

    test('blocks vbscript: URLs', () => {
      expect(sanitizeUrl('vbscript:msgbox(1)')).toBe('');
    });

    test('handles empty input', () => {
      expect(sanitizeUrl('')).toBe('');
    });
  });

  describe('sanitizePhone', () => {
    test('removes non-numeric characters', () => {
      expect(sanitizePhone('(01) 234-567-890')).toBe('01234567890');
    });

    test('keeps + for international format', () => {
      expect(sanitizePhone('+20 123 456 7890')).toBe('+201234567890');
    });

    test('removes letters', () => {
      expect(sanitizePhone('01234abc567')).toBe('01234567');
    });
  });

  describe('sanitizeEmail', () => {
    test('returns valid email', () => {
      expect(sanitizeEmail('Test@Example.COM')).toBe('test@example.com');
    });

    test('trims whitespace', () => {
      expect(sanitizeEmail('  test@example.com  ')).toBe('test@example.com');
    });

    test('returns empty for invalid email', () => {
      expect(sanitizeEmail('not-an-email')).toBe('');
      expect(sanitizeEmail('missing@domain')).toBe('');
      expect(sanitizeEmail('@nodomain.com')).toBe('');
    });
  });

  describe('sanitizeObject', () => {
    test('sanitizes string values', () => {
      const input = {
        name: '<script>evil()</script>John',
        age: 25,
      };
      const result = sanitizeObject(input);
      expect(result.name).not.toContain('<script>');
      expect(result.age).toBe(25);
    });

    test('sanitizes nested objects', () => {
      const input = {
        user: {
          name: '<b>John</b>',
        },
      };
      const result = sanitizeObject(input);
      expect(result.user.name).not.toContain('<b>');
    });

    test('sanitizes arrays', () => {
      const input = {
        items: ['<script>1</script>', '<script>2</script>'],
      };
      const result = sanitizeObject(input);
      expect(result.items[0]).not.toContain('<script>');
      expect(result.items[1]).not.toContain('<script>');
    });

    test('handles null and undefined', () => {
      const input = {
        nullValue: null,
        undefinedValue: undefined,
      };
      const result = sanitizeObject(input);
      expect(result.nullValue).toBeNull();
      expect(result.undefinedValue).toBeUndefined();
    });
  });
});
