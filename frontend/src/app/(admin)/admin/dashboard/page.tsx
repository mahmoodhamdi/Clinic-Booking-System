'use client';

import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import {
  Users,
  Calendar,
  Clock,
  DollarSign,
  TrendingUp,
  CheckCircle2,
  AlertCircle,
  FileText,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { adminApi } from '@/lib/api/admin';
import type { Appointment, Activity, DashboardStats, ApiResponse } from '@/types';

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} className="h-32" />
        ))}
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton className="h-64 lg:col-span-2" />
        <Skeleton className="h-64" />
      </div>
    </div>
  );
}

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  color: 'blue' | 'green' | 'yellow' | 'purple';
  subtext?: string;
  trend?: { value: string; positive: boolean };
  linkHref?: string;
  linkText?: string;
}

function StatCard({ title, value, icon, color, subtext, trend, linkHref, linkText }: StatCardProps) {
  const colors = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    purple: 'bg-purple-100 text-purple-600',
  };

  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-500">{title}</p>
            <p className="text-3xl font-bold mt-1">{value}</p>
          </div>
          <div className={`h-12 w-12 rounded-full flex items-center justify-center ${colors[color]}`}>
            {icon}
          </div>
        </div>
        {trend && (
          <div className={`flex items-center gap-1 mt-2 text-sm ${trend.positive ? 'text-green-600' : 'text-red-600'}`}>
            <TrendingUp className="h-4 w-4" />
            <span>{trend.value}</span>
          </div>
        )}
        {subtext && (
          <div className="mt-2 text-sm text-gray-500">{subtext}</div>
        )}
        {linkHref && linkText && (
          <Button variant="link" className="p-0 h-auto mt-2" asChild>
            <Link href={linkHref}>{linkText}</Link>
          </Button>
        )}
      </CardContent>
    </Card>
  );
}

interface AppointmentRowProps {
  appointment: Appointment;
}

function AppointmentRow({ appointment }: AppointmentRowProps) {
  const statusColors: Record<string, string> = {
    confirmed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800',
    no_show: 'bg-gray-100 text-gray-800',
  };

  const statusLabels: Record<string, string> = {
    confirmed: 'مؤكد',
    pending: 'معلق',
    completed: 'مكتمل',
    cancelled: 'ملغى',
    no_show: 'لم يحضر',
  };

  const StatusIcon = appointment.status === 'pending' ? Clock : CheckCircle2;
  const patientName = (appointment as Appointment & { patient?: { name: string } }).patient?.name || 'مريض';

  return (
    <div className="flex items-center justify-between p-4 rounded-lg border bg-gray-50 dark:bg-gray-800">
      <div className="flex items-center gap-4">
        <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
          <span className="font-medium text-primary">
            {patientName.charAt(0)}
          </span>
        </div>
        <div>
          <p className="font-medium">{patientName}</p>
          <p className="text-sm text-gray-500">{appointment.slot_time}</p>
        </div>
      </div>
      <div className="flex items-center gap-2">
        <Badge className={statusColors[appointment.status] || 'bg-gray-100'}>
          <StatusIcon className="h-3 w-3 me-1" />
          {statusLabels[appointment.status] || appointment.status}
        </Badge>
        <Button variant="ghost" size="sm" asChild>
          <Link href={`/admin/appointments/${appointment.id}`}>عرض</Link>
        </Button>
      </div>
    </div>
  );
}

interface ActivityRowProps {
  activity: Activity;
}

function ActivityRow({ activity }: ActivityRowProps) {
  const typeIcons: Record<string, React.ReactNode> = {
    appointment: <Calendar className="h-4 w-4 text-blue-600" />,
    payment: <DollarSign className="h-4 w-4 text-green-600" />,
    medical_record: <FileText className="h-4 w-4 text-purple-600" />,
  };

  return (
    <div className="flex items-start gap-3 pb-4 border-b last:border-0 last:pb-0">
      <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
        {typeIcons[activity.type] || <AlertCircle className="h-4 w-4 text-gray-600" />}
      </div>
      <div>
        <p className="text-sm font-medium">{activity.description}</p>
        <p className="text-xs text-gray-400 mt-1">
          {format(new Date(activity.created_at), 'PPp', { locale: ar })}
        </p>
      </div>
    </div>
  );
}

function EmptyState({ message }: { message: string }) {
  return (
    <div className="text-center py-8 text-gray-500">
      {message}
    </div>
  );
}

export default function AdminDashboard() {
  const t = useTranslations('admin.dashboard');

  // Fetch dashboard stats
  const { data: stats, isLoading: statsLoading } = useQuery<ApiResponse<DashboardStats>>({
    queryKey: ['admin-dashboard-stats'],
    queryFn: () => adminApi.getDashboardStats(),
    refetchInterval: 60000, // Refresh every minute
  });

  // Fetch today's appointments
  const { data: todayAppointments, isLoading: todayLoading } = useQuery<ApiResponse<Appointment[]>>({
    queryKey: ['admin-today-appointments'],
    queryFn: () => adminApi.getTodayAppointments(),
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  // Fetch recent activity
  const { data: recentActivity, isLoading: activityLoading } = useQuery<ApiResponse<Activity[]>>({
    queryKey: ['admin-recent-activity'],
    queryFn: () => adminApi.getRecentActivity(),
    refetchInterval: 30000,
  });

  const isLoading = statsLoading || todayLoading || activityLoading;

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const dashboardStats = stats?.data;
  const pendingCount = todayAppointments?.data?.filter(a => a.status === 'pending').length ?? 0;

  return (
    <div className="space-y-6">
      {/* Page Title */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {t('title')}
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-1">
          {new Date().toLocaleDateString('ar-EG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
          })}
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title={t('totalPatients')}
          value={dashboardStats?.total_patients ?? 0}
          icon={<Users className="h-6 w-6" />}
          color="blue"
        />
        <StatCard
          title={t('todayAppointments')}
          value={dashboardStats?.today_appointments ?? 0}
          icon={<Calendar className="h-6 w-6" />}
          color="green"
          subtext={`${pendingCount} في انتظار التأكيد`}
        />
        <StatCard
          title={t('pendingAppointments')}
          value={dashboardStats?.pending_appointments ?? 0}
          icon={<Clock className="h-6 w-6" />}
          color="yellow"
          linkHref="/admin/appointments?status=pending"
          linkText="عرض الكل"
        />
        <StatCard
          title={t('todayRevenue')}
          value={`${dashboardStats?.today_revenue ?? 0} ج.م`}
          icon={<DollarSign className="h-6 w-6" />}
          color="purple"
        />
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Today's Appointments */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>{t('todayAppointments')}</CardTitle>
            <Button variant="outline" size="sm" asChild>
              <Link href="/admin/appointments?date=today">عرض الكل</Link>
            </Button>
          </CardHeader>
          <CardContent>
            {todayAppointments?.data?.length === 0 ? (
              <EmptyState message="لا توجد مواعيد اليوم" />
            ) : (
              <div className="space-y-4">
                {todayAppointments?.data?.slice(0, 5).map((appointment) => (
                  <AppointmentRow key={appointment.id} appointment={appointment} />
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle>{t('recentActivity')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentActivity?.data?.length === 0 ? (
              <EmptyState message="لا يوجد نشاط حديث" />
            ) : (
              <div className="space-y-4">
                {recentActivity?.data?.slice(0, 5).map((activity, index) => (
                  <ActivityRow key={`${activity.type}-${index}`} activity={activity} />
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
