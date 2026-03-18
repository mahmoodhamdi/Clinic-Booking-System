'use client';

import { useState, useCallback, useMemo } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
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
  Bell,
  ChevronLeft,
  ChevronRight,
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
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';
import { ThemeToggle } from '@/components/shared/ThemeToggle';
import { useAuthStore } from '@/lib/stores/auth';
import { patientApi } from '@/lib/api/patient';
import { cn } from '@/lib/utils';
import type { ApiResponse } from '@/types';

interface AdminLayoutProps {
  children: React.ReactNode;
}

interface NavItem {
  name: string;
  href: string;
  icon: React.ElementType;
}

interface NavLinkProps {
  item: NavItem;
  collapsed?: boolean;
  onClick?: () => void;
  isActive: boolean;
}

function NavLink({ item, collapsed = false, onClick, isActive }: NavLinkProps) {
  return (
    <Link
      href={item.href}
      onClick={onClick}
      className={cn(
        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
        isActive
          ? 'bg-primary text-primary-foreground shadow-primary'
          : 'text-muted-foreground hover:bg-primary/10 hover:text-primary',
        collapsed && 'justify-center px-2'
      )}
      title={collapsed ? item.name : undefined}
    >
      <item.icon className="h-5 w-5 flex-shrink-0" />
      {!collapsed && <span>{item.name}</span>}
    </Link>
  );
}

interface SidebarContentProps {
  collapsed?: boolean;
  mobile?: boolean;
  navigation: NavItem[];
  settingsNavigation: NavItem[];
  pathname: string;
  onNavClick?: () => void;
  onCollapseToggle?: () => void;
  onLogout?: () => void;
  appName: string;
  closeLabel: string;
  logoutLabel?: string;
}

function SidebarContent({
  collapsed = false,
  mobile = false,
  navigation,
  settingsNavigation,
  pathname,
  onNavClick,
  onCollapseToggle,
  onLogout,
  appName,
  closeLabel,
  logoutLabel,
}: SidebarContentProps) {
  const isActive = (href: string) => pathname === href || pathname.startsWith(href + '/');

  return (
    <div className="flex flex-col h-full">
      {/* Logo */}
      <div className={cn('flex items-center h-16 px-4', collapsed && 'justify-center px-2')}>
        <Link href="/admin/dashboard" className="flex items-center gap-2.5 group">
          <div className="h-9 w-9 rounded-xl bg-gradient-primary flex items-center justify-center shadow-primary flex-shrink-0 transition-transform duration-200 group-hover:scale-105">
            <Heart className="h-4.5 w-4.5 text-white" fill="white" />
          </div>
          {!collapsed && (
            <span className="font-bold text-lg text-foreground">{appName}</span>
          )}
        </Link>
      </div>

      <Separator className="opacity-50" />

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto p-3 space-y-1">
        {navigation.map((item) => (
          <NavLink
            key={item.href}
            item={item}
            collapsed={collapsed}
            isActive={isActive(item.href)}
            onClick={onNavClick}
          />
        ))}

        <Separator className="my-4 opacity-50" />

        {settingsNavigation.map((item) => (
          <NavLink
            key={item.href}
            item={item}
            collapsed={collapsed}
            isActive={isActive(item.href)}
            onClick={onNavClick}
          />
        ))}
      </nav>

      {/* Collapse Button (Desktop only) */}
      {!mobile && onCollapseToggle && (
        <div className="p-3 border-t border-border/50">
          <Button
            variant="ghost"
            size="sm"
            className="w-full hover:bg-primary/10 hover:text-primary"
            onClick={onCollapseToggle}
          >
            {collapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <>
                <ChevronLeft className="h-4 w-4 me-2" />
                {closeLabel}
              </>
            )}
          </Button>
        </div>
      )}

      {/* Logout Button (Mobile only) */}
      {mobile && onLogout && (
        <div className="p-3 border-t border-border/50">
          <button
            onClick={() => {
              onNavClick?.();
              onLogout();
            }}
            className="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-destructive hover:bg-destructive/10 w-full transition-colors"
          >
            <LogOut className="h-4 w-4" />
            {logoutLabel}
          </button>
        </div>
      )}
    </div>
  );
}

export function AdminLayout({ children }: AdminLayoutProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const { user, logout } = useAuthStore();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Fetch unread notification count
  const { data: unreadData } = useQuery<ApiResponse<{ count: number }>>({
    queryKey: ['admin-unread-notifications-count'],
    queryFn: () => patientApi.getUnreadCount(),
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  const unreadCount = unreadData?.data?.count ?? 0;

  const navigation = useMemo<NavItem[]>(() => [
    { name: t('admin.dashboard.title'), href: '/admin/dashboard', icon: LayoutDashboard },
    { name: t('admin.appointments.title'), href: '/admin/appointments', icon: Calendar },
    { name: t('admin.patients.title'), href: '/admin/patients', icon: Users },
    { name: t('admin.medicalRecords.title'), href: '/admin/medical-records', icon: FileText },
    { name: t('admin.prescriptions.title'), href: '/admin/prescriptions', icon: Pill },
    { name: t('admin.payments.title'), href: '/admin/payments', icon: CreditCard },
    { name: t('admin.reports.title'), href: '/admin/reports', icon: BarChart3 },
  ], [t]);

  const settingsNavigation = useMemo<NavItem[]>(() => [
    { name: t('admin.settings.title'), href: '/admin/settings', icon: Settings },
    { name: t('admin.schedules.title'), href: '/admin/schedules', icon: Clock },
    { name: t('admin.vacations.title'), href: '/admin/vacations', icon: CalendarOff },
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

  const handleCollapseToggle = useCallback(() => {
    setSidebarCollapsed(prev => !prev);
  }, []);

  return (
    <div className="min-h-screen bg-background">
      {/* Desktop Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 start-0 z-50 hidden lg:flex flex-col bg-card border-e border-border/50 transition-all duration-300 shadow-sm',
          sidebarCollapsed ? 'w-16' : 'w-64'
        )}
      >
        <SidebarContent
          collapsed={sidebarCollapsed}
          navigation={navigation}
          settingsNavigation={settingsNavigation}
          pathname={pathname}
          onCollapseToggle={handleCollapseToggle}
          appName={t('common.appName')}
          closeLabel={t('common.close')}
        />
      </aside>

      {/* Mobile Sidebar */}
      <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
        <SheetContent side="left" className="w-72 p-0">
          <SidebarContent
            mobile
            navigation={navigation}
            settingsNavigation={settingsNavigation}
            pathname={pathname}
            onNavClick={handleMobileNavClick}
            onLogout={handleLogout}
            appName={t('common.appName')}
            closeLabel={t('common.close')}
            logoutLabel={t('auth.logout')}
          />
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
        <header className="sticky top-0 z-40 glass border-b border-border/50">
          <div className="flex h-16 items-center justify-between px-4 sm:px-6">
            {/* Mobile Menu Button */}
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden hover:bg-primary/10"
              onClick={() => setMobileMenuOpen(true)}
            >
              <Menu className="h-5 w-5" />
            </Button>

            {/* Spacer */}
            <div className="flex-1" />

            {/* Right Side */}
            <div className="flex items-center gap-1">
              {/* Notifications */}
              <Button variant="ghost" size="icon" className="relative hover:bg-primary/10" aria-label={t('navigation.notifications')} asChild>
                <Link href="/admin/notifications">
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
                        {user?.name?.charAt(0) || 'A'}
                      </AvatarFallback>
                    </Avatar>
                    <div className="hidden sm:block text-start">
                      <p className="text-sm font-medium">{user?.name}</p>
                      <p className="text-xs text-muted-foreground">{user?.role}</p>
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
                    className="text-destructive cursor-pointer"
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
        <main className="p-4 sm:p-6">{children}</main>
      </div>
    </div>
  );
}
