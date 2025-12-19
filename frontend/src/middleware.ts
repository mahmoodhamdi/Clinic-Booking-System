import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// Routes that don't require authentication
const publicRoutes = ['/login', '/register', '/forgot-password', '/verify-otp', '/reset-password'];

// Routes that require admin role
const adminRoutes = ['/admin'];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const token = request.cookies.get('token')?.value;
  const userCookie = request.cookies.get('user')?.value;

  // Check if it's an API route or static file
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/api') ||
    pathname.includes('.')
  ) {
    return NextResponse.next();
  }

  // Check if route is public
  const isPublicRoute = publicRoutes.some((route) => pathname.startsWith(route));

  // If no token and trying to access protected route
  if (!token && !isPublicRoute && pathname !== '/') {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  // If has token and trying to access auth pages
  if (token && isPublicRoute) {
    // Parse user to check role
    let user = null;
    try {
      if (userCookie) {
        user = JSON.parse(userCookie);
      }
    } catch {
      // Invalid user cookie
    }

    // Redirect based on role
    if (user?.role === 'admin' || user?.role === 'secretary') {
      return NextResponse.redirect(new URL('/admin/dashboard', request.url));
    }
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  // Check admin routes
  if (pathname.startsWith('/admin')) {
    let user = null;
    try {
      if (userCookie) {
        user = JSON.parse(userCookie);
      }
    } catch {
      // Invalid user cookie
    }

    if (!user || (user.role !== 'admin' && user.role !== 'secretary')) {
      return NextResponse.redirect(new URL('/dashboard', request.url));
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
