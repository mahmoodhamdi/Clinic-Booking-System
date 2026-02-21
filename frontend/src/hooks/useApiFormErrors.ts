import { useEffect } from 'react';
import { UseFormReturn, FieldValues, Path } from 'react-hook-form';
import { getValidationErrors } from '@/lib/api/client';

/**
 * Automatically maps API validation errors (422 responses) onto
 * React Hook Form field errors.
 *
 * Usage:
 *   useApiFormErrors(form, mutationError);
 *
 * When `error` changes and contains validation details, each field
 * message is set via `form.setError(field, { type: 'server', message })`.
 */
export function useApiFormErrors<T extends FieldValues>(
  form: UseFormReturn<T>,
  error: unknown
) {
  useEffect(() => {
    const validationErrors = getValidationErrors(error);
    if (validationErrors) {
      Object.entries(validationErrors).forEach(([field, messages]) => {
        form.setError(field as Path<T>, {
          type: 'server',
          message: Array.isArray(messages) ? messages[0] : messages,
        });
      });
    }
  }, [error, form]);
}
