<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير المرضى</title>
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            direction: rtl;
            font-size: 12px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .summary {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        .summary-item .label {
            color: #7f8c8d;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .active { color: #27ae60; }
        .inactive { color: #e74c3c; }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #7f8c8d;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير المرضى</h1>
        <p>الفترة: {{ $period['from'] }} إلى {{ $period['to'] }}</p>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $summary['total_patients'] }}</div>
                <div class="label">إجمالي المرضى</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $summary['active_patients'] }}</div>
                <div class="label">مرضى نشطون</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $summary['inactive_patients'] }}</div>
                <div class="label">مرضى غير نشطون</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المريض</th>
                <th>رقم الهاتف</th>
                <th>البريد الإلكتروني</th>
                <th>تاريخ التسجيل</th>
                <th>عدد المواعيد</th>
                <th>المكتملة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $index => $patient)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $patient['name'] }}</td>
                <td>{{ $patient['phone'] }}</td>
                <td>{{ $patient['email'] ?? '-' }}</td>
                <td>{{ $patient['registered_at'] }}</td>
                <td>{{ $patient['total_appointments'] }}</td>
                <td>{{ $patient['completed_appointments'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير آلياً - نظام حجز العيادة</p>
    </div>
</body>
</html>
