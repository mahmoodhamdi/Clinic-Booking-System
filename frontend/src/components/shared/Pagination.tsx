'use client';

import { FC } from 'react';
import { useTranslations } from 'next-intl';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface PaginationProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
  className?: string;
}

function buildPageNumbers(currentPage: number, lastPage: number): Array<number | 'ellipsis'> {
  if (lastPage <= 7) {
    return Array.from({ length: lastPage }, (_, i) => i + 1);
  }

  const pages: Array<number | 'ellipsis'> = [];

  // Always show first page
  pages.push(1);

  if (currentPage > 3) {
    pages.push('ellipsis');
  }

  // Pages around current
  const start = Math.max(2, currentPage - 1);
  const end = Math.min(lastPage - 1, currentPage + 1);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  if (currentPage < lastPage - 2) {
    pages.push('ellipsis');
  }

  // Always show last page
  pages.push(lastPage);

  return pages;
}

export const Pagination: FC<PaginationProps> = ({
  currentPage,
  lastPage,
  onPageChange,
  className,
}) => {
  const t = useTranslations();

  if (lastPage <= 1) return null;

  const pages = buildPageNumbers(currentPage, lastPage);

  return (
    <div
      className={cn('flex items-center justify-center gap-1 flex-wrap', className)}
      role="navigation"
      aria-label={t('common.pagination')}
    >
      {/* Previous button */}
      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
        aria-label={t('common.previous')}
        className="gap-1"
      >
        <ChevronRight className="h-4 w-4 rtl:rotate-0 ltr:rotate-180" />
        {t('common.previous')}
      </Button>

      {/* Page numbers */}
      {pages.map((page, index) =>
        page === 'ellipsis' ? (
          <span
            key={`ellipsis-${index}`}
            className="px-2 py-1 text-sm text-muted-foreground select-none"
            aria-hidden="true"
          >
            &hellip;
          </span>
        ) : (
          <Button
            key={page}
            variant={page === currentPage ? 'default' : 'outline'}
            size="sm"
            onClick={() => onPageChange(page)}
            aria-label={`${t('common.page')} ${page}`}
            aria-current={page === currentPage ? 'page' : undefined}
            className="min-w-[2rem]"
          >
            {page}
          </Button>
        )
      )}

      {/* Next button */}
      <Button
        variant="outline"
        size="sm"
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === lastPage}
        aria-label={t('common.next')}
        className="gap-1"
      >
        {t('common.next')}
        <ChevronLeft className="h-4 w-4 rtl:rotate-0 ltr:rotate-180" />
      </Button>
    </div>
  );
};

export default Pagination;
