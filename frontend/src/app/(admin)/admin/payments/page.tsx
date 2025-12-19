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
  CreditCard,
  Search,
  Plus,
  Calendar,
  User,
  DollarSign,
  CheckCircle2,
  Clock,
  Filter,
} from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { adminApi } from '@/lib/api/admin';

const paymentSchema = z.object({
  patient_id: z.string().min(1, 'Patient is required'),
  amount: z.string().min(1, 'Amount is required'),
  payment_method: z.string().min(1, 'Payment method is required'),
  notes: z.string().optional(),
});

type PaymentFormData = z.infer<typeof paymentSchema>;

export default function AdminPaymentsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);

  const form = useForm<PaymentFormData>({
    resolver: zodResolver(paymentSchema),
    defaultValues: {
      patient_id: '',
      amount: '',
      payment_method: 'cash',
      notes: '',
    },
  });

  // Fetch payments
  const { data: payments, isLoading } = useQuery({
    queryKey: ['adminPayments', statusFilter],
    queryFn: () =>
      adminApi.getPayments({
        status: statusFilter !== 'all' ? statusFilter : undefined,
      }),
  });

  // Fetch patients for select
  const { data: patients } = useQuery({
    queryKey: ['adminPatients'],
    queryFn: () => adminApi.getPatients(),
  });

  // Record payment mutation
  const createMutation = useMutation({
    mutationFn: (data: PaymentFormData) =>
      adminApi.recordPayment({
        patient_id: parseInt(data.patient_id),
        amount: parseFloat(data.amount),
        payment_method: data.payment_method,
        notes: data.notes,
      }),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminPayments'] });
      setCreateDialogOpen(false);
      form.reset();
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const onSubmit = (data: PaymentFormData) => {
    createMutation.mutate(data);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'completed':
        return (
          <Badge className="bg-green-100 text-green-800">
            <CheckCircle2 className="h-3 w-3 me-1" />
            {t('admin.payments.completed')}
          </Badge>
        );
      case 'pending':
        return (
          <Badge className="bg-yellow-100 text-yellow-800">
            <Clock className="h-3 w-3 me-1" />
            {t('admin.payments.pending')}
          </Badge>
        );
      case 'failed':
        return (
          <Badge className="bg-red-100 text-red-800">
            {t('admin.payments.failed')}
          </Badge>
        );
      default:
        return null;
    }
  };

  const getPaymentMethodLabel = (method: string) => {
    switch (method) {
      case 'cash':
        return t('admin.payments.cash');
      case 'card':
        return t('admin.payments.card');
      case 'bank_transfer':
        return t('admin.payments.bankTransfer');
      default:
        return method;
    }
  };

  const filteredPayments = payments?.data?.filter((payment: any) => {
    if (!searchQuery) return true;
    const search = searchQuery.toLowerCase();
    return payment.patient?.name?.toLowerCase().includes(search);
  });

  // Calculate totals
  const totalAmount = filteredPayments?.reduce(
    (sum: number, p: any) => sum + (p.amount || 0),
    0
  ) || 0;
  const completedAmount =
    filteredPayments
      ?.filter((p: any) => p.status === 'completed')
      .reduce((sum: number, p: any) => sum + (p.amount || 0), 0) || 0;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">{t('admin.payments.title')}</h1>
        <Button onClick={() => setCreateDialogOpen(true)}>
          <Plus className="h-4 w-4 me-2" />
          {t('admin.payments.recordPayment')}
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('admin.payments.totalPayments')}</p>
                <p className="text-2xl font-bold">{totalAmount.toFixed(2)} ج.م</p>
              </div>
              <div className="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <DollarSign className="h-5 w-5 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('admin.payments.completedPayments')}</p>
                <p className="text-2xl font-bold">{completedAmount.toFixed(2)} ج.م</p>
              </div>
              <div className="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                <CheckCircle2 className="h-5 w-5 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{t('admin.payments.transactionsCount')}</p>
                <p className="text-2xl font-bold">{filteredPayments?.length || 0}</p>
              </div>
              <div className="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <CreditCard className="h-5 w-5 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>
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
                <SelectValue placeholder={t('admin.payments.filterByStatus')} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">{t('common.all')}</SelectItem>
                <SelectItem value="completed">{t('admin.payments.completed')}</SelectItem>
                <SelectItem value="pending">{t('admin.payments.pending')}</SelectItem>
                <SelectItem value="failed">{t('admin.payments.failed')}</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Payments List */}
      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : filteredPayments && filteredPayments.length > 0 ? (
        <div className="space-y-4">
          {filteredPayments.map((payment: any) => (
            <Card key={payment.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                      <CreditCard className="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <User className="h-4 w-4 text-gray-400" />
                        <span className="font-medium">{payment.patient?.name}</span>
                      </div>
                      <div className="flex items-center gap-4 text-sm text-gray-500">
                        <div className="flex items-center gap-1">
                          <Calendar className="h-4 w-4" />
                          <span>
                            {format(new Date(payment.created_at), 'PPP', { locale: ar })}
                          </span>
                        </div>
                        <span>|</span>
                        <span>{getPaymentMethodLabel(payment.payment_method)}</span>
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col items-end gap-2">
                    <span className="text-lg font-bold text-green-600">
                      {payment.amount?.toFixed(2)} ج.م
                    </span>
                    {getStatusBadge(payment.status)}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <CreditCard className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('common.noData')}</p>
        </div>
      )}

      {/* Create Payment Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{t('admin.payments.recordPayment')}</DialogTitle>
          </DialogHeader>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              <FormField
                control={form.control}
                name="patient_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.patients.title')}</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder={t('admin.payments.selectPatient')} />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {patients?.data?.map((patient: any) => (
                          <SelectItem key={patient.id} value={patient.id.toString()}>
                            {patient.name} - {patient.phone}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="amount"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.payments.amount')}</FormLabel>
                    <FormControl>
                      <div className="relative">
                        <DollarSign className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                          type="number"
                          step="0.01"
                          className="ps-10"
                          placeholder="0.00"
                          {...field}
                        />
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="payment_method"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.payments.paymentMethod')}</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="cash">{t('admin.payments.cash')}</SelectItem>
                        <SelectItem value="card">{t('admin.payments.card')}</SelectItem>
                        <SelectItem value="bank_transfer">
                          {t('admin.payments.bankTransfer')}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('common.notes')}</FormLabel>
                    <FormControl>
                      <Textarea rows={3} {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <DialogFooter>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setCreateDialogOpen(false)}
                >
                  {t('common.cancel')}
                </Button>
                <Button type="submit" disabled={createMutation.isPending}>
                  {createMutation.isPending ? t('common.loading') : t('common.save')}
                </Button>
              </DialogFooter>
            </form>
          </Form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
