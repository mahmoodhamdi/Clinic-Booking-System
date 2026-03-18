'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import {
  FileText,
  Download,
  Calendar,
  DollarSign,
  Users,
  TrendingUp,
  CheckCircle2,
  XCircle,
  Clock,
  AlertTriangle,
  Loader2,
} from 'lucide-react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { adminApi } from '@/lib/api/admin';
import { toast } from 'sonner';

const STATUS_COLORS: Record<string, string> = {
  completed: '#0D9488',
  confirmed: '#0891B2',
  pending: '#D97706',
  cancelled: '#DC2626',
  no_show: '#6B7280',
};

function getQuickDateRange(period: string): { from: string; to: string } {
  const today = new Date();
  const formatDate = (d: Date) => d.toISOString().split('T')[0];

  switch (period) {
    case 'this_week': {
      const start = new Date(today);
      start.setDate(today.getDate() - today.getDay());
      return { from: formatDate(start), to: formatDate(today) };
    }
    case 'this_month': {
      const start = new Date(today.getFullYear(), today.getMonth(), 1);
      return { from: formatDate(start), to: formatDate(today) };
    }
    case 'last_month': {
      const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const end = new Date(today.getFullYear(), today.getMonth(), 0);
      return { from: formatDate(start), to: formatDate(end) };
    }
    case 'last_3_months': {
      const start = new Date(today.getFullYear(), today.getMonth() - 3, 1);
      return { from: formatDate(start), to: formatDate(today) };
    }
    default:
      return { from: '', to: '' };
  }
}

interface DateRangePickerProps {
  startDate: string;
  endDate: string;
  onStartDateChange: (date: string) => void;
  onEndDateChange: (date: string) => void;
  onExport: () => void;
  isExporting: boolean;
  exportDisabled: boolean;
  t: ReturnType<typeof useTranslations>;
}

function DateRangePicker({
  startDate,
  endDate,
  onStartDateChange,
  onEndDateChange,
  onExport,
  isExporting,
  exportDisabled,
  t,
}: DateRangePickerProps) {
  const quickRanges = [
    { key: 'this_week', label: t('admin.reports.thisWeek') },
    { key: 'this_month', label: t('admin.reports.thisMonth') },
    { key: 'last_month', label: t('admin.reports.lastMonth') },
    { key: 'last_3_months', label: t('admin.reports.last3Months') },
  ];

  const handleQuickRange = (period: string) => {
    const { from, to } = getQuickDateRange(period);
    onStartDateChange(from);
    onEndDateChange(to);
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-2">
        {quickRanges.map((range) => (
          <Button
            key={range.key}
            variant="outline"
            size="sm"
            onClick={() => handleQuickRange(range.key)}
          >
            {range.label}
          </Button>
        ))}
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>{t('admin.settings.startDate')}</Label>
          <Input
            type="date"
            value={startDate}
            onChange={(e) => onStartDateChange(e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label>{t('admin.settings.endDate')}</Label>
          <Input
            type="date"
            value={endDate}
            onChange={(e) => onEndDateChange(e.target.value)}
          />
        </div>
        <div className="flex items-end">
          <Button
            onClick={onExport}
            disabled={exportDisabled || isExporting}
            className="w-full"
          >
            {isExporting ? (
              <Loader2 className="h-4 w-4 me-2 animate-spin" />
            ) : (
              <Download className="h-4 w-4 me-2" />
            )}
            {t('admin.reports.exportPdf')}
          </Button>
        </div>
      </div>
    </div>
  );
}

function StatCard({
  title,
  value,
  icon,
  color = 'text-foreground',
}: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  color?: string;
}) {
  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="text-sm font-medium text-muted-foreground flex items-center gap-2">
          {icon}
          {title}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <p className={`text-2xl font-bold ${color}`}>{value}</p>
      </CardContent>
    </Card>
  );
}

export default function ReportsPage() {
  const t = useTranslations();
  const [revenueStartDate, setRevenueStartDate] = useState('');
  const [revenueEndDate, setRevenueEndDate] = useState('');
  const [appointmentsStartDate, setAppointmentsStartDate] = useState('');
  const [appointmentsEndDate, setAppointmentsEndDate] = useState('');
  const [patientsStartDate, setPatientsStartDate] = useState('');
  const [patientsEndDate, setPatientsEndDate] = useState('');
  const [exportingRevenue, setExportingRevenue] = useState(false);
  const [exportingAppointments, setExportingAppointments] = useState(false);
  const [exportingPatients, setExportingPatients] = useState(false);

  // Fetch revenue report
  const { data: revenueReport, isLoading: isLoadingRevenue } = useQuery({
    queryKey: ['revenueReport', revenueStartDate, revenueEndDate],
    queryFn: () =>
      adminApi.getRevenueReport({
        from_date: revenueStartDate,
        to_date: revenueEndDate,
      }),
    enabled: !!revenueStartDate && !!revenueEndDate,
  });

  // Fetch appointments report
  const { data: appointmentsReport, isLoading: isLoadingAppointments } = useQuery({
    queryKey: ['appointmentsReport', appointmentsStartDate, appointmentsEndDate],
    queryFn: () =>
      adminApi.getAppointmentsReport({
        from_date: appointmentsStartDate,
        to_date: appointmentsEndDate,
      }),
    enabled: !!appointmentsStartDate && !!appointmentsEndDate,
  });

  // Fetch patients report
  const { data: patientsReport, isLoading: isLoadingPatients } = useQuery({
    queryKey: ['patientsReport', patientsStartDate, patientsEndDate],
    queryFn: () =>
      adminApi.getPatientsReport({
        from_date: patientsStartDate,
        to_date: patientsEndDate,
      }),
    enabled: !!patientsStartDate && !!patientsEndDate,
  });

  const handleExport = async (
    exportFn: (params: { from_date: string; to_date: string }) => Promise<Blob>,
    startDate: string,
    endDate: string,
    filename: string,
    setExporting: (v: boolean) => void
  ) => {
    if (!startDate || !endDate) return;
    setExporting(true);
    try {
      const blob = await exportFn({ from_date: startDate, to_date: endDate });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${filename}-${startDate}-${endDate}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      toast.success(t('common.success'));
    } catch {
      toast.error(t('common.error'));
    } finally {
      setExporting(false);
    }
  };

  // Prepare chart data from backend response
  // Backend appointments response: { summary: { total, completed, cancelled, no_show, pending, confirmed }, completion_rate, cancellation_rate, appointments: [] }
  const appointmentsSummary = appointmentsReport?.data?.summary;
  const appointmentStatusData = appointmentsSummary
    ? [
        { name: t('patient.appointments.status.completed'), value: appointmentsSummary.completed, color: STATUS_COLORS.completed },
        { name: t('patient.appointments.status.confirmed'), value: appointmentsSummary.confirmed, color: STATUS_COLORS.confirmed },
        { name: t('patient.appointments.status.pending'), value: appointmentsSummary.pending, color: STATUS_COLORS.pending },
        { name: t('patient.appointments.status.cancelled'), value: appointmentsSummary.cancelled, color: STATUS_COLORS.cancelled },
        { name: t('patient.appointments.status.no_show'), value: appointmentsSummary.no_show, color: STATUS_COLORS.no_show },
      ].filter((d) => d.value > 0)
    : [];

  // Backend revenue response: { summary: { total_revenue, total_discount, total_payments, average_payment }, by_method: { cash, card, wallet }, breakdown: [{period, label, total, count}], payments: [] }
  const revenueSummary = revenueReport?.data?.summary;
  const revenueByMethod = revenueReport?.data?.by_method;
  const revenueBreakdown = revenueReport?.data?.breakdown || [];

  const paymentMethodData = revenueByMethod
    ? [
        { name: t('admin.payments.cash'), value: revenueByMethod.cash || 0, color: '#0D9488' },
        { name: t('admin.payments.card'), value: revenueByMethod.card || 0, color: '#0891B2' },
        { name: t('admin.payments.wallet'), value: revenueByMethod.wallet || 0, color: '#7C3AED' },
      ].filter((d) => d.value > 0)
    : [];

  // Backend patients response: { summary: { total_patients, active_patients, inactive_patients }, patients: [] }
  const patientsSummary = patientsReport?.data?.summary;

  return (
    <div className="space-y-6 animate-fade-in-up">
      <div>
        <h1 className="text-2xl font-bold text-gradient-primary">{t('admin.reports.title')}</h1>
      </div>

      <Tabs defaultValue="revenue" className="space-y-6">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="revenue">
            <DollarSign className="h-4 w-4 me-2" />
            {t('admin.reports.revenueReport')}
          </TabsTrigger>
          <TabsTrigger value="appointments">
            <Calendar className="h-4 w-4 me-2" />
            {t('admin.reports.appointmentsReport')}
          </TabsTrigger>
          <TabsTrigger value="patients">
            <Users className="h-4 w-4 me-2" />
            {t('admin.reports.patientsReport')}
          </TabsTrigger>
        </TabsList>

        {/* Revenue Report Tab */}
        <TabsContent value="revenue">
          <Card>
            <CardHeader>
              <CardTitle>{t('admin.reports.revenueReport')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <DateRangePicker
                startDate={revenueStartDate}
                endDate={revenueEndDate}
                onStartDateChange={setRevenueStartDate}
                onEndDateChange={setRevenueEndDate}
                onExport={() =>
                  handleExport(adminApi.exportRevenueReport, revenueStartDate, revenueEndDate, 'revenue-report', setExportingRevenue)
                }
                isExporting={exportingRevenue}
                exportDisabled={!revenueStartDate || !revenueEndDate}
                t={t}
              />

              {isLoadingRevenue ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {[1, 2, 3, 4].map((i) => <Skeleton key={i} className="h-24" />)}
                  </div>
                  <Skeleton className="h-64" />
                </div>
              ) : revenueSummary ? (
                <div className="space-y-6">
                  {/* Summary Cards */}
                  <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <StatCard
                      title={t('admin.dashboard.totalRevenue')}
                      value={`${revenueSummary.total_revenue?.toLocaleString() || 0} ${t('common.currency')}`}
                      icon={<DollarSign className="h-4 w-4" />}
                      color="text-success"
                    />
                    <StatCard
                      title={t('admin.payments.transactionsCount')}
                      value={revenueSummary.total_payments || 0}
                      icon={<FileText className="h-4 w-4" />}
                    />
                    <StatCard
                      title={t('admin.payments.discount')}
                      value={`${revenueSummary.total_discount?.toLocaleString() || 0} ${t('common.currency')}`}
                      icon={<TrendingUp className="h-4 w-4" />}
                      color="text-warning"
                    />
                    <StatCard
                      title={t('admin.payments.amount')}
                      value={`${revenueSummary.average_payment?.toLocaleString() || 0} ${t('common.currency')}`}
                      icon={<DollarSign className="h-4 w-4" />}
                    />
                  </div>

                  {/* Revenue Trend Chart */}
                  {revenueBreakdown.length > 0 && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="text-base">{t('admin.reports.revenueReport')}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="h-72">
                          <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={revenueBreakdown}>
                              <CartesianGrid strokeDasharray="3 3" />
                              <XAxis dataKey="label" fontSize={12} />
                              <YAxis fontSize={12} />
                              <Tooltip
                                formatter={(value) => [`${Number(value).toLocaleString()} ${t('common.currency')}`, t('admin.dashboard.totalRevenue')]}
                              />
                              <Bar dataKey="total" fill="#0D9488" radius={[4, 4, 0, 0]} />
                            </BarChart>
                          </ResponsiveContainer>
                        </div>
                      </CardContent>
                    </Card>
                  )}

                  {/* Payment Methods Pie Chart */}
                  {paymentMethodData.length > 0 && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="text-base">{t('admin.payments.paymentMethod')}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="h-64">
                          <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                              <Pie
                                data={paymentMethodData}
                                cx="50%"
                                cy="50%"
                                innerRadius={60}
                                outerRadius={80}
                                dataKey="value"
                                label={({ name, percent }) => `${name} ${((percent ?? 0) * 100).toFixed(0)}%`}
                              >
                                {paymentMethodData.map((entry, index) => (
                                  <Cell key={`cell-${index}`} fill={entry.color} />
                                ))}
                              </Pie>
                              <Tooltip formatter={(value) => `${Number(value).toLocaleString()} ${t('common.currency')}`} />
                              <Legend />
                            </PieChart>
                          </ResponsiveContainer>
                        </div>
                      </CardContent>
                    </Card>
                  )}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-muted-foreground/70 mx-auto mb-4" />
                  <p className="text-muted-foreground">{t('admin.reports.dateRange')}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Appointments Report Tab */}
        <TabsContent value="appointments">
          <Card>
            <CardHeader>
              <CardTitle>{t('admin.reports.appointmentsReport')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <DateRangePicker
                startDate={appointmentsStartDate}
                endDate={appointmentsEndDate}
                onStartDateChange={setAppointmentsStartDate}
                onEndDateChange={setAppointmentsEndDate}
                onExport={() =>
                  handleExport(adminApi.exportAppointmentsReport, appointmentsStartDate, appointmentsEndDate, 'appointments-report', setExportingAppointments)
                }
                isExporting={exportingAppointments}
                exportDisabled={!appointmentsStartDate || !appointmentsEndDate}
                t={t}
              />

              {isLoadingAppointments ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {[1, 2, 3, 4].map((i) => <Skeleton key={i} className="h-24" />)}
                  </div>
                  <Skeleton className="h-64" />
                </div>
              ) : appointmentsSummary ? (
                <div className="space-y-6">
                  {/* Summary Cards */}
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard
                      title={t('admin.dashboard.totalAppointments')}
                      value={appointmentsSummary.total || 0}
                      icon={<Calendar className="h-4 w-4" />}
                    />
                    <StatCard
                      title={t('patient.appointments.status.completed')}
                      value={appointmentsSummary.completed || 0}
                      icon={<CheckCircle2 className="h-4 w-4" />}
                      color="text-success"
                    />
                    <StatCard
                      title={t('patient.appointments.status.cancelled')}
                      value={appointmentsSummary.cancelled || 0}
                      icon={<XCircle className="h-4 w-4" />}
                      color="text-destructive"
                    />
                    <StatCard
                      title={t('patient.appointments.status.no_show')}
                      value={appointmentsSummary.no_show || 0}
                      icon={<AlertTriangle className="h-4 w-4" />}
                      color="text-warning"
                    />
                  </div>

                  {/* Rates */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card>
                      <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                          <span className="text-sm text-muted-foreground">{t('admin.reports.completionRate')}</span>
                          <span className="text-2xl font-bold text-success">
                            {appointmentsReport?.data?.completion_rate || 0}%
                          </span>
                        </div>
                        <div className="mt-2 h-2 bg-border rounded-full">
                          <div
                            className="h-full bg-success rounded-full transition-all"
                            style={{ width: `${Math.min(appointmentsReport?.data?.completion_rate || 0, 100)}%` }}
                          />
                        </div>
                      </CardContent>
                    </Card>
                    <Card>
                      <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                          <span className="text-sm text-muted-foreground">{t('admin.reports.cancellationRate')}</span>
                          <span className="text-2xl font-bold text-destructive">
                            {appointmentsReport?.data?.cancellation_rate || 0}%
                          </span>
                        </div>
                        <div className="mt-2 h-2 bg-border rounded-full">
                          <div
                            className="h-full bg-destructive rounded-full transition-all"
                            style={{ width: `${Math.min(appointmentsReport?.data?.cancellation_rate || 0, 100)}%` }}
                          />
                        </div>
                      </CardContent>
                    </Card>
                  </div>

                  {/* Status Distribution Pie Chart */}
                  {appointmentStatusData.length > 0 && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="text-base">{t('admin.reports.statusDistribution')}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="h-72">
                          <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                              <Pie
                                data={appointmentStatusData}
                                cx="50%"
                                cy="50%"
                                innerRadius={60}
                                outerRadius={90}
                                dataKey="value"
                                label={({ name, percent }) => `${name} ${((percent ?? 0) * 100).toFixed(0)}%`}
                              >
                                {appointmentStatusData.map((entry, index) => (
                                  <Cell key={`cell-${index}`} fill={entry.color} />
                                ))}
                              </Pie>
                              <Tooltip />
                              <Legend />
                            </PieChart>
                          </ResponsiveContainer>
                        </div>
                      </CardContent>
                    </Card>
                  )}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-muted-foreground/70 mx-auto mb-4" />
                  <p className="text-muted-foreground">{t('admin.reports.dateRange')}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Patients Report Tab */}
        <TabsContent value="patients">
          <Card>
            <CardHeader>
              <CardTitle>{t('admin.reports.patientsReport')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <DateRangePicker
                startDate={patientsStartDate}
                endDate={patientsEndDate}
                onStartDateChange={setPatientsStartDate}
                onEndDateChange={setPatientsEndDate}
                onExport={() =>
                  handleExport(adminApi.exportPatientsReport, patientsStartDate, patientsEndDate, 'patients-report', setExportingPatients)
                }
                isExporting={exportingPatients}
                exportDisabled={!patientsStartDate || !patientsEndDate}
                t={t}
              />

              {isLoadingPatients ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {[1, 2, 3].map((i) => <Skeleton key={i} className="h-24" />)}
                  </div>
                </div>
              ) : patientsSummary ? (
                <div className="space-y-6">
                  {/* Summary Cards */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <StatCard
                      title={t('admin.dashboard.totalPatients')}
                      value={patientsSummary.total_patients || 0}
                      icon={<Users className="h-4 w-4" />}
                    />
                    <StatCard
                      title={t('common.active')}
                      value={patientsSummary.active_patients || 0}
                      icon={<CheckCircle2 className="h-4 w-4" />}
                      color="text-success"
                    />
                    <StatCard
                      title={t('admin.reports.inactivePatients')}
                      value={patientsSummary.inactive_patients || 0}
                      icon={<Clock className="h-4 w-4" />}
                      color="text-muted-foreground"
                    />
                  </div>

                  {/* Patients Table */}
                  {patientsReport?.data?.patients && patientsReport.data.patients.length > 0 && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="text-base">{t('admin.patients.allPatients')}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="overflow-x-auto">
                          <table className="w-full text-sm">
                            <thead>
                              <tr className="border-b">
                                <th className="text-start p-3 font-medium text-muted-foreground">{t('auth.name')}</th>
                                <th className="text-start p-3 font-medium text-muted-foreground">{t('auth.phone')}</th>
                                <th className="text-start p-3 font-medium text-muted-foreground">{t('admin.dashboard.totalAppointments')}</th>
                                <th className="text-start p-3 font-medium text-muted-foreground">{t('patient.appointments.status.completed')}</th>
                                <th className="text-start p-3 font-medium text-muted-foreground">{t('common.date')}</th>
                              </tr>
                            </thead>
                            <tbody>
                              {patientsReport.data.patients.slice(0, 20).map((patient) => (
                                <tr key={patient.id} className="border-b hover:bg-muted/50">
                                  <td className="p-3 font-medium">{patient.name}</td>
                                  <td className="p-3 text-muted-foreground" dir="ltr">{patient.phone}</td>
                                  <td className="p-3">{patient.total_appointments}</td>
                                  <td className="p-3 text-success">{patient.completed_appointments}</td>
                                  <td className="p-3 text-muted-foreground">{patient.registered_at}</td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      </CardContent>
                    </Card>
                  )}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-muted-foreground/70 mx-auto mb-4" />
                  <p className="text-muted-foreground">{t('admin.reports.dateRange')}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
