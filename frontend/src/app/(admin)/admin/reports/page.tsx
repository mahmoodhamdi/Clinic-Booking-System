'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import {
  FileText,
  Download,
  Calendar,
  DollarSign,
  Users,
  TrendingUp,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { adminApi } from '@/lib/api/admin';

export default function ReportsPage() {
  const t = useTranslations();
  const [revenueStartDate, setRevenueStartDate] = useState('');
  const [revenueEndDate, setRevenueEndDate] = useState('');
  const [appointmentsStartDate, setAppointmentsStartDate] = useState('');
  const [appointmentsEndDate, setAppointmentsEndDate] = useState('');
  const [patientsStartDate, setPatientsStartDate] = useState('');
  const [patientsEndDate, setPatientsEndDate] = useState('');

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

  const handleExportRevenue = async () => {
    if (!revenueStartDate || !revenueEndDate) return;
    try {
      const blob = await adminApi.exportRevenueReport({
        from_date: revenueStartDate,
        to_date: revenueEndDate,
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `revenue-report-${revenueStartDate}-${revenueEndDate}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  const handleExportAppointments = async () => {
    if (!appointmentsStartDate || !appointmentsEndDate) return;
    try {
      const blob = await adminApi.exportAppointmentsReport({
        from_date: appointmentsStartDate,
        to_date: appointmentsEndDate,
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `appointments-report-${appointmentsStartDate}-${appointmentsEndDate}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  const handleExportPatients = async () => {
    if (!patientsStartDate || !patientsEndDate) return;
    try {
      const blob = await adminApi.exportPatientsReport({
        from_date: patientsStartDate,
        to_date: patientsEndDate,
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `patients-report-${patientsStartDate}-${patientsEndDate}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('admin.reports.title')}</h1>
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
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>{t('admin.settings.startDate')}</Label>
                  <Input
                    type="date"
                    value={revenueStartDate}
                    onChange={(e) => setRevenueStartDate(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>{t('admin.settings.endDate')}</Label>
                  <Input
                    type="date"
                    value={revenueEndDate}
                    onChange={(e) => setRevenueEndDate(e.target.value)}
                  />
                </div>
                <div className="flex items-end">
                  <Button
                    onClick={handleExportRevenue}
                    disabled={!revenueStartDate || !revenueEndDate}
                    className="w-full"
                  >
                    <Download className="h-4 w-4 me-2" />
                    {t('admin.reports.exportPdf')}
                  </Button>
                </div>
              </div>

              {isLoadingRevenue ? (
                <div className="space-y-4">
                  <Skeleton className="h-32" />
                </div>
              ) : revenueReport?.data ? (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('admin.payments.totalPayments')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {revenueReport.data.total_revenue?.toLocaleString() || 0} {t('common.currency')}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('admin.payments.completedPayments')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {revenueReport.data.total_paid?.toLocaleString() || 0} {t('common.currency')}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('admin.payments.pending')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {revenueReport.data.total_pending?.toLocaleString() || 0} {t('common.currency')}
                      </p>
                    </CardContent>
                  </Card>
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-500">{t('admin.reports.dateRange')}</p>
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
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>{t('admin.settings.startDate')}</Label>
                  <Input
                    type="date"
                    value={appointmentsStartDate}
                    onChange={(e) => setAppointmentsStartDate(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>{t('admin.settings.endDate')}</Label>
                  <Input
                    type="date"
                    value={appointmentsEndDate}
                    onChange={(e) => setAppointmentsEndDate(e.target.value)}
                  />
                </div>
                <div className="flex items-end">
                  <Button
                    onClick={handleExportAppointments}
                    disabled={!appointmentsStartDate || !appointmentsEndDate}
                    className="w-full"
                  >
                    <Download className="h-4 w-4 me-2" />
                    {t('admin.reports.exportPdf')}
                  </Button>
                </div>
              </div>

              {isLoadingAppointments ? (
                <div className="space-y-4">
                  <Skeleton className="h-32" />
                </div>
              ) : appointmentsReport?.data ? (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('admin.dashboard.totalAppointments')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {appointmentsReport.data.total || 0}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('patient.appointments.status.completed')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold text-green-600">
                        {appointmentsReport.data.by_status?.completed || 0}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('patient.appointments.status.cancelled')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold text-red-600">
                        {appointmentsReport.data.by_status?.cancelled || 0}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('patient.appointments.status.no_show')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold text-orange-600">
                        {appointmentsReport.data.by_status?.no_show || 0}
                      </p>
                    </CardContent>
                  </Card>
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-500">{t('admin.reports.dateRange')}</p>
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
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>{t('admin.settings.startDate')}</Label>
                  <Input
                    type="date"
                    value={patientsStartDate}
                    onChange={(e) => setPatientsStartDate(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>{t('admin.settings.endDate')}</Label>
                  <Input
                    type="date"
                    value={patientsEndDate}
                    onChange={(e) => setPatientsEndDate(e.target.value)}
                  />
                </div>
                <div className="flex items-end">
                  <Button
                    onClick={handleExportPatients}
                    disabled={!patientsStartDate || !patientsEndDate}
                    className="w-full"
                  >
                    <Download className="h-4 w-4 me-2" />
                    {t('admin.reports.exportPdf')}
                  </Button>
                </div>
              </div>

              {isLoadingPatients ? (
                <div className="space-y-4">
                  <Skeleton className="h-32" />
                </div>
              ) : patientsReport?.data ? (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('admin.dashboard.totalPatients')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {patientsReport.data.total_patients || 0}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        {t('common.add')} {t('navigation.patients')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold text-green-600">
                        {patientsReport.data.new_patients || 0}
                      </p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm font-medium text-gray-600">
                        <TrendingUp className="h-4 w-4 inline me-1" />
                        {t('common.active')}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-2xl font-bold">
                        {patientsReport.data.returning_patients || 0}
                      </p>
                    </CardContent>
                  </Card>
                </div>
              ) : (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-500">{t('admin.reports.dateRange')}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
