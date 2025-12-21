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
import { CalendarOff, Plus, Trash2 } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
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

const vacationSchema = z.object({
  start_date: z.string().min(1, 'Start date is required'),
  end_date: z.string().min(1, 'End date is required'),
  reason: z.string().optional(),
});

type VacationFormData = z.infer<typeof vacationSchema>;

export default function VacationsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [vacationDialogOpen, setVacationDialogOpen] = useState(false);
  const [deleteVacationId, setDeleteVacationId] = useState<number | null>(null);

  const vacationForm = useForm<VacationFormData>({
    resolver: zodResolver(vacationSchema),
    defaultValues: {
      start_date: '',
      end_date: '',
      reason: '',
    },
  });

  // Fetch vacations
  const { data: vacations, isLoading } = useQuery({
    queryKey: ['vacations'],
    queryFn: () => adminApi.getVacations(),
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

  const onVacationSubmit = (data: VacationFormData) => {
    createVacationMutation.mutate(data);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">{t('admin.vacations.title')}</h1>
        <Button onClick={() => setVacationDialogOpen(true)}>
          <Plus className="h-4 w-4 me-2" />
          {t('admin.vacations.addVacation')}
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('admin.vacations.title')}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
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

      {/* Add Vacation Dialog */}
      <Dialog open={vacationDialogOpen} onOpenChange={setVacationDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{t('admin.vacations.addVacation')}</DialogTitle>
          </DialogHeader>
          <Form {...vacationForm}>
            <form onSubmit={vacationForm.handleSubmit(onVacationSubmit)} className="space-y-4">
              <FormField
                control={vacationForm.control}
                name="start_date"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.vacations.startDate')}</FormLabel>
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
                    <FormLabel>{t('admin.vacations.endDate')}</FormLabel>
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
                    <FormLabel>{t('admin.vacations.reason')}</FormLabel>
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
