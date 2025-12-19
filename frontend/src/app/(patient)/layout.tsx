import { PatientLayout } from '@/components/layouts/PatientLayout';

export default function PatientRootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <PatientLayout>{children}</PatientLayout>;
}
