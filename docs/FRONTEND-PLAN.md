# Next.js Frontend - Full Implementation Plan

## Project Overview

Build a modern, responsive Next.js frontend for the Clinic Booking System API with:
- **Framework**: Next.js 14+ (App Router)
- **Language**: TypeScript
- **Styling**: Tailwind CSS + shadcn/ui
- **State Management**: Zustand / React Query
- **Forms**: React Hook Form + Zod
- **Internationalization**: next-intl (Arabic/English)
- **Charts**: Recharts
- **PDF Viewer**: react-pdf

---

## Project Structure

```
frontend/
├── app/
│   ├── [locale]/
│   │   ├── (auth)/
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   ├── forgot-password/
│   │   │   ├── verify-otp/
│   │   │   └── reset-password/
│   │   ├── (patient)/
│   │   │   ├── dashboard/
│   │   │   ├── appointments/
│   │   │   ├── book/
│   │   │   ├── medical-records/
│   │   │   ├── prescriptions/
│   │   │   ├── notifications/
│   │   │   └── profile/
│   │   ├── (admin)/
│   │   │   └── admin/
│   │   │       ├── dashboard/
│   │   │       ├── appointments/
│   │   │       ├── patients/
│   │   │       ├── medical-records/
│   │   │       ├── prescriptions/
│   │   │       ├── payments/
│   │   │       ├── reports/
│   │   │       ├── schedules/
│   │   │       ├── vacations/
│   │   │       └── settings/
│   │   ├── layout.tsx
│   │   └── page.tsx
│   ├── api/
│   │   └── [...proxy]/
│   └── globals.css
├── components/
│   ├── ui/                    # shadcn/ui components
│   ├── layouts/
│   │   ├── AuthLayout.tsx
│   │   ├── PatientLayout.tsx
│   │   └── AdminLayout.tsx
│   ├── auth/
│   ├── patient/
│   ├── admin/
│   └── shared/
├── lib/
│   ├── api/
│   │   ├── client.ts          # Axios instance
│   │   ├── auth.ts
│   │   ├── appointments.ts
│   │   ├── patients.ts
│   │   ├── medical-records.ts
│   │   ├── prescriptions.ts
│   │   ├── payments.ts
│   │   ├── notifications.ts
│   │   └── admin/
│   ├── hooks/
│   ├── stores/
│   ├── utils/
│   └── validations/
├── messages/
│   ├── ar.json
│   └── en.json
├── public/
├── types/
└── middleware.ts
```

---

## Phase 1: Project Setup & Authentication

### 1.1 Project Initialization
- [ ] Create Next.js 14 project with TypeScript
- [ ] Configure Tailwind CSS
- [ ] Install and configure shadcn/ui
- [ ] Setup ESLint & Prettier
- [ ] Configure path aliases

### 1.2 Core Configuration
- [ ] Setup environment variables (.env.local)
- [ ] Configure next-intl for Arabic/English
- [ ] Setup RTL support for Arabic
- [ ] Create Axios API client with interceptors
- [ ] Configure React Query

### 1.3 Authentication Pages
- [ ] Login page (phone + password)
- [ ] Registration page
- [ ] Forgot password page
- [ ] OTP verification page
- [ ] Reset password page

### 1.4 Auth State Management
- [ ] Create auth store (Zustand)
- [ ] Token management (localStorage/cookies)
- [ ] Auto-refresh token logic
- [ ] Protected route middleware
- [ ] Role-based access control

### 1.5 Shared Components
- [ ] Loading spinner
- [ ] Toast notifications
- [ ] Error boundary
- [ ] Empty state
- [ ] Confirmation dialog

**Deliverables:**
- Working authentication flow
- Protected routes
- Language switcher (AR/EN)
- RTL/LTR support

---

## Phase 2: Patient Portal - Core Features

### 2.1 Patient Layout
- [ ] Responsive sidebar/navbar
- [ ] User menu (profile, logout)
- [ ] Notifications dropdown
- [ ] Mobile navigation
- [ ] Breadcrumbs

### 2.2 Patient Dashboard
- [ ] Welcome message
- [ ] Upcoming appointments card
- [ ] Recent medical records
- [ ] Quick action buttons
- [ ] Statistics overview

### 2.3 Appointment Booking
- [ ] Calendar date picker
- [ ] Available slots grid
- [ ] Slot selection
- [ ] Booking confirmation
- [ ] Success/error handling

### 2.4 My Appointments
- [ ] Appointments list with filters
- [ ] Status badges (pending, confirmed, completed, cancelled)
- [ ] Appointment details modal
- [ ] Cancel appointment functionality
- [ ] Empty state

### 2.5 Profile Management
- [ ] View profile
- [ ] Edit profile form
- [ ] Avatar upload
- [ ] Change password
- [ ] Medical info (blood type, allergies, etc.)

**Deliverables:**
- Complete patient booking flow
- Appointment management
- Profile management

---

## Phase 3: Patient Portal - Medical Features

### 3.1 Medical Records
- [ ] Records list with pagination
- [ ] Record details view
- [ ] Vital signs display
- [ ] Diagnosis & notes
- [ ] Attachments viewer

### 3.2 Prescriptions
- [ ] Prescriptions list
- [ ] Prescription details
- [ ] Medication items display
- [ ] PDF download button
- [ ] Status badge (dispensed/pending)

### 3.3 Notifications
- [ ] Notifications list
- [ ] Mark as read
- [ ] Mark all as read
- [ ] Delete notification
- [ ] Unread count badge
- [ ] Real-time updates (optional)

### 3.4 Patient History
- [ ] Timeline view
- [ ] Filter by type
- [ ] Date range filter
- [ ] Export functionality

**Deliverables:**
- Complete medical records view
- Prescriptions management
- Notifications system

---

## Phase 4: Admin Dashboard - Core

### 4.1 Admin Layout
- [ ] Collapsible sidebar
- [ ] Top navbar with search
- [ ] User menu
- [ ] Notifications
- [ ] Mobile responsive

### 4.2 Main Dashboard
- [ ] Statistics cards (patients, appointments, revenue)
- [ ] Today's appointments
- [ ] Appointments chart (weekly/monthly)
- [ ] Revenue chart
- [ ] Recent activity feed
- [ ] Quick actions

### 4.3 Dashboard Widgets
- [ ] Upcoming appointments widget
- [ ] Pending confirmations widget
- [ ] Follow-ups due widget
- [ ] Today's schedule widget

**Deliverables:**
- Admin dashboard with charts
- Real-time statistics
- Activity feed

---

## Phase 5: Admin - Appointments Management

### 5.1 Appointments List
- [ ] Data table with sorting
- [ ] Filters (status, date range, patient)
- [ ] Search functionality
- [ ] Pagination
- [ ] Bulk actions

### 5.2 Appointment Details
- [ ] Full appointment info
- [ ] Patient info card
- [ ] Status actions (confirm, complete, cancel, no-show)
- [ ] Notes editing
- [ ] Payment info

### 5.3 Calendar View
- [ ] Monthly calendar
- [ ] Daily schedule view
- [ ] Drag & drop (optional)
- [ ] Color-coded status

### 5.4 Today's Appointments
- [ ] Queue view
- [ ] Quick status updates
- [ ] Patient check-in
- [ ] Time tracking

**Deliverables:**
- Complete appointments management
- Calendar integration
- Status workflow

---

## Phase 6: Admin - Patients Management

### 6.1 Patients List
- [ ] Data table with search
- [ ] Filters (status, date registered)
- [ ] Quick view
- [ ] Export to CSV

### 6.2 Patient Profile
- [ ] Personal information
- [ ] Medical information
- [ ] Contact & emergency info
- [ ] Edit functionality
- [ ] Status toggle (active/inactive)

### 6.3 Patient History
- [ ] Appointments tab
- [ ] Medical records tab
- [ ] Prescriptions tab
- [ ] Payments tab
- [ ] Notes section

### 6.4 Patient Statistics
- [ ] Visit frequency
- [ ] Payment history
- [ ] Treatment overview

**Deliverables:**
- Patient management system
- Complete patient profiles
- Patient history tracking

---

## Phase 7: Admin - Medical Records & Prescriptions

### 7.1 Medical Records Management
- [ ] Records list with filters
- [ ] Create new record form
- [ ] Vital signs input
- [ ] Diagnosis & notes editor
- [ ] Follow-up scheduling

### 7.2 Attachments
- [ ] File upload (images, PDFs, documents)
- [ ] File preview
- [ ] Download functionality
- [ ] Delete with confirmation

### 7.3 Prescriptions Management
- [ ] Prescriptions list
- [ ] Create prescription form
- [ ] Add medication items
- [ ] Dosage & instructions
- [ ] PDF preview
- [ ] Mark as dispensed

### 7.4 Follow-ups
- [ ] Due follow-ups list
- [ ] Schedule follow-up
- [ ] Follow-up reminders

**Deliverables:**
- Medical records CRUD
- File attachments
- Prescription generation

---

## Phase 8: Admin - Payments & Reports

### 8.1 Payments Management
- [ ] Payments list with filters
- [ ] Create payment form
- [ ] Payment methods (cash, card, wallet)
- [ ] Apply discounts
- [ ] Mark as paid
- [ ] Process refunds

### 8.2 Payment Statistics
- [ ] Revenue overview
- [ ] Payment methods breakdown
- [ ] Pending payments
- [ ] Refunds summary

### 8.3 Reports
- [ ] Revenue report with date range
- [ ] Appointments report
- [ ] Patients report
- [ ] Charts & visualizations
- [ ] Export to PDF

### 8.4 Report Filters
- [ ] Date range picker
- [ ] Category filters
- [ ] Comparison views

**Deliverables:**
- Payments management
- Financial reports
- PDF export

---

## Phase 9: Admin - Settings & Configuration

### 9.1 Clinic Settings
- [ ] Clinic name & info
- [ ] Contact information
- [ ] Logo upload
- [ ] Slot duration
- [ ] Max patients per slot
- [ ] Advance booking days
- [ ] Cancellation policy

### 9.2 Schedules Management
- [ ] Weekly schedule grid
- [ ] Day-wise time slots
- [ ] Toggle days on/off
- [ ] Break times
- [ ] Special hours

### 9.3 Vacations Management
- [ ] Vacations calendar
- [ ] Add vacation period
- [ ] Vacation reasons
- [ ] Affected appointments

### 9.4 User Management (Optional)
- [ ] Staff list
- [ ] Add new staff
- [ ] Role assignment
- [ ] Permissions

**Deliverables:**
- Clinic configuration
- Schedule management
- Vacation planning

---

## Phase 10: Advanced Features

### 10.1 Real-time Notifications
- [ ] WebSocket integration
- [ ] Push notifications
- [ ] Sound alerts
- [ ] Desktop notifications

### 10.2 Search & Filters
- [ ] Global search
- [ ] Advanced filters
- [ ] Saved filters
- [ ] Recent searches

### 10.3 Data Export
- [ ] CSV export
- [ ] PDF export
- [ ] Print functionality

### 10.4 Performance Optimization
- [ ] Image optimization
- [ ] Code splitting
- [ ] Caching strategies
- [ ] Bundle analysis

### 10.5 PWA Features
- [ ] Service worker
- [ ] Offline support
- [ ] Install prompt
- [ ] Background sync

**Deliverables:**
- Real-time features
- Export functionality
- PWA support

---

## Phase 11: Testing & Quality

### 11.1 Unit Tests
- [ ] Component tests (Jest + React Testing Library)
- [ ] Hook tests
- [ ] Utility function tests
- [ ] Store tests

### 11.2 Integration Tests
- [ ] API integration tests
- [ ] Form submission tests
- [ ] Navigation tests

### 11.3 E2E Tests
- [ ] Playwright/Cypress setup
- [ ] Auth flow tests
- [ ] Booking flow tests
- [ ] Admin workflow tests

### 11.4 Accessibility
- [ ] ARIA labels
- [ ] Keyboard navigation
- [ ] Screen reader testing
- [ ] Color contrast

**Deliverables:**
- Test coverage > 80%
- Accessibility compliance
- E2E test suite

---

## Phase 12: Deployment & DevOps

### 12.1 Build Configuration
- [ ] Production build optimization
- [ ] Environment variables
- [ ] API URL configuration

### 12.2 Docker Setup
- [ ] Dockerfile for frontend
- [ ] Docker Compose integration
- [ ] Nginx configuration

### 12.3 CI/CD Pipeline
- [ ] GitHub Actions workflow
- [ ] Automated testing
- [ ] Automated deployment
- [ ] Preview deployments

### 12.4 Monitoring
- [ ] Error tracking (Sentry)
- [ ] Analytics
- [ ] Performance monitoring

**Deliverables:**
- Production-ready build
- Docker containerization
- CI/CD pipeline

---

## Tech Stack Summary

| Category | Technology |
|----------|------------|
| Framework | Next.js 14 (App Router) |
| Language | TypeScript |
| Styling | Tailwind CSS |
| UI Components | shadcn/ui |
| Icons | Lucide Icons |
| State Management | Zustand |
| Server State | React Query (TanStack Query) |
| Forms | React Hook Form |
| Validation | Zod |
| HTTP Client | Axios |
| i18n | next-intl |
| Charts | Recharts |
| Tables | TanStack Table |
| Date Picker | react-day-picker |
| PDF | react-pdf / @react-pdf/renderer |
| Testing | Jest, React Testing Library, Playwright |

---

## API Integration

### Base Configuration
```typescript
// lib/api/client.ts
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add auth token interceptor
api.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  config.headers['Accept-Language'] = getLocale();
  return config;
});
```

---

## Folder Conventions

```
components/
├── ComponentName/
│   ├── index.tsx           # Main component
│   ├── ComponentName.tsx   # Component implementation
│   ├── ComponentName.test.tsx
│   └── types.ts            # Component types
```

---

## Timeline Estimate

| Phase | Description | Duration |
|-------|-------------|----------|
| 1 | Setup & Authentication | 3-4 days |
| 2 | Patient Portal Core | 4-5 days |
| 3 | Patient Medical Features | 3-4 days |
| 4 | Admin Dashboard Core | 3-4 days |
| 5 | Admin Appointments | 4-5 days |
| 6 | Admin Patients | 3-4 days |
| 7 | Medical Records & Prescriptions | 4-5 days |
| 8 | Payments & Reports | 4-5 days |
| 9 | Settings & Configuration | 3-4 days |
| 10 | Advanced Features | 4-5 days |
| 11 | Testing | 3-4 days |
| 12 | Deployment | 2-3 days |

**Total Estimate: 40-52 days**

---

## Getting Started

```bash
# Create project
npx create-next-app@latest frontend --typescript --tailwind --eslint --app

# Install dependencies
cd frontend
npm install @tanstack/react-query axios zustand zod react-hook-form
npm install @radix-ui/react-* lucide-react recharts
npm install next-intl
npx shadcn-ui@latest init

# Start development
npm run dev
```

---

## Notes

1. **RTL Support**: All components must support RTL for Arabic language
2. **Responsive Design**: Mobile-first approach
3. **Accessibility**: WCAG 2.1 AA compliance
4. **Performance**: Core Web Vitals optimization
5. **Security**: XSS prevention, CSRF protection, secure token storage
