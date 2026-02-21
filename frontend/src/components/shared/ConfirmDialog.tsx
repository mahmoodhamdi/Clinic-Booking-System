'use client';

import { FC } from 'react';
import { useTranslations } from 'next-intl';
import { Loader2 } from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

interface ConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'default' | 'destructive';
  loading?: boolean;
  onConfirm: () => void;
}

export const ConfirmDialog: FC<ConfirmDialogProps> = ({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel,
  cancelLabel,
  variant = 'default',
  loading = false,
  onConfirm,
}) => {
  const t = useTranslations();

  const resolvedConfirmLabel = confirmLabel ?? t('common.confirm');
  const resolvedCancelLabel = cancelLabel ?? t('common.cancel');

  const handleConfirm = (e: React.MouseEvent<HTMLButtonElement>) => {
    // Prevent dialog from auto-closing when loading — we control close manually
    e.preventDefault();
    if (!loading) {
      onConfirm();
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          {description && (
            <AlertDialogDescription>{description}</AlertDialogDescription>
          )}
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={loading}>
            {resolvedCancelLabel}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={handleConfirm}
            disabled={loading}
            className={cn(
              variant === 'destructive'
                ? buttonVariants({ variant: 'destructive' })
                : buttonVariants({ variant: 'default' })
            )}
          >
            {loading && <Loader2 className="h-4 w-4 animate-spin me-2" />}
            {resolvedConfirmLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
};

export default ConfirmDialog;
