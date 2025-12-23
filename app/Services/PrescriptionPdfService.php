<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\ClinicSetting;
use App\Models\Prescription;
use App\Traits\LogsActivity;
use Barryvdh\DomPDF\Facade\Pdf;

class PrescriptionPdfService
{
    use LogsActivity;

    public function generate(Prescription $prescription): \Barryvdh\DomPDF\PDF
    {
        $this->logInfo('Generating prescription PDF', [
            'prescription_id' => $prescription->id,
            'prescription_number' => $prescription->prescription_number,
        ]);

        $prescription->load(['medicalRecord.patient', 'medicalRecord.appointment', 'items']);

        // Validate prescription has required data
        if (!$prescription->medicalRecord) {
            throw new BusinessLogicException(
                __('الوصفة غير مرتبطة بسجل طبي'),
                'PRESCRIPTION_NO_MEDICAL_RECORD',
                ['prescription_id' => $prescription->id]
            );
        }

        if (!$prescription->medicalRecord->patient) {
            throw new BusinessLogicException(
                __('السجل الطبي غير مرتبط بمريض'),
                'MEDICAL_RECORD_NO_PATIENT',
                ['prescription_id' => $prescription->id]
            );
        }

        $clinicSetting = ClinicSetting::getInstance();

        $data = [
            'prescription' => $prescription,
            'patient' => $prescription->medicalRecord->patient,
            'appointment' => $prescription->medicalRecord->appointment,
            'medicalRecord' => $prescription->medicalRecord,
            'items' => $prescription->items,
            'clinic' => [
                'name' => $clinicSetting->getSetting('clinic_name', config('clinic.defaults.clinic_name', 'العيادة')),
                'address' => $clinicSetting->getSetting('clinic_address', ''),
                'phone' => $clinicSetting->getSetting('clinic_phone', ''),
                'email' => $clinicSetting->getSetting('clinic_email', ''),
            ],
        ];

        $pdf = Pdf::loadView('pdfs.prescription', $data);

        $pdf->setPaper('a4');

        $this->logInfo('Prescription PDF generated successfully', [
            'prescription_id' => $prescription->id,
        ]);

        return $pdf;
    }

    public function download(Prescription $prescription)
    {
        $pdf = $this->generate($prescription);

        return $pdf->download("prescription-{$prescription->prescription_number}.pdf");
    }

    public function stream(Prescription $prescription)
    {
        $pdf = $this->generate($prescription);

        return $pdf->stream("prescription-{$prescription->prescription_number}.pdf");
    }
}
