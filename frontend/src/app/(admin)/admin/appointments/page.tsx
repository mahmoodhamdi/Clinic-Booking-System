'use client';

import { useState } from 'react';
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
  Search,
  Filter,
  MoreVertical,
  User,
  Phone,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { adminApi } from '@/lib/api/admin';
import { Appointment } from '@/types';

export default function AdminAppointmentsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [statusFilter, setStatusFilter] = useState('all');
  const [dateFilter, setDateFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusDialogOpen, setStatusDialogOpen] = useState(false);
  const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);
  const [newStatus, setNewStatus] = useState('');
  const [statusNotes, setStatusNotes] = useState('');

  // Fetch appointments
  const { data: appointments, isLoading } = useQuery({
    queryKey: ['adminAppointments', statusFilter, dateFilter],
    queryFn: () =>
      adminApi.getAppointments({
        status: statusFilter !== 'all' ? statusFilter : undefined,
        date: dateFilter || undefined,
      }),
  });

  // Status change mutations
  const confirmMutation = useMutation({
    mutationFn: (id: number) => adminApi.confirmAppointment(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      closeStatusDialog();
    },
    onError: () => toast.error(t('common.error')),
  });

  const completeMutation = useMutation({
    mutationFn: (id: number) => adminApi.completeAppointment(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      closeStatusDialog();
    },
    onError: () => toast.error(t('common.error')),
  });

  const cancelMutation = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) =>
      adminApi.cancelAppointment(id, reason),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      closeStatusDialog();
    },
    onError: () => toast.error(t('common.error')),
  });

  const noShowMutation = useMutation({
    mutationFn: (id: number) => adminApi.markNoShow(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      closeStatusDialog();
    },
    onError: () => toast.error(t('common.error')),
  });

  const closeStatusDialog = () => {
    setStatusDialogOpen(false);
    setSelectedAppointment(null);
    setNewStatus('');
    setStatusNotes('');
  };

  const isPending = confirmMutation.isPending || completeMutation.isPending ||
    cancelMutation.isPending || noShowMutation.isPending;

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
  };

  const handleStatusChange = (appointment: Appointment, status: string) => {
    setSelectedAppointment(appointment);
    setNewStatus(status);
    setStatusDialogOpen(true);
  };

  const handleStatusConfirm = () => {
    if (!selectedAppointment) return;

    switch (newStatus) {
      case 'confirmed':
        confirmMutation.mutate(selectedAppointment.id);
        break;
      case 'completed':
        completeMutation.mutate(selectedAppointment.id);
        break;
      case 'cancelled':
        cancelMutation.mutate({ id: selectedAppointment.id, reason: statusNotes });
        break;
      case 'no_show':
        noShowMutation.mutate(selectedAppointment.id);
        break;
    }
  };

  const filteredAppointments = appointments?.data?.filter((appointment: Appointment) => {
    if (!searchQuery) return true;
    const search = searchQuery.toLowerCase();
    return (
      appointment.patient?.name?.toLowerCase().includes(search) ||
      appointment.patient?.phone?.includes(search)
    );
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">{t('admin.appointments.title')}</h1>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
              <Input
                placeholder={t('common.search')}
                className="ps-10"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder={t('admin.appointments.filterByStatus')} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{t('common.all')}</SelectItem>
                <SelectItem value="pending">{t('patient.appointments.status.pending')}</SelectItem>
                <SelectItem value="confirmed">{t('patient.appointments.status.confirmed')}</SelectItem>
                <SelectItem value="completed">{t('patient.appointments.status.completed')}</SelectItem>
                <SelectItem value="cancelled">{t('patient.appointments.status.cancelled')}</SelectItem>
                <SelectItem value="no_show">{t('patient.appointments.status.no_show')}</SelectItem>
              </SelectContent>
            </Select>
            <Input
              type="date"
              className="w-full md:w-48"
              value={dateFilter}
              onChange={(e) => setDateFilter(e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Appointments List */}
      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : filteredAppointments && filteredAppointments.length > 0 ? (
        <div className="space-y-4">
          {filteredAppointments.map((appointment: Appointment) => (
            <Card key={appointment.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-14 w-14 rounded-lg bg-primary/10 flex flex-col items-center justify-center">
                      <span className="text-lg font-bold text-primary">
                        {new Date(appointment.date).getDate()}
                      </span>
                      <span className="text-xs text-primary">
                        {format(new Date(appointment.date), 'MMM', { locale: ar })}
                      </span>
                    </div>
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <User className="h-4 w-4 text-gray-400" />
                        <span className="font-medium">{appointment.patient?.name}</span>
                      </div>
                      <div className="flex items-center gap-4 text-sm text-gray-500">
                        <div className="flex items-center gap-1">
                          <Phone className="h-3 w-3" />
                          <span>{appointment.patient?.phone}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Clock className="h-3 w-3" />
                          <span>{appointment.slot_time}</span>
                        </div>
                      </div>
                      {appointment.reason && (
                        <p className="text-sm text-gray-500 mt-1 line-clamp-1">
                          {appointment.reason}
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {getStatusBadge(appointment.status)}
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                          <MoreVertical className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        {appointment.status === 'pending' && (
                          <DropdownMenuItem
                            onClick={() => handleStatusChange(appointment, 'confirmed')}
                          >
                            <CheckCircle2 className="h-4 w-4 me-2 text-green-600" />
                            {t('admin.appointments.confirm')}
                          </DropdownMenuItem>
                        )}
                        {(appointment.status === 'pending' ||
                          appointment.status === 'confirmed') && (
                          <>
                            <DropdownMenuItem
                              onClick={() => handleStatusChange(appointment, 'completed')}
                            >
                              <CheckCircle2 className="h-4 w-4 me-2 text-blue-600" />
                              {t('admin.appointments.markCompleted')}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => handleStatusChange(appointment, 'no_show')}
                            >
                              <AlertCircle className="h-4 w-4 me-2 text-gray-600" />
                              {t('admin.appointments.markNoShow')}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => handleStatusChange(appointment, 'cancelled')}
                              className="text-red-600"
                            >
                              <XCircle className="h-4 w-4 me-2" />
                              {t('common.cancel')}
                            </DropdownMenuItem>
                          </>
                        )}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('common.noData')}</p>
        </div>
      )}

      {/* Status Change Dialog */}
      <Dialog open={statusDialogOpen} onOpenChange={setStatusDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('admin.appointments.changeStatus')}</DialogTitle>
            <DialogDescription>
              {t('admin.appointments.changeStatusDescription')}
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Textarea
              placeholder={t('common.notes')}
              value={statusNotes}
              onChange={(e) => setStatusNotes(e.target.value)}
              rows={3}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setStatusDialogOpen(false)}>
              {t('common.cancel')}
            </Button>
            <Button
              onClick={handleStatusConfirm}
              disabled={isPending}
            >
              {isPending ? t('common.loading') : t('common.confirm')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
