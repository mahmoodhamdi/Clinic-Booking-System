<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'xbriyaz', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            line-height: 1.8;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .clinic-info {
            font-size: 11px;
            color: #666;
        }
        .rx-symbol {
            font-size: 32px;
            color: #2563eb;
            font-weight: bold;
            font-family: serif;
            font-style: italic;
            margin: 15px 0;
        }
        .prescription-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .patient-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .patient-info h3 {
            margin: 0 0 10px 0;
            color: #2563eb;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
        }
        .info-table td {
            padding: 5px 0;
        }
        .label {
            font-weight: bold;
            color: #333;
        }
        .medications {
            margin-bottom: 20px;
        }
        .medications h3 {
            color: #2563eb;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .medication-item {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .medication-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .medication-details {
            font-size: 11px;
            color: #4b5563;
        }
        .medication-detail {
            display: inline-block;
            margin-left: 15px;
            margin-bottom: 5px;
            background: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .medication-instructions {
            font-size: 11px;
            color: #dc2626;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #ddd;
        }
        .notes {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .notes h4 {
            margin: 0 0 5px 0;
            color: #92400e;
            font-size: 12px;
        }
        .validity {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #ecfdf5;
            border: 1px solid #6ee7b7;
            border-radius: 8px;
            color: #065f46;
        }
        .footer {
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            margin-top: 40px;
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            width: 180px;
            border-top: 2px solid #333;
            display: inline-block;
            text-align: center;
            padding-top: 8px;
            font-size: 11px;
            color: #666;
        }
        .date-info {
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="clinic-name">{{ $clinic['name'] }}</div>
        <div class="clinic-info">
            @if($clinic['address']){{ $clinic['address'] }}@endif
            @if($clinic['phone']) | {{ $clinic['phone'] }}@endif
            @if($clinic['email']) | {{ $clinic['email'] }}@endif
        </div>
        <div class="rx-symbol">Rx</div>
        <div class="prescription-number">رقم الوصفة: {{ $prescription->prescription_number }}</div>
    </div>

    <div class="patient-info">
        <h3>معلومات المريض</h3>
        <table class="info-table">
            <tr>
                <td width="50%"><span class="label">الاسم:</span> {{ $patient->name }}</td>
                <td width="50%"><span class="label">الهاتف:</span> {{ $patient->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td><span class="label">التشخيص:</span> {{ $medicalRecord->diagnosis }}</td>
                <td><span class="label">تاريخ الكشف:</span> {{ $appointment->appointment_date->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>

    <div class="medications">
        <h3>الأدوية الموصوفة</h3>
        @foreach($items as $index => $item)
        <div class="medication-item">
            <div class="medication-name">{{ $index + 1 }}. {{ $item->medication_name }}</div>
            <div class="medication-details">
                <span class="medication-detail"><strong>الجرعة:</strong> {{ $item->dosage }}</span>
                <span class="medication-detail"><strong>التكرار:</strong> {{ $item->frequency }}</span>
                <span class="medication-detail"><strong>المدة:</strong> {{ $item->duration }}</span>
                @if($item->quantity)
                <span class="medication-detail"><strong>الكمية:</strong> {{ $item->quantity }}</span>
                @endif
            </div>
            @if($item->instructions)
            <div class="medication-instructions">
                <strong>التعليمات:</strong> {{ $item->instructions }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($prescription->notes)
    <div class="notes">
        <h4>ملاحظات الطبيب:</h4>
        <p style="margin: 0;">{{ $prescription->notes }}</p>
    </div>
    @endif

    @if($prescription->valid_until)
    <div class="validity">
        <strong>صالحة حتى:</strong> {{ $prescription->valid_until->format('Y-m-d') }}
    </div>
    @endif

    <div class="footer">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: right;">
                    <div class="date-info">
                        تاريخ إصدار الوصفة: {{ $prescription->created_at->format('Y-m-d H:i') }}
                    </div>
                </td>
                <td width="50%" style="text-align: left;">
                    <div class="signature">
                        <div class="signature-line">توقيع الطبيب</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
