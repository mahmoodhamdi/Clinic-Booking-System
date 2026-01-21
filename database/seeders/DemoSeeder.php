<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\BloodType;
use App\Enums\CancelledBy;
use App\Enums\Gender;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Vacation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating demo data...');

        // Create Secretary
        $secretary = User::create([
            'name' => 'سارة أحمد',
            'email' => 'secretary@clinic.com',
            'phone' => '01100000000',
            'password' => Hash::make('secretary123'),
            'role' => UserRole::SECRETARY,
            'gender' => Gender::FEMALE,
            'date_of_birth' => '1990-05-15',
            'address' => 'القاهرة، مصر الجديدة',
            'is_active' => true,
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->command->info("✓ Secretary created: {$secretary->phone}");

        // Create Patients with full profiles
        $patients = $this->createPatients();
        $this->command->info('✓ Created ' . count($patients) . ' patients with full profiles');

        // Create Appointments with various statuses
        $appointments = $this->createAppointments($patients);
        $this->command->info('✓ Created ' . count($appointments) . ' appointments');

        // Create Medical Records with Prescriptions
        $this->createMedicalRecords($appointments);
        $this->command->info('✓ Created medical records and prescriptions');

        // Create Payments
        $this->createPayments($appointments);
        $this->command->info('✓ Created payments');

        // Create Notifications
        $this->createNotifications($patients);
        $this->command->info('✓ Created notifications');

        // Create upcoming vacation
        Vacation::create([
            'title' => 'عطلة عيد الفطر',
            'start_date' => now()->addMonth()->startOfWeek(),
            'end_date' => now()->addMonth()->startOfWeek()->addDays(3),
            'reason' => 'إجازة رسمية',
        ]);
        $this->command->info('✓ Created vacation');

        $this->command->newLine();
        $this->command->info('=== Demo Data Created Successfully ===');
        $this->command->newLine();
        $this->printCredentials();
    }

    private function createPatients(): array
    {
        $patientsData = [
            [
                'name' => 'محمد أحمد علي',
                'phone' => '01200000001',
                'email' => 'mohamed@example.com',
                'gender' => Gender::MALE,
                'date_of_birth' => '1985-03-20',
                'address' => 'القاهرة، المعادي، شارع 9',
                'profile' => [
                    'blood_type' => BloodType::A_POSITIVE,
                    'emergency_contact_name' => 'فاطمة أحمد',
                    'emergency_contact_phone' => '+201234567890',
                    'allergies' => ['البنسلين', 'الأسبرين'],
                    'chronic_diseases' => ['السكري', 'ضغط الدم'],
                    'current_medications' => ['ميتفورمين 500mg', 'أملوديبين 5mg'],
                    'medical_notes' => 'يحتاج متابعة دورية للسكر وضغط الدم',
                    'insurance_provider' => 'شركة مصر للتأمين',
                    'insurance_number' => 'INS-123456',
                ],
            ],
            [
                'name' => 'فاطمة محمود حسن',
                'phone' => '01200000002',
                'email' => 'fatma@example.com',
                'gender' => Gender::FEMALE,
                'date_of_birth' => '1992-07-10',
                'address' => 'الجيزة، الدقي، شارع التحرير',
                'profile' => [
                    'blood_type' => BloodType::B_NEGATIVE,
                    'emergency_contact_name' => 'أحمد محمود',
                    'emergency_contact_phone' => '+201098765432',
                    'allergies' => ['السلفا'],
                    'chronic_diseases' => ['الربو'],
                    'current_medications' => ['فنتولين بخاخ'],
                    'medical_notes' => 'تعاني من حساسية موسمية',
                    'insurance_provider' => 'AXA للتأمين',
                    'insurance_number' => 'INS-789012',
                ],
            ],
            [
                'name' => 'أحمد سعيد إبراهيم',
                'phone' => '01200000003',
                'email' => 'ahmed.saeed@example.com',
                'gender' => Gender::MALE,
                'date_of_birth' => '1978-11-25',
                'address' => 'الإسكندرية، سموحة',
                'profile' => [
                    'blood_type' => BloodType::O_POSITIVE,
                    'emergency_contact_name' => 'منى سعيد',
                    'emergency_contact_phone' => '+201122334455',
                    'allergies' => null,
                    'chronic_diseases' => ['الكولسترول', 'أمراض القلب'],
                    'current_medications' => ['أتورفاستاتين 20mg', 'أسبرين 81mg'],
                    'medical_notes' => 'خضع لعملية قسطرة منذ سنتين',
                    'insurance_provider' => 'بوبا للتأمين',
                    'insurance_number' => 'INS-456789',
                ],
            ],
            [
                'name' => 'نورا عبدالله محمد',
                'phone' => '01200000004',
                'email' => 'noura@example.com',
                'gender' => Gender::FEMALE,
                'date_of_birth' => '1995-01-15',
                'address' => 'القاهرة، مدينة نصر',
                'profile' => [
                    'blood_type' => BloodType::AB_POSITIVE,
                    'emergency_contact_name' => 'عبدالله محمد',
                    'emergency_contact_phone' => '+201555666777',
                    'allergies' => ['الغلوتين', 'اللاكتوز'],
                    'chronic_diseases' => null,
                    'current_medications' => null,
                    'medical_notes' => 'حساسية غذائية متعددة',
                    'insurance_provider' => null,
                    'insurance_number' => null,
                ],
            ],
            [
                'name' => 'خالد عمر يوسف',
                'phone' => '01200000005',
                'email' => 'khaled@example.com',
                'gender' => Gender::MALE,
                'date_of_birth' => '1988-09-30',
                'address' => 'القاهرة، الزمالك',
                'profile' => [
                    'blood_type' => BloodType::A_NEGATIVE,
                    'emergency_contact_name' => 'سمية عمر',
                    'emergency_contact_phone' => '+201666777888',
                    'allergies' => ['اللاتكس'],
                    'chronic_diseases' => ['الصداع النصفي'],
                    'current_medications' => ['سوماتريبتان عند اللزوم'],
                    'medical_notes' => 'نوبات صداع نصفي متكررة',
                    'insurance_provider' => 'MetLife',
                    'insurance_number' => 'INS-321654',
                ],
            ],
        ];

        $patients = [];
        foreach ($patientsData as $data) {
            $profileData = $data['profile'];
            unset($data['profile']);

            $patient = User::create([
                ...$data,
                'password' => Hash::make('patient123'),
                'role' => UserRole::PATIENT,
                'is_active' => true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]);

            PatientProfile::create([
                'user_id' => $patient->id,
                ...$profileData,
            ]);

            $patients[] = $patient;
        }

        return $patients;
    }

    private function createAppointments(array $patients): array
    {
        $appointments = [];
        $times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00'];
        $reasons = [
            'كشف عام',
            'متابعة دورية',
            'ألم في الصدر',
            'صداع مستمر',
            'فحص دوري',
            'متابعة ضغط الدم',
            'متابعة السكر',
            'آلام في المفاصل',
        ];

        // Today's appointments (various statuses)
        $todayDate = now()->toDateString();

        // Pending appointment for today
        $appointments[] = Appointment::create([
            'user_id' => $patients[0]->id,
            'appointment_date' => $todayDate,
            'appointment_time' => '10:00',
            'status' => AppointmentStatus::PENDING,
            'notes' => 'موعد جديد يحتاج تأكيد',
        ]);

        // Confirmed appointments for today
        $appointments[] = Appointment::create([
            'user_id' => $patients[1]->id,
            'appointment_date' => $todayDate,
            'appointment_time' => '10:30',
            'status' => AppointmentStatus::CONFIRMED,
            'notes' => 'متابعة حالة الربو',
            'confirmed_at' => now()->subHours(2),
        ]);

        $appointments[] = Appointment::create([
            'user_id' => $patients[2]->id,
            'appointment_date' => $todayDate,
            'appointment_time' => '11:00',
            'status' => AppointmentStatus::CONFIRMED,
            'notes' => 'فحص دوري للقلب',
            'admin_notes' => 'مريض قلب - يحتاج عناية خاصة',
            'confirmed_at' => now()->subHours(3),
        ]);

        // Tomorrow's appointments
        $tomorrowDate = now()->addDay()->toDateString();

        $appointments[] = Appointment::create([
            'user_id' => $patients[3]->id,
            'appointment_date' => $tomorrowDate,
            'appointment_time' => '09:00',
            'status' => AppointmentStatus::PENDING,
            'notes' => 'استشارة غذائية',
        ]);

        $appointments[] = Appointment::create([
            'user_id' => $patients[4]->id,
            'appointment_date' => $tomorrowDate,
            'appointment_time' => '09:30',
            'status' => AppointmentStatus::CONFIRMED,
            'notes' => 'متابعة الصداع النصفي',
            'confirmed_at' => now()->subHour(),
        ]);

        // Past completed appointments (last 30 days)
        for ($i = 1; $i <= 20; $i++) {
            $patient = $patients[array_rand($patients)];
            $daysAgo = rand(1, 30);
            $date = now()->subDays($daysAgo)->toDateString();

            $appointments[] = Appointment::create([
                'user_id' => $patient->id,
                'appointment_date' => $date,
                'appointment_time' => $times[array_rand($times)],
                'status' => AppointmentStatus::COMPLETED,
                'notes' => $reasons[array_rand($reasons)],
                'admin_notes' => 'تم الكشف بنجاح',
                'confirmed_at' => now()->subDays($daysAgo)->subHours(rand(1, 5)),
                'completed_at' => now()->subDays($daysAgo),
            ]);
        }

        // Some cancelled appointments
        $appointments[] = Appointment::create([
            'user_id' => $patients[0]->id,
            'appointment_date' => now()->subDays(5)->toDateString(),
            'appointment_time' => '14:00',
            'status' => AppointmentStatus::CANCELLED,
            'notes' => 'كشف عام',
            'cancellation_reason' => 'ظرف طارئ',
            'cancelled_by' => CancelledBy::PATIENT,
            'cancelled_at' => now()->subDays(6),
        ]);

        $appointments[] = Appointment::create([
            'user_id' => $patients[1]->id,
            'appointment_date' => now()->subDays(10)->toDateString(),
            'appointment_time' => '15:00',
            'status' => AppointmentStatus::CANCELLED,
            'notes' => 'متابعة',
            'cancellation_reason' => 'إعادة جدولة بناءً على طلب الطبيب',
            'cancelled_by' => CancelledBy::ADMIN,
            'cancelled_at' => now()->subDays(11),
        ]);

        // No-show appointment
        $appointments[] = Appointment::create([
            'user_id' => $patients[2]->id,
            'appointment_date' => now()->subDays(7)->toDateString(),
            'appointment_time' => '11:30',
            'status' => AppointmentStatus::NO_SHOW,
            'notes' => 'فحص دوري',
            'admin_notes' => 'لم يحضر المريض',
            'confirmed_at' => now()->subDays(8),
        ]);

        // Future appointments (next week)
        for ($i = 2; $i <= 7; $i++) {
            $patient = $patients[array_rand($patients)];
            $date = now()->addDays($i)->toDateString();

            $appointments[] = Appointment::create([
                'user_id' => $patient->id,
                'appointment_date' => $date,
                'appointment_time' => $times[array_rand($times)],
                'status' => rand(0, 1) ? AppointmentStatus::PENDING : AppointmentStatus::CONFIRMED,
                'notes' => $reasons[array_rand($reasons)],
                'confirmed_at' => rand(0, 1) ? now() : null,
            ]);
        }

        return $appointments;
    }

    private function createMedicalRecords(array $appointments): void
    {
        $diagnoses = [
            'التهاب الحلق الحاد',
            'نزلة برد',
            'التهاب الجيوب الأنفية',
            'ارتفاع ضغط الدم',
            'السكري من النوع الثاني - متابعة',
            'التهاب المعدة',
            'الصداع النصفي',
            'آلام أسفل الظهر',
            'التهاب المفاصل',
            'حساسية موسمية',
        ];

        $symptoms = [
            'حمى، سعال، التهاب في الحلق',
            'صداع، دوخة، غثيان',
            'آلام في البطن، قيء',
            'ضيق في التنفس، تعب عام',
            'آلام في المفاصل، تورم',
            'سيلان الأنف، عطس متكرر',
            'ارتفاع في درجة الحرارة',
        ];

        $treatments = [
            'راحة تامة مع تناول السوائل والأدوية الموصوفة',
            'نظام غذائي صحي ومتابعة دورية',
            'علاج دوائي مع متابعة بعد أسبوعين',
            'علاج طبيعي مع مسكنات',
            'مضاد حيوي لمدة 7 أيام',
        ];

        $completedAppointments = array_filter($appointments, fn($a) => $a->status === AppointmentStatus::COMPLETED);

        foreach ($completedAppointments as $appointment) {
            $record = MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->user_id,
                'diagnosis' => $diagnoses[array_rand($diagnoses)],
                'symptoms' => $symptoms[array_rand($symptoms)],
                'examination_notes' => 'تم الفحص السريري الكامل',
                'treatment_plan' => $treatments[array_rand($treatments)],
                'follow_up_date' => rand(0, 1) ? now()->addWeeks(rand(1, 4))->toDateString() : null,
                'follow_up_notes' => rand(0, 1) ? 'متابعة الحالة بعد العلاج' : null,
                'vital_signs' => [
                    'blood_pressure' => rand(110, 140) . '/' . rand(70, 90),
                    'heart_rate' => rand(60, 100),
                    'temperature' => round(rand(365, 385) / 10, 1),
                    'weight' => rand(50, 120),
                    'height' => rand(150, 190),
                ],
            ]);

            // Create prescription for some records
            if (rand(0, 1)) {
                $this->createPrescription($record);
            }
        }
    }

    private function createPrescription(MedicalRecord $record): void
    {
        $prescription = Prescription::create([
            'medical_record_id' => $record->id,
            'notes' => 'يرجى الالتزام بالجرعات المحددة',
            'valid_until' => now()->addMonths(rand(1, 3)),
            'is_dispensed' => rand(0, 1),
            'dispensed_at' => rand(0, 1) ? now()->subDays(rand(1, 7)) : null,
        ]);

        $medications = [
            ['name' => 'أموكسيسيلين', 'dosage' => '500 مجم', 'frequency' => 'ثلاث مرات يومياً', 'duration' => '7 أيام', 'instructions' => 'بعد الأكل'],
            ['name' => 'باراسيتامول', 'dosage' => '500 مجم', 'frequency' => 'عند اللزوم', 'duration' => '5 أيام', 'instructions' => 'كل 6 ساعات عند الحاجة'],
            ['name' => 'أوميبرازول', 'dosage' => '20 مجم', 'frequency' => 'مرة واحدة يومياً', 'duration' => '14 يوم', 'instructions' => 'قبل الفطور'],
            ['name' => 'سيتريزين', 'dosage' => '10 مجم', 'frequency' => 'مرة واحدة يومياً', 'duration' => '10 أيام', 'instructions' => 'قبل النوم'],
            ['name' => 'فيتامين د', 'dosage' => '1000 وحدة', 'frequency' => 'مرة واحدة يومياً', 'duration' => '3 أشهر', 'instructions' => 'مع الإفطار'],
        ];

        $numItems = rand(1, 4);
        $selectedMeds = array_rand($medications, $numItems);
        if (!is_array($selectedMeds)) {
            $selectedMeds = [$selectedMeds];
        }

        foreach ($selectedMeds as $index) {
            $med = $medications[$index];
            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medication_name' => $med['name'],
                'dosage' => $med['dosage'],
                'frequency' => $med['frequency'],
                'duration' => $med['duration'],
                'instructions' => $med['instructions'],
                'quantity' => rand(1, 30),
            ]);
        }
    }

    private function createPayments(array $appointments): void
    {
        $completedAppointments = array_filter($appointments, fn($a) => $a->status === AppointmentStatus::COMPLETED);

        foreach ($completedAppointments as $appointment) {
            $amount = rand(15, 50) * 10; // 150-500 in increments of 10
            $discount = rand(0, 3) ? 0 : rand(1, 5) * 10; // Some discounts
            $total = $amount - $discount;

            $isPaid = rand(0, 4) > 0; // 80% paid

            Payment::create([
                'appointment_id' => $appointment->id,
                'amount' => $amount,
                'discount' => $discount,
                'total' => $total,
                'method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())],
                'status' => $isPaid ? PaymentStatus::PAID : PaymentStatus::PENDING,
                'transaction_id' => $isPaid ? 'TXN-' . rand(10000000, 99999999) : null,
                'notes' => $discount > 0 ? 'خصم للمرضى المتكررين' : null,
                'paid_at' => $isPaid ? $appointment->completed_at : null,
            ]);
        }

        // Add some refunded payments
        $paidAppointments = array_slice(array_filter($appointments, fn($a) => $a->status === AppointmentStatus::COMPLETED), 0, 2);
        foreach ($paidAppointments as $appointment) {
            // Check if payment already exists
            if (Payment::where('appointment_id', $appointment->id)->exists()) {
                continue;
            }

            Payment::create([
                'appointment_id' => $appointment->id,
                'amount' => 200,
                'discount' => 0,
                'total' => 200,
                'method' => PaymentMethod::CARD,
                'status' => PaymentStatus::REFUNDED,
                'transaction_id' => 'TXN-REF-' . rand(10000000, 99999999),
                'notes' => 'تم الاسترداد بسبب إلغاء الخدمة',
                'paid_at' => now()->subWeek(),
                'refunded_at' => now()->subDays(3),
            ]);
        }
    }

    private function createNotifications(array $patients): void
    {
        $notificationTypes = [
            [
                'type' => 'App\\Notifications\\AppointmentConfirmed',
                'data' => ['title' => 'تأكيد الموعد', 'message' => 'تم تأكيد موعدك بنجاح'],
            ],
            [
                'type' => 'App\\Notifications\\AppointmentReminder',
                'data' => ['title' => 'تذكير بالموعد', 'message' => 'لديك موعد غداً الساعة 10:00 صباحاً'],
            ],
            [
                'type' => 'App\\Notifications\\PrescriptionReady',
                'data' => ['title' => 'روشتة جاهزة', 'message' => 'روشتتك الطبية جاهزة للتحميل'],
            ],
        ];

        foreach ($patients as $patient) {
            $numNotifications = rand(2, 5);
            for ($i = 0; $i < $numNotifications; $i++) {
                $notif = $notificationTypes[array_rand($notificationTypes)];
                DatabaseNotification::create([
                    'id' => Str::uuid()->toString(),
                    'type' => $notif['type'],
                    'notifiable_type' => User::class,
                    'notifiable_id' => $patient->id,
                    'data' => $notif['data'],
                    'read_at' => rand(0, 1) ? now()->subHours(rand(1, 48)) : null,
                    'created_at' => now()->subDays(rand(0, 7)),
                    'updated_at' => now()->subDays(rand(0, 7)),
                ]);
            }
        }
    }

    private function printCredentials(): void
    {
        $this->command->table(
            ['الدور', 'رقم الهاتف', 'كلمة المرور', 'الملاحظات'],
            [
                ['Admin (طبيب)', '01000000000', 'admin123', 'صلاحيات كاملة'],
                ['Secretary (سكرتارية)', '01100000000', 'secretary123', 'إدارة المواعيد'],
                ['Patient 1', '01200000001', 'patient123', 'محمد - سكري وضغط'],
                ['Patient 2', '01200000002', 'patient123', 'فاطمة - ربو'],
                ['Patient 3', '01200000003', 'patient123', 'أحمد - أمراض قلب'],
                ['Patient 4', '01200000004', 'patient123', 'نورا - حساسية غذائية'],
                ['Patient 5', '01200000005', 'patient123', 'خالد - صداع نصفي'],
            ]
        );
    }
}
