'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import {
  Users,
  Calendar,
  Clock,
  DollarSign,
  TrendingUp,
  CheckCircle2,
  XCircle,
  AlertCircle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

export default function AdminDashboard() {
  const t = useTranslations('admin.dashboard');

  // Mock data - will be replaced with API calls
  const stats = {
    totalPatients: 156,
    todayAppointments: 12,
    pendingAppointments: 5,
    todayRevenue: 2500,
  };

  const todayAppointments = [
    {
      id: 1,
      patientName: 'أحمد محمد',
      time: '09:00',
      status: 'confirmed',
    },
    {
      id: 2,
      patientName: 'فاطمة علي',
      time: '09:30',
      status: 'pending',
    },
    {
      id: 3,
      patientName: 'محمود حسن',
      time: '10:00',
      status: 'completed',
    },
    {
      id: 4,
      patientName: 'نورا أحمد',
      time: '10:30',
      status: 'confirmed',
    },
  ];

  const recentActivity = [
    {
      id: 1,
      action: 'تم تأكيد موعد',
      patient: 'أحمد محمد',
      time: 'منذ 5 دقائق',
    },
    {
      id: 2,
      action: 'تم إضافة سجل طبي',
      patient: 'فاطمة علي',
      time: 'منذ 15 دقيقة',
    },
    {
      id: 3,
      action: 'تم استلام دفعة',
      patient: 'محمود حسن',
      time: 'منذ 30 دقيقة',
    },
  ];

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'confirmed':
        return (
          <Badge className="bg-green-100 text-green-800">
            <CheckCircle2 className="h-3 w-3 me-1" />
            مؤكد
          </Badge>
        );
      case 'pending':
        return (
          <Badge className="bg-yellow-100 text-yellow-800">
            <Clock className="h-3 w-3 me-1" />
            معلق
          </Badge>
        );
      case 'completed':
        return (
          <Badge className="bg-blue-100 text-blue-800">
            <CheckCircle2 className="h-3 w-3 me-1" />
            مكتمل
          </Badge>
        );
      default:
        return null;
    }
  };

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
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('totalPatients')}</p>
                <p className="text-3xl font-bold mt-1">{stats.totalPatients}</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                <Users className="h-6 w-6 text-blue-600" />
              </div>
            </div>
            <div className="flex items-center gap-1 mt-2 text-sm text-green-600">
              <TrendingUp className="h-4 w-4" />
              <span>+12% هذا الشهر</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('todayAppointments')}</p>
                <p className="text-3xl font-bold mt-1">{stats.todayAppointments}</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                <Calendar className="h-6 w-6 text-green-600" />
              </div>
            </div>
            <div className="mt-2 text-sm text-gray-500">
              {stats.pendingAppointments} في انتظار التأكيد
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('pendingAppointments')}</p>
                <p className="text-3xl font-bold mt-1">{stats.pendingAppointments}</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                <Clock className="h-6 w-6 text-yellow-600" />
              </div>
            </div>
            <Button variant="link" className="p-0 h-auto mt-2" asChild>
              <Link href="/admin/appointments?status=pending">
                عرض الكل
              </Link>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('todayRevenue')}</p>
                <p className="text-3xl font-bold mt-1">{stats.todayRevenue} ج.م</p>
              </div>
              <div className="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                <DollarSign className="h-6 w-6 text-purple-600" />
              </div>
            </div>
            <div className="flex items-center gap-1 mt-2 text-sm text-green-600">
              <TrendingUp className="h-4 w-4" />
              <span>+8% عن أمس</span>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Today's Appointments */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>{t('todayAppointments')}</CardTitle>
            <Button variant="outline" size="sm" asChild>
              <Link href="/admin/appointments/today">عرض الكل</Link>
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {todayAppointments.map((appointment) => (
                <div
                  key={appointment.id}
                  className="flex items-center justify-between p-4 rounded-lg border bg-gray-50 dark:bg-gray-800"
                >
                  <div className="flex items-center gap-4">
                    <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <span className="font-medium text-primary">
                        {appointment.patientName.charAt(0)}
                      </span>
                    </div>
                    <div>
                      <p className="font-medium">{appointment.patientName}</p>
                      <p className="text-sm text-gray-500">{appointment.time}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {getStatusBadge(appointment.status)}
                    <Button variant="ghost" size="sm" asChild>
                      <Link href={`/admin/appointments/${appointment.id}`}>
                        عرض
                      </Link>
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle>{t('recentActivity')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentActivity.map((activity) => (
                <div
                  key={activity.id}
                  className="flex items-start gap-3 pb-4 border-b last:border-0 last:pb-0"
                >
                  <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <AlertCircle className="h-4 w-4 text-gray-600" />
                  </div>
                  <div>
                    <p className="text-sm font-medium">{activity.action}</p>
                    <p className="text-sm text-gray-500">{activity.patient}</p>
                    <p className="text-xs text-gray-400 mt-1">{activity.time}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
