# Phase 7: Notifications System - Implementation Plan

## Overview
In-app notification system for appointments and system events.

## Database Schema

### notifications table (using Laravel's default)
```
- id (uuid)
- type
- notifiable_type
- notifiable_id
- data (json)
- read_at (timestamp, nullable)
- created_at
- updated_at
```

## Notification Types

### AppointmentConfirmed
- Sent when appointment is confirmed by admin
- Data: appointment_id, appointment_date, appointment_time

### AppointmentReminder
- Sent 24 hours before appointment
- Data: appointment_id, appointment_date, appointment_time

### AppointmentCancelled
- Sent when appointment is cancelled
- Data: appointment_id, cancelled_by, reason

### PrescriptionReady
- Sent when prescription is created
- Data: prescription_id, medical_record_id

## API Endpoints

### Patient Notification APIs
- GET /api/notifications - List my notifications
- GET /api/notifications/unread-count - Get unread count
- POST /api/notifications/{id}/read - Mark as read
- POST /api/notifications/read-all - Mark all as read
- DELETE /api/notifications/{id} - Delete notification

## Services

### NotificationService
- send(User, Notification)
- sendAppointmentConfirmed(Appointment)
- sendAppointmentReminder(Appointment)
- sendAppointmentCancelled(Appointment, cancelledBy, reason)
- sendPrescriptionReady(Prescription)
- getUnreadCount(User)
- markAsRead(Notification)
- markAllAsRead(User)

## Tests Required
- Unit: NotificationServiceTest
- Feature: NotificationTest
