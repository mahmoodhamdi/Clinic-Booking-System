'use client';

import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { Pill, Calendar, Download, Eye, CheckCircle2, Clock } from 'lucide-react';
import Link from 'next/link';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api/client';
import type { Prescription, ApiResponse } from '@/types';

export default function PrescriptionsPage() {
  const t = useTranslations();

  // Fetch prescriptions
  const { data: prescriptions, isLoading } = useQuery<ApiResponse<Prescription[]>>({
    queryKey: ['myPrescriptions'],
    queryFn: async () => {
      const response = await api.get('/prescriptions');
      return response.data;
    },
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('navigation.prescriptions')}</h1>
      </div>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
      ) : prescriptions?.data && prescriptions.data.length > 0 ? (
        <div className="space-y-4">
          {prescriptions.data.map((prescription) => (
            <Card key={prescription.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    <div className="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                      <Pill className="h-6 w-6 text-purple-600" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <Calendar className="h-4 w-4" />
                        <span>
                          {format(new Date(prescription.created_at), 'PPP', {
                            locale: ar,
                          })}
                        </span>
                      </div>
                      <p className="font-medium mb-1">{prescription.diagnosis}</p>
                      <p className="text-sm text-gray-500">
                        {prescription.items?.length || 0} {t('admin.prescriptions.medications')}
                      </p>
                    </div>
                  </div>
                  <div className="flex flex-col items-end gap-2">
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
                    <div className="flex gap-2">
                      <Button variant="ghost" size="sm" asChild>
                        <Link href={`/prescriptions/${prescription.id}`}>
                          <Eye className="h-4 w-4 me-1" />
                          {t('common.view')}
                        </Link>
                      </Button>
                    </div>
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
    </div>
  );
}
