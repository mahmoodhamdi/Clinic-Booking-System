'use client';

import { useParams, useRouter } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import Link from 'next/link';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { patientApi } from '@/lib/api/patient';
import type { MedicalRecord, ApiResponse } from '@/types';
import {
  ArrowRight,
  Calendar,
  FileText,
  Pill,
  Stethoscope,
  Heart,
  Thermometer,
  Activity,
  Scale,
  Ruler,
  Paperclip,
  Download,
  CheckCircle,
  XCircle,
} from 'lucide-react';

export default function MedicalRecordDetailPage() {
  const params = useParams();
  const router = useRouter();
  const t = useTranslations();
  const recordId = params.id as string;

  const { data, isLoading, error } = useQuery<ApiResponse<MedicalRecord>>({
    queryKey: ['medical-record', recordId],
    queryFn: () => patientApi.getMedicalRecord(Number(recordId)),
    enabled: !!recordId,
  });

  if (isLoading) {
    return <MedicalRecordSkeleton />;
  }

  if (error || !data?.data) {
    return (
      <div className="text-center py-12">
        <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-muted-foreground">لم يتم العثور على السجل الطبي</p>
        <Button onClick={() => router.back()} variant="outline" className="mt-4">
          {t('common.back')}
        </Button>
      </div>
    );
  }

  const record = data.data;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{t('navigation.medicalRecords')}</h1>
          <p className="text-muted-foreground">
            {format(new Date(record.created_at), 'EEEE، d MMMM yyyy', { locale: ar })}
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="grid gap-6 md:grid-cols-2">
        {/* Diagnosis */}
        <Card className="md:col-span-2">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Stethoscope className="h-5 w-5" />
              {t('admin.medicalRecords.diagnosis')}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg">{record.diagnosis}</p>
          </CardContent>
        </Card>

        {/* Vital Signs */}
        {(record.blood_pressure_systolic || record.heart_rate || record.temperature || record.weight || record.height) && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Activity className="h-5 w-5" />
                {t('admin.medicalRecords.vitalSigns')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-4">
                {record.blood_pressure_systolic && record.blood_pressure_diastolic && (
                  <VitalSign
                    icon={<Heart className="h-4 w-4" />}
                    label={t('admin.medicalRecords.bloodPressure')}
                    value={`${record.blood_pressure_systolic}/${record.blood_pressure_diastolic}`}
                    unit="mmHg"
                  />
                )}
                {record.heart_rate && (
                  <VitalSign
                    icon={<Activity className="h-4 w-4" />}
                    label={t('admin.medicalRecords.heartRate')}
                    value={record.heart_rate}
                    unit="نبضة/دقيقة"
                  />
                )}
                {record.temperature && (
                  <VitalSign
                    icon={<Thermometer className="h-4 w-4" />}
                    label={t('admin.medicalRecords.temperature')}
                    value={record.temperature}
                    unit="°C"
                  />
                )}
                {record.weight && (
                  <VitalSign
                    icon={<Scale className="h-4 w-4" />}
                    label={t('admin.medicalRecords.weight')}
                    value={record.weight}
                    unit="كجم"
                  />
                )}
                {record.height && (
                  <VitalSign
                    icon={<Ruler className="h-4 w-4" />}
                    label={t('admin.medicalRecords.height')}
                    value={record.height}
                    unit="سم"
                  />
                )}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Notes */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              {t('admin.medicalRecords.notes')}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {record.notes ? (
              <p>{record.notes}</p>
            ) : (
              <p className="text-muted-foreground">لا توجد ملاحظات</p>
            )}
          </CardContent>
        </Card>

        {/* Treatment Plan */}
        {record.treatment_plan && (
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle>{t('admin.medicalRecords.treatmentPlan')}</CardTitle>
            </CardHeader>
            <CardContent>
              <p>{record.treatment_plan}</p>
            </CardContent>
          </Card>
        )}

        {/* Follow Up */}
        {record.follow_up_date && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                {t('admin.medicalRecords.followUp')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-medium">
                {format(new Date(record.follow_up_date), 'EEEE، d MMMM yyyy', { locale: ar })}
              </p>
              {record.follow_up_notes && (
                <p className="text-muted-foreground mt-2">{record.follow_up_notes}</p>
              )}
            </CardContent>
          </Card>
        )}

        {/* Attachments */}
        {record.attachments && record.attachments.length > 0 && (
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Paperclip className="h-5 w-5" />
                {t('admin.medicalRecords.attachments')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-2">
                {record.attachments.map((attachment) => (
                  <div
                    key={attachment.id}
                    className="flex items-center justify-between p-3 border rounded-lg"
                  >
                    <div className="flex items-center gap-3">
                      <FileText className="h-5 w-5 text-muted-foreground" />
                      <div>
                        <p className="font-medium">{attachment.file_name}</p>
                        <p className="text-sm text-muted-foreground">
                          {(attachment.file_size / 1024).toFixed(1)} KB
                        </p>
                      </div>
                    </div>
                    <a
                      href={attachment.file_path}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <Button variant="outline" size="sm">
                        <Download className="h-4 w-4 ml-2" />
                        تحميل
                      </Button>
                    </a>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}

function VitalSign({
  icon,
  label,
  value,
  unit,
}: {
  icon: React.ReactNode;
  label: string;
  value: string | number;
  unit: string;
}) {
  return (
    <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
      <div className="text-muted-foreground">{icon}</div>
      <div>
        <p className="text-sm text-muted-foreground">{label}</p>
        <p className="font-medium">
          {value} <span className="text-sm text-muted-foreground">{unit}</span>
        </p>
      </div>
    </div>
  );
}

function MedicalRecordSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Skeleton className="h-10 w-10 rounded-full" />
        <div>
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-4 w-32 mt-2" />
        </div>
      </div>
      <div className="grid gap-6 md:grid-cols-2">
        <Skeleton className="h-48 md:col-span-2" />
        <Skeleton className="h-48" />
        <Skeleton className="h-48" />
      </div>
    </div>
  );
}
