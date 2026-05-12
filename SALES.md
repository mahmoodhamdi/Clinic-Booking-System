# نظام حجز العيادات الذكي

## نظام جاهز للتسليم لعيادات الأطباء الخاصة في مصر والوطن العربي

> منصة حجز مواعيد، سجلات طبية، وروشتات إلكترونية لعيادة طبيب واحد —
> ثنائية اللغة (عربي/إنجليزي)، آمنة طبياً، وقابلة للتركيب على نطاق العميل
> خلال يومين عمل.

---

## ليه النظام ده؟

| التحدي اللي بيواجه الطبيب | اللي بيوفره النظام |
|---------------------------|---------------------|
| المرضى بيكلموا السكرتارية على التليفون لحجز موعد، ومش بيلاقوا غير "تعالى بكرة" | المريض بيحجز بنفسه من تليفونه على slot متاح فعلاً، والسكرتارية بتأكد بضغطة زرار |
| دفتر مواعيد ورق بيتعارك مع مواعيد الجراحات والإجازات | تقويم أسبوعي، وقت كسر، إجازات، وكل واحد بيحجز بيخصم تلقائياً من الـ slots المتاحة |
| السجل الطبي للمريض موزع بين دواليب ورقية أو ملفات Word | سجل طبي رقمي بكل زيارة، signs، تشخيص، خطة علاج، وroshetta PDF جاهزة للطباعة |
| التحويلات والدفعات والتأمين بتتحاسب يدوي | نظام دفعات مدمج: كاش/شبكة/تأمين، خصومات، refunds، وreporting شهري بالـ PDF |
| منصات الحجز التانية بتاخد عمولة، وبتشارك بيانات المرضى مع منصتها | بيانات العيادة عند العيادة. مفيش commission، مفيش third party بيشوف اسم المريض |
| تنزيل تطبيقات الأطباء على الموبايل عملية مكلفة لكل عيادة | النظام شغال على الموبايل من المتصفح كـ PWA — مفيش حاجة تتنزل من Play Store |

---

## مين العميل المثالي؟

- **طبيب عيادة خاصة** (واحد أو اتنين دكاترة في نفس العنوان)
- بيشغل **سكرتارية واحدة** أو اتنين
- بيستقبل **50–500 موعد شهرياً**
- عاوز يخرج من نظام Vezeeta / منصة عمولة (لأنها بتاخد %، وعنده مرضى منتظمين)
- محتاج **سجل طبي مرتب** + روشتات بالـ PDF + reports شهرية للإيرادات
- بيتعامل مع مرضى عرب — الواجهة لازم تكون **عربية RTL** بشكل صحيح

عيادات التخصصات اللي جربناها: باطنة، أطفال، نسا وتوليد، عظام، أسنان، جلدية،
نفسية، تغذية، علاج طبيعي. أي تخصص ما بيعتمدش على معدات تصوير ضخمة بيشتغل
عليه النظام بدون تخصيص.

---

## بترتكب عند العميل في يومين

### اليوم الأول
1. تركيب الـ stack على السيرفر بتاع العميل (Docker واحد، أوامر معدودة).
2. ربط الـ domain + شهادة TLS (مجانية من Let's Encrypt).
3. ربط مزود الـ SMS (Vonage أو Twilio) لإرسال الـ OTP.
4. تخصيص بيانات العيادة (اسم، تخصص، شعار، صورة hero، خدمات).

### اليوم التاني
5. تدريب الطبيب على dashboard (15 دقيقة).
6. تدريب السكرتارية على إدارة المواعيد + الـ patients (30 دقيقة).
7. استيراد قائمة المرضى الحاليين (اختياري — لو عنده ملف Excel).
8. تشغيل reminder cron + backup cron + uptime monitoring.

بعد كده العيادة شغالة. أول مريض بيحجز خلال أسبوع غالباً، وأول إيراد بيتحاسب
داخل النظام نفس اليوم.

---

## مميزات بتميز النظام عن المنافسين

### 1. ثنائية اللغة بشكل صحيح
- RTL مدعوم بشكل كامل في الـ layout (مش مجرد ترجمة نصوص).
- Cairo font لكل واجهة العربي، Geist Mono للأرقام.
- المريض ممكن يقلب اللغة من header. السكرتارية ممكن تشتغل بالعربي والـ reports
  تطلع بالإنجليزي للمحاسبين.

### 2. أمان طبي على مستوى production
- Authentication مبنية على Laravel Sanctum + OTP عبر التليفون (مفيش password
  على أول لقاء — الـ OTP بيوصل، يدخل، يحدد password).
- Brute-force protection على الـ OTP: 5 محاولات، ساعة نص lockout.
- Rate limiting على كل endpoint حساس.
- Role-based authorization: Admin (الدكتور) / Secretary / Patient، policies لكل model.
- Soft deletes على كل البيانات الطبية — مفيش undo بـ accident.
- OWASP Top 10 audit متعمل مع تثبيت الـ findings.

### 3. تصدير PDF أصلي للروشتات والـ reports
- Roshetta بـ logo العيادة + بيانات الطبيب + معلومات المريض + الـ medications.
- Revenue / Appointments / Patients reports — كلهم بتصدير PDF بـ branding العيادة.

### 4. تصميم clinical-grade
- Medical teal palette (OKLCh-based عشان color reproduction ثابت على الشاشات).
- Dark mode (مفيد للسكرتارية في النوبت يات الطويلة).
- 25 صفحة كاملة الـ redesign.
- Lighthouse scores صحية + axe-core a11y.

### 5. اختبارات شاملة على الـ codebase
- **961 backend test** (Laravel feature + unit، 2,443 assertion، 17 ثانية للسويت كاملة)
- **500 frontend test** (Jest + React Testing Library، 33 suite، 3 ثواني)
- Coverage floor مضمون بـ CI: 80%+، مع خطة رفعها لـ 90% في الـ waves اللي جاية.
- E2E tests بـ Playwright على Chromium + Firefox + WebKit.

### 6. النظام بيبق ع طول
- Daily backup cron جاهز.
- JSON logs structured + request_id لكل request — قابل للربط بـ Loki أو ELK أو Datadog.
- `/api/health` endpoint للـ uptime monitoring.
- Upgrade path موثق (`git pull && composer install && migrate && config:cache`).

---

## الباقات المقترحة

> **القيم تحت قابلة للتفاوض حسب حجم العيادة، الـ customization المطلوب،
> ومدة عقد الدعم.** الـ pricing tier ده اقتراحنا الافتراضي.

### Basic — جاهز يشتغل
**$1,500 / مرة واحدة + $50/شهر دعم**

شامل:
- النظام كامل (backend + frontend + DB schemas)
- تركيب على سيرفر العميل (لو عنده — VPS سعره منفصل)
- domain configuration + TLS
- ربط مزود SMS واحد (Vonage مثلاً)
- تخصيص هوية العيادة (اسم، شعار، خدمات)
- جلسة تدريب أونلاين ساعة للطبيب + ساعة للسكرتارية
- **شهر دعم مدفوع** (bug fixes + answers)
- **3 شهور warranty** لأي bug جوهري في الـ scope الأصلي

ما هو غير شامل:
- استضافة (recommendation: $5–$10/شهر VPS)
- مدفوعات مزود SMS (per-message cost عند Vonage/Twilio)
- custom features خارج الـ scope المعروض

---

### Pro — الأكثر طلباً
**$3,000 / مرة واحدة + $150/شهر دعم**

كل اللي في Basic، زائد:
- **استضافة جاهزة لمدة سنة** (VPS + setup + monitoring)
- backup cron إلى S3 أو DigitalOcean Spaces، مع testing شهري للـ restore
- **custom branding كامل**: لوحة ألوان مختلفة لو العيادة عاوزة، logo modifications
- صفحة landing مفصلة للعيادة (services، doctor bio، gallery)
- ربط Google Analytics + Search Console
- **6 شهور دعم مدفوع** بـ priority response (24 ساعة).
- training شخصي (نأتي للعيادة لو في القاهرة/الجيزة، أو زوم session مدتها 90 دقيقة)
- migration من نظام قديم (Excel/ Word records) — لحد 500 مريض

---

### Enterprise — للعيادات الأكبر أو السلاسل
**$7,500+ / مرة واحدة + $400/شهر دعم**

كل اللي في Pro، زائد:
- **Multi-branch support** (modifications للـ schema عشان كذا فرع نفس الطبيب
  أو نفس السكرتارية)
- **White-label**: اسم النظام بتاع العميل، domain خاص، مفيش mention لينا
  داخل النظام
- **Integrations مخصصة**: مع مزود التأمين (Egypt Care, Globemed)، أو
  معامل التحاليل (المختبر، Al-Borg)، أو نظام محاسبة موجود
- **SLA رسمي**: 99.5% uptime، 4 ساعات response للـ priority، 12 ساعة للـ regular
- **on-call** 5 أيام في الأسبوع
- **12 شهر دعم مدفوع** متجدد سنوياً
- **3 جلسات تدريب** + documentation مخصصة بالعربي للموظفين الجدد

---

## أسئلة بنسمعها كتير

**س: لو فيه بug بعد فترة الدعم؟**
ج: في فترة الـ warranty (3 شهور Basic، 6 Pro، 12 Enterprise) أي bug جوهري في الـ
scope الأصلي بيتصلح مجاناً. بعد كده، أو لـ features جديدة، البالغ بالساعة
حسب الـ tier المتعاقد عليه.

**س: عاوز feature مش في القائمة؟**
ج: تحت `SUPPORT-PLANS.md` فيه scope الـ change requests. حالات شائعة (إضافة
صفحة، تعديل report) بيتعمل أحياناً ضمن support hours الشهرية. features أكبر
بـ scope منفصل وبيتفاوض عليها.

**س: العميل عاوز يستضيف بنفسه؟**
ج: ممتاز — Basic tier مصمم لده. السورس بيتسلم كامل، DEPLOY.md فيه كل التفاصيل
(Docker أو manual، Nginx config، Let's Encrypt). فريق العميل التقني بيقدر
يتولى الـ deployment، إحنا بنشتغل support من بعدها.

**س: لو الطبيب عنده كذا فرع؟**
ج: Enterprise tier. النظام مصمم single-clinic by default — للـ multi-branch
محتاجين schema changes (clinic_id على كل model) + UI changes (clinic switcher).
الـ scope بيتفاوض على حسب عدد الفروع.

**س: ينفع تربطوه بمنصة الـ insurance؟**
ج: مدعومة في Enterprise. الـ APIs المشهورة (Egypt Care, Globemed) عندها webhook
patterns واضحة، والـ integration بياخد usually 2–4 أسابيع شغل.

---

## للتواصل

**Mahmoud Hamdy — MWM Software Solutions**
- 📧 mwm.softwars.solutions@gmail.com
- 📱 [WhatsApp / phone]
- 🌐 [website]
- 💼 LinkedIn

موافق على الـ demo؟ احنا بنرتب جلسة عربية لمدة 30 دقيقة، بنوريك النظام شغال
على عيادة وهمية، وبنعدد للأسئلة. الـ demo مجانية ومن غير commitment.
