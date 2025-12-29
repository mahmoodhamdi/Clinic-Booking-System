'use client';

import { useState, useCallback, useMemo } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';
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
import { useAuthStore } from '@/lib/stores/auth';
import { cn } from '@/lib/utils';

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
              'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
              isActive
                ? 'bg-primary text-primary-foreground'
                : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
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
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <header className="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 items-center justify-between">
            {/* Logo */}
            <Link href="/dashboard" className="flex items-center gap-2">
              <div className="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
                <span className="text-white font-bold text-lg">C</span>
              </div>
              <span className="font-bold text-lg hidden sm:block">
                {t('common.appName')}
              </span>
            </Link>

            {/* Desktop Navigation */}
            <div className="hidden lg:flex">
              <NavLinks navigation={navigation} pathname={pathname} />
            </div>

            {/* Right Side */}
            <div className="flex items-center gap-2">
              {/* Notifications */}
              <Button variant="ghost" size="icon" className="relative" asChild>
                <Link href="/notifications">
                  <Bell className="h-5 w-5" />
                  <Badge className="absolute -top-1 -end-1 h-5 w-5 flex items-center justify-center p-0 text-xs">
                    3
                  </Badge>
                </Link>
              </Button>

              {/* Language Switcher */}
              <LanguageSwitcher />

              {/* User Menu */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="flex items-center gap-2">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={user?.avatar || ''} />
                      <AvatarFallback>
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
                    className="text-red-600 cursor-pointer"
                  >
                    <LogOut className="h-4 w-4 me-2" />
                    {t('auth.logout')}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Mobile Menu Button */}
              <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="lg:hidden">
                    <Menu className="h-5 w-5" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" className="w-72">
                  <div className="flex flex-col h-full">
                    <div className="flex items-center justify-between mb-6">
                      <span className="font-bold text-lg">{t('common.appName')}</span>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={handleMobileNavClick}
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
