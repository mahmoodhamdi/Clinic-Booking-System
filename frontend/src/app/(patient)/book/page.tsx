'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { format } from 'date-fns';
import { CalendarIcon, Clock, CheckCircle2, ArrowRight, ArrowLeft } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { appointmentsApi } from '@/lib/api/appointments';
import { getErrorMessage } from '@/lib/api/client';
import { cn, getDateLocale } from '@/lib/utils';

type BookingStep = 'date' | 'time' | 'confirm';

export default function BookAppointmentPage() {
  const t = useTranslations();
  const locale = useLocale();
  const BackIcon = locale === 'ar' ? ArrowLeft : ArrowRight;
  const router = useRouter();
  const queryClient = useQueryClient();
  const [step, setStep] = useState<BookingStep>('date');
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedTime, setSelectedTime] = useState<string | null>(null);
  const [reason, setReason] = useState('');
  const [showSuccessDialog, setShowSuccessDialog] = useState(false);

  // Fetch available dates
  const { data: availableDates, isLoading: loadingDates } = useQuery({
    queryKey: ['availableDates'],
    queryFn: () => appointmentsApi.getAvailableDates(),
  });

  // Fetch slots for selected date
  const { data: slotsData, isLoading: loadingSlots } = useQuery({
    queryKey: ['slots', selectedDate?.toISOString()],
    queryFn: () => appointmentsApi.getSlots(format(selectedDate!, 'yyyy-MM-dd')),
    enabled: !!selectedDate,
  });

  // Book appointment mutation
  const bookMutation = useMutation({
    mutationFn: appointmentsApi.book,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['availableDates'] });
      queryClient.invalidateQueries({ queryKey: ['slots'] });
      queryClient.invalidateQueries({ queryKey: ['appointments'] });
      queryClient.invalidateQueries({ queryKey: ['upcomingAppointments'] });
      setShowSuccessDialog(true);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
    },
  });

  const handleDateSelect = (date: Date | undefined) => {
    setSelectedDate(date);
    setSelectedTime(null);
    if (date) {
      setStep('time');
    }
  };

  const handleTimeSelect = (time: string) => {
    setSelectedTime(time);
    setStep('confirm');
  };

  const handleConfirm = () => {
    if (!selectedDate || !selectedTime) return;

    bookMutation.mutate({
      date: format(selectedDate, 'yyyy-MM-dd'),
      slot_time: selectedTime,
      reason: reason || undefined,
    });
  };

  const handleBack = () => {
    if (step === 'time') {
      setStep('date');
    } else if (step === 'confirm') {
      setStep('time');
    }
  };

  const handleSuccessClose = () => {
    setShowSuccessDialog(false);
    router.push('/appointments');
  };

  // Check if a date is available
  const isDateAvailable = (date: Date) => {
    // API returns { data: { dates: [...], summary: {...} } }
    const dates = (availableDates?.data as { dates?: Array<{ date: string }> })?.dates;
    if (!dates || !Array.isArray(dates)) return false;
    const dateStr = format(date, 'yyyy-MM-dd');
    return dates.some((d) => d.date === dateStr);
  };

  return (
    <div className="max-w-2xl mx-auto animate-fade-in-up">
      {/* Progress Steps */}
      <div className="flex items-center justify-center mb-8">
        <div className="flex items-center gap-2">
          <div
            className={cn(
              'h-10 w-10 rounded-full flex items-center justify-center font-medium transition-all duration-300',
              step === 'date'
                ? 'bg-primary text-white shadow-primary'
                : 'bg-primary/20 text-primary'
            )}
          >
            1
          </div>
          <span className="text-sm font-medium hidden sm:inline">{t('patient.booking.selectDate')}</span>
        </div>
        <div className={cn('h-0.5 w-8 mx-2 transition-colors duration-300', step !== 'date' ? 'bg-primary/40' : 'bg-border')} />
        <div className="flex items-center gap-2">
          <div
            className={cn(
              'h-10 w-10 rounded-full flex items-center justify-center font-medium transition-all duration-300',
              step === 'time'
                ? 'bg-primary text-white shadow-primary'
                : step === 'confirm'
                ? 'bg-primary/20 text-primary'
                : 'bg-border text-muted-foreground/70'
            )}
          >
            2
          </div>
          <span className="text-sm font-medium hidden sm:inline">{t('patient.booking.selectTime')}</span>
        </div>
        <div className={cn('h-0.5 w-8 mx-2 transition-colors duration-300', step === 'confirm' ? 'bg-primary/40' : 'bg-border')} />
        <div className="flex items-center gap-2">
          <div
            className={cn(
              'h-10 w-10 rounded-full flex items-center justify-center font-medium transition-all duration-300',
              step === 'confirm'
                ? 'bg-primary text-white shadow-primary'
                : 'bg-border text-muted-foreground/70'
            )}
          >
            3
          </div>
          <span className="text-sm font-medium hidden sm:inline">{t('patient.booking.confirmBooking')}</span>
        </div>
      </div>

      {/* Step 1: Date Selection */}
      {step === 'date' && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <CalendarIcon className="h-5 w-5" />
              {t('patient.booking.selectDate')}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {loadingDates ? (
              <div className="flex justify-center p-8">
                <Skeleton className="h-64 w-full max-w-sm" />
              </div>
            ) : (
              <div className="flex justify-center">
                <Calendar
                  mode="single"
                  selected={selectedDate}
                  onSelect={handleDateSelect}
                  disabled={(date) => !isDateAvailable(date) || date < new Date()}
                  locale={getDateLocale(locale)}
                  className="rounded-md border"
                />
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Step 2: Time Selection */}
      {step === 'time' && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5" />
              {t('patient.booking.selectTime')}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="mb-4">
              <p className="text-muted-foreground">
                {selectedDate &&
                  format(selectedDate, 'EEEE، d MMMM yyyy', { locale: getDateLocale(locale) })}
              </p>
            </div>

            {loadingSlots ? (
              <div className="grid grid-cols-3 sm:grid-cols-4 gap-3">
                {Array.from({ length: 8 }).map((_, i) => (
                  <Skeleton key={i} className="h-12" />
                ))}
              </div>
            ) : slotsData?.data && slotsData.data.length > 0 ? (
              <div className="grid grid-cols-3 sm:grid-cols-4 gap-3">
                {slotsData.data.map((slot) => (
                  <Button
                    key={slot.time}
                    variant={selectedTime === slot.time ? 'default' : 'outline'}
                    disabled={!slot.available}
                    onClick={() => handleTimeSelect(slot.time)}
                    className="h-12 card-hover"
                  >
                    {slot.time}
                  </Button>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-muted-foreground">
                {t('patient.booking.noSlots')}
              </div>
            )}

            <div className="flex justify-start mt-6">
              <Button variant="outline" onClick={handleBack}>
                <BackIcon className="h-4 w-4 me-2" />
                {t('common.back')}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Step 3: Confirmation */}
      {step === 'confirm' && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <CheckCircle2 className="h-5 w-5" />
              {t('patient.booking.confirmBooking')}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Summary */}
            <div className="bg-muted/50 rounded-xl p-5 space-y-3 border border-border/50">
              <div className="flex items-center gap-3">
                <CalendarIcon className="h-5 w-5 text-muted-foreground/70" />
                <div>
                  <p className="text-sm text-muted-foreground">{t('patient.booking.selectDate')}</p>
                  <p className="font-medium">
                    {selectedDate &&
                      format(selectedDate, 'EEEE، d MMMM yyyy', { locale: getDateLocale(locale) })}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Clock className="h-5 w-5 text-muted-foreground/70" />
                <div>
                  <p className="text-sm text-muted-foreground">{t('patient.booking.selectTime')}</p>
                  <p className="font-medium">{selectedTime}</p>
                </div>
              </div>
            </div>

            {/* Reason */}
            <div className="space-y-2">
              <Label htmlFor="reason">{t('patient.booking.reason')}</Label>
              <Textarea
                id="reason"
                placeholder={t('patient.booking.reasonPlaceholder')}
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                rows={3}
              />
            </div>

            {/* Actions */}
            <div className="flex gap-3">
              <Button variant="outline" onClick={handleBack} className="flex-1">
                <BackIcon className="h-4 w-4 me-2" />
                {t('common.back')}
              </Button>
              <Button
                onClick={handleConfirm}
                disabled={bookMutation.isPending}
                className="flex-1"
              >
                {bookMutation.isPending ? (
                  <span className="flex items-center gap-2">
                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    {t('common.loading')}
                  </span>
                ) : (
                  t('patient.booking.confirmBooking')
                )}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Success Dialog */}
      <Dialog open={showSuccessDialog} onOpenChange={setShowSuccessDialog}>
        <DialogContent>
          <DialogHeader>
            <div className="flex justify-center mb-4">
              <div className="h-16 w-16 rounded-full bg-success/10 flex items-center justify-center">
                <CheckCircle2 className="h-8 w-8 text-success" />
              </div>
            </div>
            <DialogTitle className="text-center">
              {t('patient.booking.bookingSuccess')}
            </DialogTitle>
            <DialogDescription className="text-center">
              {selectedDate &&
                format(selectedDate, 'EEEE، d MMMM yyyy', { locale: getDateLocale(locale) })}{' '}
              - {selectedTime}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button onClick={handleSuccessClose} className="w-full">
              {t('patient.dashboard.viewAll')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
