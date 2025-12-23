import { appointmentsApi } from '@/lib/api/appointments';
import api from '@/lib/api/client';
import {
  createAppointment,
  createSlot,
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

describe('appointmentsApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getAvailableDates', () => {
    it('should call GET /slots/dates', async () => {
      const availableDates = [
        { date: '2025-01-15', day_name: 'Wednesday', slots_count: 10 },
        { date: '2025-01-16', day_name: 'Thursday', slots_count: 8 },
      ];
      const mockResponse = {
        data: wrapInApiResponse(availableDates),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getAvailableDates();

      expect(mockApi.get).toHaveBeenCalledWith('/slots/dates');
      expect(result.data).toEqual(availableDates);
    });
  });

  describe('getSlots', () => {
    it('should call GET /slots/:date', async () => {
      const slots = createMany(() => createSlot(), 5);
      const mockResponse = {
        data: wrapInApiResponse(slots),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getSlots('2025-01-15');

      expect(mockApi.get).toHaveBeenCalledWith('/slots/2025-01-15');
      expect(result.data).toEqual(slots);
    });

    it('should handle different dates', async () => {
      const slots = createMany(() => createSlot(), 3);
      const mockResponse = {
        data: wrapInApiResponse(slots),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await appointmentsApi.getSlots('2025-02-20');

      expect(mockApi.get).toHaveBeenCalledWith('/slots/2025-02-20');
    });
  });

  describe('checkSlot', () => {
    it('should call POST /slots/check with date and time', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ available: true }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.checkSlot('2025-01-15', '09:00');

      expect(mockApi.post).toHaveBeenCalledWith('/slots/check', {
        date: '2025-01-15',
        slot_time: '09:00',
      });
      expect(result.data?.available).toBe(true);
    });

    it('should return false for unavailable slot', async () => {
      const mockResponse = {
        data: wrapInApiResponse({ available: false }),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.checkSlot('2025-01-15', '10:00');

      expect(result.data?.available).toBe(false);
    });
  });

  describe('book', () => {
    it('should call POST /appointments with booking data', async () => {
      const appointment = createAppointment();
      const mockResponse = {
        data: wrapInApiResponse(appointment, 'Appointment booked successfully'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const bookingData = {
        date: '2025-01-15',
        slot_time: '09:00',
        reason: 'Checkup',
      };
      const result = await appointmentsApi.book(bookingData);

      expect(mockApi.post).toHaveBeenCalledWith('/appointments', bookingData);
      expect(result.data).toEqual(appointment);
    });

    it('should book without optional reason', async () => {
      const appointment = createAppointment();
      const mockResponse = {
        data: wrapInApiResponse(appointment),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const bookingData = {
        date: '2025-01-15',
        slot_time: '09:00',
      };
      await appointmentsApi.book(bookingData);

      expect(mockApi.post).toHaveBeenCalledWith('/appointments', bookingData);
    });

    it('should handle booking conflict error', async () => {
      const error = new Error('Slot is no longer available');
      mockApi.post.mockRejectedValueOnce(error);

      const bookingData = {
        date: '2025-01-15',
        slot_time: '09:00',
      };

      await expect(appointmentsApi.book(bookingData)).rejects.toThrow(
        'Slot is no longer available'
      );
    });
  });

  describe('getMyAppointments', () => {
    it('should call GET /appointments without params', async () => {
      const appointments = createMany(() => createAppointment(), 5);
      const mockResponse = {
        data: wrapInPaginatedResponse(appointments),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getMyAppointments();

      expect(mockApi.get).toHaveBeenCalledWith('/appointments', { params: undefined });
      expect(result.data).toEqual(appointments);
    });

    it('should call GET /appointments with status filter', async () => {
      const appointments = createMany(() => createAppointment({ status: 'pending' }), 3);
      const mockResponse = {
        data: wrapInPaginatedResponse(appointments),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getMyAppointments({ status: 'pending' });

      expect(mockApi.get).toHaveBeenCalledWith('/appointments', {
        params: { status: 'pending' },
      });
      expect(result.data).toHaveLength(3);
    });

    it('should call GET /appointments with pagination', async () => {
      const appointments = createMany(() => createAppointment(), 15);
      const mockResponse = {
        data: wrapInPaginatedResponse(appointments, 2, 15, 30),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getMyAppointments({ page: 2 });

      expect(mockApi.get).toHaveBeenCalledWith('/appointments', { params: { page: 2 } });
      expect(result.meta?.current_page).toBe(2);
    });
  });

  describe('getUpcoming', () => {
    it('should call GET /appointments/upcoming', async () => {
      const appointments = createMany(() => createAppointment({ status: 'confirmed' }), 3);
      const mockResponse = {
        data: wrapInApiResponse(appointments),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getUpcoming();

      expect(mockApi.get).toHaveBeenCalledWith('/appointments/upcoming');
      expect(result.data).toEqual(appointments);
    });

    it('should return empty array when no upcoming appointments', async () => {
      const mockResponse = {
        data: wrapInApiResponse([]),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getUpcoming();

      expect(result.data).toEqual([]);
    });
  });

  describe('getById', () => {
    it('should call GET /appointments/:id', async () => {
      const appointment = createAppointment({ id: 123 });
      const mockResponse = {
        data: wrapInApiResponse(appointment),
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.getById(123);

      expect(mockApi.get).toHaveBeenCalledWith('/appointments/123');
      expect(result.data?.id).toBe(123);
    });

    it('should handle not found error', async () => {
      const error = new Error('Appointment not found');
      mockApi.get.mockRejectedValueOnce(error);

      await expect(appointmentsApi.getById(999)).rejects.toThrow('Appointment not found');
    });
  });

  describe('cancel', () => {
    it('should call POST /appointments/:id/cancel with reason', async () => {
      const appointment = createAppointment({
        id: 123,
        status: 'cancelled',
        cancellation_reason: 'Cannot make it',
      });
      const mockResponse = {
        data: wrapInApiResponse(appointment, 'Appointment cancelled'),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await appointmentsApi.cancel(123, 'Cannot make it');

      expect(mockApi.post).toHaveBeenCalledWith('/appointments/123/cancel', {
        cancellation_reason: 'Cannot make it',
      });
      expect(result.data?.status).toBe('cancelled');
    });

    it('should call POST /appointments/:id/cancel without reason', async () => {
      const appointment = createAppointment({ id: 123, status: 'cancelled' });
      const mockResponse = {
        data: wrapInApiResponse(appointment),
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await appointmentsApi.cancel(123);

      expect(mockApi.post).toHaveBeenCalledWith('/appointments/123/cancel', {
        cancellation_reason: undefined,
      });
    });

    it('should handle cancellation not allowed error', async () => {
      const error = new Error('Cancellation not allowed within 24 hours');
      mockApi.post.mockRejectedValueOnce(error);

      await expect(appointmentsApi.cancel(123)).rejects.toThrow(
        'Cancellation not allowed within 24 hours'
      );
    });
  });
});
