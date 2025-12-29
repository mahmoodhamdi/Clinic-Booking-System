'use client';

import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { FileText, Calendar, Stethoscope, Eye } from 'lucide-react';
import Link from 'next/link';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api/client';
import type { MedicalRecord, ApiResponse } from '@/types';

export default function MedicalRecordsPage() {
  const t = useTranslations();

  // Fetch medical records
  const { data: records, isLoading } = useQuery<ApiResponse<MedicalRecord[]>>({
    queryKey: ['myMedicalRecords'],
    queryFn: async () => {
      const response = await api.get('/medical-records');
      return response.data;
    },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('navigation.medicalRecords')}</h1>
      </div>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
      ) : records?.data && records.data.length > 0 ? (
        <div className="space-y-4">
          {records.data.map((record) => (
            <Card key={record.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                      <FileText className="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <Calendar className="h-4 w-4" />
                        <span>
                          {format(new Date(record.created_at), 'PPP', { locale: ar })}
                        </span>
                      </div>
                      <div className="flex items-center gap-2 mb-2">
                        <Stethoscope className="h-4 w-4 text-gray-400" />
                        <p className="font-medium">{record.diagnosis}</p>
                      </div>
                      {record.notes && (
                        <p className="text-sm text-gray-500 line-clamp-2">
                          {record.notes}
                        </p>
                      )}
                    </div>
                  </div>
                  <Button variant="ghost" size="sm" asChild>
                    <Link href={`/medical-records/${record.id}`}>
                      <Eye className="h-4 w-4 me-1" />
                      {t('common.view')}
                    </Link>
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
    </div>
  );
}
