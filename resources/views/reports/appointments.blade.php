<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير المواعيد</title>
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
            color: #2c3e50;
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
        .status-pending { color: #f39c12; }
        .status-confirmed { color: #3498db; }
        .status-completed { color: #27ae60; }
        .status-cancelled { color: #e74c3c; }
        .status-no_show { color: #95a5a6; }
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
        <h1>تقرير المواعيد</h1>
        <p>الفترة: {{ $period['from'] }} إلى {{ $period['to'] }}</p>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $summary['total'] }}</div>
                <div class="label">إجمالي المواعيد</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $summary['completed'] }}</div>
                <div class="label">مكتمل</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $summary['cancelled'] }}</div>
                <div class="label">ملغي</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $completion_rate }}%</div>
                <div class="label">نسبة الإكمال</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المريض</th>
                <th>رقم الهاتف</th>
                <th>التاريخ</th>
                <th>الوقت</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appointments as $index => $appointment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $appointment['patient_name'] }}</td>
                <td>{{ $appointment['patient_phone'] }}</td>
                <td>{{ $appointment['date'] }}</td>
                <td>{{ $appointment['time'] }}</td>
                <td class="status-{{ $appointment['status'] }}">{{ $appointment['status_label'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير آلياً - نظام حجز العيادة</p>
    </div>
</body>
</html>
