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
  FileText,
  Search,
  Plus,
  Calendar,
  User,
  Stethoscope,
  Eye,
  Upload,
} from 'lucide-react';

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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { adminApi } from '@/lib/api/admin';
import type { MedicalRecord, User, Attachment, ApiResponse, PaginatedResponse } from '@/types';

const recordSchema = z.object({
  patient_id: z.string().min(1, 'Patient is required'),
  appointment_id: z.string().optional(),
  diagnosis: z.string().min(1, 'Diagnosis is required'),
  notes: z.string().optional(),
});

type RecordFormData = z.infer<typeof recordSchema>;

export default function AdminMedicalRecordsPage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<MedicalRecord | null>(null);

  const form = useForm<RecordFormData>({
    resolver: zodResolver(recordSchema),
    defaultValues: {
      patient_id: '',
      appointment_id: '',
      diagnosis: '',
      notes: '',
    },
  });

  // Fetch medical records
  const { data: records, isLoading } = useQuery<ApiResponse<MedicalRecord[]>>({
    queryKey: ['adminMedicalRecords'],
    queryFn: () => adminApi.getMedicalRecords(),
  });

  // Fetch patients for select
  const { data: patients } = useQuery<PaginatedResponse<User>>({
    queryKey: ['adminPatients'],
    queryFn: () => adminApi.getPatients(),
  });

  // Create record mutation
  const createMutation = useMutation({
    mutationFn: (data: RecordFormData) =>
      adminApi.createMedicalRecord({
        patient_id: parseInt(data.patient_id),
        appointment_id: data.appointment_id ? parseInt(data.appointment_id) : undefined,
        diagnosis: data.diagnosis,
        notes: data.notes,
      }),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminMedicalRecords'] });
      setCreateDialogOpen(false);
      form.reset();
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const handleViewRecord = (record: MedicalRecord) => {
    setSelectedRecord(record);
    setViewDialogOpen(true);
  };

  const onSubmit = (data: RecordFormData) => {
    createMutation.mutate(data);
  };

  const filteredRecords = records?.data?.filter((record) => {
    if (!searchQuery) return true;
    const search = searchQuery.toLowerCase();
    return (
      (record as MedicalRecord & { patient?: User }).patient?.name?.toLowerCase().includes(search) ||
      record.diagnosis?.toLowerCase().includes(search)
    );
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">{t('admin.medicalRecords.title')}</h1>
        <Button onClick={() => setCreateDialogOpen(true)}>
          <Plus className="h-4 w-4 me-2" />
          {t('admin.medicalRecords.addNew')}
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

      {/* Records List */}
      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3, 4, 5].map((i) => (
            <Skeleton key={i} className="h-24" />
          ))}
        </div>
      ) : filteredRecords && filteredRecords.length > 0 ? (
        <div className="space-y-4">
          {filteredRecords.map((record) => (
            <Card key={record.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                      <FileText className="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <User className="h-4 w-4 text-gray-400" />
                        <span className="font-medium">{record.patient?.name}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-gray-500">
                        <Stethoscope className="h-4 w-4" />
                        <span>{record.diagnosis}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <Calendar className="h-4 w-4" />
                        <span>
                          {format(new Date(record.created_at), 'PPP', { locale: ar })}
                        </span>
                      </div>
                    </div>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => handleViewRecord(record)}>
                    <Eye className="h-4 w-4 me-1" />
                    {t('common.view')}
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-12">
          <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('common.noData')}</p>
        </div>
      )}

      {/* Create Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{t('admin.medicalRecords.addNew')}</DialogTitle>
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
                          <SelectValue placeholder={t('admin.medicalRecords.selectPatient')} />
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
                    <FormLabel>{t('admin.medicalRecords.diagnosis')}</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
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
                      <Textarea rows={4} {...field} />
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
            <DialogTitle>{t('admin.medicalRecords.recordDetails')}</DialogTitle>
          </DialogHeader>
          {selectedRecord && (
            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-500">{t('admin.patients.title')}</p>
                  <p className="font-medium">{selectedRecord.patient?.name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">{t('common.date')}</p>
                  <p className="font-medium">
                    {format(new Date(selectedRecord.created_at), 'PPP', { locale: ar })}
                  </p>
                </div>
              </div>
              <div>
                <p className="text-sm text-gray-500">{t('admin.medicalRecords.diagnosis')}</p>
                <p className="font-medium">{selectedRecord.diagnosis}</p>
              </div>
              {selectedRecord.notes && (
                <div>
                  <p className="text-sm text-gray-500">{t('common.notes')}</p>
                  <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {selectedRecord.notes}
                  </p>
                </div>
              )}
              {selectedRecord.attachments && selectedRecord.attachments.length > 0 && (
                <div>
                  <p className="text-sm text-gray-500 mb-2">
                    {t('admin.medicalRecords.attachments')}
                  </p>
                  <div className="flex flex-wrap gap-2">
                    {selectedRecord.attachments.map((attachment, index) => (
                      <a
                        key={index}
                        href={attachment.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-700"
                      >
                        <Upload className="h-3 w-3" />
                        {attachment.name}
                      </a>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
