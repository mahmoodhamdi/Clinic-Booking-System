'use client';

import React, { memo, useState } from 'react';
import Image from 'next/image';
import { cn } from '@/lib/utils';

interface AvatarProps {
  src: string | null | undefined;
  alt: string;
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
  fallbackClassName?: string;
}

const sizeMap = {
  xs: 24,
  sm: 32,
  md: 40,
  lg: 48,
  xl: 64,
};

const textSizeMap = {
  xs: 'text-xs',
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
  xl: 'text-xl',
};

/**
 * Optimized Avatar component with lazy loading and fallback.
 * Uses Next.js Image for automatic optimization.
 */
export const Avatar = memo(function Avatar({
  src,
  alt,
  size = 'md',
  className = '',
  fallbackClassName = '',
}: AvatarProps) {
  const [imageError, setImageError] = useState(false);
  const dimension = sizeMap[size];
  const textSize = textSizeMap[size];

  // Get initials from alt text (name)
  const getInitials = (name: string): string => {
    const parts = name.trim().split(' ');
    if (parts.length === 1) {
      return parts[0].charAt(0).toUpperCase();
    }
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
  };

  // Show fallback if no src or image failed to load
  if (!src || imageError) {
    return (
      <div
        className={cn(
          'flex items-center justify-center rounded-full bg-primary/10 text-primary font-medium',
          textSize,
          fallbackClassName,
          className
        )}
        style={{ width: dimension, height: dimension }}
        aria-label={alt}
      >
        {getInitials(alt)}
      </div>
    );
  }

  return (
    <div
      className={cn('relative rounded-full overflow-hidden', className)}
      style={{ width: dimension, height: dimension }}
    >
      <Image
        src={src}
        alt={alt}
        width={dimension}
        height={dimension}
        className="object-cover"
        loading="lazy"
        onError={() => setImageError(true)}
        unoptimized={src.startsWith('http')} // Skip optimization for external URLs
      />
    </div>
  );
});

Avatar.displayName = 'Avatar';

export default Avatar;
