<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير الإيرادات</title>
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
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
        }
        .summary-item .label {
            color: #7f8c8d;
            font-size: 11px;
        }
        .methods {
            margin: 20px 0;
        }
        .methods h3 {
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .method-item {
            display: inline-block;
            width: 30%;
            text-align: center;
            padding: 10px;
            background: #ecf0f1;
            margin: 5px;
            border-radius: 5px;
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
            background-color: #27ae60;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .amount {
            font-weight: bold;
            color: #27ae60;
        }
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
        <h1>تقرير الإيرادات</h1>
        <p>الفترة: {{ $period['from'] }} إلى {{ $period['to'] }}</p>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ number_format($summary['total_revenue'], 2) }} ج.م</div>
                <div class="label">إجمالي الإيرادات</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($summary['total_discount'], 2) }} ج.م</div>
                <div class="label">إجمالي الخصومات</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $summary['total_payments'] }}</div>
                <div class="label">عدد المدفوعات</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($summary['average_payment'], 2) }} ج.م</div>
                <div class="label">متوسط الدفعة</div>
            </div>
        </div>
    </div>

    <div class="methods">
        <h3>توزيع طرق الدفع</h3>
        <div class="method-item">
            <div style="font-size: 18px; font-weight: bold;">{{ number_format($by_method['cash'], 2) }} ج.م</div>
            <div>نقدي</div>
        </div>
        <div class="method-item">
            <div style="font-size: 18px; font-weight: bold;">{{ number_format($by_method['card'], 2) }} ج.م</div>
            <div>بطاقة</div>
        </div>
        <div class="method-item">
            <div style="font-size: 18px; font-weight: bold;">{{ number_format($by_method['wallet'], 2) }} ج.م</div>
            <div>محفظة</div>
        </div>
    </div>

    <h3>تفاصيل المدفوعات</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المريض</th>
                <th>المبلغ</th>
                <th>الخصم</th>
                <th>الإجمالي</th>
                <th>طريقة الدفع</th>
                <th>تاريخ الدفع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $payment['patient_name'] ?? '-' }}</td>
                <td>{{ number_format($payment['amount'], 2) }} ج.م</td>
                <td>{{ number_format($payment['discount'], 2) }} ج.م</td>
                <td class="amount">{{ number_format($payment['total'], 2) }} ج.م</td>
                <td>{{ $payment['method_label'] }}</td>
                <td>{{ $payment['paid_at'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير آلياً - نظام حجز العيادة</p>
    </div>
</body>
</html>
