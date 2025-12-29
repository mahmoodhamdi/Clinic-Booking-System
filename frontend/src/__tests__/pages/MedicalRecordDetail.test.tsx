import { render, screen, waitFor } from '@/__tests__/utils/test-utils';
import MedicalRecordDetailPage from '@/app/(patient)/medical-records/[id]/page';
import { patientApi } from '@/lib/api/patient';

// Mock next/navigation
const mockBack = jest.fn();
jest.mock('next/navigation', () => ({
  useParams: () => ({ id: '1' }),
  useRouter: () => ({ back: mockBack }),
}));

// Mock next-intl
jest.mock('next-intl', () => ({
  useTranslations: () => (key: string) => key,
}));

// Mock the patient API
jest.mock('@/lib/api/patient', () => ({
  patientApi: {
    getMedicalRecord: jest.fn(),
  },
}));

const mockMedicalRecord = {
  id: 1,
  diagnosis: 'Common Cold',
  notes: 'Rest and hydration recommended',
  blood_pressure_systolic: 120,
  blood_pressure_diastolic: 80,
  heart_rate: 72,
  temperature: 37.5,
  weight: 70,
  height: 175,
  treatment_plan: 'Take medication for 5 days',
  follow_up_date: '2024-02-15',
  follow_up_notes: 'Check progress',
  created_at: '2024-01-15T10:00:00Z',
  prescriptions: [],
  attachments: [
    {
      id: 1,
      file_name: 'test-report.pdf',
      file_path: '/storage/reports/test-report.pdf',
      file_size: 102400,
    },
  ],
};

describe('MedicalRecordDetailPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders loading skeleton initially', () => {
    (patientApi.getMedicalRecord as jest.Mock).mockReturnValue(new Promise(() => {}));

    render(<MedicalRecordDetailPage />);

    // Check for skeleton elements
    const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it('renders medical record details', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Common Cold')).toBeInTheDocument();
    });

    expect(screen.getByText('Rest and hydration recommended')).toBeInTheDocument();
    expect(screen.getByText('120/80')).toBeInTheDocument();
  });

  it('displays vital signs when available', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('120/80')).toBeInTheDocument();
    });

    expect(screen.getByText('72')).toBeInTheDocument();
    expect(screen.getByText('37.5')).toBeInTheDocument();
    expect(screen.getByText('70')).toBeInTheDocument();
    expect(screen.getByText('175')).toBeInTheDocument();
  });

  it('displays treatment plan when available', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Take medication for 5 days')).toBeInTheDocument();
    });
  });

  it('displays attachments when available', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('test-report.pdf')).toBeInTheDocument();
    });

    expect(screen.getByText('100.0 KB')).toBeInTheDocument();
  });

  it('shows error state when record not found', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: null,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('لم يتم العثور على السجل الطبي')).toBeInTheDocument();
    });
  });

  it('shows error state on API error', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockRejectedValue(new Error('API Error'));

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('لم يتم العثور على السجل الطبي')).toBeInTheDocument();
    });
  });

  it('handles back button click', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Common Cold')).toBeInTheDocument();
    });

    const backButton = screen.getAllByRole('button')[0];
    backButton.click();

    expect(mockBack).toHaveBeenCalled();
  });

  it('displays follow-up information when available', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: mockMedicalRecord,
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Check progress')).toBeInTheDocument();
    });
  });

  it('hides notes section when no notes available', async () => {
    (patientApi.getMedicalRecord as jest.Mock).mockResolvedValue({
      data: { ...mockMedicalRecord, notes: null },
    });

    render(<MedicalRecordDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('لا توجد ملاحظات')).toBeInTheDocument();
    });
  });
});
