import {
  createUser,
  createAdmin,
  createSecretary,
  createPatient,
  createAppointment,
  createPendingAppointment,
  createConfirmedAppointment,
  createCompletedAppointment,
  createCancelledAppointment,
  createMedicalRecord,
  createPrescription,
  createPrescriptionItem,
  createPayment,
  createPaidPayment,
  createPatientProfile,
  createSchedule,
  createVacation,
  createNotification,
  createSlot,
  createDashboardStats,
  createClinicSettings,
  createMany,
  wrapInApiResponse,
  wrapInPaginatedResponse,
  resetFactoryCounter,
} from '../factories';

describe('Factory Functions', () => {
  beforeEach(() => {
    resetFactoryCounter();
  });

  describe('User factories', () => {
    it('should create a user with default values', () => {
      const user = createUser();
      expect(user.id).toBeDefined();
      expect(user.name).toBe('Test User');
      expect(user.role).toBe('patient');
      expect(user.is_active).toBe(true);
    });

    it('should create a user with custom values', () => {
      const user = createUser({ name: 'Custom Name', email: 'custom@test.com' });
      expect(user.name).toBe('Custom Name');
      expect(user.email).toBe('custom@test.com');
    });

    it('should create an admin user', () => {
      const admin = createAdmin();
      expect(admin.role).toBe('admin');
      expect(admin.name).toBe('Admin User');
    });

    it('should create a secretary user', () => {
      const secretary = createSecretary();
      expect(secretary.role).toBe('secretary');
      expect(secretary.name).toBe('Secretary User');
    });

    it('should create a patient user', () => {
      const patient = createPatient();
      expect(patient.role).toBe('patient');
    });

    it('should increment IDs for multiple users', () => {
      const user1 = createUser();
      const user2 = createUser();
      expect(user2.id).toBeGreaterThan(user1.id);
    });
  });

  describe('Appointment factories', () => {
    it('should create an appointment with default values', () => {
      const appointment = createAppointment();
      expect(appointment.id).toBeDefined();
      expect(appointment.status).toBe('pending');
      expect(appointment.appointment_date).toBeTruthy();
    });

    it('should create a pending appointment', () => {
      const appointment = createPendingAppointment();
      expect(appointment.status).toBe('pending');
    });

    it('should create a confirmed appointment', () => {
      const appointment = createConfirmedAppointment();
      expect(appointment.status).toBe('confirmed');
      expect(appointment.confirmed_at).toBeTruthy();
    });

    it('should create a completed appointment', () => {
      const appointment = createCompletedAppointment();
      expect(appointment.status).toBe('completed');
      expect(appointment.completed_at).toBeTruthy();
    });

    it('should create a cancelled appointment', () => {
      const appointment = createCancelledAppointment();
      expect(appointment.status).toBe('cancelled');
      expect(appointment.cancelled_by).toBe('patient');
      expect(appointment.cancellation_reason).toBeTruthy();
    });

    it('should create appointment with custom values', () => {
      const appointment = createAppointment({
        reason: 'Custom reason',
        notes: 'Custom notes',
      });
      expect(appointment.reason).toBe('Custom reason');
      expect(appointment.notes).toBe('Custom notes');
    });
  });

  describe('Medical Record factories', () => {
    it('should create a medical record with default values', () => {
      const record = createMedicalRecord();
      expect(record.id).toBeDefined();
      expect(record.diagnosis).toBe('Test diagnosis');
      expect(record.vital_signs).toBeDefined();
    });

    it('should create a medical record with vital signs', () => {
      const record = createMedicalRecord();
      expect(record.vital_signs.blood_pressure).toBe('120/80');
      expect(record.vital_signs.heart_rate).toBe(72);
    });

    it('should allow custom vital signs', () => {
      const record = createMedicalRecord({
        blood_pressure_systolic: 130,
        blood_pressure_diastolic: 85,
      });
      expect(record.blood_pressure_systolic).toBe(130);
      expect(record.blood_pressure_diastolic).toBe(85);
    });
  });

  describe('Prescription factories', () => {
    it('should create a prescription with default values', () => {
      const prescription = createPrescription();
      expect(prescription.id).toBeDefined();
      expect(prescription.items).toHaveLength(1);
    });

    it('should create a prescription item', () => {
      const item = createPrescriptionItem();
      expect(item.medication_name).toBe('Test Medication');
      expect(item.dosage).toBe('500mg');
    });

    it('should create prescription with custom items', () => {
      const customItem = createPrescriptionItem({ medication_name: 'Aspirin' });
      const prescription = createPrescription({ items: [customItem] });
      expect(prescription.items[0].medication_name).toBe('Aspirin');
    });
  });

  describe('Payment factories', () => {
    it('should create a payment with default values', () => {
      const payment = createPayment();
      expect(payment.id).toBeDefined();
      expect(payment.amount).toBe(100);
      expect(payment.status).toBe('pending');
    });

    it('should create a paid payment', () => {
      const payment = createPaidPayment();
      expect(payment.status).toBe('paid');
      expect(payment.paid_at).toBeTruthy();
    });

    it('should create payment with custom amount', () => {
      const payment = createPayment({ amount: 500, discount: 50 });
      expect(payment.amount).toBe(500);
      expect(payment.discount).toBe(50);
    });
  });

  describe('Schedule factories', () => {
    it('should create a schedule with default values', () => {
      const schedule = createSchedule();
      expect(schedule.id).toBeDefined();
      expect(schedule.day_of_week).toBe(0);
      expect(schedule.is_active).toBe(true);
    });

    it('should create schedule with custom day', () => {
      const schedule = createSchedule({ day_of_week: 5 });
      expect(schedule.day_of_week).toBe(5);
    });

    it('should create schedule with break times', () => {
      const schedule = createSchedule();
      expect(schedule.break_start).toBe('13:00');
      expect(schedule.break_end).toBe('14:00');
    });
  });

  describe('Vacation factories', () => {
    it('should create a vacation with default values', () => {
      const vacation = createVacation();
      expect(vacation.id).toBeDefined();
      expect(vacation.title).toBe('Test Vacation');
    });

    it('should create vacation with custom dates', () => {
      const vacation = createVacation({
        start_date: '2025-03-01',
        end_date: '2025-03-05',
      });
      expect(vacation.start_date).toBe('2025-03-01');
      expect(vacation.end_date).toBe('2025-03-05');
    });
  });

  describe('Notification factories', () => {
    it('should create a notification with default values', () => {
      const notification = createNotification();
      expect(notification.id).toBeDefined();
      expect(notification.title).toBe('Test Notification');
      expect(notification.type).toBe('appointment_reminder');
    });

    it('should create unread notification by default', () => {
      const notification = createNotification();
      expect(notification.read_at).toBeNull();
    });

    it('should create read notification', () => {
      const notification = createNotification({ read_at: '2025-01-01T00:00:00.000Z' });
      expect(notification.read_at).toBeTruthy();
    });
  });

  describe('Slot factories', () => {
    it('should create a slot with default values', () => {
      const slot = createSlot();
      expect(slot.time).toBe('09:00');
      expect(slot.available).toBe(true);
    });

    it('should create unavailable slot', () => {
      const slot = createSlot({ available: false, remaining: 0 });
      expect(slot.available).toBe(false);
      expect(slot.remaining).toBe(0);
    });
  });

  describe('Dashboard stats factories', () => {
    it('should create dashboard stats with default values', () => {
      const stats = createDashboardStats();
      expect(stats.total_patients).toBe(100);
      expect(stats.total_appointments).toBe(250);
    });

    it('should create custom stats', () => {
      const stats = createDashboardStats({ total_patients: 500 });
      expect(stats.total_patients).toBe(500);
    });
  });

  describe('Clinic settings factories', () => {
    it('should create clinic settings with default values', () => {
      const settings = createClinicSettings();
      expect(settings.slot_duration).toBe(30);
      expect(settings.max_patients_per_slot).toBe(1);
    });

    it('should create custom settings', () => {
      const settings = createClinicSettings({ slot_duration: 15 });
      expect(settings.slot_duration).toBe(15);
    });
  });

  describe('createMany helper', () => {
    it('should create multiple items', () => {
      const users = createMany(createUser, 5);
      expect(users).toHaveLength(5);
    });

    it('should create items with unique IDs', () => {
      resetFactoryCounter();
      const users = createMany(createUser, 3);
      const ids = users.map((u) => u.id);
      expect(new Set(ids).size).toBe(3);
    });
  });

  describe('API response wrappers', () => {
    it('should wrap data in API response', () => {
      const data = { foo: 'bar' };
      const response = wrapInApiResponse(data);
      expect(response.success).toBe(true);
      expect(response.data).toEqual(data);
    });

    it('should include message in API response', () => {
      const response = wrapInApiResponse(null, 'Success message');
      expect(response.message).toBe('Success message');
    });

    it('should wrap data in paginated response', () => {
      const items = createMany(createUser, 3);
      const response = wrapInPaginatedResponse(items);
      expect(response.success).toBe(true);
      expect(response.data).toEqual(items);
      expect(response.meta.current_page).toBe(1);
    });

    it('should calculate pagination metadata', () => {
      const items = createMany(createUser, 3);
      const response = wrapInPaginatedResponse(items, 2, 10, 30);
      expect(response.meta.current_page).toBe(2);
      expect(response.meta.per_page).toBe(10);
      expect(response.meta.total).toBe(30);
      expect(response.meta.last_page).toBe(3);
    });

    it('should handle empty data in pagination', () => {
      const response = wrapInPaginatedResponse([]);
      expect(response.data).toEqual([]);
      expect(response.meta.from).toBeNull();
      expect(response.meta.to).toBeNull();
    });
  });
});

describe('Patient Profile factories', () => {
  beforeEach(() => {
    resetFactoryCounter();
  });

  it('should create patient profile with default values', () => {
    const profile = createPatientProfile();
    expect(profile.id).toBeDefined();
    expect(profile.user_id).toBe(1);
  });

  it('should create profile with medical info', () => {
    const profile = createPatientProfile({
      blood_type: 'A+',
      allergies: 'Penicillin',
    });
    expect(profile.blood_type).toBe('A+');
    expect(profile.allergies).toBe('Penicillin');
  });

  it('should create profile with emergency contact', () => {
    const profile = createPatientProfile({
      emergency_contact_name: 'John Doe',
      emergency_contact_phone: '01234567890',
    });
    expect(profile.emergency_contact_name).toBe('John Doe');
    expect(profile.emergency_contact_phone).toBe('01234567890');
  });
});
