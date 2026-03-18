# Design System - Clinic Booking System

## Color Palette

### Primary (Teal - Medical)
| Token | Light Mode | Dark Mode |
|-------|-----------|-----------|
| `--primary` | `oklch(0.600 0.130 185)` (#0D9488) | `oklch(0.720 0.130 183)` |
| `--primary-foreground` | White | Dark slate |

### Semantic Colors
| Token | Usage | Light | Dark |
|-------|-------|-------|------|
| `--success` | Positive states, confirmed | `oklch(0.620 0.185 145)` | Lighter |
| `--warning` | Pending, attention | `oklch(0.750 0.183 65)` | Lighter |
| `--info` | Informational | `oklch(0.600 0.180 255)` | Lighter |
| `--destructive` | Errors, danger | `oklch(0.577 0.245 27)` | Lighter |

### Chart Colors
1. Teal (primary) - `--chart-1`
2. Blue - `--chart-2`
3. Amber - `--chart-3`
4. Purple - `--chart-4`
5. Coral - `--chart-5`

### Neutral Scale
Using OKLCh with subtle teal tint for light mode backgrounds.

## Typography
- **Font Family**: Cairo (Arabic + Latin) via Google Fonts
- **Weights**: 400 (regular), 500 (medium), 600 (semibold), 700 (bold)
- **Monospace**: Geist Mono

## Spacing
- Base unit: 4px (Tailwind default)
- Common: p-4, p-6, gap-2, gap-4, gap-6

## Border Radius
- Base: 0.75rem (12px) - `--radius`
- Cards: `rounded-2xl`
- Buttons: `rounded-md`
- Nav items: `rounded-xl`
- Avatars/Badges: `rounded-full`

## Shadows
- `shadow-primary`: Teal-tinted shadow for primary elements
- `shadow-primary-lg`: Larger teal shadow for hover states
- `shadow-sm/md/lg/xl`: Standard Tailwind shadows

## Effects
- **Glass**: `backdrop-blur(12px)` + semi-transparent background
- **Card Hover**: `translateY(-2px)` + enhanced shadow
- **Gradients**: `bg-gradient-primary`, `bg-gradient-hero`, `bg-gradient-subtle`

## Animations
- `animate-fade-in`: 0.5s fade
- `animate-fade-in-up`: 0.5s fade + slide up
- `animate-fade-in-down`: 0.4s fade + slide down
- `animate-slide-in-right/left`: 0.3s directional slide
- `animate-scale-in`: 0.2s scale up
- `animate-shimmer`: Loading shimmer effect
- `animate-float`: Gentle floating motion
- Stagger classes: `.stagger-1` through `.stagger-6`

## Dark Mode
- Uses `next-themes` with `.dark` class strategy
- System preference as default
- Persisted in localStorage
- All OKLCh colors have dark variants defined
