'use client';

import { useTranslations, useLocale } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import {
  Users,
  Calendar,
  Clock,
  DollarSign,
  TrendingUp,
  CheckCircle2,
  AlertCircle,
  FileText,
  BarChart3,
} from 'lucide-react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  BarChart,
  Bar,
} from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { adminApi } from '@/lib/api/admin';
import { getIntlLocale } from '@/lib/utils';
import type {
  Appointment,
  Activity,
  DashboardStats,
  DashboardChartData,
  WeeklyStats,
  ApiResponse,
} from '@/types';

// ─── Skeleton ────────────────────────────────────────────────────────────────

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} className="h-32" />
        ))}
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton className="h-72 lg:col-span-2" />
        <Skeleton className="h-72" />
      </div>
      <Skeleton className="h-64 w-full" />
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Skeleton className="h-48" />
        <Skeleton className="h-48" />
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton className="h-64 lg:col-span-2" />
        <Skeleton className="h-64" />
      </div>
    </div>
  );
}

function ChartSkeleton({ height = 'h-64' }: { height?: string }) {
  return (
    <div className={`${height} flex items-center justify-center`}>
      <div className="space-y-3 w-full px-4">
        <Skeleton className="h-4 w-1/3" />
        <Skeleton className="h-40 w-full" />
        <Skeleton className="h-3 w-full" />
      </div>
    </div>
  );
}

// ─── Stat Card ────────────────────────────────────────────────────────────────

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
    blue: 'bg-info/10 text-info',
    green: 'bg-success/10 text-success',
    yellow: 'bg-warning/10 text-warning',
    purple: 'bg-chart-4/10 text-chart-4',
  };

  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-muted-foreground">{title}</p>
            <p className="text-3xl font-bold mt-1">{value}</p>
          </div>
          <div className={`h-12 w-12 rounded-full flex items-center justify-center ${colors[color]}`}>
            {icon}
          </div>
        </div>
        {trend && (
          <div className={`flex items-center gap-1 mt-2 text-sm ${trend.positive ? 'text-success' : 'text-destructive'}`}>
            <TrendingUp className="h-4 w-4" />
            <span>{trend.value}</span>
          </div>
        )}
        {subtext && (
          <div className="mt-2 text-sm text-muted-foreground">{subtext}</div>
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

// ─── Mini Stat Item ───────────────────────────────────────────────────────────

interface MiniStatProps {
  label: string;
  value: string | number;
  icon: React.ReactNode;
  colorClass: string;
}

function MiniStat({ label, value, icon, colorClass }: MiniStatProps) {
  return (
    <div className="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
      <div className={`h-9 w-9 rounded-full flex items-center justify-center flex-shrink-0 ${colorClass}`}>
        {icon}
      </div>
      <div className="min-w-0">
        <p className="text-xs text-muted-foreground truncate">{label}</p>
        <p className="text-lg font-bold truncate">{value}</p>
      </div>
    </div>
  );
}

// ─── Appointment Row ──────────────────────────────────────────────────────────

interface AppointmentRowProps {
  appointment: Appointment;
  t: ReturnType<typeof useTranslations>;
}

function AppointmentRow({ appointment, t }: AppointmentRowProps) {
  const statusColors: Record<string, string> = {
    confirmed: 'bg-success/10 text-success',
    pending: 'bg-warning/10 text-warning',
    completed: 'bg-info/10 text-info',
    cancelled: 'bg-destructive/10 text-destructive',
    no_show: 'bg-muted text-muted-foreground',
  };

  const StatusIcon = appointment.status === 'pending' ? Clock : CheckCircle2;
  const patientName = (appointment as Appointment & { patient?: { name: string } }).patient?.name || t('common.patient');
  const appointmentTime = (appointment as Appointment & { time?: string }).time || appointment.slot_time;

  return (
    <div className="flex items-center justify-between p-4 rounded-lg border bg-muted/50">
      <div className="flex items-center gap-4">
        <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
          <span className="font-medium text-primary">
            {patientName.charAt(0)}
          </span>
        </div>
        <div>
          <p className="font-medium">{patientName}</p>
          <p className="text-sm text-muted-foreground">{appointmentTime}</p>
        </div>
      </div>
      <div className="flex items-center gap-2">
        <Badge className={statusColors[appointment.status] || 'bg-muted'}>
          <StatusIcon className="h-3 w-3 me-1" />
          {t(`admin.appointments.status.${appointment.status}` as Parameters<typeof t>[0]) || appointment.status}
        </Badge>
        <Button variant="ghost" size="sm" asChild>
          <Link href={`/admin/appointments/${appointment.id}`}>{t('common.view')}</Link>
        </Button>
      </div>
    </div>
  );
}

// ─── Activity Row ─────────────────────────────────────────────────────────────

interface ActivityRowProps {
  activity: Activity;
}

function ActivityRow({ activity }: ActivityRowProps) {
  const typeIcons: Record<string, React.ReactNode> = {
    appointment: <Calendar className="h-4 w-4 text-info" />,
    payment: <DollarSign className="h-4 w-4 text-success" />,
    medical_record: <FileText className="h-4 w-4 text-chart-4" />,
  };

  const activityDate = (activity as Activity & { date?: string }).date || activity.created_at;

  return (
    <div className="flex items-start gap-3 pb-4 border-b last:border-0 last:pb-0">
      <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center flex-shrink-0">
        {typeIcons[activity.type] || <AlertCircle className="h-4 w-4 text-muted-foreground" />}
      </div>
      <div>
        <p className="text-sm font-medium">{activity.description}</p>
        <p className="text-xs text-muted-foreground/70 mt-1">
          {activityDate || ''}
        </p>
      </div>
    </div>
  );
}

// ─── Empty State ──────────────────────────────────────────────────────────────

function EmptyState({ message }: { message: string }) {
  return (
    <div className="text-center py-8 text-muted-foreground">
      {message}
    </div>
  );
}

// ─── Chart Error State ────────────────────────────────────────────────────────

function ChartError({ message }: { message: string }) {
  return (
    <div className="flex flex-col items-center justify-center py-8 text-muted-foreground/70 gap-2">
      <BarChart3 className="h-10 w-10 opacity-40" />
      <p className="text-sm">{message}</p>
    </div>
  );
}

// ─── Status colours for pie chart ─────────────────────────────────────────────

const STATUS_COLORS: Record<string, string> = {
  confirmed: '#0D9488',
  pending: '#eab308',
  completed: '#3b82f6',
  cancelled: '#ef4444',
  no_show: '#9ca3af',
};

// ─── Custom Pie Label ─────────────────────────────────────────────────────────
// Recharts passes its own props object to the label render function.
// We accept `unknown` and cast internally to avoid the overloaded type conflict.

function renderCustomPieLabel(props: unknown) {
  const { cx, cy, midAngle, innerRadius, outerRadius, percent } = props as {
    cx: number;
    cy: number;
    midAngle: number;
    innerRadius: number;
    outerRadius: number;
    percent: number;
  };

  if (percent < 0.05) return null;

  const RADIAN = Math.PI / 180;
  const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
  const x = cx + radius * Math.cos(-midAngle * RADIAN);
  const y = cy + radius * Math.sin(-midAngle * RADIAN);

  return (
    <text
      x={x}
      y={y}
      fill="white"
      textAnchor="middle"
      dominantBaseline="central"
      fontSize={12}
      fontWeight="bold"
    >
      {`${(percent * 100).toFixed(0)}%`}
    </text>
  );
}

// ─── Appointment Trend Line Chart ─────────────────────────────────────────────

interface AppointmentTrendChartProps {
  data: DashboardChartData['appointments_trend'];
  isRtl: boolean;
  appointmentsLabel: string;
}

function AppointmentTrendChart({ data, isRtl, appointmentsLabel }: AppointmentTrendChartProps) {
  if (!data || data.length === 0) {
    return null;
  }

  return (
    <ResponsiveContainer width="100%" height={220}>
      <LineChart data={data} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
        <XAxis
          dataKey="day"
          tick={{ fontSize: 11 }}
          reversed={isRtl}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          tick={{ fontSize: 11 }}
          tickLine={false}
          axisLine={false}
          allowDecimals={false}
          orientation={isRtl ? 'right' : 'left'}
        />
        <Tooltip
          contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: 12 }}
          formatter={(value) => [value, appointmentsLabel]}
        />
        <Line
          type="monotone"
          dataKey="count"
          stroke="#0D9488"
          strokeWidth={2.5}
          dot={{ fill: '#0D9488', r: 4 }}
          activeDot={{ r: 6 }}
        />
      </LineChart>
    </ResponsiveContainer>
  );
}

// ─── Status Distribution Pie Chart ───────────────────────────────────────────

interface StatusPieChartProps {
  distribution: DashboardChartData['status_distribution'];
  statusLabels: Record<string, string>;
  noDataLabel: string;
}

function StatusPieChart({ distribution, statusLabels, noDataLabel }: StatusPieChartProps) {
  const pieData = Object.entries(distribution)
    .filter(([, value]) => value > 0)
    .map(([key, value]) => ({
      name: statusLabels[key] ?? key,
      value,
      status: key,
    }));

  if (pieData.length === 0) {
    return <ChartError message={noDataLabel} />;
  }

  return (
    <>
      <ResponsiveContainer width="100%" height={180}>
        <PieChart>
          <Pie
            data={pieData}
            cx="50%"
            cy="50%"
            outerRadius={80}
            dataKey="value"
            labelLine={false}
            label={renderCustomPieLabel}
          >
            {pieData.map((entry) => (
              <Cell key={entry.status} fill={STATUS_COLORS[entry.status] ?? '#9ca3af'} />
            ))}
          </Pie>
          <Tooltip
            contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: 12 }}
          />
        </PieChart>
      </ResponsiveContainer>

      {/* Legend */}
      <div className="flex flex-wrap justify-center gap-x-4 gap-y-1 mt-2">
        {pieData.map((entry) => (
          <div key={entry.status} className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <span
              className="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0"
              style={{ backgroundColor: STATUS_COLORS[entry.status] ?? '#9ca3af' }}
            />
            <span>{entry.name}</span>
            <span className="text-muted-foreground/70">({entry.value})</span>
          </div>
        ))}
      </div>
    </>
  );
}

// ─── Revenue Trend Bar Chart ──────────────────────────────────────────────────

interface RevenueTrendChartProps {
  data: DashboardChartData['revenue_trend'];
  isRtl: boolean;
  currency: string;
  revenueLabel: string;
}

function RevenueTrendChart({ data, isRtl, currency, revenueLabel }: RevenueTrendChartProps) {
  if (!data || data.length === 0) {
    return null;
  }

  return (
    <ResponsiveContainer width="100%" height={220}>
      <BarChart data={data} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
        <XAxis
          dataKey="day"
          tick={{ fontSize: 11 }}
          reversed={isRtl}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          tick={{ fontSize: 11 }}
          tickLine={false}
          axisLine={false}
          allowDecimals={false}
          orientation={isRtl ? 'right' : 'left'}
        />
        <Tooltip
          contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', fontSize: 12 }}
          formatter={(value) => [`${value} ${currency}`, revenueLabel]}
        />
        <Bar dataKey="amount" fill="#0D9488" radius={[4, 4, 0, 0]} />
      </BarChart>
    </ResponsiveContainer>
  );
}

// ─── Weekly Stats Widget ──────────────────────────────────────────────────────

interface WeeklyStatsWidgetProps {
  stats: WeeklyStats;
  t: ReturnType<typeof useTranslations>;
  currency: string;
}

function WeeklyStatsWidget({ stats, t, currency }: WeeklyStatsWidgetProps) {
  return (
    <div className="grid grid-cols-2 gap-3">
      <MiniStat
        label={t('admin.dashboard.appointments')}
        value={stats.appointments}
        icon={<Calendar className="h-4 w-4" />}
        colorClass="bg-info/10 text-info"
      />
      <MiniStat
        label={t('admin.dashboard.completed')}
        value={stats.completed}
        icon={<CheckCircle2 className="h-4 w-4" />}
        colorClass="bg-success/10 text-success"
      />
      <MiniStat
        label={t('admin.dashboard.revenue')}
        value={`${stats.revenue} ${currency}`}
        icon={<DollarSign className="h-4 w-4" />}
        colorClass="bg-chart-4/10 text-chart-4"
      />
      <MiniStat
        label={t('admin.dashboard.newPatients')}
        value={stats.new_patients}
        icon={<Users className="h-4 w-4" />}
        colorClass="bg-warning/10 text-warning"
      />
    </div>
  );
}

// ─── Payment Stats Widget ─────────────────────────────────────────────────────

interface PaymentStatsWidgetProps {
  stats: {
    total_revenue: number;
    today_revenue: number;
    this_month_revenue: number;
    total_pending: number;
  };
  t: ReturnType<typeof useTranslations>;
  currency: string;
}

function PaymentStatsWidget({ stats, t, currency }: PaymentStatsWidgetProps) {
  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between py-2 border-b">
        <span className="text-sm text-muted-foreground">{t('admin.dashboard.totalRevenueAll')}</span>
        <span className="font-semibold text-success">
          {stats.total_revenue} {currency}
        </span>
      </div>
      <div className="flex items-center justify-between py-2 border-b">
        <span className="text-sm text-muted-foreground">{t('admin.dashboard.monthRevenue')}</span>
        <span className="font-semibold">
          {stats.this_month_revenue} {currency}
        </span>
      </div>
      <div className="flex items-center justify-between py-2 border-b">
        <span className="text-sm text-muted-foreground">{t('admin.dashboard.todayRevenueLabel')}</span>
        <span className="font-semibold">
          {stats.today_revenue} {currency}
        </span>
      </div>
      <div className="flex items-center justify-between py-2">
        <span className="text-sm text-muted-foreground">{t('admin.dashboard.pendingPayments')}</span>
        <span className="font-semibold text-warning">
          {stats.total_pending} {currency}
        </span>
      </div>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function AdminDashboard() {
  const t = useTranslations();
  const locale = useLocale();
  const isRtl = locale === 'ar';
  const currency = t('common.currency');

  // Fetch dashboard stats (1 min refresh)
  const { data: stats, isLoading: statsLoading } = useQuery<ApiResponse<DashboardStats>>({
    queryKey: ['admin-dashboard-stats'],
    queryFn: () => adminApi.getDashboardStats(),
    refetchInterval: 60000,
  });

  // Fetch today's appointments (30 s refresh)
  const { data: todayAppointments, isLoading: todayLoading } = useQuery<ApiResponse<Appointment[]>>({
    queryKey: ['admin-today-appointments'],
    queryFn: () => adminApi.getTodayAppointments(),
    refetchInterval: 30000,
  });

  // Fetch recent activity (30 s refresh)
  const { data: recentActivity, isLoading: activityLoading } = useQuery<ApiResponse<Activity[]>>({
    queryKey: ['admin-recent-activity'],
    queryFn: () => adminApi.getRecentActivity(),
    refetchInterval: 30000,
  });

  // Fetch chart data – week period (30 s refresh)
  const { data: chartData, isLoading: chartLoading } = useQuery<ApiResponse<DashboardChartData>>({
    queryKey: ['admin-dashboard-chart', 'week'],
    queryFn: () => adminApi.getDashboardChart('week'),
    refetchInterval: 30000,
  });

  // Fetch weekly stats (30 s refresh)
  const { data: weeklyStatsData, isLoading: weeklyLoading } = useQuery<ApiResponse<WeeklyStats>>({
    queryKey: ['admin-weekly-stats'],
    queryFn: () => adminApi.getWeeklyStats(),
    refetchInterval: 30000,
  });

  // Fetch payment statistics (30 s refresh)
  const { data: paymentStatsData, isLoading: paymentStatsLoading } = useQuery<ApiResponse<{
    total_revenue: number;
    total_pending: number;
    total_paid: number;
    total_refunded: number;
    today_revenue: number;
    this_month_revenue: number;
  }>>({
    queryKey: ['admin-payment-statistics'],
    queryFn: () => adminApi.getPaymentStatistics(),
    refetchInterval: 30000,
  });

  const isLoading = statsLoading || todayLoading || activityLoading;

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const dashboardStats = stats?.data;
  const chartInfo = chartData?.data;
  const weeklyStats = weeklyStatsData?.data;
  const paymentStats = paymentStatsData?.data;
  const pendingCount = todayAppointments?.data?.filter(a => a.status === 'pending').length ?? 0;

  // Build status label map once (avoids calling t() inside child component)
  const statusLabels: Record<string, string> = {
    confirmed: t('admin.dashboard.statusConfirmed'),
    pending: t('admin.dashboard.statusPending'),
    completed: t('admin.dashboard.statusCompleted'),
    cancelled: t('admin.dashboard.statusCancelled'),
    no_show: t('admin.dashboard.statusNoShow'),
  };

  return (
    <div className="space-y-6">
      {/* Page Title */}
      <div>
        <h1 className="text-2xl font-bold text-foreground">
          {t('admin.dashboard.title')}
        </h1>
        <p className="text-muted-foreground mt-1">
          {new Date().toLocaleDateString(getIntlLocale(locale), {
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
          title={t('admin.dashboard.totalPatients')}
          value={dashboardStats?.total_patients ?? 0}
          icon={<Users className="h-6 w-6" />}
          color="blue"
        />
        <StatCard
          title={t('admin.dashboard.todayAppointments')}
          value={dashboardStats?.today_appointments ?? 0}
          icon={<Calendar className="h-6 w-6" />}
          color="green"
          subtext={`${pendingCount} ${t('admin.dashboard.pendingConfirmation')}`}
        />
        <StatCard
          title={t('admin.dashboard.pendingAppointments')}
          value={dashboardStats?.pending_appointments ?? 0}
          icon={<Clock className="h-6 w-6" />}
          color="yellow"
          linkHref="/admin/appointments?status=pending"
          linkText={t('common.viewAll')}
        />
        <StatCard
          title={t('admin.dashboard.todayRevenue')}
          value={`${dashboardStats?.today_revenue ?? 0} ${currency}`}
          icon={<DollarSign className="h-6 w-6" />}
          color="purple"
        />
      </div>

      {/* Charts Row: Trend (2/3) + Pie (1/3) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Appointment Trend Line Chart */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-base">{t('admin.dashboard.appointmentTrend')}</CardTitle>
          </CardHeader>
          <CardContent>
            {chartLoading ? (
              <ChartSkeleton height="h-56" />
            ) : chartInfo && chartInfo.appointments_trend.length > 0 ? (
              <AppointmentTrendChart
                data={chartInfo.appointments_trend}
                isRtl={isRtl}
                appointmentsLabel={t('admin.dashboard.appointments')}
              />
            ) : (
              <ChartError message={t('admin.dashboard.noChartData')} />
            )}
          </CardContent>
        </Card>

        {/* Status Distribution Pie Chart */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">{t('admin.dashboard.statusDistribution')}</CardTitle>
          </CardHeader>
          <CardContent>
            {chartLoading ? (
              <ChartSkeleton height="h-56" />
            ) : chartInfo ? (
              <StatusPieChart
                distribution={chartInfo.status_distribution}
                statusLabels={statusLabels}
                noDataLabel={t('admin.dashboard.noChartData')}
              />
            ) : (
              <ChartError message={t('admin.dashboard.chartLoadError')} />
            )}
          </CardContent>
        </Card>
      </div>

      {/* Revenue Trend Bar Chart – full width */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('admin.dashboard.revenueTrend')}</CardTitle>
        </CardHeader>
        <CardContent>
          {chartLoading ? (
            <ChartSkeleton height="h-56" />
          ) : chartInfo && chartInfo.revenue_trend.length > 0 ? (
            <RevenueTrendChart
              data={chartInfo.revenue_trend}
              isRtl={isRtl}
              currency={currency}
              revenueLabel={t('admin.dashboard.revenue')}
            />
          ) : (
            <ChartError message={t('admin.dashboard.noChartData')} />
          )}
        </CardContent>
      </Card>

      {/* Additional Widgets Row: Weekly Stats + Payment Stats */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Weekly Stats */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">
              {t('admin.dashboard.weeklyStats')}
              <span className="ms-2 text-xs font-normal text-muted-foreground/70">
                ({t('admin.dashboard.thisWeek')})
              </span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {weeklyLoading ? (
              <div className="grid grid-cols-2 gap-3">
                {[1, 2, 3, 4].map((i) => (
                  <Skeleton key={i} className="h-16 rounded-lg" />
                ))}
              </div>
            ) : weeklyStats ? (
              <WeeklyStatsWidget stats={weeklyStats} t={t} currency={currency} />
            ) : (
              <EmptyState message={t('common.noData')} />
            )}
          </CardContent>
        </Card>

        {/* Payment Statistics */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">{t('admin.dashboard.paymentStats')}</CardTitle>
          </CardHeader>
          <CardContent>
            {paymentStatsLoading ? (
              <div className="space-y-3">
                {[1, 2, 3, 4].map((i) => (
                  <Skeleton key={i} className="h-10 rounded" />
                ))}
              </div>
            ) : paymentStats ? (
              <PaymentStatsWidget stats={paymentStats} t={t} currency={currency} />
            ) : (
              <EmptyState message={t('common.noData')} />
            )}
          </CardContent>
        </Card>
      </div>

      {/* Main Content Grid: Today's Appointments + Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Today's Appointments */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>{t('admin.dashboard.todayAppointments')}</CardTitle>
            <Button variant="outline" size="sm" asChild>
              <Link href="/admin/appointments?date=today">{t('common.viewAll')}</Link>
            </Button>
          </CardHeader>
          <CardContent>
            {todayAppointments?.data?.length === 0 ? (
              <EmptyState message={t('admin.dashboard.noTodayAppointments')} />
            ) : (
              <div className="space-y-4">
                {todayAppointments?.data?.slice(0, 5).map((appointment) => (
                  <AppointmentRow key={appointment.id} appointment={appointment} t={t} />
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle>{t('admin.dashboard.recentActivity')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentActivity?.data?.length === 0 ? (
              <EmptyState message={t('admin.dashboard.noRecentActivity')} />
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
