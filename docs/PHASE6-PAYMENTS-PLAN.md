# Phase 6: Payments System - Implementation Plan

> **Status: COMPLETED**
> All tasks in this phase have been successfully implemented and tested.

## Overview
Payment tracking system for clinic appointments with cash, card, and wallet support.

## Database Schema

### payments table
```
- id
- appointment_id (FK)
- amount (decimal)
- discount (decimal)
- total (decimal)
- method (enum: cash, card, wallet)
- status (enum: pending, paid, refunded)
- transaction_id (nullable)
- notes (nullable)
- paid_at (timestamp, nullable)
- timestamps
```

## Enums

### PaymentMethod
- CASH
- CARD
- WALLET

### PaymentStatus
- PENDING
- PAID
- REFUNDED

## Model Features

### Payment Model
- Relationships: belongsTo Appointment
- Accessors: formatted_amount, formatted_total, status_label
- Scopes: paid, pending, refunded, forPatient, forDateRange
- Methods: markAsPaid, refund

## API Endpoints

### Admin Payment APIs
- GET /api/admin/payments - List all payments (with filters)
- GET /api/admin/payments/statistics - Payment statistics
- GET /api/admin/payments/report - Revenue report
- POST /api/admin/appointments/{appointment}/payment - Create payment
- GET /api/admin/payments/{payment} - View payment
- PUT /api/admin/payments/{payment} - Update payment
- POST /api/admin/payments/{payment}/refund - Refund payment

## Services

### PaymentService
- createPayment(Appointment, data)
- updatePayment(Payment, data)
- markAsPaid(Payment)
- refund(Payment, reason)
- calculateTotal(amount, discount)
- getStatistics(dateRange)
- getRevenueReport(dateRange)

## Tests Required
- Unit: PaymentTest, PaymentMethodTest, PaymentStatusTest
- Feature: AdminPaymentTest
