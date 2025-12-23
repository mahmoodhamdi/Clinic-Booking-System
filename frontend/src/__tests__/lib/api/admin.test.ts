import { adminApi } from '@/lib/api/admin';
import api from '@/lib/api/client';
import {
  createAppointment,
  createUser,
  createPatient,
  createMedicalRecord,
  createPrescription,
  createPayment,
  createPaidPayment,
  createSchedule,
  createVacation,
  createClinicSettings,
  createDashboardStats,
  createPatientProfile,
  createMany,
  wrapInApiResponse,
  wrapInPaginatedResponse,
} from '@/__tests__/factories';

// Mock the API client
jest.mock('@/lib/api/client', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    patch: jest.fn(),
    delete: jest.fn(),
  },
}));

const mockApi = api as jest.Mocked<typeof api>;

describe('adminApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // Dashboard Tests
  describe('Dashboard', () => {
    describe('getDashboardStats', () => {
      it('should call GET /admin/dashboard/stats', async () => {
        const stats = createDashboardStats();
        const mockResponse = { data: wrapInApiResponse(stats) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getDashboardStats();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/dashboard/stats');
        expect(result.data).toEqual(stats);
      });
    });

    describe('getTodayAppointments', () => {
      it('should call GET /admin/dashboard/today', async () => {
        const appointments = createMany(() => createAppointment(), 5);
        const mockResponse = { data: wrapInApiResponse(appointments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getTodayAppointments();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/dashboard/today');
        expect(result.data).toEqual(appointments);
      });
    });

    describe('getRecentActivity', () => {
      it('should call GET /admin/dashboard/recent-activity', async () => {
        const activity = [
          { id: 1, type: 'appointment_created', description: 'New appointment' },
        ];
        const mockResponse = { data: wrapInApiResponse(activity) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getRecentActivity();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/dashboard/recent-activity');
        expect(result.data).toEqual(activity);
      });
    });

    describe('getUpcomingAppointments', () => {
      it('should call GET /admin/dashboard/upcoming-appointments', async () => {
        const appointments = createMany(() => createAppointment({ status: 'confirmed' }), 3);
        const mockResponse = { data: wrapInApiResponse(appointments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getUpcomingAppointments();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/dashboard/upcoming-appointments');
        expect(result.data).toEqual(appointments);
      });
    });
  });

  // Appointments Tests
  describe('Appointments', () => {
    describe('getAppointments', () => {
      it('should call GET /admin/appointments without params', async () => {
        const appointments = createMany(() => createAppointment(), 10);
        const mockResponse = { data: wrapInPaginatedResponse(appointments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getAppointments();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/appointments', { params: undefined });
        expect(result.data).toEqual(appointments);
      });

      it('should call GET /admin/appointments with filters', async () => {
        const appointments = createMany(() => createAppointment({ status: 'pending' }), 5);
        const mockResponse = { data: wrapInPaginatedResponse(appointments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getAppointments({
          status: 'pending',
          date: '2025-01-15',
          page: 1,
        });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/appointments', {
          params: { status: 'pending', date: '2025-01-15', page: 1 },
        });
      });
    });

    describe('getAppointment', () => {
      it('should call GET /admin/appointments/:id', async () => {
        const appointment = createAppointment({ id: 123 });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getAppointment(123);

        expect(mockApi.get).toHaveBeenCalledWith('/admin/appointments/123');
        expect(result.data?.id).toBe(123);
      });
    });

    describe('confirmAppointment', () => {
      it('should call POST /admin/appointments/:id/confirm', async () => {
        const appointment = createAppointment({ id: 123, status: 'confirmed' });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.confirmAppointment(123);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/appointments/123/confirm');
        expect(result.data?.status).toBe('confirmed');
      });
    });

    describe('completeAppointment', () => {
      it('should call POST /admin/appointments/:id/complete', async () => {
        const appointment = createAppointment({ id: 123, status: 'completed' });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.completeAppointment(123);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/appointments/123/complete');
        expect(result.data?.status).toBe('completed');
      });
    });

    describe('cancelAppointment', () => {
      it('should call POST /admin/appointments/:id/cancel', async () => {
        const appointment = createAppointment({ id: 123, status: 'cancelled' });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.cancelAppointment(123, 'Doctor unavailable');

        expect(mockApi.post).toHaveBeenCalledWith('/admin/appointments/123/cancel', {
          cancellation_reason: 'Doctor unavailable',
        });
        expect(result.data?.status).toBe('cancelled');
      });
    });

    describe('markNoShow', () => {
      it('should call POST /admin/appointments/:id/no-show', async () => {
        const appointment = createAppointment({ id: 123, status: 'no_show' });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.markNoShow(123);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/appointments/123/no-show');
      });
    });

    describe('updateAppointmentNotes', () => {
      it('should call PUT /admin/appointments/:id/notes', async () => {
        const appointment = createAppointment({ id: 123, notes: 'Updated notes' });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.put.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.updateAppointmentNotes(123, 'Patient notes', 'Admin notes');

        expect(mockApi.put).toHaveBeenCalledWith('/admin/appointments/123/notes', {
          notes: 'Patient notes',
          admin_notes: 'Admin notes',
        });
      });
    });

    describe('rescheduleAppointment', () => {
      it('should call POST /admin/appointments/:id/reschedule', async () => {
        const appointment = createAppointment({
          id: 123,
          appointment_date: '2025-01-20',
          slot_time: '11:00',
        });
        const mockResponse = { data: wrapInApiResponse(appointment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.rescheduleAppointment(123, '2025-01-20', '11:00');

        expect(mockApi.post).toHaveBeenCalledWith('/admin/appointments/123/reschedule', {
          date: '2025-01-20',
          slot_time: '11:00',
        });
      });
    });
  });

  // Patients Tests
  describe('Patients', () => {
    describe('getPatients', () => {
      it('should call GET /admin/patients', async () => {
        const patients = createMany(() => createPatient(), 10);
        const mockResponse = { data: wrapInPaginatedResponse(patients) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getPatients();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/patients', { params: undefined });
        expect(result.data).toEqual(patients);
      });

      it('should search patients', async () => {
        const patients = createMany(() => createPatient({ name: 'Ahmed' }), 3);
        const mockResponse = { data: wrapInPaginatedResponse(patients) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getPatients({ search: 'Ahmed' });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/patients', {
          params: { search: 'Ahmed' },
        });
      });
    });

    describe('getPatient', () => {
      it('should call GET /admin/patients/:id', async () => {
        const patient = createPatient({ id: 123 });
        const mockResponse = { data: wrapInApiResponse({ ...patient, profile: createPatientProfile() }) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getPatient(123);

        expect(mockApi.get).toHaveBeenCalledWith('/admin/patients/123');
      });
    });

    describe('createPatient', () => {
      it('should call POST /admin/patients', async () => {
        const patient = createPatient();
        const mockResponse = { data: wrapInApiResponse(patient) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const patientData = {
          name: 'New Patient',
          phone: '01234567890',
          email: 'patient@test.com',
        };
        const result = await adminApi.createPatient(patientData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/patients', patientData);
      });
    });

    describe('getPatientAppointments', () => {
      it('should call GET /admin/patients/:id/appointments', async () => {
        const appointments = createMany(() => createAppointment(), 5);
        const mockResponse = { data: wrapInPaginatedResponse(appointments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getPatientAppointments(123, 1);

        expect(mockApi.get).toHaveBeenCalledWith('/admin/patients/123/appointments', {
          params: { page: 1 },
        });
      });
    });

    describe('updatePatientProfile', () => {
      it('should call PUT /admin/patients/:id/profile', async () => {
        const profile = createPatientProfile();
        const mockResponse = { data: wrapInApiResponse(profile) };
        mockApi.put.mockResolvedValueOnce(mockResponse);

        const profileData = { blood_type: 'O+', allergies: 'None' };
        await adminApi.updatePatientProfile(123, profileData);

        expect(mockApi.put).toHaveBeenCalledWith('/admin/patients/123/profile', profileData);
      });
    });
  });

  // Medical Records Tests
  describe('Medical Records', () => {
    describe('getMedicalRecords', () => {
      it('should call GET /admin/medical-records', async () => {
        const records = createMany(() => createMedicalRecord(), 10);
        const mockResponse = { data: wrapInPaginatedResponse(records) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getMedicalRecords();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/medical-records', { params: undefined });
      });

      it('should filter by patient_id', async () => {
        const records = createMany(() => createMedicalRecord(), 5);
        const mockResponse = { data: wrapInPaginatedResponse(records) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getMedicalRecords({ patient_id: 123 });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/medical-records', {
          params: { patient_id: 123 },
        });
      });
    });

    describe('createMedicalRecord', () => {
      it('should call POST /admin/medical-records', async () => {
        const record = createMedicalRecord();
        const mockResponse = { data: wrapInApiResponse(record) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const recordData = {
          patient_id: 1,
          diagnosis: 'Common cold',
          symptoms: 'Cough, fever',
          treatment_plan: 'Rest and fluids',
        };
        await adminApi.createMedicalRecord(recordData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/medical-records', recordData);
      });
    });

    describe('updateMedicalRecord', () => {
      it('should call PUT /admin/medical-records/:id', async () => {
        const record = createMedicalRecord({ id: 123 });
        const mockResponse = { data: wrapInApiResponse(record) };
        mockApi.put.mockResolvedValueOnce(mockResponse);

        await adminApi.updateMedicalRecord(123, { diagnosis: 'Updated diagnosis' });

        expect(mockApi.put).toHaveBeenCalledWith('/admin/medical-records/123', {
          diagnosis: 'Updated diagnosis',
        });
      });
    });

    describe('deleteMedicalRecord', () => {
      it('should call DELETE /admin/medical-records/:id', async () => {
        const mockResponse = { data: wrapInApiResponse(null) };
        mockApi.delete.mockResolvedValueOnce(mockResponse);

        await adminApi.deleteMedicalRecord(123);

        expect(mockApi.delete).toHaveBeenCalledWith('/admin/medical-records/123');
      });
    });

    describe('uploadAttachment', () => {
      it('should call POST /admin/medical-records/:id/attachments', async () => {
        const attachment = { id: 1, filename: 'report.pdf', url: '/attachments/1' };
        const mockResponse = { data: wrapInApiResponse(attachment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const file = new File(['test'], 'report.pdf', { type: 'application/pdf' });
        await adminApi.uploadAttachment(123, file, 'Lab report');

        expect(mockApi.post).toHaveBeenCalledWith(
          '/admin/medical-records/123/attachments',
          expect.any(FormData)
        );
      });
    });
  });

  // Prescriptions Tests
  describe('Prescriptions', () => {
    describe('getPrescriptions', () => {
      it('should call GET /admin/prescriptions', async () => {
        const prescriptions = createMany(() => createPrescription(), 10);
        const mockResponse = { data: wrapInPaginatedResponse(prescriptions) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getPrescriptions();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/prescriptions', { params: undefined });
      });
    });

    describe('createPrescription', () => {
      it('should call POST /admin/prescriptions', async () => {
        const prescription = createPrescription();
        const mockResponse = { data: wrapInApiResponse(prescription) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const prescriptionData = {
          patient_id: 1,
          diagnosis: 'Flu',
          items: [
            {
              medication_name: 'Paracetamol',
              dosage: '500mg',
              frequency: 'Three times daily',
              duration: '5 days',
            },
          ],
        };
        await adminApi.createPrescription(prescriptionData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/prescriptions', prescriptionData);
      });
    });

    describe('dispensePrescription', () => {
      it('should call POST /admin/prescriptions/:id/dispense', async () => {
        const prescription = createPrescription({ id: 123, is_dispensed: true });
        const mockResponse = { data: wrapInApiResponse(prescription) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.dispensePrescription(123);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/prescriptions/123/dispense');
        expect(result.data?.is_dispensed).toBe(true);
      });
    });

    describe('downloadPrescriptionPdf', () => {
      it('should call GET /admin/prescriptions/:id/download', async () => {
        const blob = new Blob(['PDF content'], { type: 'application/pdf' });
        mockApi.get.mockResolvedValueOnce({ data: blob });

        const result = await adminApi.downloadPrescriptionPdf(123);

        expect(mockApi.get).toHaveBeenCalledWith('/admin/prescriptions/123/download', {
          responseType: 'blob',
        });
        expect(result).toBeInstanceOf(Blob);
      });
    });
  });

  // Payments Tests
  describe('Payments', () => {
    describe('getPayments', () => {
      it('should call GET /admin/payments', async () => {
        const payments = createMany(() => createPayment(), 10);
        const mockResponse = { data: wrapInPaginatedResponse(payments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getPayments();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/payments', { params: undefined });
      });

      it('should filter by date range', async () => {
        const payments = createMany(() => createPayment(), 5);
        const mockResponse = { data: wrapInPaginatedResponse(payments) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getPayments({
          from_date: '2025-01-01',
          to_date: '2025-01-31',
        });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/payments', {
          params: { from_date: '2025-01-01', to_date: '2025-01-31' },
        });
      });
    });

    describe('createPayment', () => {
      it('should call POST /admin/payments', async () => {
        const payment = createPayment();
        const mockResponse = { data: wrapInApiResponse(payment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const paymentData = {
          appointment_id: 1,
          amount: 200,
          payment_method: 'cash',
        };
        await adminApi.createPayment(paymentData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/payments', paymentData);
      });
    });

    describe('markPaymentPaid', () => {
      it('should call POST /admin/payments/:id/mark-paid', async () => {
        const payment = createPaidPayment({ id: 123 });
        const mockResponse = { data: wrapInApiResponse(payment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.markPaymentPaid(123, 'card');

        expect(mockApi.post).toHaveBeenCalledWith('/admin/payments/123/mark-paid', {
          payment_method: 'card',
        });
        expect(result.data?.status).toBe('paid');
      });
    });

    describe('refundPayment', () => {
      it('should call POST /admin/payments/:id/refund', async () => {
        const payment = createPayment({ id: 123, status: 'refunded' });
        const mockResponse = { data: wrapInApiResponse(payment) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        await adminApi.refundPayment(123, 'Patient requested refund');

        expect(mockApi.post).toHaveBeenCalledWith('/admin/payments/123/refund', {
          reason: 'Patient requested refund',
        });
      });
    });

    describe('getPaymentStatistics', () => {
      it('should call GET /admin/payments/statistics', async () => {
        const stats = {
          total_revenue: 50000,
          total_pending: 5000,
          total_paid: 45000,
          total_refunded: 1000,
          today_revenue: 2000,
          this_month_revenue: 15000,
        };
        const mockResponse = { data: wrapInApiResponse(stats) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getPaymentStatistics();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/payments/statistics');
        expect(result.data).toEqual(stats);
      });
    });
  });

  // Settings Tests
  describe('Settings', () => {
    describe('getClinicSettings', () => {
      it('should call GET /admin/settings', async () => {
        const settings = createClinicSettings();
        const mockResponse = { data: wrapInApiResponse(settings) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getClinicSettings();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/settings');
        expect(result.data).toEqual(settings);
      });
    });

    describe('updateClinicSettings', () => {
      it('should call PUT /admin/settings', async () => {
        const settings = createClinicSettings({ clinic_name: 'Updated Clinic' });
        const mockResponse = { data: wrapInApiResponse(settings) };
        mockApi.put.mockResolvedValueOnce(mockResponse);

        const settingsData = { clinic_name: 'Updated Clinic', consultation_fee: 300 };
        await adminApi.updateClinicSettings(settingsData);

        expect(mockApi.put).toHaveBeenCalledWith('/admin/settings', settingsData);
      });
    });

    describe('uploadClinicLogo', () => {
      it('should call POST /admin/settings/logo', async () => {
        const mockResponse = { data: wrapInApiResponse({ logo: '/logos/clinic.png' }) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const file = new File(['logo'], 'logo.png', { type: 'image/png' });
        await adminApi.uploadClinicLogo(file);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/settings/logo', expect.any(FormData));
      });
    });
  });

  // Schedules Tests
  describe('Schedules', () => {
    describe('getSchedules', () => {
      it('should call GET /admin/schedules', async () => {
        const schedules = createMany(() => createSchedule(), 7);
        const mockResponse = { data: wrapInApiResponse(schedules) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getSchedules();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/schedules');
        expect(result.data).toHaveLength(7);
      });
    });

    describe('createSchedule', () => {
      it('should call POST /admin/schedules', async () => {
        const schedule = createSchedule();
        const mockResponse = { data: wrapInApiResponse(schedule) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const scheduleData = {
          day_of_week: 1,
          start_time: '09:00',
          end_time: '17:00',
          break_start: '13:00',
          break_end: '14:00',
        };
        await adminApi.createSchedule(scheduleData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/schedules', scheduleData);
      });
    });

    describe('toggleSchedule', () => {
      it('should call PATCH /admin/schedules/:id/toggle', async () => {
        const schedule = createSchedule({ id: 123, is_active: false });
        const mockResponse = { data: wrapInApiResponse(schedule) };
        mockApi.patch.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.toggleSchedule(123);

        expect(mockApi.patch).toHaveBeenCalledWith('/admin/schedules/123/toggle');
      });
    });
  });

  // Vacations Tests
  describe('Vacations', () => {
    describe('getVacations', () => {
      it('should call GET /admin/vacations', async () => {
        const vacations = createMany(() => createVacation(), 5);
        const mockResponse = { data: wrapInApiResponse(vacations) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        const result = await adminApi.getVacations();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/vacations');
        expect(result.data).toHaveLength(5);
      });
    });

    describe('createVacation', () => {
      it('should call POST /admin/vacations', async () => {
        const vacation = createVacation();
        const mockResponse = { data: wrapInApiResponse(vacation) };
        mockApi.post.mockResolvedValueOnce(mockResponse);

        const vacationData = {
          title: 'Eid Vacation',
          start_date: '2025-04-10',
          end_date: '2025-04-13',
          reason: 'Eid Al-Fitr',
        };
        await adminApi.createVacation(vacationData);

        expect(mockApi.post).toHaveBeenCalledWith('/admin/vacations', vacationData);
      });
    });

    describe('deleteVacation', () => {
      it('should call DELETE /admin/vacations/:id', async () => {
        const mockResponse = { data: wrapInApiResponse(null) };
        mockApi.delete.mockResolvedValueOnce(mockResponse);

        await adminApi.deleteVacation(123);

        expect(mockApi.delete).toHaveBeenCalledWith('/admin/vacations/123');
      });
    });
  });

  // Reports Tests
  describe('Reports', () => {
    describe('getAppointmentsReport', () => {
      it('should call GET /admin/reports/appointments', async () => {
        const report = {
          total: 100,
          by_status: { pending: 10, confirmed: 50, completed: 30, cancelled: 10 },
        };
        const mockResponse = { data: wrapInApiResponse(report) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getAppointmentsReport({ from_date: '2025-01-01', to_date: '2025-01-31' });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/reports/appointments', {
          params: { from_date: '2025-01-01', to_date: '2025-01-31' },
        });
      });
    });

    describe('getRevenueReport', () => {
      it('should call GET /admin/reports/revenue', async () => {
        const report = { total_revenue: 50000, by_month: {} };
        const mockResponse = { data: wrapInApiResponse(report) };
        mockApi.get.mockResolvedValueOnce(mockResponse);

        await adminApi.getRevenueReport();

        expect(mockApi.get).toHaveBeenCalledWith('/admin/reports/revenue', { params: undefined });
      });
    });

    describe('exportAppointmentsReport', () => {
      it('should call GET /admin/reports/appointments/export', async () => {
        const blob = new Blob(['CSV content'], { type: 'text/csv' });
        mockApi.get.mockResolvedValueOnce({ data: blob });

        const result = await adminApi.exportAppointmentsReport({ from_date: '2025-01-01' });

        expect(mockApi.get).toHaveBeenCalledWith('/admin/reports/appointments/export', {
          params: { from_date: '2025-01-01' },
          responseType: 'blob',
        });
        expect(result).toBeInstanceOf(Blob);
      });
    });
  });
});
