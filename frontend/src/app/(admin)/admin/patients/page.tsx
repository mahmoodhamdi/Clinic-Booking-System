'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import {
  Users,
  Search,
  Phone,
  Mail,
  Calendar,
  Eye,
  FileText,
  Pill,
} from 'lucide-react';
import Link from 'next/link';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { adminApi } from '@/lib/api/admin';
import type { User, Appointment, MedicalRecord, Prescription, PaginatedResponse } from '@/types';

export default function AdminPatientsPage() {
  const t = useTranslations();
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedPatient, setSelectedPatient] = useState<User | null>(null);
  const [detailsDialogOpen, setDetailsDialogOpen] = useState(false);

  // Fetch patients
  const { data: patients, isLoading } = useQuery<PaginatedResponse<User>>({
    queryKey: ['adminPatients', searchQuery],
    queryFn: () => adminApi.getPatients({ search: searchQuery || undefined }),
  });

  // Fetch patient details when selected
  const { data: patientDetails, isLoading: isLoadingDetails } = useQuery({
    queryKey: ['patientDetails', selectedPatient?.id],
    queryFn: () => (selectedPatient ? adminApi.getPatient(selectedPatient.id) : null),
    enabled: !!selectedPatient,
  });

  const handleViewPatient = (patient: User) => {
    setSelectedPatient(patient);
    setDetailsDialogOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">{t('admin.patients.title')}</h1>
      </div>

      {/* Search */}
      <Card>
        <CardContent className="p-4">
          <div className="relative">
            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              placeholder={t('admin.patients.searchPlaceholder')}
              className="ps-10"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Patients List */}
      {isLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <Skeleton key={i} className="h-40" />
          ))}
        </div>
      ) : patients?.data && patients.data.length > 0 ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {patients.data.map((patient: User) => (
            <Card key={patient.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start gap-4">
                  <Avatar className="h-12 w-12">
                    <AvatarImage src={patient.avatar || ''} />
                    <AvatarFallback>{patient.name?.charAt(0) || 'P'}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <h3 className="font-medium truncate">{patient.name}</h3>
                    <div className="flex items-center gap-1 text-sm text-gray-500 mt-1">
                      <Phone className="h-3 w-3" />
                      <span>{patient.phone}</span>
                    </div>
                    {patient.email && (
                      <div className="flex items-center gap-1 text-sm text-gray-500">
                        <Mail className="h-3 w-3" />
                        <span className="truncate">{patient.email}</span>
                      </div>
                    )}
                  </div>
                </div>
                <div className="flex items-center justify-between mt-4 pt-4 border-t">
                  <div className="flex items-center gap-2 text-sm text-gray-500">
                    <Calendar className="h-4 w-4" />
                    <span>
                      {patient.created_at
                        ? format(new Date(patient.created_at), 'PP', { locale: ar })
                        : '-'}
                    </span>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => handleViewPatient(patient)}>
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
          <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500">{t('common.noData')}</p>
        </div>
      )}

      {/* Patient Details Dialog */}
      <Dialog open={detailsDialogOpen} onOpenChange={setDetailsDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{t('admin.patients.patientDetails')}</DialogTitle>
          </DialogHeader>
          {isLoadingDetails ? (
            <div className="space-y-4 py-4">
              <Skeleton className="h-20" />
              <Skeleton className="h-40" />
            </div>
          ) : patientDetails?.data ? (
            <div className="py-4">
              {/* Patient Info */}
              <div className="flex items-start gap-4 mb-6">
                <Avatar className="h-16 w-16">
                  <AvatarImage src={patientDetails.data.avatar || ''} />
                  <AvatarFallback className="text-xl">
                    {patientDetails.data.name?.charAt(0) || 'P'}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <h3 className="text-lg font-medium">{patientDetails.data.name}</h3>
                  <div className="flex flex-wrap gap-4 text-sm text-gray-500 mt-1">
                    <div className="flex items-center gap-1">
                      <Phone className="h-4 w-4" />
                      <span>{patientDetails.data.phone}</span>
                    </div>
                    {patientDetails.data.email && (
                      <div className="flex items-center gap-1">
                        <Mail className="h-4 w-4" />
                        <span>{patientDetails.data.email}</span>
                      </div>
                    )}
                  </div>
                  <div className="flex gap-2 mt-2">
                    {patientDetails.data.gender && (
                      <Badge variant="outline">
                        {patientDetails.data.gender === 'male'
                          ? t('patient.profile.male')
                          : t('patient.profile.female')}
                      </Badge>
                    )}
                    {patientDetails.data.date_of_birth && (
                      <Badge variant="outline">
                        {format(new Date(patientDetails.data.date_of_birth), 'PP', {
                          locale: ar,
                        })}
                      </Badge>
                    )}
                  </div>
                </div>
              </div>

              {/* Tabs for Records */}
              <Tabs defaultValue="appointments" className="w-full">
                <TabsList className="grid w-full grid-cols-3">
                  <TabsTrigger value="appointments">
                    <Calendar className="h-4 w-4 me-2" />
                    {t('navigation.appointments')}
                  </TabsTrigger>
                  <TabsTrigger value="records">
                    <FileText className="h-4 w-4 me-2" />
                    {t('navigation.medicalRecords')}
                  </TabsTrigger>
                  <TabsTrigger value="prescriptions">
                    <Pill className="h-4 w-4 me-2" />
                    {t('navigation.prescriptions')}
                  </TabsTrigger>
                </TabsList>

                <TabsContent value="appointments" className="mt-4">
                  {(patientDetails.data.appointments?.length ?? 0) > 0 ? (
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                      {patientDetails.data.appointments?.map((apt: Appointment) => (
                        <div
                          key={apt.id}
                          className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                          <div>
                            <p className="font-medium">
                              {format(new Date(apt.date), 'PPP', { locale: ar })}
                            </p>
                            <p className="text-sm text-gray-500">{apt.slot_time}</p>
                          </div>
                          <Badge
                            className={
                              apt.status === 'completed'
                                ? 'bg-green-100 text-green-800'
                                : apt.status === 'cancelled'
                                ? 'bg-red-100 text-red-800'
                                : 'bg-yellow-100 text-yellow-800'
                            }
                          >
                            {apt.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-center text-gray-500 py-4">{t('common.noData')}</p>
                  )}
                </TabsContent>

                <TabsContent value="records" className="mt-4">
                  {(patientDetails.data.medical_records?.length ?? 0) > 0 ? (
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                      {patientDetails.data.medical_records?.map((record: MedicalRecord) => (
                        <div
                          key={record.id}
                          className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                          <p className="font-medium">{record.diagnosis}</p>
                          <p className="text-sm text-gray-500 mt-1">
                            {format(new Date(record.created_at), 'PPP', { locale: ar })}
                          </p>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-center text-gray-500 py-4">{t('common.noData')}</p>
                  )}
                </TabsContent>

                <TabsContent value="prescriptions" className="mt-4">
                  {(patientDetails.data.prescriptions?.length ?? 0) > 0 ? (
                    <div className="space-y-2 max-h-60 overflow-y-auto">
                      {patientDetails.data.prescriptions?.map((prescription: Prescription) => (
                        <div
                          key={prescription.id}
                          className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                        >
                          <div className="flex items-center justify-between">
                            <p className="font-medium">{prescription.diagnosis}</p>
                            <Badge
                              className={
                                prescription.is_dispensed
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-yellow-100 text-yellow-800'
                              }
                            >
                              {prescription.is_dispensed ? 'تم الصرف' : 'لم يصرف'}
                            </Badge>
                          </div>
                          <p className="text-sm text-gray-500 mt-1">
                            {prescription.items?.length || 0} {t('admin.prescriptions.medications')}
                          </p>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-center text-gray-500 py-4">{t('common.noData')}</p>
                  )}
                </TabsContent>
              </Tabs>
            </div>
          ) : null}
        </DialogContent>
      </Dialog>
    </div>
  );
}
