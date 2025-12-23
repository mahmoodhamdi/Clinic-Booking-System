import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// Routes that don't require authentication
const publicRoutes = ['/login', '/register', '/forgot-password', '/verify-otp', '/reset-password'];

// Routes that require admin role
const adminRoutes = ['/admin'];

interface UserCookie {
  id: number;
  name: string;
  role: 'admin' | 'secretary' | 'patient';
  avatar: string | null;
}

function parseUserCookie(cookieValue: string | undefined): UserCookie | null {
  if (!cookieValue) return null;
  try {
    return JSON.parse(cookieValue) as UserCookie;
  } catch {
    return null;
  }
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // auth_token is HttpOnly, so we check the 'user' cookie for auth status
  // The user cookie is set by the server alongside auth_token
  const authToken = request.cookies.get('auth_token')?.value;
  const userCookie = request.cookies.get('user')?.value;
  const user = parseUserCookie(userCookie);

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

  // If no auth token and trying to access protected route
  if (!authToken && !isPublicRoute && pathname !== '/') {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  // If has auth token and trying to access auth pages
  if (authToken && isPublicRoute) {
    // Redirect based on role
    if (user?.role === 'admin' || user?.role === 'secretary') {
      return NextResponse.redirect(new URL('/admin/dashboard', request.url));
    }
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

  // Check admin routes
  if (pathname.startsWith('/admin')) {
    if (!user || (user.role !== 'admin' && user.role !== 'secretary')) {
      return NextResponse.redirect(new URL('/dashboard', request.url));
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
