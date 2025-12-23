<?php

namespace App\Http\Traits;

/**
 * Trait to standardize eager loading relationships across controllers.
 * This helps prevent N+1 query issues by providing consistent relation sets.
 */
trait EagerLoadsRelations
{
    /**
     * Get the standard relations to eager load for appointments.
     */
    protected function getAppointmentRelations(): array
    {
        return ['user', 'payment', 'medicalRecord'];
    }

    /**
     * Get the standard relations for appointment listings (lighter version).
     */
    protected function getAppointmentListRelations(): array
    {
        return ['user'];
    }

    /**
     * Get the standard relations for medical records.
     */
    protected function getMedicalRecordRelations(): array
    {
        return ['user', 'appointment', 'prescriptions.items', 'attachments'];
    }

    /**
     * Get the standard relations for medical record listings.
     */
    protected function getMedicalRecordListRelations(): array
    {
        return ['user', 'appointment'];
    }

    /**
     * Get the standard relations for patients.
     */
    protected function getPatientRelations(): array
    {
        return ['profile'];
    }

    /**
     * Get the standard relations for patient detail view.
     */
    protected function getPatientDetailRelations(): array
    {
        return [
            'profile',
            'appointments' => fn ($q) => $q->latest('appointment_date')->limit(10),
        ];
    }

    /**
     * Get the standard relations for prescriptions.
     */
    protected function getPrescriptionRelations(): array
    {
        return ['items', 'user', 'medicalRecord'];
    }

    /**
     * Get the standard relations for prescription listings.
     */
    protected function getPrescriptionListRelations(): array
    {
        return ['items', 'user'];
    }

    /**
     * Get the standard relations for payments.
     */
    protected function getPaymentRelations(): array
    {
        return ['appointment.user'];
    }
}
