'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import {
  CalendarPlus,
  Calendar,
  FileText,
  Clock,
  CheckCircle2,
  AlertCircle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useAuthStore } from '@/lib/stores/auth';

export default function PatientDashboard() {
  const t = useTranslations();
  const { user } = useAuthStore();

  // Mock data - will be replaced with API calls
  const upcomingAppointments = [
    {
      id: 1,
      date: '2025-01-15',
      time: '10:00',
      status: 'confirmed',
    },
    {
      id: 2,
      date: '2025-01-20',
      time: '14:30',
      status: 'pending',
    },
  ];

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'confirmed':
        return (
          <Badge className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
            <CheckCircle2 className="h-3 w-3 me-1" />
            {t('patient.appointments.status.confirmed')}
          </Badge>
        );
      case 'pending':
        return (
          <Badge className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100">
            <Clock className="h-3 w-3 me-1" />
            {t('patient.appointments.status.pending')}
          </Badge>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            {t('patient.dashboard.welcome')}، {user?.name} 👋
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
        <Button asChild>
          <Link href="/book">
            <CalendarPlus className="h-4 w-4 me-2" />
            {t('patient.dashboard.bookNow')}
          </Link>
        </Button>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Link href="/book">
          <Card className="hover:shadow-md transition-shadow cursor-pointer">
            <CardContent className="p-4 flex flex-col items-center text-center">
              <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center mb-2">
                <CalendarPlus className="h-6 w-6 text-primary" />
              </div>
              <span className="text-sm font-medium">
                {t('navigation.bookAppointment')}
              </span>
            </CardContent>
          </Card>
        </Link>
        <Link href="/appointments">
          <Card className="hover:shadow-md transition-shadow cursor-pointer">
            <CardContent className="p-4 flex flex-col items-center text-center">
              <div className="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                <Calendar className="h-6 w-6 text-blue-600" />
              </div>
              <span className="text-sm font-medium">
                {t('navigation.myAppointments')}
              </span>
            </CardContent>
          </Card>
        </Link>
        <Link href="/medical-records">
          <Card className="hover:shadow-md transition-shadow cursor-pointer">
            <CardContent className="p-4 flex flex-col items-center text-center">
              <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mb-2">
                <FileText className="h-6 w-6 text-green-600" />
              </div>
              <span className="text-sm font-medium">
                {t('navigation.medicalRecords')}
              </span>
            </CardContent>
          </Card>
        </Link>
        <Link href="/profile">
          <Card className="hover:shadow-md transition-shadow cursor-pointer">
            <CardContent className="p-4 flex flex-col items-center text-center">
              <div className="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mb-2">
                <FileText className="h-6 w-6 text-purple-600" />
              </div>
              <span className="text-sm font-medium">
                {t('navigation.profile')}
              </span>
            </CardContent>
          </Card>
        </Link>
      </div>

      {/* Upcoming Appointments */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>{t('patient.dashboard.upcomingAppointments')}</CardTitle>
          <Button variant="ghost" size="sm" asChild>
            <Link href="/appointments">{t('patient.dashboard.viewAll')}</Link>
          </Button>
        </CardHeader>
        <CardContent>
          {upcomingAppointments.length > 0 ? (
            <div className="space-y-4">
              {upcomingAppointments.map((appointment) => (
                <div
                  key={appointment.id}
                  className="flex items-center justify-between p-4 rounded-lg border bg-gray-50 dark:bg-gray-800"
                >
                  <div className="flex items-center gap-4">
                    <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                      <Calendar className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <p className="font-medium">
                        {new Date(appointment.date).toLocaleDateString('ar-EG', {
                          weekday: 'long',
                          month: 'long',
                          day: 'numeric',
                        })}
                      </p>
                      <p className="text-sm text-gray-500">{appointment.time}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {getStatusBadge(appointment.status)}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <AlertCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-500">{t('patient.dashboard.noUpcoming')}</p>
              <Button asChild className="mt-4">
                <Link href="/book">{t('patient.dashboard.bookNow')}</Link>
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
