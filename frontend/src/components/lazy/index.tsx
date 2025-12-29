'use client';

import dynamic from 'next/dynamic';
import React from 'react';

// Lazy load Calendar component
export const LazyCalendar = dynamic(
  () => import('@/components/ui/calendar').then((mod) => mod.Calendar),
  {
    loading: () => (
      <div className="h-64 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg" />
    ),
    ssr: false,
  }
);

// Lazy load VirtualizedList component
export const LazyVirtualizedList = dynamic(
  () => import('@/components/shared/VirtualizedList').then((mod) => mod.VirtualizedList),
  {
    loading: () => (
      <div className="h-96 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg" />
    ),
  }
);

// Lazy load Dialog component for heavy modals
export const LazyDialog = dynamic(
  () => import('@/components/ui/dialog').then((mod) => mod.Dialog),
  {
    loading: () => null,
  }
);

// Lazy load Tabs component
export const LazyTabs = dynamic(
  () => import('@/components/ui/tabs').then((mod) => mod.Tabs),
  {
    loading: () => (
      <div className="h-12 bg-gray-100 dark:bg-gray-800 animate-pulse rounded-lg" />
    ),
  }
);
