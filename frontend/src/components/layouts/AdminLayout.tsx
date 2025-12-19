'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';
import {
  LayoutDashboard,
  Calendar,
  Users,
  FileText,
  Pill,
  CreditCard,
  BarChart3,
  Settings,
  Clock,
  CalendarOff,
  LogOut,
  Menu,
  X,
  Bell,
  ChevronLeft,
  ChevronRight,
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
import { Separator } from '@/components/ui/separator';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';
import { useAuthStore } from '@/lib/stores/auth';
import { cn } from '@/lib/utils';

interface AdminLayoutProps {
  children: React.ReactNode;
}

export function AdminLayout({ children }: AdminLayoutProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const { user, logout } = useAuthStore();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navigation = [
    { name: t('admin.dashboard.title'), href: '/admin/dashboard', icon: LayoutDashboard },
    { name: t('admin.appointments.title'), href: '/admin/appointments', icon: Calendar },
    { name: t('admin.patients.title'), href: '/admin/patients', icon: Users },
    { name: t('admin.medicalRecords.title'), href: '/admin/medical-records', icon: FileText },
    { name: t('admin.prescriptions.title'), href: '/admin/prescriptions', icon: Pill },
    { name: t('admin.payments.title'), href: '/admin/payments', icon: CreditCard },
    { name: t('admin.reports.title'), href: '/admin/reports', icon: BarChart3 },
  ];

  const settingsNavigation = [
    { name: t('admin.settings.title'), href: '/admin/settings', icon: Settings },
    { name: t('admin.schedules.title'), href: '/admin/schedules', icon: Clock },
    { name: t('admin.vacations.title'), href: '/admin/vacations', icon: CalendarOff },
  ];

  const handleLogout = async () => {
    await logout();
    document.cookie = 'token=;path=/;max-age=0';
    document.cookie = 'user=;path=/;max-age=0';
    window.location.href = '/login';
  };

  const NavLink = ({
    item,
    collapsed = false,
    onClick,
  }: {
    item: { name: string; href: string; icon: React.ElementType };
    collapsed?: boolean;
    onClick?: () => void;
  }) => {
    const isActive = pathname === item.href || pathname.startsWith(item.href + '/');
    return (
      <Link
        href={item.href}
        onClick={onClick}
        className={cn(
          'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
          isActive
            ? 'bg-primary text-primary-foreground'
            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
          collapsed && 'justify-center px-2'
        )}
        title={collapsed ? item.name : undefined}
      >
        <item.icon className="h-5 w-5 flex-shrink-0" />
        {!collapsed && <span>{item.name}</span>}
      </Link>
    );
  };

  const SidebarContent = ({ collapsed = false, mobile = false }: { collapsed?: boolean; mobile?: boolean }) => (
    <div className="flex flex-col h-full">
      {/* Logo */}
      <div className={cn('flex items-center h-16 px-4', collapsed && 'justify-center px-2')}>
        <Link href="/admin/dashboard" className="flex items-center gap-2">
          <div className="h-8 w-8 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
            <span className="text-white font-bold text-lg">C</span>
          </div>
          {!collapsed && (
            <span className="font-bold text-lg">{t('common.appName')}</span>
          )}
        </Link>
      </div>

      <Separator />

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto p-3 space-y-1">
        {navigation.map((item) => (
          <NavLink
            key={item.href}
            item={item}
            collapsed={collapsed}
            onClick={() => mobile && setMobileMenuOpen(false)}
          />
        ))}

        <Separator className="my-4" />

        {settingsNavigation.map((item) => (
          <NavLink
            key={item.href}
            item={item}
            collapsed={collapsed}
            onClick={() => mobile && setMobileMenuOpen(false)}
          />
        ))}
      </nav>

      {/* Collapse Button (Desktop only) */}
      {!mobile && (
        <div className="p-3 border-t">
          <Button
            variant="ghost"
            size="sm"
            className="w-full"
            onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
          >
            {collapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <>
                <ChevronLeft className="h-4 w-4 me-2" />
                {t('common.close')}
              </>
            )}
          </Button>
        </div>
      )}
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
      {/* Desktop Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 start-0 z-50 hidden lg:flex flex-col bg-white dark:bg-gray-800 border-e border-gray-200 dark:border-gray-700 transition-all duration-300',
          sidebarCollapsed ? 'w-16' : 'w-64'
        )}
      >
        <SidebarContent collapsed={sidebarCollapsed} />
      </aside>

      {/* Mobile Sidebar */}
      <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
        <SheetContent side="left" className="w-72 p-0">
          <SidebarContent mobile />
        </SheetContent>
      </Sheet>

      {/* Main Content */}
      <div
        className={cn(
          'transition-all duration-300',
          sidebarCollapsed ? 'lg:ps-16' : 'lg:ps-64'
        )}
      >
        {/* Header */}
        <header className="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
          <div className="flex h-16 items-center justify-between px-4">
            {/* Mobile Menu Button */}
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden"
              onClick={() => setMobileMenuOpen(true)}
            >
              <Menu className="h-5 w-5" />
            </Button>

            {/* Search (placeholder) */}
            <div className="flex-1 max-w-md mx-4">
              {/* Search input can be added here */}
            </div>

            {/* Right Side */}
            <div className="flex items-center gap-2">
              {/* Notifications */}
              <Button variant="ghost" size="icon" className="relative">
                <Bell className="h-5 w-5" />
                <Badge className="absolute -top-1 -end-1 h-5 w-5 flex items-center justify-center p-0 text-xs">
                  5
                </Badge>
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
                        {user?.name?.charAt(0) || 'A'}
                      </AvatarFallback>
                    </Avatar>
                    <div className="hidden sm:block text-start">
                      <p className="text-sm font-medium">{user?.name}</p>
                      <p className="text-xs text-gray-500">{user?.role}</p>
                    </div>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                  <DropdownMenuItem asChild>
                    <Link href="/admin/settings" className="flex items-center gap-2">
                      <Settings className="h-4 w-4" />
                      {t('admin.settings.title')}
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
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="p-6">{children}</main>
      </div>
    </div>
  );
}
