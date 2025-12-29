# Phase 6: Frontend API Integration Fixes

## Overview
هذه المرحلة تركز على استبدال البيانات الوهمية بالـ API الحقيقي وربط الـ forms.

**الأولوية:** عالية
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 5

---

## Pre-requisites Checklist
- [ ] Phase 5 completed
- [ ] Backend running: `composer dev`
- [ ] Frontend running: `cd frontend && npm run dev`

---

## Milestone 6.1: Replace Mock Data in Admin Dashboard

### المشكلة
الـ Admin Dashboard يستخدم بيانات وهمية بدلاً من الـ API.

### الملف المتأثر
`frontend/src/app/(admin)/admin/dashboard/page.tsx`

### المهام

#### Task 6.1.1: Replace Mock Stats with API Call
ابحث عن البيانات الوهمية (around lines 23-77) واستبدلها:

```tsx
"use client";

import { useQuery } from "@tanstack/react-query";
import { adminApi } from "@/lib/api/admin";
import type { DashboardStats, DashboardAppointment, RecentActivity } from "@/types";

export default function AdminDashboard() {
  const { data: stats, isLoading: statsLoading } = useQuery<{ data: DashboardStats }>({
    queryKey: ["admin-dashboard-stats"],
    queryFn: () => adminApi.getDashboardStats(),
    refetchInterval: 60000, // Refresh every minute
  });

  const { data: todayAppointments, isLoading: todayLoading } = useQuery<{ data: DashboardAppointment[] }>({
    queryKey: ["admin-today-appointments"],
    queryFn: () => adminApi.getTodayAppointments(),
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  const { data: recentActivity, isLoading: activityLoading } = useQuery<{ data: RecentActivity[] }>({
    queryKey: ["admin-recent-activity"],
    queryFn: () => adminApi.getRecentActivity(),
    refetchInterval: 30000,
  });

  const isLoading = statsLoading || todayLoading || activityLoading;

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="إجمالي المرضى"
          value={stats?.data.total_patients ?? 0}
          icon={<Users className="h-5 w-5" />}
          color="blue"
        />
        <StatCard
          title="مواعيد اليوم"
          value={stats?.data.today_appointments ?? 0}
          icon={<Calendar className="h-5 w-5" />}
          color="green"
        />
        <StatCard
          title="المواعيد القادمة"
          value={stats?.data.upcoming_appointments ?? 0}
          icon={<Clock className="h-5 w-5" />}
          color="yellow"
        />
        <StatCard
          title="إيرادات الشهر"
          value={`${stats?.data.monthly_revenue ?? 0} ج.م`}
          icon={<DollarSign className="h-5 w-5" />}
          color="purple"
        />
      </div>

      {/* Today's Appointments */}
      <Card>
        <CardHeader>
          <CardTitle>مواعيد اليوم</CardTitle>
        </CardHeader>
        <CardContent>
          {todayAppointments?.data.length === 0 ? (
            <EmptyState message="لا توجد مواعيد اليوم" />
          ) : (
            <div className="space-y-4">
              {todayAppointments?.data.map((appointment) => (
                <AppointmentRow key={appointment.id} appointment={appointment} />
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Recent Activity */}
      <Card>
        <CardHeader>
          <CardTitle>النشاط الأخير</CardTitle>
        </CardHeader>
        <CardContent>
          {recentActivity?.data.length === 0 ? (
            <EmptyState message="لا يوجد نشاط حديث" />
          ) : (
            <div className="space-y-4">
              {recentActivity?.data.map((activity) => (
                <ActivityRow key={`${activity.type}-${activity.id}`} activity={activity} />
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

// Helper components
function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} className="h-32" />
        ))}
      </div>
      <Skeleton className="h-64" />
      <Skeleton className="h-64" />
    </div>
  );
}

function StatCard({ title, value, icon, color }: {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  color: 'blue' | 'green' | 'yellow' | 'purple';
}) {
  const colors = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    purple: 'bg-purple-100 text-purple-600',
  };

  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-muted-foreground">{title}</p>
            <p className="text-2xl font-bold mt-1">{value}</p>
          </div>
          <div className={`p-3 rounded-full ${colors[color]}`}>
            {icon}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

function AppointmentRow({ appointment }: { appointment: DashboardAppointment }) {
  const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    no_show: 'bg-gray-100 text-gray-800',
  };

  return (
    <div className="flex items-center justify-between p-4 border rounded-lg">
      <div className="flex items-center gap-3">
        <Avatar patient_avatar={appointment.patient_avatar} name={appointment.patient_name} />
        <div>
          <p className="font-medium">{appointment.patient_name}</p>
          <p className="text-sm text-muted-foreground">{appointment.time}</p>
        </div>
      </div>
      <Badge className={statusColors[appointment.status]}>
        {appointment.status === 'pending' && 'معلق'}
        {appointment.status === 'confirmed' && 'مؤكد'}
        {appointment.status === 'completed' && 'مكتمل'}
        {appointment.status === 'cancelled' && 'ملغى'}
        {appointment.status === 'no_show' && 'لم يحضر'}
      </Badge>
    </div>
  );
}

function ActivityRow({ activity }: { activity: RecentActivity }) {
  const typeIcons = {
    appointment: <Calendar className="h-4 w-4" />,
    payment: <DollarSign className="h-4 w-4" />,
    medical_record: <FileText className="h-4 w-4" />,
  };

  return (
    <div className="flex items-center gap-3 p-3 border rounded-lg">
      <div className="p-2 bg-gray-100 rounded-full">
        {typeIcons[activity.type]}
      </div>
      <div className="flex-1">
        <p className="text-sm">{activity.description}</p>
        <p className="text-xs text-muted-foreground">
          {new Date(activity.created_at).toLocaleString('ar-EG')}
        </p>
      </div>
    </div>
  );
}

function EmptyState({ message }: { message: string }) {
  return (
    <div className="text-center py-8 text-muted-foreground">
      {message}
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run dev
# Check admin dashboard loads real data
```

---

## Milestone 6.2: Replace Mock Data in Patient Dashboard

### المشكلة
الـ Patient Dashboard يستخدم بيانات وهمية.

### الملف المتأثر
`frontend/src/app/(patient)/dashboard/page.tsx`

### المهام

#### Task 6.2.1: Replace Mock Appointments with API
استبدل البيانات الوهمية:

```tsx
"use client";

import { useQuery } from "@tanstack/react-query";
import { appointmentsApi } from "@/lib/api/appointments";
import { patientApi } from "@/lib/api/patient";
import type { Appointment, PatientStatistics } from "@/types";

export default function PatientDashboard() {
  const { data: upcomingAppointments, isLoading: appointmentsLoading } = useQuery<{ data: Appointment[] }>({
    queryKey: ["upcoming-appointments"],
    queryFn: () => appointmentsApi.getUpcoming(),
  });

  const { data: statistics, isLoading: statsLoading } = useQuery<{ data: PatientStatistics }>({
    queryKey: ["patient-statistics"],
    queryFn: () => patientApi.getStatistics(),
  });

  const { data: profile } = useQuery({
    queryKey: ["patient-profile"],
    queryFn: () => patientApi.getProfile(),
  });

  const isLoading = appointmentsLoading || statsLoading;

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <Card>
        <CardContent className="p-6">
          <h1 className="text-2xl font-bold">
            مرحباً {profile?.data?.user?.name ?? 'بك'}
          </h1>
          <p className="text-muted-foreground mt-1">
            نتمنى لك يوماً سعيداً
          </p>
        </CardContent>
      </Card>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard
          label="إجمالي الزيارات"
          value={statistics?.data.total_appointments ?? 0}
        />
        <StatCard
          label="زيارات مكتملة"
          value={statistics?.data.completed_appointments ?? 0}
        />
        <StatCard
          label="مواعيد قادمة"
          value={statistics?.data.upcoming_appointments ?? 0}
        />
        <StatCard
          label="آخر زيارة"
          value={statistics?.data.last_visit
            ? new Date(statistics.data.last_visit).toLocaleDateString('ar-EG')
            : 'لم تتم'
          }
        />
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <QuickActionCard
          href="/book"
          icon={<Calendar className="h-6 w-6" />}
          label="حجز موعد"
        />
        <QuickActionCard
          href="/appointments"
          icon={<Clock className="h-6 w-6" />}
          label="مواعيدي"
        />
        <QuickActionCard
          href="/medical-records"
          icon={<FileText className="h-6 w-6" />}
          label="السجلات الطبية"
        />
        <QuickActionCard
          href="/profile"
          icon={<User className="h-6 w-6" />}
          label="ملفي الشخصي"
        />
      </div>

      {/* Upcoming Appointments */}
      <Card>
        <CardHeader>
          <CardTitle>المواعيد القادمة</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-4">
              {[1, 2].map((i) => (
                <Skeleton key={i} className="h-24" />
              ))}
            </div>
          ) : upcomingAppointments?.data.length === 0 ? (
            <div className="text-center py-8">
              <Calendar className="h-12 w-12 mx-auto text-gray-400" />
              <p className="mt-4 text-muted-foreground">لا توجد مواعيد قادمة</p>
              <Link href="/book">
                <Button className="mt-4">حجز موعد جديد</Button>
              </Link>
            </div>
          ) : (
            <div className="space-y-4">
              {upcomingAppointments?.data.map((appointment) => (
                <AppointmentCard key={appointment.id} appointment={appointment} />
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function StatCard({ label, value }: { label: string; value: string | number }) {
  return (
    <Card>
      <CardContent className="p-4 text-center">
        <p className="text-2xl font-bold">{value}</p>
        <p className="text-sm text-muted-foreground">{label}</p>
      </CardContent>
    </Card>
  );
}

function QuickActionCard({ href, icon, label }: { href: string; icon: React.ReactNode; label: string }) {
  return (
    <Link href={href}>
      <Card className="hover:bg-gray-50 transition-colors cursor-pointer">
        <CardContent className="p-4 text-center">
          <div className="flex justify-center mb-2">{icon}</div>
          <p className="text-sm font-medium">{label}</p>
        </CardContent>
      </Card>
    </Link>
  );
}

function AppointmentCard({ appointment }: { appointment: Appointment }) {
  return (
    <div className="flex items-center justify-between p-4 border rounded-lg">
      <div>
        <p className="font-medium">
          {new Date(appointment.appointment_date).toLocaleDateString('ar-EG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
          })}
        </p>
        <p className="text-sm text-muted-foreground">
          الساعة {appointment.appointment_time}
        </p>
      </div>
      <Badge variant={appointment.status === 'confirmed' ? 'default' : 'secondary'}>
        {appointment.status_label}
      </Badge>
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run dev
# Check patient dashboard loads real data
```

---

## Milestone 6.3: Connect Medical Info Form to API

### المشكلة
الـ Medical Info form في صفحة الـ Profile غير متصل بالـ API.

### الملف المتأثر
`frontend/src/app/(patient)/profile/page.tsx`

### المهام

#### Task 6.3.1: Add Medical Info Mutation
في الصفحة، أضف mutation لحفظ البيانات الطبية:

```tsx
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { patientApi } from "@/lib/api/patient";
import type { MedicalInfoFormData } from "@/types";

// Inside the component
const queryClient = useQueryClient();

const { data: profile, isLoading } = useQuery({
  queryKey: ["patient-profile"],
  queryFn: () => patientApi.getProfile(),
});

const updateMedicalInfo = useMutation({
  mutationFn: (data: MedicalInfoFormData) => patientApi.updateProfile(data),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["patient-profile"] });
    toast.success("تم حفظ المعلومات الطبية بنجاح");
  },
  onError: () => {
    toast.error("حدث خطأ أثناء حفظ المعلومات");
  },
});

// Medical Info Form
const medicalInfoForm = useForm<MedicalInfoFormData>({
  defaultValues: {
    blood_type: profile?.data?.profile?.blood_type ?? undefined,
    allergies: profile?.data?.profile?.allergies ?? "",
    chronic_diseases: profile?.data?.profile?.chronic_diseases ?? "",
    current_medications: profile?.data?.profile?.current_medications ?? "",
    emergency_contact_name: profile?.data?.profile?.emergency_contact_name ?? "",
    emergency_contact_phone: profile?.data?.profile?.emergency_contact_phone ?? "",
  },
});

// Update default values when profile loads
useEffect(() => {
  if (profile?.data?.profile) {
    medicalInfoForm.reset({
      blood_type: profile.data.profile.blood_type ?? undefined,
      allergies: profile.data.profile.allergies ?? "",
      chronic_diseases: profile.data.profile.chronic_diseases ?? "",
      current_medications: profile.data.profile.current_medications ?? "",
      emergency_contact_name: profile.data.profile.emergency_contact_name ?? "",
      emergency_contact_phone: profile.data.profile.emergency_contact_phone ?? "",
    });
  }
}, [profile]);

const onMedicalInfoSubmit = (data: MedicalInfoFormData) => {
  updateMedicalInfo.mutate(data);
};
```

#### Task 6.3.2: Update Medical Info Tab JSX
```tsx
<TabsContent value="medical">
  <form onSubmit={medicalInfoForm.handleSubmit(onMedicalInfoSubmit)} className="space-y-4">
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div className="space-y-2">
        <Label htmlFor="blood_type">فصيلة الدم</Label>
        <Select
          value={medicalInfoForm.watch("blood_type") ?? ""}
          onValueChange={(value) => medicalInfoForm.setValue("blood_type", value as BloodType)}
        >
          <SelectTrigger>
            <SelectValue placeholder="اختر فصيلة الدم" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="A+">A+</SelectItem>
            <SelectItem value="A-">A-</SelectItem>
            <SelectItem value="B+">B+</SelectItem>
            <SelectItem value="B-">B-</SelectItem>
            <SelectItem value="AB+">AB+</SelectItem>
            <SelectItem value="AB-">AB-</SelectItem>
            <SelectItem value="O+">O+</SelectItem>
            <SelectItem value="O-">O-</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div className="space-y-2">
      <Label htmlFor="allergies">الحساسية</Label>
      <Textarea
        id="allergies"
        placeholder="اذكر أي حساسية لديك..."
        {...medicalInfoForm.register("allergies")}
      />
    </div>

    <div className="space-y-2">
      <Label htmlFor="chronic_diseases">الأمراض المزمنة</Label>
      <Textarea
        id="chronic_diseases"
        placeholder="اذكر أي أمراض مزمنة..."
        {...medicalInfoForm.register("chronic_diseases")}
      />
    </div>

    <div className="space-y-2">
      <Label htmlFor="current_medications">الأدوية الحالية</Label>
      <Textarea
        id="current_medications"
        placeholder="اذكر الأدوية التي تتناولها حالياً..."
        {...medicalInfoForm.register("current_medications")}
      />
    </div>

    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div className="space-y-2">
        <Label htmlFor="emergency_contact_name">اسم جهة الاتصال للطوارئ</Label>
        <Input
          id="emergency_contact_name"
          {...medicalInfoForm.register("emergency_contact_name")}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="emergency_contact_phone">رقم هاتف الطوارئ</Label>
        <Input
          id="emergency_contact_phone"
          {...medicalInfoForm.register("emergency_contact_phone")}
        />
      </div>
    </div>

    <Button type="submit" disabled={updateMedicalInfo.isPending}>
      {updateMedicalInfo.isPending ? "جاري الحفظ..." : "حفظ المعلومات الطبية"}
    </Button>
  </form>
</TabsContent>
```

### Verification
```bash
cd frontend && npm run dev
# Test saving medical info in profile page
```

---

## Milestone 6.4: Implement Dynamic Notification Badge

### المشكلة
الـ Notification badge في الـ PatientLayout قيمته ثابتة "3".

### الملف المتأثر
`frontend/src/components/layouts/PatientLayout.tsx`

### المهام

#### Task 6.4.1: Add Notification Count Query
```tsx
import { useQuery } from "@tanstack/react-query";
import { patientApi } from "@/lib/api/patient";

// Inside PatientLayout component
const { data: unreadCount } = useQuery({
  queryKey: ["unread-notifications-count"],
  queryFn: () => patientApi.getUnreadCount(),
  refetchInterval: 30000, // Refresh every 30 seconds
});

const notificationCount = unreadCount?.data?.count ?? 0;
```

#### Task 6.4.2: Update Badge Rendering
```tsx
{/* Notifications Link with Dynamic Badge */}
<Link href="/notifications" className="relative">
  <Bell className="h-5 w-5" />
  {notificationCount > 0 && (
    <Badge
      variant="destructive"
      className="absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center p-0 text-xs"
    >
      {notificationCount > 99 ? "99+" : notificationCount}
    </Badge>
  )}
</Link>
```

#### Task 6.4.3: Invalidate Count on Read
في صفحة الـ Notifications، تأكد من invalidate الـ count عند القراءة:

```tsx
// In notifications page
const queryClient = useQueryClient();

const markAsRead = useMutation({
  mutationFn: (id: string) => patientApi.markNotificationAsRead(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
    queryClient.invalidateQueries({ queryKey: ["unread-notifications-count"] });
  },
});

const markAllAsRead = useMutation({
  mutationFn: () => patientApi.markAllNotificationsAsRead(),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
    queryClient.invalidateQueries({ queryKey: ["unread-notifications-count"] });
  },
});
```

### Verification
```bash
cd frontend && npm run dev
# Check notification badge updates dynamically
```

---

## Post-Phase Checklist

### Tests
- [ ] All tests pass: `cd frontend && npm test`
- [ ] Build succeeds: `cd frontend && npm run build`

### Functionality
- [ ] Admin dashboard loads real stats
- [ ] Patient dashboard loads real data
- [ ] Medical info form saves successfully
- [ ] Notification badge updates dynamically

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
cd frontend && npm test && npm run build && cd .. && git add -A && git commit -m "feat(api): implement Phase 6 - Frontend API Integration Fixes

- Replace mock data in admin dashboard with real API
- Replace mock data in patient dashboard with real API
- Connect medical info form to API
- Implement dynamic notification badge

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
