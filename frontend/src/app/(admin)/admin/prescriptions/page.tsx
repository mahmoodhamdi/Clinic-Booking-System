'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { toast } from 'sonner';
import {
  Pill,
  Search,
  Plus,
  Calendar,
  User as UserIcon,
  Eye,
  CheckCircle2,
  Clock,
  Trash2,
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
import type { Prescription, PrescriptionItem, User, ApiResponse, PaginatedResponse } from '@/types';

const prescriptionSchema = z.object({
  patient_id: z.string().min(1, 'Patient is required'),
  diagnosis: z.string().min(1, 'Diagnosis is required'),
  notes: z.string().optional(),
  items: z.array(
    z.object({
      medication_name: z.string().min(1, 'Medication name is required'),
      dosage: z.string().min(1, 'Dosage is required'),
      frequency: z.string().min(1, 'Frequency is required'),
      duration: z.string().min(1, 'Duration is required'),
      instructions: z.string().optional(),
    })
  ).min(1, 'At least one medication is required'),
});

type PrescriptionFormData = z.infer<typeof prescriptionSchema>;

export default function AdminPrescriptionsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [selectedPrescription, setSelectedPrescription] = useState<Prescription | null>(null);

  const form = useForm<PrescriptionFormData>({
    resolver: zodResolver(prescriptionSchema),
    defaultValues: {
      patient_id: '',
      diagnosis: '',
      notes: '',
      items: [
        {
          medication_name: '',
          dosage: '',
          frequency: '',
          duration: '',
          instructions: '',
        },
      ],
    },
  });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: 'items',
  });

  // Fetch prescriptions
  const { data: prescriptions, isLoading } = useQuery<ApiResponse<Prescription[]>>({
    queryKey: ['adminPrescriptions'],
    queryFn: () => adminApi.getPrescriptions(),
  });

  // Fetch patients for select
  const { data: patients } = useQuery<PaginatedResponse<User>>({
    queryKey: ['adminPatients'],
    queryFn: () => adminApi.getPatients(),
  });

  // Create prescription mutation
  const createMutation = useMutation({
    mutationFn: (data: PrescriptionFormData) =>
      adminApi.createPrescription({
        patient_id: parseInt(data.patient_id),
        diagnosis: data.diagnosis,
        notes: data.notes,
        items: data.items,
      }),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminPrescriptions'] });
      setCreateDialogOpen(false);
      form.reset();
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  // Dispense prescription mutation
  const dispenseMutation = useMutation({
    mutationFn: (id: number) => adminApi.dispensePrescription(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminPrescriptions'] });
      setViewDialogOpen(false);
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const handleViewPrescription = (prescription: Prescription) => {
    setSelectedPrescription(prescription);
    setViewDialogOpen(true);
  };

  const onSubmit = (data: PrescriptionFormData) => {
    createMutation.mutate(data);
  };

  const filteredPrescriptions = prescriptions?.data?.filter((prescription) => {
    if (!searchQuery) return true;
    const search = searchQuery.toLowerCase();
    return (
      (prescription as Prescription & { patient?: User }).patient?.name?.toLowerCase().includes(search) ||
      prescription.diagnosis?.toLowerCase().includes(search)
    );
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">{t('admin.prescriptions.title')}</h1>
        <Button onClick={() => setCreateDialogOpen(true)}>
          <Plus className="h-4 w-4 me-2" />
          {t('admin.prescriptions.addNew')}
        </Button>
      </div>

      {/* Search */}
      <Card>
        <CardContent className="p-4">
          <div className="relative">
            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              placeholder={t('common.search')}
              className="ps-10"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Prescriptions List */}
      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : filteredPrescriptions && filteredPrescriptions.length > 0 ? (
        <div className="space-y-4">
          {filteredPrescriptions.map((prescription) => (
            <Card key={prescription.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                      <Pill className="h-6 w-6 text-purple-600" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <UserIcon className="h-4 w-4 text-gray-400" />
                        <span className="font-medium">{prescription.patient?.name}</span>
                      </div>
                      <p className="text-sm text-gray-500">{prescription.diagnosis}</p>
                      <div className="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <Calendar className="h-4 w-4" />
                        <span>
                          {format(new Date(prescription.created_at), 'PPP', { locale: ar })}
                        </span>
                        <span className="mx-2">|</span>
                        <span>
                          {prescription.items?.length || 0} {t('admin.prescriptions.medications')}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {prescription.is_dispensed ? (
                      <Badge className="bg-green-100 text-green-800">
                        <CheckCircle2 className="h-3 w-3 me-1" />
                        تم الصرف
                      </Badge>
                    ) : (
                      <Badge className="bg-yellow-100 text-yellow-800">
                        <Clock className="h-3 w-3 me-1" />
                        لم يصرف
                      </Badge>
                    )}
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleViewPrescription(prescription)}
                    >
                      <Eye className="h-4 w-4 me-1" />
                      {t('common.view')}
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <Pill className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('common.noData')}</p>
        </div>
      )}

      {/* Create Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{t('admin.prescriptions.addNew')}</DialogTitle>
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
                          <SelectValue placeholder={t('admin.prescriptions.selectPatient')} />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        {patients?.data?.map((patient) => (
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
                name="diagnosis"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>{t('admin.prescriptions.diagnosis')}</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <FormLabel>{t('admin.prescriptions.medications')}</FormLabel>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      append({
                        medication_name: '',
                        dosage: '',
                        frequency: '',
                        duration: '',
                        instructions: '',
                      })
                    }
                  >
                    <Plus className="h-4 w-4 me-1" />
                    {t('common.add')}
                  </Button>
                </div>

                {fields.map((field, index) => (
                  <Card key={field.id}>
                    <CardContent className="p-4 space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="font-medium">
                          {t('admin.prescriptions.medication')} #{index + 1}
                        </span>
                        {fields.length > 1 && (
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="text-red-600"
                            onClick={() => remove(index)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                      <div className="grid grid-cols-2 gap-3">
                        <FormField
                          control={form.control}
                          name={`items.${index}.medication_name`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('admin.prescriptions.medicationName')}</FormLabel>
                              <FormControl>
                                <Input {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name={`items.${index}.dosage`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('admin.prescriptions.dosage')}</FormLabel>
                              <FormControl>
                                <Input {...field} placeholder="500mg" />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name={`items.${index}.frequency`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('admin.prescriptions.frequency')}</FormLabel>
                              <FormControl>
                                <Input {...field} placeholder="3 مرات يوميا" />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name={`items.${index}.duration`}
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('admin.prescriptions.duration')}</FormLabel>
                              <FormControl>
                                <Input {...field} placeholder="7 أيام" />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                      </div>
                      <FormField
                        control={form.control}
                        name={`items.${index}.instructions`}
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>{t('admin.prescriptions.instructions')}</FormLabel>
                            <FormControl>
                              <Input {...field} placeholder="بعد الأكل" />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </CardContent>
                  </Card>
                ))}
              </div>

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

      {/* View Dialog */}
      <Dialog open={viewDialogOpen} onOpenChange={setViewDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{t('admin.prescriptions.prescriptionDetails')}</DialogTitle>
          </DialogHeader>
          {selectedPrescription && (
            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-500">{t('admin.patients.title')}</p>
                  <p className="font-medium">{selectedPrescription.patient?.name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">{t('common.date')}</p>
                  <p className="font-medium">
                    {format(new Date(selectedPrescription.created_at), 'PPP', { locale: ar })}
                  </p>
                </div>
              </div>
              <div>
                <p className="text-sm text-gray-500">{t('admin.prescriptions.diagnosis')}</p>
                <p className="font-medium">{selectedPrescription.diagnosis}</p>
              </div>

              <div>
                <p className="text-sm text-gray-500 mb-2">{t('admin.prescriptions.medications')}</p>
                <div className="space-y-2">
                  {selectedPrescription.items?.map((item, index) => (
                    <div key={index} className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <p className="font-medium">{item.medication_name}</p>
                      <div className="text-sm text-gray-500 mt-1 space-x-2 rtl:space-x-reverse">
                        <span>{item.dosage}</span>
                        <span>|</span>
                        <span>{item.frequency}</span>
                        <span>|</span>
                        <span>{item.duration}</span>
                      </div>
                      {item.instructions && (
                        <p className="text-sm text-gray-500 mt-1">{item.instructions}</p>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {selectedPrescription.notes && (
                <div>
                  <p className="text-sm text-gray-500">{t('common.notes')}</p>
                  <p className="text-gray-700 dark:text-gray-300">{selectedPrescription.notes}</p>
                </div>
              )}

              {!selectedPrescription.is_dispensed && (
                <DialogFooter>
                  <Button
                    onClick={() => dispenseMutation.mutate(selectedPrescription.id)}
                    disabled={dispenseMutation.isPending}
                    className="w-full"
                  >
                    <CheckCircle2 className="h-4 w-4 me-2" />
                    {dispenseMutation.isPending
                      ? t('common.loading')
                      : t('admin.prescriptions.markDispensed')}
                  </Button>
                </DialogFooter>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
