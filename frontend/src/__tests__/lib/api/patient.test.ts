import { patientApi } from '@/lib/api/patient';
import api from '@/lib/api/client';
import {
  createPatientProfile,
  createMedicalRecord,
  createPrescription,
  createNotification,
  createAppointment,
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
    delete: jest.fn(),
  },
}));

const mockApi = api as jest.Mocked<typeof api>;

describe('patientApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getDashboard', () => {
    it('should call GET /patient/dashboard', async () => {
      const dashboardData = {
        upcoming_appointments: createMany(() => createAppointment(), 2),
        recent_medical_records: createMany(() => createMedicalRecord(), 2),
        unread_notifications: 5,
      };
      const mockResponse = {
        data: wrapInApiResponse(dashboardData),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getDashboard();

      expect(mockApi.get).toHaveBeenCalledWith('/patient/dashboard');
      expect(result.data).toEqual(dashboardData);
    });
  });

  describe('getProfile', () => {
    it('should call GET /patient/profile', async () => {
      const profile = createPatientProfile();
      const mockResponse = {
        data: wrapInApiResponse(profile),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getProfile();

      expect(mockApi.get).toHaveBeenCalledWith('/patient/profile');
      expect(result.data).toEqual(profile);
    });
  });

  describe('createProfile', () => {
    it('should call POST /patient/profile', async () => {
      const profile = createPatientProfile({ blood_type: 'A+' });
      const mockResponse = {
        data: wrapInApiResponse(profile),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const profileData = {
        blood_type: 'A+',
        allergies: 'Penicillin',
        emergency_contact_name: 'John Doe',
        emergency_contact_phone: '01111111111',
      };
      const result = await patientApi.createProfile(profileData);

      expect(mockApi.post).toHaveBeenCalledWith('/patient/profile', profileData);
      expect(result.data?.blood_type).toBe('A+');
    });
  });

  describe('updateProfile', () => {
    it('should call PUT /patient/profile', async () => {
      const profile = createPatientProfile({ allergies: 'Updated allergies' });
      const mockResponse = {
        data: wrapInApiResponse(profile),
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const profileData = { allergies: 'Updated allergies' };
      const result = await patientApi.updateProfile(profileData);

      expect(mockApi.put).toHaveBeenCalledWith('/patient/profile', profileData);
      expect(result.data).toEqual(profile);
    });
  });

  describe('getHistory', () => {
    it('should call GET /patient/history', async () => {
      const history = {
        appointments: createMany(() => createAppointment(), 5),
        medical_records: createMany(() => createMedicalRecord(), 3),
        prescriptions: createMany(() => createPrescription(), 2),
      };
      const mockResponse = {
        data: wrapInApiResponse(history),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getHistory();

      expect(mockApi.get).toHaveBeenCalledWith('/patient/history');
      expect(result.data).toEqual(history);
    });
  });

  describe('getStatistics', () => {
    it('should call GET /patient/statistics', async () => {
      const statistics = {
        total_appointments: 10,
        completed_appointments: 8,
        cancelled_appointments: 1,
        upcoming_appointments: 1,
        total_medical_records: 5,
        total_prescriptions: 3,
      };
      const mockResponse = {
        data: wrapInApiResponse(statistics),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getStatistics();

      expect(mockApi.get).toHaveBeenCalledWith('/patient/statistics');
      expect(result.data).toEqual(statistics);
    });
  });

  describe('getMedicalRecords', () => {
    it('should call GET /medical-records without params', async () => {
      const records = createMany(() => createMedicalRecord(), 5);
      const mockResponse = {
        data: wrapInPaginatedResponse(records),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getMedicalRecords();

      expect(mockApi.get).toHaveBeenCalledWith('/medical-records', { params: undefined });
      expect(result.data).toEqual(records);
    });

    it('should call GET /medical-records with pagination', async () => {
      const records = createMany(() => createMedicalRecord(), 15);
      const mockResponse = {
        data: wrapInPaginatedResponse(records, 2, 15, 30),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getMedicalRecords({ page: 2 });

      expect(mockApi.get).toHaveBeenCalledWith('/medical-records', { params: { page: 2 } });
      expect(result.meta?.current_page).toBe(2);
    });
  });

  describe('getMedicalRecord', () => {
    it('should call GET /medical-records/:id', async () => {
      const record = createMedicalRecord({ id: 123 });
      const mockResponse = {
        data: wrapInApiResponse(record),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getMedicalRecord(123);

      expect(mockApi.get).toHaveBeenCalledWith('/medical-records/123');
      expect(result.data?.id).toBe(123);
    });
  });

  describe('getPrescriptions', () => {
    it('should call GET /prescriptions without params', async () => {
      const prescriptions = createMany(() => createPrescription(), 5);
      const mockResponse = {
        data: wrapInPaginatedResponse(prescriptions),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getPrescriptions();

      expect(mockApi.get).toHaveBeenCalledWith('/prescriptions', { params: undefined });
      expect(result.data).toEqual(prescriptions);
    });

    it('should call GET /prescriptions with pagination', async () => {
      const prescriptions = createMany(() => createPrescription(), 10);
      const mockResponse = {
        data: wrapInPaginatedResponse(prescriptions, 1, 10, 20),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getPrescriptions({ page: 1 });

      expect(mockApi.get).toHaveBeenCalledWith('/prescriptions', { params: { page: 1 } });
    });
  });

  describe('getPrescription', () => {
    it('should call GET /prescriptions/:id', async () => {
      const prescription = createPrescription({ id: 456 });
      const mockResponse = {
        data: wrapInApiResponse(prescription),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getPrescription(456);

      expect(mockApi.get).toHaveBeenCalledWith('/prescriptions/456');
      expect(result.data?.id).toBe(456);
    });
  });

  describe('getNotifications', () => {
    it('should call GET /notifications without params', async () => {
      const notifications = createMany(() => createNotification(), 10);
      const mockResponse = {
        data: wrapInPaginatedResponse(notifications),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getNotifications();

      expect(mockApi.get).toHaveBeenCalledWith('/notifications', { params: undefined });
      expect(result.data).toEqual(notifications);
    });

    it('should call GET /notifications with pagination', async () => {
      const notifications = createMany(() => createNotification(), 15);
      const mockResponse = {
        data: wrapInPaginatedResponse(notifications, 2, 15, 30),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getNotifications({ page: 2 });

      expect(mockApi.get).toHaveBeenCalledWith('/notifications', { params: { page: 2 } });
    });
  });

  describe('getUnreadCount', () => {
    it('should call GET /notifications/unread-count', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ count: 5 }),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getUnreadCount();

      expect(mockApi.get).toHaveBeenCalledWith('/notifications/unread-count');
      expect(result.data?.count).toBe(5);
    });

    it('should return zero when no unread notifications', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ count: 0 }),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.getUnreadCount();

      expect(result.data?.count).toBe(0);
    });
  });

  describe('markAsRead', () => {
    it('should call POST /notifications/:id/read', async () => {
      const notification = createNotification({
        id: 789,
        read_at: '2025-01-15T10:00:00.000Z',
      });
      const mockResponse = {
        data: wrapInApiResponse(notification),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.markAsRead('789');

      expect(mockApi.post).toHaveBeenCalledWith('/notifications/789/read');
      expect(result.data?.read_at).not.toBeNull();
    });
  });

  describe('markAllAsRead', () => {
    it('should call POST /notifications/read-all', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'All notifications marked as read'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.markAllAsRead();

      expect(mockApi.post).toHaveBeenCalledWith('/notifications/read-all');
      expect(result.success).toBe(true);
    });
  });

  describe('deleteNotification', () => {
    it('should call DELETE /notifications/:id', async () => {
      const mockResponse = {
        data: wrapInApiResponse(null, 'Notification deleted'),
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      const result = await patientApi.deleteNotification('789');

      expect(mockApi.delete).toHaveBeenCalledWith('/notifications/789');
      expect(result.success).toBe(true);
    });
  });
});
