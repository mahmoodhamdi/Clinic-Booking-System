import { http, HttpResponse } from 'msw';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:9000/api';

// Mock data
const mockUser = {
  id: 1,
  name: 'Test User',
  phone: '01234567890',
  email: 'test@example.com',
  role: 'patient' as const,
  avatar: null,
  is_active: true,
  date_of_birth: null,
  gender: null,
  address: null,
  phone_verified_at: null,
  created_at: '2025-01-01T00:00:00.000Z',
};

const mockAdmin = {
  ...mockUser,
  id: 2,
  name: 'Admin User',
  phone: '01000000000',
  role: 'admin' as const,
};

const mockAppointment = {
  id: 1,
  user_id: 1,
  appointment_date: '2025-01-15',
  appointment_time: '10:00:00',
  status: 'pending' as const,
  notes: null,
  admin_notes: null,
  cancellation_reason: null,
  cancelled_at: null,
  cancelled_by: null,
  confirmed_at: null,
  completed_at: null,
  created_at: '2025-01-01T00:00:00.000Z',
  updated_at: '2025-01-01T00:00:00.000Z',
  patient: mockUser,
};

const mockDashboardStats = {
  total_patients: 100,
  total_appointments: 250,
  today_appointments: 15,
  pending_appointments: 5,
  completed_appointments: 200,
  total_revenue: 50000,
  today_revenue: 5000,
};

const mockSlots = [
  { time: '09:00', end_time: '09:30', available: true, remaining: 1 },
  { time: '09:30', end_time: '10:00', available: true, remaining: 1 },
  { time: '10:00', end_time: '10:30', available: false, remaining: 0 },
  { time: '10:30', end_time: '11:00', available: true, remaining: 1 },
];

const mockAvailableDates = [
  { date: '2025-01-15', day_name: 'الأربعاء', day_name_en: 'Wednesday', slots_count: 10 },
  { date: '2025-01-16', day_name: 'الخميس', day_name_en: 'Thursday', slots_count: 8 },
  { date: '2025-01-17', day_name: 'الجمعة', day_name_en: 'Friday', slots_count: 12 },
];

export const handlers = [
  // Auth handlers
  http.post(`${API_URL}/auth/login`, async ({ request }) => {
    const body = (await request.json()) as { phone: string; password: string };

    if (body.phone === '01234567890' && body.password === 'password') {
      return HttpResponse.json({
        success: true,
        message: 'تم تسجيل الدخول بنجاح',
        data: {
          user: mockUser,
          token: 'mock-token-12345',
        },
      });
    }

    if (body.phone === '01000000000' && body.password === 'admin123') {
      return HttpResponse.json({
        success: true,
        message: 'تم تسجيل الدخول بنجاح',
        data: {
          user: mockAdmin,
          token: 'mock-admin-token-12345',
        },
      });
    }

    return HttpResponse.json(
      { success: false, message: 'بيانات الدخول غير صحيحة' },
      { status: 401 }
    );
  }),

  http.post(`${API_URL}/auth/register`, async ({ request }) => {
    const body = (await request.json()) as { name: string; phone: string; password: string };

    return HttpResponse.json(
      {
        success: true,
        message: 'تم التسجيل بنجاح',
        data: {
          user: { ...mockUser, name: body.name, phone: body.phone },
          token: 'mock-token-new-user',
        },
      },
      { status: 201 }
    );
  }),

  http.post(`${API_URL}/auth/logout`, () => {
    return HttpResponse.json({
      success: true,
      message: 'تم تسجيل الخروج بنجاح',
    });
  }),

  http.get(`${API_URL}/auth/me`, () => {
    return HttpResponse.json({
      success: true,
      data: mockUser,
    });
  }),

  // Appointments handlers
  http.get(`${API_URL}/appointments`, () => {
    return HttpResponse.json({
      success: true,
      data: [mockAppointment],
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 15,
        to: 1,
        total: 1,
      },
    });
  }),

  http.get(`${API_URL}/appointments/:id`, ({ params }) => {
    return HttpResponse.json({
      success: true,
      data: { ...mockAppointment, id: Number(params.id) },
    });
  }),

  http.post(`${API_URL}/appointments`, async ({ request }) => {
    const body = (await request.json()) as { date: string; slot_time: string; notes?: string };
    return HttpResponse.json(
      {
        success: true,
        message: 'تم حجز الموعد بنجاح',
        data: {
          ...mockAppointment,
          id: 2,
          appointment_date: body.date,
          appointment_time: body.slot_time,
          notes: body.notes || null,
          status: 'pending',
          created_at: new Date().toISOString(),
        },
      },
      { status: 201 }
    );
  }),

  http.post(`${API_URL}/appointments/:id/cancel`, async ({ params, request }) => {
    const body = (await request.json()) as { reason?: string };
    return HttpResponse.json({
      success: true,
      message: 'تم إلغاء الحجز بنجاح',
      data: {
        ...mockAppointment,
        id: Number(params.id),
        status: 'cancelled',
        cancellation_reason: body.reason,
        cancelled_at: new Date().toISOString(),
        cancelled_by: 'patient',
      },
    });
  }),

  // Slots handlers
  http.get(`${API_URL}/slots/dates`, () => {
    return HttpResponse.json({
      success: true,
      data: mockAvailableDates,
    });
  }),

  http.get(`${API_URL}/slots/:date`, () => {
    return HttpResponse.json({
      success: true,
      data: mockSlots,
    });
  }),

  // Patient handlers
  http.get(`${API_URL}/patient/dashboard`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        user: mockUser,
        upcoming_appointments: [mockAppointment],
        profile_complete: false,
      },
    });
  }),

  http.get(`${API_URL}/patient/profile`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        id: 1,
        user_id: 1,
        blood_type: null,
        allergies: null,
        chronic_diseases: null,
        current_medications: null,
        emergency_contact_name: null,
        emergency_contact_phone: null,
        insurance_provider: null,
        created_at: '2025-01-01T00:00:00.000Z',
      },
    });
  }),

  http.get(`${API_URL}/patient/statistics`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        total_appointments: 5,
        completed_appointments: 3,
        cancelled_appointments: 1,
        no_shows: 0,
        upcoming_appointments: 1,
        last_visit: '2025-01-10',
        profile_complete: false,
      },
    });
  }),

  // Admin handlers
  http.get(`${API_URL}/admin/dashboard/stats`, () => {
    return HttpResponse.json({
      success: true,
      data: mockDashboardStats,
    });
  }),

  http.get(`${API_URL}/admin/dashboard/today`, () => {
    return HttpResponse.json({
      success: true,
      data: [mockAppointment],
    });
  }),

  http.get(`${API_URL}/admin/appointments`, ({ request }) => {
    const url = new URL(request.url);
    const status = url.searchParams.get('status');
    const appointments = status
      ? [{ ...mockAppointment, status }]
      : [mockAppointment, { ...mockAppointment, id: 2, status: 'confirmed' }];

    return HttpResponse.json({
      success: true,
      data: appointments,
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 15,
        to: appointments.length,
        total: appointments.length,
      },
    });
  }),

  http.post(`${API_URL}/admin/appointments/:id/confirm`, ({ params }) => {
    return HttpResponse.json({
      success: true,
      message: 'تم تأكيد الحجز بنجاح',
      data: {
        ...mockAppointment,
        id: Number(params.id),
        status: 'confirmed',
        confirmed_at: new Date().toISOString(),
      },
    });
  }),

  http.post(`${API_URL}/admin/appointments/:id/complete`, ({ params }) => {
    return HttpResponse.json({
      success: true,
      message: 'تم إتمام الحجز بنجاح',
      data: {
        ...mockAppointment,
        id: Number(params.id),
        status: 'completed',
        completed_at: new Date().toISOString(),
      },
    });
  }),

  http.get(`${API_URL}/admin/patients`, () => {
    return HttpResponse.json({
      success: true,
      data: [mockUser],
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 15,
        to: 1,
        total: 1,
      },
    });
  }),

  http.get(`${API_URL}/admin/patients/:id`, ({ params }) => {
    return HttpResponse.json({
      success: true,
      data: { ...mockUser, id: Number(params.id) },
    });
  }),

  // Medical records
  http.get(`${API_URL}/medical-records`, () => {
    return HttpResponse.json({
      success: true,
      data: [],
      meta: {
        current_page: 1,
        from: null,
        last_page: 1,
        per_page: 15,
        to: null,
        total: 0,
      },
    });
  }),

  // Prescriptions
  http.get(`${API_URL}/prescriptions`, () => {
    return HttpResponse.json({
      success: true,
      data: [],
      meta: {
        current_page: 1,
        from: null,
        last_page: 1,
        per_page: 15,
        to: null,
        total: 0,
      },
    });
  }),

  // Notifications
  http.get(`${API_URL}/notifications`, () => {
    return HttpResponse.json({
      success: true,
      data: [],
      meta: {
        current_page: 1,
        from: null,
        last_page: 1,
        per_page: 15,
        to: null,
        total: 0,
      },
    });
  }),

  http.get(`${API_URL}/notifications/unread-count`, () => {
    return HttpResponse.json({
      success: true,
      data: { count: 0 },
    });
  }),

  // Settings
  http.get(`${API_URL}/admin/settings`, () => {
    return HttpResponse.json({
      success: true,
      data: {
        id: 1,
        clinic_name: 'عيادة الاختبار',
        doctor_name: 'دكتور اختبار',
        specialization: 'طب عام',
        phone: '01000000000',
        email: 'clinic@test.com',
        address: 'شارع الاختبار',
        logo: null,
        slot_duration: 30,
        max_patients_per_slot: 1,
        advance_booking_days: 30,
        cancellation_hours: 24,
      },
    });
  }),

  // Schedules
  http.get(`${API_URL}/admin/schedules`, () => {
    return HttpResponse.json({
      success: true,
      data: [
        {
          id: 1,
          day_of_week: 0,
          start_time: '09:00',
          end_time: '17:00',
          break_start: '13:00',
          break_end: '14:00',
          is_active: true,
        },
      ],
    });
  }),

  // Vacations
  http.get(`${API_URL}/admin/vacations`, () => {
    return HttpResponse.json({
      success: true,
      data: [],
    });
  }),
];

export { mockUser, mockAdmin, mockAppointment, mockDashboardStats, mockSlots, mockAvailableDates };
