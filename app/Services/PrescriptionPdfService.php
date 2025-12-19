<?php

namespace App\Services;

use App\Models\ClinicSetting;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;

class PrescriptionPdfService
{
    public function generate(Prescription $prescription): \Barryvdh\DomPDF\PDF
    {
        $prescription->load(['medicalRecord.patient', 'medicalRecord.appointment', 'items']);

        $clinicSetting = ClinicSetting::getInstance();

        $data = [
            'prescription' => $prescription,
            'patient' => $prescription->medicalRecord->patient,
            'appointment' => $prescription->medicalRecord->appointment,
            'medicalRecord' => $prescription->medicalRecord,
            'items' => $prescription->items,
            'clinic' => [
                'name' => $clinicSetting->getSetting('clinic_name', 'العيادة'),
                'address' => $clinicSetting->getSetting('clinic_address', ''),
                'phone' => $clinicSetting->getSetting('clinic_phone', ''),
                'email' => $clinicSetting->getSetting('clinic_email', ''),
            ],
        ];

        $pdf = Pdf::loadView('pdfs.prescription', $data);

        $pdf->setPaper('a4');

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
