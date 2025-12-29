# Phase 8: Missing Pages Implementation

## Overview
هذه المرحلة تركز على إنشاء الصفحات الناقصة (تفاصيل السجلات الطبية والوصفات).

**الأولوية:** متوسطة
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 6

---

## Pre-requisites Checklist
- [ ] Phase 6 completed
- [ ] Backend running: `composer dev`
- [ ] Frontend running: `cd frontend && npm run dev`

---

## Milestone 8.1: Medical Records Detail Page

### المشكلة
صفحة تفاصيل السجل الطبي غير موجودة.

### الملف المطلوب
`frontend/src/app/(patient)/medical-records/[id]/page.tsx`

### المهام

#### Task 8.1.1: Create Medical Record Detail Page
```tsx
"use client";

import { useParams, useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import { format } from "date-fns";
import { ar } from "date-fns/locale";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import { patientApi } from "@/lib/api/patient";
import type { MedicalRecord } from "@/types";
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
} from "lucide-react";

export default function MedicalRecordDetailPage() {
  const params = useParams();
  const router = useRouter();
  const recordId = params.id as string;

  const { data: record, isLoading, error } = useQuery<{ data: MedicalRecord }>({
    queryKey: ["medical-record", recordId],
    queryFn: () => patientApi.getMedicalRecord(recordId),
  });

  if (isLoading) {
    return <MedicalRecordSkeleton />;
  }

  if (error || !record?.data) {
    return (
      <div className="text-center py-12">
        <p className="text-muted-foreground">لم يتم العثور على السجل الطبي</p>
        <Button onClick={() => router.back()} variant="outline" className="mt-4">
          العودة
        </Button>
      </div>
    );
  }

  const medicalRecord = record.data;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()}>
          <ArrowRight className="h-5 w-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">السجل الطبي</h1>
          <p className="text-muted-foreground">
            {format(new Date(medicalRecord.created_at), "EEEE، d MMMM yyyy", { locale: ar })}
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
              التشخيص
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg">{medicalRecord.diagnosis}</p>
            {medicalRecord.symptoms && (
              <div className="mt-4">
                <h4 className="font-medium text-muted-foreground mb-2">الأعراض</h4>
                <p>{medicalRecord.symptoms}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Vital Signs */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Activity className="h-5 w-5" />
              العلامات الحيوية
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4">
              {medicalRecord.blood_pressure && (
                <VitalSign
                  icon={<Heart className="h-4 w-4" />}
                  label="ضغط الدم"
                  value={medicalRecord.blood_pressure}
                  unit="mmHg"
                />
              )}
              {medicalRecord.heart_rate && (
                <VitalSign
                  icon={<Activity className="h-4 w-4" />}
                  label="معدل النبض"
                  value={medicalRecord.heart_rate}
                  unit="نبضة/دقيقة"
                />
              )}
              {medicalRecord.temperature && (
                <VitalSign
                  icon={<Thermometer className="h-4 w-4" />}
                  label="الحرارة"
                  value={medicalRecord.temperature}
                  unit="°C"
                />
              )}
              {medicalRecord.weight && (
                <VitalSign
                  icon={<Scale className="h-4 w-4" />}
                  label="الوزن"
                  value={medicalRecord.weight}
                  unit="كجم"
                />
              )}
              {medicalRecord.height && (
                <VitalSign
                  icon={<Ruler className="h-4 w-4" />}
                  label="الطول"
                  value={medicalRecord.height}
                  unit="سم"
                />
              )}
            </div>
          </CardContent>
        </Card>

        {/* Examination Notes */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              ملاحظات الفحص
            </CardTitle>
          </CardHeader>
          <CardContent>
            {medicalRecord.examination_notes ? (
              <p>{medicalRecord.examination_notes}</p>
            ) : (
              <p className="text-muted-foreground">لا توجد ملاحظات</p>
            )}
          </CardContent>
        </Card>

        {/* Treatment Plan */}
        {medicalRecord.treatment_plan && (
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle>خطة العلاج</CardTitle>
            </CardHeader>
            <CardContent>
              <p>{medicalRecord.treatment_plan}</p>
            </CardContent>
          </Card>
        )}

        {/* Follow Up */}
        {medicalRecord.follow_up_date && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                موعد المتابعة
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-lg font-medium">
                {format(new Date(medicalRecord.follow_up_date), "EEEE، d MMMM yyyy", { locale: ar })}
              </p>
              {medicalRecord.follow_up_notes && (
                <p className="text-muted-foreground mt-2">{medicalRecord.follow_up_notes}</p>
              )}
            </CardContent>
          </Card>
        )}

        {/* Prescriptions */}
        {medicalRecord.prescriptions && medicalRecord.prescriptions.length > 0 && (
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Pill className="h-5 w-5" />
                الوصفات الطبية
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {medicalRecord.prescriptions.map((prescription) => (
                  <div key={prescription.id} className="border rounded-lg p-4">
                    <div className="flex items-center justify-between mb-3">
                      <span className="font-medium">وصفة #{prescription.prescription_number}</span>
                      <Badge variant={prescription.is_dispensed ? "default" : "secondary"}>
                        {prescription.is_dispensed ? "تم الصرف" : "لم يصرف"}
                      </Badge>
                    </div>
                    {prescription.items && (
                      <div className="space-y-2">
                        {prescription.items.map((item) => (
                          <div key={item.id} className="text-sm">
                            <span className="font-medium">{item.medication_name}</span>
                            <span className="text-muted-foreground">
                              {" "}- {item.dosage} - {item.frequency} - {item.duration}
                            </span>
                          </div>
                        ))}
                      </div>
                    )}
                    <Link href={`/prescriptions/${prescription.id}`}>
                      <Button variant="outline" size="sm" className="mt-3">
                        عرض التفاصيل
                      </Button>
                    </Link>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Attachments */}
        {medicalRecord.attachments && medicalRecord.attachments.length > 0 && (
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Paperclip className="h-5 w-5" />
                المرفقات
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-2">
                {medicalRecord.attachments.map((attachment) => (
                  <div key={attachment.id} className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center gap-3">
                      <FileText className="h-5 w-5 text-muted-foreground" />
                      <div>
                        <p className="font-medium">{attachment.file_name}</p>
                        <p className="text-sm text-muted-foreground">
                          {(attachment.file_size / 1024).toFixed(1)} KB
                        </p>
                      </div>
                    </div>
                    <a href={attachment.download_url} target="_blank" rel="noopener noreferrer">
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
```

### Verification
```bash
cd frontend && npm run dev
# Navigate to /medical-records/1
```

---

## Milestone 8.2: Prescription Detail Page

### المشكلة
صفحة تفاصيل الوصفة الطبية غير موجودة.

### الملف المطلوب
`frontend/src/app/(patient)/prescriptions/[id]/page.tsx`

### المهام

#### Task 8.2.1: Create Prescription Detail Page
```tsx
"use client";

import { useParams, useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { format } from "date-fns";
import { ar } from "date-fns/locale";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Separator } from "@/components/ui/separator";
import { patientApi } from "@/lib/api/patient";
import type { Prescription } from "@/types";
import {
  ArrowRight,
  Pill,
  Calendar,
  Clock,
  FileText,
  Download,
  CheckCircle,
  XCircle,
} from "lucide-react";

export default function PrescriptionDetailPage() {
  const params = useParams();
  const router = useRouter();
  const prescriptionId = params.id as string;

  const { data: prescription, isLoading, error } = useQuery<{ data: Prescription }>({
    queryKey: ["prescription", prescriptionId],
    queryFn: () => patientApi.getPrescription(prescriptionId),
  });

  if (isLoading) {
    return <PrescriptionSkeleton />;
  }

  if (error || !prescription?.data) {
    return (
      <div className="text-center py-12">
        <p className="text-muted-foreground">لم يتم العثور على الوصفة الطبية</p>
        <Button onClick={() => router.back()} variant="outline" className="mt-4">
          العودة
        </Button>
      </div>
    );
  }

  const rx = prescription.data;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={() => router.back()}>
            <ArrowRight className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">وصفة #{rx.prescription_number}</h1>
            <p className="text-muted-foreground">
              {format(new Date(rx.created_at), "EEEE، d MMMM yyyy", { locale: ar })}
            </p>
          </div>
        </div>
        <Badge
          variant={rx.is_dispensed ? "default" : "secondary"}
          className="flex items-center gap-1"
        >
          {rx.is_dispensed ? (
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
      {rx.is_dispensed && rx.dispensed_at && (
        <Card className="bg-green-50 border-green-200">
          <CardContent className="py-4">
            <div className="flex items-center gap-2 text-green-700">
              <CheckCircle className="h-5 w-5" />
              <span>
                تم صرف هذه الوصفة في{" "}
                {format(new Date(rx.dispensed_at), "d MMMM yyyy الساعة h:mm a", { locale: ar })}
              </span>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Medications List */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Pill className="h-5 w-5" />
            الأدوية
          </CardTitle>
        </CardHeader>
        <CardContent>
          {rx.items && rx.items.length > 0 ? (
            <div className="space-y-4">
              {rx.items.map((item, index) => (
                <div key={item.id}>
                  {index > 0 && <Separator className="my-4" />}
                  <MedicationItem item={item} />
                </div>
              ))}
            </div>
          ) : (
            <p className="text-muted-foreground text-center py-4">لا توجد أدوية</p>
          )}
        </CardContent>
      </Card>

      {/* Notes */}
      {rx.notes && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              ملاحظات الطبيب
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p>{rx.notes}</p>
          </CardContent>
        </Card>
      )}

      {/* Download Button */}
      <div className="flex justify-center">
        <Button
          onClick={() => window.open(`/api/prescriptions/${rx.id}/download`, '_blank')}
          className="gap-2"
        >
          <Download className="h-4 w-4" />
          تحميل الوصفة PDF
        </Button>
      </div>
    </div>
  );
}

function MedicationItem({ item }: { item: Prescription['items'][0] }) {
  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">{item.medication_name}</h3>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <InfoItem label="الجرعة" value={item.dosage} />
        <InfoItem label="التكرار" value={item.frequency} />
        <InfoItem label="المدة" value={item.duration} />
      </div>
      {item.instructions && (
        <div className="mt-3 p-3 bg-yellow-50 rounded-lg">
          <p className="text-sm font-medium text-yellow-800">تعليمات:</p>
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
      <div className="flex items-center gap-4">
        <Skeleton className="h-10 w-10 rounded-full" />
        <div>
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-4 w-32 mt-2" />
        </div>
      </div>
      <Skeleton className="h-32" />
      <Skeleton className="h-64" />
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run dev
# Navigate to /prescriptions/1
```

---

## Milestone 8.3: Add API Methods for Detail Pages

### المشكلة
الـ API methods للـ detail pages غير موجودة.

### الملف المتأثر
`frontend/src/lib/api/patient.ts`

### المهام

#### Task 8.3.1: Add getMedicalRecord and getPrescription
```typescript
// Add to patient.ts

export const patientApi = {
  // ... existing methods

  getMedicalRecord: async (id: string) => {
    const response = await api.get<ApiResponse<MedicalRecord>>(`/medical-records/${id}`);
    return response.data;
  },

  getPrescription: async (id: string) => {
    const response = await api.get<ApiResponse<Prescription>>(`/prescriptions/${id}`);
    return response.data;
  },

  downloadPrescription: async (id: string) => {
    const response = await api.get(`/prescriptions/${id}/download`, {
      responseType: 'blob',
    });
    return response.data;
  },
};
```

### Verification
```bash
cd frontend && npx tsc --noEmit
```

---

## Milestone 8.4: Update Navigation Links

### المشكلة
روابط التفاصيل في صفحات القوائم غير صحيحة.

### الملفات المتأثرة
1. `frontend/src/app/(patient)/medical-records/page.tsx`
2. `frontend/src/app/(patient)/prescriptions/page.tsx`

### المهام

#### Task 8.4.1: Update Medical Records Page Links
```tsx
// In medical-records/page.tsx
// Change the "View" link to:

<Link href={`/medical-records/${record.id}`}>
  <Button variant="outline" size="sm">
    عرض التفاصيل
  </Button>
</Link>
```

#### Task 8.4.2: Update Prescriptions Page Links
```tsx
// In prescriptions/page.tsx
// Change the "View" link to:

<Link href={`/prescriptions/${prescription.id}`}>
  <Button variant="outline" size="sm">
    عرض التفاصيل
  </Button>
</Link>
```

### Verification
```bash
cd frontend && npm run dev
# Click on "View Details" links in both pages
```

---

## Post-Phase Checklist

### Tests
- [ ] Frontend tests pass: `cd frontend && npm test`
- [ ] Build succeeds: `cd frontend && npm run build`

### Functionality
- [ ] Medical record detail page shows all info
- [ ] Prescription detail page shows all medications
- [ ] PDF download works
- [ ] Navigation from list pages works
- [ ] Back button works

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
cd frontend && npm test && npm run build && cd .. && git add -A && git commit -m "feat(pages): implement Phase 8 - Missing Pages Implementation

- Add Medical Records detail page with vital signs and attachments
- Add Prescription detail page with medications list
- Add API methods for fetching details
- Update navigation links in list pages
- Add PDF download functionality

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
