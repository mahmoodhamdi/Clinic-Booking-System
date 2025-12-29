'use client';

import { useState, useCallback, useMemo } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { toast } from 'sonner';
import {
  Calendar,
  Clock,
  CheckCircle2,
  XCircle,
  AlertCircle,
  X,
} from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { appointmentsApi } from '@/lib/api/appointments';
import { Appointment } from '@/types';

interface AppointmentCardProps {
  appointment: Appointment;
  onCancelClick: (appointment: Appointment) => void;
  getStatusBadge: (status: string) => React.ReactNode;
  cancelLabel: string;
}

function AppointmentCard({
  appointment,
  onCancelClick,
  getStatusBadge,
  cancelLabel,
}: AppointmentCardProps) {
  return (
    <Card className="hover:shadow-md transition-shadow">
      <CardContent className="p-4">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-4">
            <div className="h-14 w-14 rounded-lg bg-primary/10 flex flex-col items-center justify-center">
              <span className="text-lg font-bold text-primary">
                {new Date(appointment.date).getDate()}
              </span>
              <span className="text-xs text-primary">
                {format(new Date(appointment.date), 'MMM', { locale: ar })}
              </span>
            </div>
            <div>
              <p className="font-medium">
                {format(new Date(appointment.date), 'EEEE', { locale: ar })}
              </p>
              <div className="flex items-center gap-2 text-sm text-gray-500 mt-1">
                <Clock className="h-4 w-4" />
                <span>{appointment.slot_time}</span>
              </div>
              {appointment.reason && (
                <p className="text-sm text-gray-500 mt-1 line-clamp-1">
                  {appointment.reason}
                </p>
              )}
            </div>
          </div>
          <div className="flex flex-col items-end gap-2">
            {getStatusBadge(appointment.status)}
            <div className="flex gap-2">
              {(appointment.status === 'pending' ||
                appointment.status === 'confirmed') && (
                <Button
                  variant="ghost"
                  size="sm"
                  className="text-red-600 hover:text-red-700 hover:bg-red-50"
                  onClick={() => onCancelClick(appointment)}
                >
                  <X className="h-4 w-4 me-1" />
                  {cancelLabel}
                </Button>
              )}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

interface EmptyStateProps {
  message: string;
}

function EmptyState({ message }: EmptyStateProps) {
  return (
    <div className="text-center py-12">
      <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <p className="text-gray-500">{message}</p>
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-4">
      {[1, 2, 3].map((i) => (
        <Skeleton key={i} className="h-24" />
      ))}
    </div>
  );
}

export default function AppointmentsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [selectedTab, setSelectedTab] = useState('upcoming');
  const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
  const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);
  const [cancelReason, setCancelReason] = useState('');

  // Fetch appointments
  const { data: appointments, isLoading } = useQuery({
    queryKey: ['myAppointments'],
    queryFn: () => appointmentsApi.getMyAppointments(),
  });

  // Cancel mutation
  const cancelMutation = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      appointmentsApi.cancel(id, reason),
    onSuccess: () => {
      toast.success(t('patient.appointments.cancelSuccess'));
      queryClient.invalidateQueries({ queryKey: ['myAppointments'] });
      setCancelDialogOpen(false);
      setSelectedAppointment(null);
      setCancelReason('');
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const getStatusBadge = useCallback((status: string) => {
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
      case 'completed':
        return (
          <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100">
            <CheckCircle2 className="h-3 w-3 me-1" />
            {t('patient.appointments.status.completed')}
          </Badge>
        );
      case 'cancelled':
        return (
          <Badge className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100">
            <XCircle className="h-3 w-3 me-1" />
            {t('patient.appointments.status.cancelled')}
          </Badge>
        );
      case 'no_show':
        return (
          <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100">
            <AlertCircle className="h-3 w-3 me-1" />
            {t('patient.appointments.status.no_show')}
          </Badge>
        );
      default:
        return null;
    }
  }, [t]);

  const filterAppointments = useCallback((status: string) => {
    const data = appointments?.data;
    if (!data) return [];

    const now = new Date();

    switch (status) {
      case 'upcoming':
        return data.filter(
          (a) =>
            new Date(a.date) >= now &&
            (a.status === 'pending' || a.status === 'confirmed')
        );
      case 'past':
        return data.filter(
          (a) => a.status === 'completed' || new Date(a.date) < now
        );
      case 'cancelled':
        return data.filter((a) => a.status === 'cancelled');
      default:
        return data;
    }
  }, [appointments]);

  const handleCancelClick = useCallback((appointment: Appointment) => {
    setSelectedAppointment(appointment);
    setCancelDialogOpen(true);
  }, []);

  const handleCancelConfirm = useCallback(() => {
    if (!selectedAppointment) return;
    cancelMutation.mutate({
      id: selectedAppointment.id,
      reason: cancelReason,
    });
  }, [cancelMutation, selectedAppointment, cancelReason]);

  const upcomingAppointments = useMemo(() => filterAppointments('upcoming'), [filterAppointments]);
  const pastAppointments = useMemo(() => filterAppointments('past'), [filterAppointments]);
  const cancelledAppointments = useMemo(() => filterAppointments('cancelled'), [filterAppointments]);

  const cancelLabel = t('patient.appointments.cancelAppointment');

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('navigation.myAppointments')}</h1>
      </div>

      <Tabs value={selectedTab} onValueChange={setSelectedTab}>
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="upcoming">
            {t('patient.appointments.status.pending')}
          </TabsTrigger>
          <TabsTrigger value="past">
            {t('patient.appointments.status.completed')}
          </TabsTrigger>
          <TabsTrigger value="cancelled">
            {t('patient.appointments.status.cancelled')}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="upcoming" className="mt-6">
          {isLoading ? (
            <LoadingSkeleton />
          ) : upcomingAppointments.length > 0 ? (
            <div className="space-y-4">
              {upcomingAppointments.map((appointment) => (
                <AppointmentCard
                  key={appointment.id}
                  appointment={appointment}
                  onCancelClick={handleCancelClick}
                  getStatusBadge={getStatusBadge}
                  cancelLabel={cancelLabel}
                />
              ))}
            </div>
          ) : (
            <EmptyState message={t('patient.dashboard.noUpcoming')} />
          )}
        </TabsContent>

        <TabsContent value="past" className="mt-6">
          {isLoading ? (
            <LoadingSkeleton />
          ) : pastAppointments.length > 0 ? (
            <div className="space-y-4">
              {pastAppointments.map((appointment) => (
                <AppointmentCard
                  key={appointment.id}
                  appointment={appointment}
                  onCancelClick={handleCancelClick}
                  getStatusBadge={getStatusBadge}
                  cancelLabel={cancelLabel}
                />
              ))}
            </div>
          ) : (
            <EmptyState message={t('common.noData')} />
          )}
        </TabsContent>

        <TabsContent value="cancelled" className="mt-6">
          {isLoading ? (
            <LoadingSkeleton />
          ) : cancelledAppointments.length > 0 ? (
            <div className="space-y-4">
              {cancelledAppointments.map((appointment) => (
                <AppointmentCard
                  key={appointment.id}
                  appointment={appointment}
                  onCancelClick={handleCancelClick}
                  getStatusBadge={getStatusBadge}
                  cancelLabel={cancelLabel}
                />
              ))}
            </div>
          ) : (
            <EmptyState message={t('common.noData')} />
          )}
        </TabsContent>
      </Tabs>

      {/* Cancel Dialog */}
      <Dialog open={cancelDialogOpen} onOpenChange={setCancelDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('patient.appointments.cancelAppointment')}</DialogTitle>
            <DialogDescription>
              {t('patient.appointments.cancelConfirm')}
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Textarea
              placeholder={t('patient.appointments.cancelReason')}
              value={cancelReason}
              onChange={(e) => setCancelReason(e.target.value)}
              rows={3}
            />
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setCancelDialogOpen(false)}
            >
              {t('common.back')}
            </Button>
            <Button
              variant="destructive"
              onClick={handleCancelConfirm}
              disabled={cancelMutation.isPending}
            >
              {cancelMutation.isPending ? t('common.loading') : t('common.confirm')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
