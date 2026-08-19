<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Financial Statement - {{ $student->admission_no }}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; color: #111827; }
    .box { border: 1px solid #d1d5db; padding: 16px; }
    .title { font-size: 22px; font-weight: bold; margin: 8px 0 12px; }
    .muted { color: #6b7280; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; text-align: left; }
    th { background: #f3f4f6; }
    .summary { margin-top: 12px; }
    .summary div { margin: 4px 0; }
    @media print { button { display: none; } }
  </style>
</head>
<body>
  <button onclick="window.print()">Print Statement</button>

  <div class="box">
    <div style="display:flex;justify-content:space-between;align-items:start;">
      <img src="{{ $school['logo'] }}" alt="School Logo" style="height:72px;">
      <div style="text-align:right;">
        <div><strong>{{ $school['name'] }}</strong></div>
        <div class="muted">School Code: {{ $school['code'] }}</div>
        <div class="muted">{{ $school['location'] }}</div>
      </div>
    </div>

    <div class="title">Student Financial Statement</div>

    <div><strong>Student:</strong> {{ $student->full_name }}</div>
    <div><strong>Admission No:</strong> {{ $student->admission_no }}</div>
    <div><strong>Class:</strong> {{ $student->class_level }} {{ $student->stream ? '(' . $student->stream . ')' : '' }}</div>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Receipt No</th>
          <th>Mode</th>
          <th>Amount</th>
          <th>Organization</th>
        </tr>
      </thead>
      <tbody>
        @forelse($account?->payments ?? [] as $payment)
          <tr>
            <td>{{ optional($payment->paid_at)->format('Y-m-d') }}</td>
            <td>{{ $payment->receipt_no }}</td>
            <td>{{ strtoupper($payment->payment_method) }}</td>
            <td>{{ number_format((float)$payment->amount, 2) }}</td>
            <td>{{ $payment->organization_name ? $payment->organization_name . ($payment->organization_id ? ' (' . $payment->organization_id . ')' : '') : '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="5">No payments found.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="summary">
      <div><strong>Total Fee Due:</strong> {{ number_format($due, 2) }}</div>
      <div><strong>Total Paid:</strong> {{ number_format($paidTotal, 2) }}</div>
      <div><strong>Balance:</strong> {{ number_format($balance, 2) }}</div>
    </div>

    <p class="muted">It is illegal without the official school stamp and signature.</p>
  </div>
</body>
</html>