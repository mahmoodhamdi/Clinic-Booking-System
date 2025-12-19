<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>وصفة طبية - {{ $prescription->prescription_number }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
        }
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            direction: rtl;
            text-align: right;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
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
            font-size: 36px;
            color: #2563eb;
            font-weight: bold;
            margin: 10px 0;
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
        .patient-row {
            display: flex;
            margin-bottom: 5px;
        }
        .patient-label {
            font-weight: bold;
            width: 100px;
            display: inline-block;
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
            margin-bottom: 5px;
        }
        .medication-details {
            font-size: 11px;
            color: #4b5563;
        }
        .medication-details span {
            display: inline-block;
            margin-left: 15px;
        }
        .medication-instructions {
            font-size: 11px;
            color: #dc2626;
            margin-top: 5px;
            font-style: italic;
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
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 30px;
        }
        .signature {
            text-align: left;
            margin-top: 40px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            display: inline-block;
            text-align: center;
            padding-top: 5px;
        }
        .date-info {
            font-size: 11px;
            color: #666;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 3px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="clinic-name">{{ $clinic['name'] }}</div>
        <div class="clinic-info">
            @if($clinic['address']){{ $clinic['address'] }} | @endif
            @if($clinic['phone']){{ $clinic['phone'] }} | @endif
            @if($clinic['email']){{ $clinic['email'] }}@endif
        </div>
        <div class="rx-symbol">℞</div>
        <div class="prescription-number">رقم الوصفة: {{ $prescription->prescription_number }}</div>
    </div>

    <div class="patient-info">
        <h3>معلومات المريض</h3>
        <table>
            <tr>
                <td><span class="patient-label">الاسم:</span> {{ $patient->name }}</td>
                <td><span class="patient-label">الهاتف:</span> {{ $patient->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td><span class="patient-label">التشخيص:</span> {{ $medicalRecord->diagnosis }}</td>
                <td><span class="patient-label">تاريخ الكشف:</span> {{ $appointment->appointment_date->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>

    <div class="medications">
        <h3>الأدوية الموصوفة</h3>
        @foreach($items as $index => $item)
        <div class="medication-item">
            <div class="medication-name">{{ $index + 1 }}. {{ $item->medication_name }}</div>
            <div class="medication-details">
                <span><strong>الجرعة:</strong> {{ $item->dosage }}</span>
                <span><strong>المرات:</strong> {{ $item->frequency }}</span>
                <span><strong>المدة:</strong> {{ $item->duration }}</span>
                @if($item->quantity)
                <span><strong>الكمية:</strong> {{ $item->quantity }}</span>
                @endif
            </div>
            @if($item->instructions)
            <div class="medication-instructions">
                <strong>تعليمات:</strong> {{ $item->instructions }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($prescription->notes)
    <div class="notes">
        <h4>ملاحظات الطبيب:</h4>
        <p>{{ $prescription->notes }}</p>
    </div>
    @endif

    @if($prescription->valid_until)
    <div class="validity">
        <strong>صالحة حتى:</strong> {{ $prescription->valid_until->format('Y-m-d') }}
    </div>
    @endif

    <div class="footer">
        <div class="signature">
            <div class="signature-line">توقيع الطبيب</div>
        </div>
        <div class="date-info">
            <p>تاريخ إصدار الوصفة: {{ $prescription->created_at->format('Y-m-d H:i') }}</p>
        </div>
    </div>
</body>
</html>
