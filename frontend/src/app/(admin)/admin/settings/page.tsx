'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { toast } from 'sonner';
import {
  Settings,
  Building2,
  Clock,
  CalendarOff,
  Save,
  Plus,
  Trash2,
  Phone,
  Mail,
  MapPin,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { adminApi } from '@/lib/api/admin';

const settingsSchema = z.object({
  clinic_name: z.string().min(1, 'Clinic name is required'),
  address: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email().optional().or(z.literal('')),
});

const vacationSchema = z.object({
  start_date: z.string().min(1, 'Start date is required'),
  end_date: z.string().min(1, 'End date is required'),
  reason: z.string().optional(),
});

type SettingsFormData = z.infer<typeof settingsSchema>;
type VacationFormData = z.infer<typeof vacationSchema>;

const DAYS_OF_WEEK = [
  { value: 0, label: 'الأحد' },
  { value: 1, label: 'الاثنين' },
  { value: 2, label: 'الثلاثاء' },
  { value: 3, label: 'الأربعاء' },
  { value: 4, label: 'الخميس' },
  { value: 5, label: 'الجمعة' },
  { value: 6, label: 'السبت' },
];

export default function AdminSettingsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [vacationDialogOpen, setVacationDialogOpen] = useState(false);
  const [deleteVacationId, setDeleteVacationId] = useState<number | null>(null);

  const settingsForm = useForm<SettingsFormData>({
    resolver: zodResolver(settingsSchema),
    defaultValues: {
      clinic_name: '',
      address: '',
      phone: '',
      email: '',
    },
  });

  const vacationForm = useForm<VacationFormData>({
    resolver: zodResolver(vacationSchema),
    defaultValues: {
      start_date: '',
      end_date: '',
      reason: '',
    },
  });

  // Fetch settings
  const { data: settings, isLoading: isLoadingSettings } = useQuery({
    queryKey: ['clinicSettings'],
    queryFn: () => adminApi.getClinicSettings(),
  });

  // Fetch schedules
  const { data: schedules, isLoading: isLoadingSchedules } = useQuery({
    queryKey: ['schedules'],
    queryFn: () => adminApi.getSchedules(),
  });

  // Fetch vacations
  const { data: vacations, isLoading: isLoadingVacations } = useQuery({
    queryKey: ['vacations'],
    queryFn: () => adminApi.getVacations(),
  });

  // Update settings mutation
  const updateSettingsMutation = useMutation({
    mutationFn: (data: SettingsFormData) => adminApi.updateClinicSettings(data),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['clinicSettings'] });
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  // Update schedule mutation
  const updateScheduleMutation = useMutation({
    mutationFn: ({
      scheduleId,
      data,
    }: {
      scheduleId: number;
      data: { is_working?: boolean; start_time?: string; end_time?: string; slot_duration?: number };
    }) => adminApi.updateSchedule(scheduleId, data),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['schedules'] });
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  // Create vacation mutation
  const createVacationMutation = useMutation({
    mutationFn: (data: VacationFormData) => adminApi.createVacation(data),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['vacations'] });
      setVacationDialogOpen(false);
      vacationForm.reset();
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  // Delete vacation mutation
  const deleteVacationMutation = useMutation({
    mutationFn: (id: number) => adminApi.deleteVacation(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['vacations'] });
      setDeleteVacationId(null);
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const onSettingsSubmit = (data: SettingsFormData) => {
    updateSettingsMutation.mutate(data);
  };

  const onVacationSubmit = (data: VacationFormData) => {
    createVacationMutation.mutate(data);
  };

  const handleScheduleToggle = (scheduleId: number, isWorking: boolean) => {
    updateScheduleMutation.mutate({
      scheduleId,
      data: { is_working: isWorking },
    });
  };

  const handleScheduleTimeChange = (
    scheduleId: number,
    schedule: { is_working: boolean; start_time: string; end_time: string },
    field: 'start_time' | 'end_time',
    value: string
  ) => {
    updateScheduleMutation.mutate({
      scheduleId,
      data: {
        is_working: schedule.is_working,
        start_time: field === 'start_time' ? value : schedule.start_time,
        end_time: field === 'end_time' ? value : schedule.end_time,
      },
    });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('admin.settings.title')}</h1>
      </div>

      <Tabs defaultValue="clinic" className="space-y-6">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="clinic">
            <Building2 className="h-4 w-4 me-2" />
            {t('admin.settings.clinicInfo')}
          </TabsTrigger>
          <TabsTrigger value="schedules">
            <Clock className="h-4 w-4 me-2" />
            {t('admin.settings.workingHours')}
          </TabsTrigger>
          <TabsTrigger value="vacations">
            <CalendarOff className="h-4 w-4 me-2" />
            {t('admin.settings.vacations')}
          </TabsTrigger>
        </TabsList>

        {/* Clinic Info Tab */}
        <TabsContent value="clinic">
          <Card>
            <CardHeader>
              <CardTitle>{t('admin.settings.clinicInfo')}</CardTitle>
            </CardHeader>
            <CardContent>
              {isLoadingSettings ? (
                <div className="space-y-4">
                  {[1, 2, 3, 4].map((i) => (
                    <Skeleton key={i} className="h-10" />
                  ))}
                </div>
              ) : (
                <Form {...settingsForm}>
                  <form
                    onSubmit={settingsForm.handleSubmit(onSettingsSubmit)}
                    className="space-y-4"
                  >
                    <FormField
                      control={settingsForm.control}
                      name="clinic_name"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('admin.settings.clinicName')}</FormLabel>
                          <FormControl>
                            <div className="relative">
                              <Building2 className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                              <Input className="ps-10" {...field} />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={settingsForm.control}
                      name="phone"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('admin.settings.phone')}</FormLabel>
                          <FormControl>
                            <div className="relative">
                              <Phone className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                              <Input className="ps-10" {...field} />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={settingsForm.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('admin.settings.email')}</FormLabel>
                          <FormControl>
                            <div className="relative">
                              <Mail className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                              <Input type="email" className="ps-10" {...field} />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={settingsForm.control}
                      name="address"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('admin.settings.address')}</FormLabel>
                          <FormControl>
                            <div className="relative">
                              <MapPin className="absolute start-3 top-3 h-4 w-4 text-gray-400" />
                              <Textarea className="ps-10" rows={2} {...field} />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <Button type="submit" disabled={updateSettingsMutation.isPending}>
                      <Save className="h-4 w-4 me-2" />
                      {updateSettingsMutation.isPending
                        ? t('common.loading')
                        : t('common.save')}
                    </Button>
                  </form>
                </Form>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Schedules Tab */}
        <TabsContent value="schedules">
          <Card>
            <CardHeader>
              <CardTitle>{t('admin.settings.workingHours')}</CardTitle>
            </CardHeader>
            <CardContent>
              {isLoadingSchedules ? (
                <div className="space-y-4">
                  {[1, 2, 3, 4, 5, 6, 7].map((i) => (
                    <Skeleton key={i} className="h-16" />
                  ))}
                </div>
              ) : (
                <div className="space-y-4">
                  {DAYS_OF_WEEK.map((day) => {
                    const schedule = schedules?.data?.find(
                      (s: any) => s.day_of_week === day.value
                    );
                    if (!schedule) return null;
                    return (
                      <div
                        key={day.value}
                        className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                      >
                        <div className="flex items-center gap-4">
                          <Switch
                            checked={schedule.is_working ?? false}
                            onCheckedChange={(checked) =>
                              handleScheduleToggle(schedule.id, checked)
                            }
                          />
                          <span className="font-medium w-24">{day.label}</span>
                        </div>
                        {schedule.is_working && (
                          <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                              <Label className="text-sm">{t('admin.settings.from')}</Label>
                              <Input
                                type="time"
                                className="w-32"
                                value={schedule.start_time || '09:00'}
                                onChange={(e) =>
                                  handleScheduleTimeChange(
                                    schedule.id,
                                    schedule,
                                    'start_time',
                                    e.target.value
                                  )
                                }
                              />
                            </div>
                            <div className="flex items-center gap-2">
                              <Label className="text-sm">{t('admin.settings.to')}</Label>
                              <Input
                                type="time"
                                className="w-32"
                                value={schedule.end_time || '17:00'}
                                onChange={(e) =>
                                  handleScheduleTimeChange(
                                    schedule.id,
                                    schedule,
                                    'end_time',
                                    e.target.value
                                  )
                                }
                              />
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Vacations Tab */}
        <TabsContent value="vacations">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>{t('admin.settings.vacations')}</CardTitle>
              <Button onClick={() => setVacationDialogOpen(true)}>
                <Plus className="h-4 w-4 me-2" />
                {t('admin.settings.addVacation')}
              </Button>
            </CardHeader>
            <CardContent>
              {isLoadingVacations ? (
                <div className="space-y-4">
                  {[1, 2, 3].map((i) => (
                    <Skeleton key={i} className="h-16" />
                  ))}
                </div>
              ) : vacations?.data && vacations.data.length > 0 ? (
                <div className="space-y-4">
                  {vacations.data.map((vacation: any) => (
                    <div
                      key={vacation.id}
                      className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                      <div>
                        <p className="font-medium">
                          {format(new Date(vacation.start_date), 'PPP', { locale: ar })} -{' '}
                          {format(new Date(vacation.end_date), 'PPP', { locale: ar })}
                        </p>
                        {vacation.reason && (
                          <p className="text-sm text-gray-500 mt-1">{vacation.reason}</p>
                        )}
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="text-red-600"
                        onClick={() => setDeleteVacationId(vacation.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <CalendarOff className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-500">{t('admin.settings.noVacations')}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Add Vacation Dialog */}
      <Dialog open={vacationDialogOpen} onOpenChange={setVacationDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{t('admin.settings.addVacation')}</DialogTitle>
          </DialogHeader>
          <Form {...vacationForm}>
            <form onSubmit={vacationForm.handleSubmit(onVacationSubmit)} className="space-y-4">
              <FormField
                control={vacationForm.control}
                name="start_date"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.settings.startDate')}</FormLabel>
                    <FormControl>
                      <Input type="date" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={vacationForm.control}
                name="end_date"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.settings.endDate')}</FormLabel>
                    <FormControl>
                      <Input type="date" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={vacationForm.control}
                name="reason"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.settings.reason')}</FormLabel>
                    <FormControl>
                      <Textarea rows={2} {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <DialogFooter>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setVacationDialogOpen(false)}
                >
                  {t('common.cancel')}
                </Button>
                <Button type="submit" disabled={createVacationMutation.isPending}>
                  {createVacationMutation.isPending ? t('common.loading') : t('common.save')}
                </Button>
              </DialogFooter>
            </form>
          </Form>
        </DialogContent>
      </Dialog>

      {/* Delete Vacation Confirmation */}
      <AlertDialog
        open={deleteVacationId !== null}
        onOpenChange={() => setDeleteVacationId(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{t('admin.settings.deleteVacation')}</AlertDialogTitle>
            <AlertDialogDescription>
              {t('admin.settings.deleteVacationConfirm')}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>{t('common.cancel')}</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteVacationId && deleteVacationMutation.mutate(deleteVacationId)}
              className="bg-red-600 hover:bg-red-700"
            >
              {deleteVacationMutation.isPending ? t('common.loading') : t('common.delete')}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
