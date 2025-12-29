'use client';

import { useParams, useRouter } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import { patientApi } from '@/lib/api/patient';
import type { Prescription, PrescriptionItem, ApiResponse } from '@/types';
import {
  ArrowRight,
  Pill,
  Calendar,
  FileText,
  Download,
  CheckCircle,
  XCircle,
} from 'lucide-react';

export default function PrescriptionDetailPage() {
  const params = useParams();
  const router = useRouter();
  const t = useTranslations();
  const prescriptionId = params.id as string;

  const { data, isLoading, error } = useQuery<ApiResponse<Prescription>>({
    queryKey: ['prescription', prescriptionId],
    queryFn: () => patientApi.getPrescription(Number(prescriptionId)),
    enabled: !!prescriptionId,
  });

  const handleDownload = () => {
    // Open PDF download in new tab
    window.open(
      `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'}/prescriptions/${prescriptionId}/download`,
      '_blank'
    );
  };

  if (isLoading) {
    return <PrescriptionSkeleton />;
  }

  if (error || !data?.data) {
    return (
      <div className="text-center py-12">
        <Pill className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-muted-foreground">لم يتم العثور على الوصفة الطبية</p>
        <Button onClick={() => router.back()} variant="outline" className="mt-4">
          {t('common.back')}
        </Button>
      </div>
    );
  }

  const prescription = data.data;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => router.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">{t('navigation.prescriptions')}</h1>
            <p className="text-muted-foreground">
              {format(new Date(prescription.created_at), 'EEEE، d MMMM yyyy', { locale: ar })}
            </p>
          </div>
        </div>
        <Badge
          variant={prescription.is_dispensed ? 'default' : 'secondary'}
          className="flex items-center gap-1"
        >
          {prescription.is_dispensed ? (
            <>
              <CheckCircle className="h-3 w-3" />
              تم الصرف
            </>
          ) : (
            <>
              <XCircle className="h-3 w-3" />
              لم يصرف
            </>
          )}
        </Badge>
      </div>

      {/* Dispensed Info */}
      {prescription.is_dispensed && prescription.dispensed_at && (
        <Card className="bg-green-50 border-green-200">
          <CardContent className="py-4">
            <div className="flex items-center gap-2 text-green-700">
              <CheckCircle className="h-5 w-5" />
              <span>
                تم صرف هذه الوصفة في{' '}
                {format(new Date(prescription.dispensed_at), 'd MMMM yyyy الساعة h:mm a', {
                  locale: ar,
                })}
              </span>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Diagnosis */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileText className="h-5 w-5" />
            {t('admin.prescriptions.diagnosis')}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p>{prescription.diagnosis}</p>
        </CardContent>
      </Card>

      {/* Medications List */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Pill className="h-5 w-5" />
            {t('admin.prescriptions.medications')}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {prescription.items && prescription.items.length > 0 ? (
            <div className="space-y-4">
              {prescription.items.map((item, index) => (
                <div key={item.id}>
                  {index > 0 && <Separator className="my-4" />}
                  <MedicationItemCard item={item} t={t} />
                </div>
              ))}
            </div>
          ) : (
            <p className="text-muted-foreground text-center py-4">لا توجد أدوية</p>
          )}
        </CardContent>
      </Card>

      {/* Notes */}
      {prescription.notes && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              {t('common.notes')}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p>{prescription.notes}</p>
          </CardContent>
        </Card>
      )}

      {/* Download Button */}
      <div className="flex justify-center">
        <Button onClick={handleDownload} className="gap-2">
          <Download className="h-4 w-4" />
          {t('admin.prescriptions.downloadPdf')}
        </Button>
      </div>
    </div>
  );
}

function MedicationItemCard({
  item,
  t,
}: {
  item: PrescriptionItem;
  t: (key: string) => string;
}) {
  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">{item.medication_name}</h3>
      <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
        <InfoItem label={t('admin.prescriptions.dosage')} value={item.dosage} />
        <InfoItem label={t('admin.prescriptions.frequency')} value={item.frequency} />
        <InfoItem label={t('admin.prescriptions.duration')} value={item.duration} />
      </div>
      {item.instructions && (
        <div className="mt-3 p-3 bg-yellow-50 rounded-lg">
          <p className="text-sm font-medium text-yellow-800">
            {t('admin.prescriptions.instructions')}:
          </p>
          <p className="text-sm text-yellow-700 mt-1">{item.instructions}</p>
        </div>
      )}
    </div>
  );
}

function InfoItem({ label, value }: { label: string; value: string }) {
  return (
    <div className="p-3 bg-gray-50 rounded-lg">
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className="font-medium">{value}</p>
    </div>
  );
}

function PrescriptionSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Skeleton className="h-10 w-10 rounded-full" />
          <div>
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-4 w-32 mt-2" />
          </div>
        </div>
        <Skeleton className="h-6 w-20" />
      </div>
      <Skeleton className="h-24" />
      <Skeleton className="h-64" />
    </div>
  );
}
