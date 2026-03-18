'use client';

import { useState, useCallback, useMemo } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import {
  LayoutDashboard,
  Calendar,
  CalendarPlus,
  FileText,
  Pill,
  Bell,
  User,
  LogOut,
  Menu,
  X,
  Heart,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Badge } from '@/components/ui/badge';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';
import { ThemeToggle } from '@/components/shared/ThemeToggle';
import { useAuthStore } from '@/lib/stores/auth';
import { patientApi } from '@/lib/api/patient';
import { cn } from '@/lib/utils';
import type { ApiResponse } from '@/types';

interface PatientLayoutProps {
  children: React.ReactNode;
}

interface NavItem {
  name: string;
  href: string;
  icon: React.ElementType;
}

interface NavLinksProps {
  navigation: NavItem[];
  pathname: string;
  mobile?: boolean;
  onNavClick?: () => void;
}

function NavLinks({ navigation, pathname, mobile = false, onNavClick }: NavLinksProps) {
  return (
    <nav className={cn('flex gap-1', mobile ? 'flex-col' : 'flex-row')}>
      {navigation.map((item) => {
        const isActive = pathname === item.href;
        return (
          <Link
            key={item.href}
            href={item.href}
            onClick={onNavClick}
            className={cn(
              'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200',
              isActive
                ? 'bg-primary text-primary-foreground shadow-primary'
                : 'text-muted-foreground hover:bg-primary/10 hover:text-primary'
            )}
          >
            <item.icon className="h-4 w-4" />
            {item.name}
          </Link>
        );
      })}
    </nav>
  );
}

export function PatientLayout({ children }: PatientLayoutProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const { user, logout } = useAuthStore();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Fetch unread notification count
  const { data: unreadData } = useQuery<ApiResponse<{ count: number }>>({
    queryKey: ['unread-notifications-count'],
    queryFn: () => patientApi.getUnreadCount(),
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  const unreadCount = unreadData?.data?.count ?? 0;

  const navigation = useMemo<NavItem[]>(() => [
    { name: t('navigation.dashboard'), href: '/dashboard', icon: LayoutDashboard },
    { name: t('navigation.bookAppointment'), href: '/book', icon: CalendarPlus },
    { name: t('navigation.myAppointments'), href: '/appointments', icon: Calendar },
    { name: t('navigation.medicalRecords'), href: '/medical-records', icon: FileText },
    { name: t('navigation.prescriptions'), href: '/prescriptions', icon: Pill },
    { name: t('navigation.notifications'), href: '/notifications', icon: Bell },
  ], [t]);

  const handleLogout = useCallback(async () => {
    await logout();
    document.cookie = 'token=;path=/;max-age=0';
    document.cookie = 'user=;path=/;max-age=0';
    window.location.href = '/login';
  }, [logout]);

  const handleMobileNavClick = useCallback(() => {
    setMobileMenuOpen(false);
  }, []);

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="sticky top-0 z-50 glass border-b border-border/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 items-center justify-between">
            {/* Logo */}
            <Link href="/dashboard" className="flex items-center gap-2.5 group">
              <div className="h-9 w-9 rounded-xl bg-gradient-primary flex items-center justify-center shadow-primary transition-transform duration-200 group-hover:scale-105">
                <Heart className="h-4.5 w-4.5 text-white" fill="white" />
              </div>
              <span className="font-bold text-lg hidden sm:block text-foreground">
                {t('common.appName')}
              </span>
            </Link>

            {/* Desktop Navigation */}
            <div className="hidden lg:flex">
              <NavLinks navigation={navigation} pathname={pathname} />
            </div>

            {/* Right Side */}
            <div className="flex items-center gap-1">
              {/* Notifications */}
              <Button variant="ghost" size="icon" className="relative hover:bg-primary/10" aria-label={t('navigation.notifications')} asChild>
                <Link href="/notifications">
                  <Bell className="h-5 w-5" />
                  {unreadCount > 0 && (
                    <Badge className="absolute -top-1 -end-1 h-5 min-w-5 flex items-center justify-center p-0 text-xs bg-destructive text-white border-2 border-background">
                      {unreadCount > 99 ? '99+' : unreadCount}
                    </Badge>
                  )}
                </Link>
              </Button>

              {/* Theme Toggle */}
              <ThemeToggle />

              {/* Language Switcher */}
              <LanguageSwitcher />

              {/* User Menu */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="flex items-center gap-2 hover:bg-primary/10">
                    <Avatar className="h-8 w-8 ring-2 ring-primary/20">
                      <AvatarImage src={user?.avatar || undefined} />
                      <AvatarFallback className="bg-primary/10 text-primary font-semibold">
                        {user?.name?.charAt(0) || 'U'}
                      </AvatarFallback>
                    </Avatar>
                    <span className="hidden sm:block text-sm font-medium">
                      {user?.name}
                    </span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                  <DropdownMenuItem asChild>
                    <Link href="/profile" className="flex items-center gap-2">
                      <User className="h-4 w-4" />
                      {t('navigation.profile')}
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onClick={handleLogout}
                    className="text-destructive cursor-pointer"
                  >
                    <LogOut className="h-4 w-4 me-2" />
                    {t('auth.logout')}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Mobile Menu Button */}
              <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="lg:hidden hover:bg-primary/10">
                    <Menu className="h-5 w-5" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" className="w-72">
                  <div className="flex flex-col h-full">
                    <div className="flex items-center justify-between mb-6">
                      <div className="flex items-center gap-2.5">
                        <div className="h-8 w-8 rounded-xl bg-gradient-primary flex items-center justify-center">
                          <Heart className="h-4 w-4 text-white" fill="white" />
                        </div>
                        <span className="font-bold text-lg">{t('common.appName')}</span>
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={handleMobileNavClick}
                        className="hover:bg-primary/10"
                      >
                        <X className="h-5 w-5" />
                      </Button>
                    </div>
                    <NavLinks
                      navigation={navigation}
                      pathname={pathname}
                      mobile
                      onNavClick={handleMobileNavClick}
                    />
                    {/* Mobile Profile & Logout */}
                    <div className="mt-auto pt-4 border-t border-border">
                      <Link
                        href="/profile"
                        onClick={handleMobileNavClick}
                        className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:bg-primary/10 hover:text-primary"
                      >
                        <User className="h-4 w-4" />
                        {t('navigation.profile')}
                      </Link>
                      <button
                        onClick={() => {
                          handleMobileNavClick();
                          handleLogout();
                        }}
                        className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 w-full"
                      >
                        <LogOut className="h-4 w-4" />
                        {t('auth.logout')}
                      </button>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
    </div>
  );
}
