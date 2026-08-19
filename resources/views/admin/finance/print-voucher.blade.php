<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Voucher - {{ $voucher->voucher_no }}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; color: #111827; }
    .box { border: 1px solid #d1d5db; padding: 16px; }
    .row { display: flex; justify-content: space-between; margin: 6px 0; }
    .title { font-size: 22px; font-weight: bold; margin: 8px 0 12px; }
    .muted { color: #6b7280; font-size: 12px; }
    .line { border-top: 1px solid #d1d5db; margin: 12px 0; }
    .sig { margin-top: 36px; display: flex; justify-content: space-between; gap: 24px; }
    .sig > div { flex: 1; border-top: 1px solid #111827; padding-top: 6px; text-align: center; font-size: 12px; }
    @media print { button { display: none; } }
  </style>
</head>
<body>
  <button onclick="window.print()">Print Voucher</button>

  <div class="box">
    <div class="row">
      <div><img src="{{ $school['logo'] }}" alt="School Logo" style="height:72px;"></div>
      <div style="text-align:right;">
        <div><strong>{{ $school['name'] }}</strong></div>
        <div class="muted">School Code: {{ $school['code'] }}</div>
        <div class="muted">{{ $school['location'] }}</div>
      </div>
    </div>

    <div class="title">Payment Voucher</div>

    <div class="row"><span>Voucher Number:</span><strong>{{ $voucher->voucher_no }}</strong></div>
    <div class="row"><span>Date:</span><strong>{{ optional($voucher->paid_at)->format('Y-m-d') }}</strong></div>

    <div class="line"></div>
    <div class="row"><span>Supplier Name:</span><strong>{{ $voucher->supplier_name }}</strong></div>
    <div class="row"><span>Supplier ID:</span><strong>{{ $voucher->supplier_id ?: '-' }}</strong></div>
    <div class="row"><span>Payment Purpose:</span><strong>{{ $voucher->purpose }}</strong></div>
    <div class="row"><span>Amount Paid by School:</span><strong>{{ number_format((float)$voucher->amount, 2) }}</strong></div>
    <div class="row"><span>Mode of Payment:</span><strong>{{ strtoupper($voucher->payment_method) }}</strong></div>

    <div class="line"></div>
    <div class="row"><span>Prepared By (Accountant):</span><strong>{{ $voucher->preparer?->name ?? '-' }}</strong></div>
    <div class="row"><span>Approved By (Principal):</span><strong>{{ $voucher->approver?->name ?? 'Pending Signature' }}</strong></div>

    <div class="sig">
      <div>Principal Signature & Official School Stamp</div>
      <div>Accountant Signature</div>
      <div>Payee Signature</div>
    </div>
  </div>
</body>
</html>