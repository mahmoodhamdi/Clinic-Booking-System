import { renderHook, act } from '@testing-library/react';
import { useDebounce, useDebouncedCallback, useThrottledCallback } from '@/hooks/useDebounce';

// Mock timers
jest.useFakeTimers();

describe('useDebounce', () => {
  afterEach(() => {
    jest.clearAllTimers();
  });

  test('returns initial value immediately', () => {
    const { result } = renderHook(() => useDebounce('initial', 500));
    expect(result.current).toBe('initial');
  });

  test('debounces value changes', () => {
    const { result, rerender } = renderHook(
      ({ value, delay }) => useDebounce(value, delay),
      { initialProps: { value: 'first', delay: 500 } }
    );

    expect(result.current).toBe('first');

    // Change value
    rerender({ value: 'second', delay: 500 });

    // Value should not change immediately
    expect(result.current).toBe('first');

    // Fast forward time
    act(() => {
      jest.advanceTimersByTime(500);
    });

    // Now value should be updated
    expect(result.current).toBe('second');
  });

  test('resets timer on rapid changes', () => {
    const { result, rerender } = renderHook(
      ({ value, delay }) => useDebounce(value, delay),
      { initialProps: { value: 'a', delay: 500 } }
    );

    // Rapid changes
    rerender({ value: 'b', delay: 500 });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    rerender({ value: 'c', delay: 500 });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    rerender({ value: 'd', delay: 500 });

    // Should still be 'a' because timer keeps resetting
    expect(result.current).toBe('a');

    // Wait for full delay
    act(() => {
      jest.advanceTimersByTime(500);
    });

    // Now should be 'd' (final value)
    expect(result.current).toBe('d');
  });

  test('works with different types', () => {
    const { result, rerender } = renderHook(
      ({ value, delay }) => useDebounce(value, delay),
      { initialProps: { value: { count: 1 }, delay: 300 } }
    );

    expect(result.current).toEqual({ count: 1 });

    rerender({ value: { count: 2 }, delay: 300 });

    act(() => {
      jest.advanceTimersByTime(300);
    });

    expect(result.current).toEqual({ count: 2 });
  });
});

describe('useDebouncedCallback', () => {
  afterEach(() => {
    jest.clearAllTimers();
  });

  test('debounces callback execution', () => {
    const callback = jest.fn();

    const { result } = renderHook(() => useDebouncedCallback(callback, 500));

    // Call multiple times
    result.current('a');
    result.current('b');
    result.current('c');

    // Callback should not be called yet
    expect(callback).not.toHaveBeenCalled();

    // Wait for delay
    act(() => {
      jest.advanceTimersByTime(500);
    });

    // Should be called once with last argument
    expect(callback).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledWith('c');
  });

  test('resets on subsequent calls', () => {
    const callback = jest.fn();

    const { result } = renderHook(() => useDebouncedCallback(callback, 500));

    result.current('first');

    act(() => {
      jest.advanceTimersByTime(400);
    });

    // Call again before timeout
    result.current('second');

    act(() => {
      jest.advanceTimersByTime(400);
    });

    // Still not called
    expect(callback).not.toHaveBeenCalled();

    act(() => {
      jest.advanceTimersByTime(100);
    });

    // Now called with 'second'
    expect(callback).toHaveBeenCalledWith('second');
  });
});

describe('useThrottledCallback', () => {
  afterEach(() => {
    jest.clearAllTimers();
  });

  test('executes first call immediately', () => {
    const callback = jest.fn();

    const { result } = renderHook(() => useThrottledCallback(callback, 500));

    result.current('first');

    // Should be called immediately
    expect(callback).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledWith('first');
  });

  test('throttles subsequent calls', () => {
    const callback = jest.fn();

    const { result } = renderHook(() => useThrottledCallback(callback, 500));

    result.current('first');
    result.current('second');
    result.current('third');

    // Only first call should execute immediately
    expect(callback).toHaveBeenCalledTimes(1);
    expect(callback).toHaveBeenCalledWith('first');

    // Wait for throttle period
    act(() => {
      jest.advanceTimersByTime(500);
    });

    // Should have called with last value
    expect(callback).toHaveBeenCalledTimes(2);
    expect(callback).toHaveBeenLastCalledWith('third');
  });

  test('allows call after throttle period', () => {
    const callback = jest.fn();

    const { result } = renderHook(() => useThrottledCallback(callback, 500));

    result.current('first');

    act(() => {
      jest.advanceTimersByTime(600);
    });

    result.current('second');

    expect(callback).toHaveBeenCalledTimes(2);
    expect(callback).toHaveBeenNthCalledWith(1, 'first');
    expect(callback).toHaveBeenNthCalledWith(2, 'second');
  });
});
