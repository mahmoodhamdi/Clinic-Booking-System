import Link from 'next/link';
import Image from 'next/image';
import { getTranslations, getLocale } from 'next-intl/server';
import { Heart, Calendar, Clock, Phone, Mail, MapPin, ArrowRight, ArrowLeft } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';
import { ThemeToggle } from '@/components/shared/ThemeToggle';
import { getPublicClinicInfo } from '@/lib/api/public';
import type { ClinicService } from '@/types';

const FALLBACK_SERVICES: ClinicService[] = [
  { title: 'general_consultation', description: 'general_consultation_desc' },
  { title: 'follow_up', description: 'follow_up_desc' },
  { title: 'preventive_care', description: 'preventive_care_desc' },
];

export default async function LandingPage() {
  const t = await getTranslations('landing');
  const tCommon = await getTranslations('common');
  const locale = await getLocale();
  const clinic = await getPublicClinicInfo();
  const Forward = locale === 'ar' ? ArrowLeft : ArrowRight;

  const clinicName = clinic?.clinic_name || tCommon('appName');
  const doctorName = clinic?.doctor_name || '';
  const specialization = clinic?.specialization;
  const tagline = clinic?.tagline || t('defaultTagline');
  const services = clinic?.services?.length ? clinic.services : FALLBACK_SERVICES;
  const isFallbackServices = !clinic?.services?.length;
  const phone = clinic?.phone;
  const email = clinic?.email;
  const address = clinic?.address;
  const aboutText = clinic?.about_text;
  const heroImageUrl = clinic?.hero_image_url;
  const logoUrl = clinic?.logo_url;

  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* Header */}
      <header className="px-4 sm:px-8 py-4 flex justify-between items-center border-b border-border/50">
        <Link href="/" className="flex items-center gap-2.5">
          {logoUrl ? (
            <Image
              src={logoUrl}
              alt={clinicName}
              width={36}
              height={36}
              className="rounded-xl object-cover"
            />
          ) : (
            <div className="h-9 w-9 rounded-xl bg-gradient-primary flex items-center justify-center shadow-primary">
              <Heart className="h-4.5 w-4.5 text-white" fill="white" />
            </div>
          )}
          <span className="text-lg font-bold text-foreground">{clinicName}</span>
        </Link>
        <div className="flex items-center gap-2">
          <ThemeToggle />
          <LanguageSwitcher />
          <Button asChild variant="outline" size="sm">
            <Link href="/login">{tCommon('login')}</Link>
          </Button>
          <Button asChild size="sm">
            <Link href="/register">{t('bookNow')}</Link>
          </Button>
        </div>
      </header>

      {/* Hero */}
      <section className="px-4 sm:px-8 py-12 sm:py-20 relative overflow-hidden">
        <div className="fixed inset-0 overflow-hidden pointer-events-none -z-10">
          <div className="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-primary/5 blur-3xl" />
          <div className="absolute top-1/2 -start-24 w-80 h-80 rounded-full bg-info/5 blur-3xl" />
        </div>
        <div className="max-w-6xl mx-auto grid md:grid-cols-2 gap-10 items-center">
          <div className="space-y-6">
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-medium">
              <Heart className="h-4 w-4" fill="currentColor" />
              <span>{specialization || t('familyMedicine')}</span>
            </div>
            <h1 className="text-4xl sm:text-5xl font-bold leading-tight text-foreground">
              {clinicName}
              {doctorName && (
                <span className="block text-2xl sm:text-3xl text-muted-foreground mt-3 font-medium">
                  {doctorName}
                </span>
              )}
            </h1>
            <p className="text-lg text-muted-foreground">{tagline}</p>
            <div className="flex flex-wrap gap-3">
              <Button asChild size="lg">
                <Link href="/register">
                  {t('bookAppointment')}
                  <Forward className="h-4 w-4 ms-2" />
                </Link>
              </Button>
              <Button asChild variant="outline" size="lg">
                <Link href="/login">{tCommon('login')}</Link>
              </Button>
            </div>
          </div>
          <div className="relative aspect-[4/3] rounded-2xl overflow-hidden bg-gradient-to-br from-primary/20 to-info/10 shadow-xl">
            {heroImageUrl ? (
              <Image
                src={heroImageUrl}
                alt={clinicName}
                fill
                priority
                className="object-cover"
                sizes="(max-width: 768px) 100vw, 50vw"
              />
            ) : (
              <div className="absolute inset-0 flex items-center justify-center">
                <Heart className="h-32 w-32 text-primary/30" fill="currentColor" />
              </div>
            )}
          </div>
        </div>
      </section>

      {/* How it works */}
      <section className="px-4 sm:px-8 py-12 bg-muted/30 border-y border-border/50">
        <div className="max-w-5xl mx-auto">
          <h2 className="text-2xl sm:text-3xl font-bold text-center mb-10">{t('howItWorksTitle')}</h2>
          <div className="grid md:grid-cols-3 gap-6">
            {(['register', 'pickSlot', 'confirm'] as const).map((step, i) => (
              <div key={step} className="bg-card rounded-2xl p-6 border border-border/50 text-center">
                <div className="h-12 w-12 mx-auto mb-4 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg">
                  {i + 1}
                </div>
                <h3 className="font-semibold mb-2">{t(`step_${step}_title`)}</h3>
                <p className="text-sm text-muted-foreground">{t(`step_${step}_desc`)}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Services */}
      <section className="px-4 sm:px-8 py-12">
        <div className="max-w-5xl mx-auto">
          <h2 className="text-2xl sm:text-3xl font-bold text-center mb-10">{t('servicesTitle')}</h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {services.map((service, i) => (
              <div key={i} className="rounded-2xl p-6 border border-border/50 hover:shadow-md transition-shadow">
                <div className="h-10 w-10 mb-4 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                  <Heart className="h-5 w-5" />
                </div>
                <h3 className="font-semibold mb-2">{isFallbackServices ? t(service.title) : service.title}</h3>
                {service.description && (
                  <p className="text-sm text-muted-foreground">
                    {isFallbackServices ? t(service.description) : service.description}
                  </p>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* About */}
      {aboutText && (
        <section className="px-4 sm:px-8 py-12 bg-muted/30 border-y border-border/50">
          <div className="max-w-3xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-bold text-center mb-6">{t('aboutTitle')}</h2>
            <p className="text-muted-foreground whitespace-pre-line text-center leading-relaxed">
              {aboutText}
            </p>
          </div>
        </section>
      )}

      {/* Contact + CTA */}
      <section className="px-4 sm:px-8 py-12">
        <div className="max-w-5xl mx-auto grid md:grid-cols-2 gap-10 items-start">
          <div className="space-y-4">
            <h2 className="text-2xl sm:text-3xl font-bold">{t('contactTitle')}</h2>
            {phone && (
              <a href={`tel:${phone}`} className="flex items-center gap-3 text-foreground hover:text-primary transition-colors">
                <Phone className="h-5 w-5 text-primary" />
                <span className="font-medium" dir="ltr">{phone}</span>
              </a>
            )}
            {email && (
              <a href={`mailto:${email}`} className="flex items-center gap-3 text-foreground hover:text-primary transition-colors">
                <Mail className="h-5 w-5 text-primary" />
                <span>{email}</span>
              </a>
            )}
            {address && (
              <div className="flex items-start gap-3 text-foreground">
                <MapPin className="h-5 w-5 text-primary mt-0.5" />
                <span className="whitespace-pre-line">{address}</span>
              </div>
            )}
          </div>
          <div className="bg-gradient-primary rounded-2xl p-8 text-white text-center shadow-primary">
            <Calendar className="h-12 w-12 mx-auto mb-4 opacity-90" />
            <h3 className="text-xl font-bold mb-2">{t('readyToBookTitle')}</h3>
            <p className="opacity-90 mb-6">{t('readyToBookDesc')}</p>
            <Button asChild size="lg" variant="secondary" className="bg-white text-primary hover:bg-white/90">
              <Link href="/register">
                {t('bookNow')}
                <Forward className="h-4 w-4 ms-2" />
              </Link>
            </Button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="px-4 sm:px-8 py-6 border-t border-border/50 mt-auto">
        <div className="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-muted-foreground">
          <div className="flex items-center gap-2">
            <Clock className="h-4 w-4" />
            <span>{t('footerHours')}</span>
          </div>
          <span>© {new Date().getFullYear()} {clinicName}</span>
        </div>
      </footer>
    </div>
  );
}
