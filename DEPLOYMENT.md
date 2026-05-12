# Customer Deployment Guide — Two Scenarios

> هذا المستند مكمّل لـ `DEPLOY.md` التقني. الـ DEPLOY.md فيه التفاصيل الكاملة
> (Nginx، Docker، migrations، backups...). الملف ده بيوضح **سيناريوهين** للتسليم
> ومين بيعمل إيه في كل سيناريو.

---

## Scenario A — العميل عنده infrastructure

العيادة أو الشركة عندهم سيرفر أو VPS، وفريق تقني داخلي بيتولى الـ
deployment. دورنا هنا: تجهيز كامل، تدريب، support بعد التسليم.

### ما يقدمه العميل
- **VPS / dedicated server** (يفي بمتطلبات النظام في DEPLOY.md §1)
- **Domain name** + DNS access لإضافة A/AAAA records
- **MySQL database** (ممكن نفس السيرفر أو خدمة managed زي AWS RDS)
- **SMTP credentials** للإيميل (Postmark / Mailgun / SES — اختيار العميل)
- **SMS provider credentials** (Vonage موصى به للمنطقة العربية)
- **SSH access** لمهندس deployment واحد على الأقل

### ما نقدمه نحن (ضمن السعر)
- ✅ كل الـ source code (private repo access أو ZIP delivery)
- ✅ DEPLOY.md technical guide (مرفق)
- ✅ Docker images جاهزة (push على GHCR أو Docker Hub بحساب العميل)
- ✅ env.example مع كل المتغيرات المطلوبة + شرح كل واحد
- ✅ Migration scripts للـ DB
- ✅ Backup + restore scripts (تحت `scripts/`)
- ✅ CI workflow templates للـ GitHub Actions
- ✅ **جلسة Zoom للـ deployment** (90 دقيقة، مع المهندس بتاع العميل)
  - تركيب الـ stack
  - ربط الـ TLS certs
  - first-run admin setup
  - smoke tests
- ✅ **جلسة تدريب** للطبيب + السكرتارية (60 دقيقة)
- ✅ Branding customization (logo, name, colors) — مرة واحدة
- ✅ Support بعد التسليم (مدة وheaviness حسب الـ tier)

### Timeline متوقعة (Scenario A)
| اليوم | النشاط | المسؤول |
|------|--------|---------|
| 1 | إعداد VPS + DNS من العميل | فريق العميل |
| 2 | تركيب الـ stack + ربط TLS + first-run | إحنا + فريق العميل (Zoom) |
| 2 | smoke test + admin setup + clinic data | إحنا |
| 3 | training session للطبيب + السكرتارية | إحنا |
| 3 | go-live + monitoring activation | فريق العميل |

**في 3 أيام عمل العيادة بتستقبل أول حجز.**

---

## Scenario B — إحنا اللي بنشتري ونجهز

عيادات صغيرة بدون فريق تقني داخلي. إحنا بنشتري الـ VPS باسم العميل (أو
باسمنا مع تحويل ملكية لاحقاً)، بنركّب كل حاجة، بنسلم الـ login.

### ما يقدمه العميل
- **بيانات العيادة**: اسم، تخصص، شعار (لو موجود)، خدمات
- **Domain** (إذا عنده) أو نشتري له واحد جديد (بـ $10–$15/سنة، يضاف للفاتورة)
- **بيانات الطبيب + السكرتارية**: أسامي، تليفونات (لـ admin login)
- **مزود SMS**: العميل بيفتح حساب Vonage (بنوجهه — 10 دقايق) ويدي الـ
  credentials. الـ pay-as-you-go SMS بيتدفع منه مباشرة.

### ما نقدمه نحن (ضمن السعر)
- ✅ كل اللي في Scenario A
- ✅ **شراء VPS** بنفس مواصفات production (DigitalOcean / Hetzner / OVHcloud)
- ✅ تركيب Docker + dependencies + firewall (ufw) + Fail2ban
- ✅ شهادة Let's Encrypt + auto-renew
- ✅ DNS configuration (لو الـ domain عندنا)
- ✅ Backup off-site إلى S3 / Spaces (حساب باسمنا أو باسم العميل)
- ✅ Monitoring (Better Uptime / UptimeRobot — حساب free tier)
- ✅ Email forwarding للـ admin alerts
- ✅ **3 شهور Pro support** بشكل افتراضي (Scenario B بيخلي support tier
  أعلى عشان إحنا اللي بنبص على الـ infra)
- ✅ **تسليم الـ access**: SSH key + admin login بعد ما نتأكد إن كل حاجة شغالة

### Timeline متوقعة (Scenario B)
| اليوم | النشاط |
|------|--------|
| 1 | شراء VPS + domain + DNS configuration |
| 1 | تركيب الـ stack + TLS + smoke test |
| 2 | إعداد الـ backup + monitoring + email alerts |
| 2 | clinic customization + admin user creation |
| 3 | training للطبيب + السكرتارية + handover |

**3 أيام عمل، العميل بيستلم system شغال جاهز يستقبل مرضى.**

### تكاليف infrastructure (Scenario B) — بتنحاسب فوق سعر النظام
| البند | الوصف | السعر التقريبي |
|------|------|----------------|
| VPS  | 2 vCPU / 4GB RAM / 80GB SSD، Ubuntu 24.04 | $20–$30 / شهر |
| Domain | .com / .clinic / .ly / .eg | $10–$25 / سنة |
| SMS | Vonage / Twilio (pay-as-you-go) | $0.05–$0.10 لكل OTP |
| Backups | S3 / DigitalOcean Spaces | $5 / شهر |
| SSL  | Let's Encrypt | مجاني |
| Monitoring | Better Uptime / UptimeRobot | مجاني (5 monitors) |

العميل بيدفع الـ infra مباشرة (لما الـ accounts بإسمه) أو بيدفعها لنا
كـ pass-through للسنة الأولى.

---

## الحماية والـ Compliance

في الـ scenarios الاتنين، التزامنا:

- 🔒 **TLS 1.2/1.3** على كل الـ traffic (HSTS مفعّل)
- 🔒 **Encrypted at rest**: الـ DB والـ backup encrypted بالـ disk-level encryption
  بتاعة المزود (DigitalOcean / Hetzner / AWS — كلهم بيوفروا ده)
- 🔒 **Brute-force protection**: 5 محاولات OTP، ساعة نص lockout
- 🔒 **Audit log**: كل عمليات الـ admin بتتسجل (login، edit، delete) — request_id
  في كل log line
- 🔒 **Soft deletes**: مفيش حذف نهائي بالخطأ، الـ records قابلة للاسترداد
- 🔒 **OWASP Top 10 audit** متعمل قبل التسليم
- 🔒 **Backup verification شهري**: مرة كل شهر بنعمل restore على staging،
  بنبعت screenshot للعميل
- 🔒 **Vulnerability scanning**: `composer audit` + `npm audit` بيتشغلوا في CI،
  أي high/critical بنرد فيه خلال 24 ساعة

ملحوظة قانونية: النظام بيخزن **personally identifiable data** و **medical
data**. لو العميل في الاتحاد الأوروبي أو UK، GDPR compliance مسؤوليته
كـ data controller. إحنا data processor — بنوقّع DPA لو احتاج.

---

## التسليم النهائي

بعد ما النظام يبقى شغال على production، بنوقّع مع العميل:

1. **HANDOVER-CHECKLIST.md** — موقّعة من الطرفين، فيها كل البنود اتأكد منها.
2. **`.env` snapshot encrypted** (passwords، API keys) — نسخة عند العميل + نسخة
   عندنا (تتمسح بعد فترة الـ warranty لو العميل طلب).
3. **Source archive ZIP** — للتأمين، نسخة كاملة من الـ source بتاع لحظة التسليم.
4. **Documentation pack**: README + API.md + DEPLOY.md + DESIGN_SYSTEM.md +
   تسجيل training session.
5. **First-month support window** بيبدأ من تاريخ التسليم، مش من بداية المشروع.

العميل يقدر يطلب re-handover بعد سنة (لو موظف جديد مسؤول عن النظام)، بنحاسب
ساعة شغل بسعر support tier الحالي.
