<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment Receipt</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
            --accent: #0ea5e9;
            --ok: #16a34a;
            --warn: #f59e0b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--ink);
            background: #fff;
        }

        .sheet {
            max-width: 860px;
            margin: 0 auto;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .brand {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .logo {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            background: var(--soft);
            border: 1px solid var(--line);
            object-fit: contain;
            display: block;
        }

        .school h1 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
        }

        .school p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .receipt-meta {
            text-align: right;
            min-width: 240px;
        }

        .receipt-title {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .receipt-no {
            margin: 6px 0 10px;
            font-size: 20px;
            font-weight: 800;
            color: var(--ink);
        }

        .pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #ecfeff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .content {
            padding: 22px 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            background: #fff;
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .rows {
            display: grid;
            grid-template-columns: 130px 1fr;
            row-gap: 8px;
            column-gap: 8px;
            font-size: 14px;
        }

        .label { color: var(--muted); }
        .value { font-weight: 600; color: var(--ink); }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        th, td {
            border: 1px solid var(--line);
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: var(--soft);
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .money { text-align: right; font-variant-numeric: tabular-nums; }

        .summary {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .summary .head {
            background: var(--soft);
            padding: 10px 12px;
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid var(--line);
        }

        .summary .body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .summary .item {
            padding: 14px 12px;
            border-right: 1px solid var(--line);
        }

        .summary .item:last-child { border-right: none; }

        .summary .k {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .summary .v {
            margin: 6px 0 0;
            font-size: 20px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .summary .v.ok { color: var(--ok); }
        .summary .v.warn { color: var(--warn); }

        .notes {
            margin-top: 16px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            background: #fcfdff;
        }

        .notes h4 {
            margin: 0 0 6px;
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .notes p {
            margin: 0;
            color: #334155;
            line-height: 1.5;
        }

        .signatures {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }

        .sig {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            text-align: center;
            color: #475569;
            font-size: 13px;
        }

        .footer {
            padding: 14px 24px 20px;
            color: #64748b;
            font-size: 12px;
            border-top: 1px solid var(--line);
            text-align: center;
        }

        .print-actions {
            max-width: 860px;
            margin: 12px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            border-radius: 8px;
            padding: 9px 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn.primary {
            border-color: #38bdf8;
            background: #e0f2fe;
            color: #0c4a6e;
        }

        @media print {
            body { padding: 0; }
            .print-actions { display: none !important; }
            .sheet { border: none; border-radius: 0; }
        }

        @media (max-width: 760px) {
            .topbar { flex-direction: column; }
            .receipt-meta { text-align: left; }
            .grid { grid-template-columns: 1fr; }
            .summary .body { grid-template-columns: 1fr; }
            .summary .item { border-right: none; border-bottom: 1px solid var(--line); }
            .summary .item:last-child { border-bottom: none; }
            .signatures { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
    $admissionNo = $student->admission_no ?? 'N/A';

    $paidAmount = (float) ($payment->amount ?? 0);

    // Current balance after this payment
    $currentBalance = (float) (
        optional($payment->account)->total_fee_due
        ?? optional(optional($payment->student)->feeAccount)->total_fee_due
        ?? 0
    );

    // Estimated previous balance before this payment
    $previousBalance = $currentBalance + $paidAmount;

    // Estimated total billed
    $estimatedBilled = $previousBalance + (float) (optional($payment->account)->total_paid ?? 0);

    $receiptNo = $payment->receipt_no ?? $payment->reference_no ?? 'N/A';
    $paymentMethod = strtoupper((string) ($payment->payment_method ?? 'N/A'));
    $orgName = $payment->organization_name ?? 'N/A';
    $orgId = $payment->organization_id ?? 'N/A';

    $rawDate = $payment->paid_at ?? $payment->created_at;
    $paymentDate = $rawDate ? \Illuminate\Support\Carbon::parse($rawDate)->format('d M Y') : 'N/A';

    $schoolName = config('app.name', 'School');
@endphp

<div class="sheet">
    <header class="topbar">
        <div class="brand">
            @if(file_exists(public_path('images/school-logo.png')))
                <img src="{{ asset('images/school-logo.png') }}" alt="School logo" class="logo">
            @else
                <div class="logo"></div>
            @endif

            <div class="school">
                <h1>{{ $schoolName }}</h1>
                <p>Official Student Fee Payment Receipt</p>
            </div>
        </div>

        <div class="receipt-meta">
            <p class="receipt-title">Receipt Number</p>
            <p class="receipt-no">{{ $receiptNo }}</p>
            <span class="pill">{{ $paymentMethod }}</span>
        </div>
    </header>

    <main class="content">
        <section class="grid">
            <article class="card">
                <h3>Student Details</h3>
                <div class="rows">
                    <div class="label">Name</div>
                    <div class="value">{{ $studentName ?: 'N/A' }}</div>

                    <div class="label">Admission No</div>
                    <div class="value">{{ $admissionNo }}</div>

                    <div class="label">Class</div>
                    <div class="value">
                        {{ $student->class_level ?? 'N/A' }}
                        @if(!empty($student->stream))
                            - {{ $student->stream }}
                        @endif
                    </div>
                </div>
            </article>

            <article class="card">
                <h3>Payment Details</h3>
                <div class="rows">
                    <div class="label">Date</div>
                    <div class="value">{{ $paymentDate }}</div>

                    <div class="label">Method</div>
                    <div class="value">{{ $paymentMethod }}</div>

                    <div class="label">Organization</div>
                    <div class="value">{{ $orgName }}</div>

                    <div class="label">Org/Reference</div>
                    <div class="value">{{ $orgId }}</div>

                    <div class="label">Recorded By</div>
                    <div class="value">{{ optional($payment->recorder)->name ?? 'System' }}</div>
                </div>
            </article>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="money">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Fee Payment Received</td>
                    <td class="money">{{ number_format($paidAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Student Balance Block (added) --}}
        <section class="summary">
            <div class="head">Student Balance Summary</div>
            <div class="body">
                <div class="item">
                    <p class="k">Balance Before Payment</p>
                    <p class="v">{{ number_format($previousBalance, 2) }}</p>
                </div>
                <div class="item">
                    <p class="k">Payment Amount</p>
                    <p class="v ok">{{ number_format($paidAmount, 2) }}</p>
                </div>
                <div class="item">
                    <p class="k">Current Balance</p>
                    <p class="v warn">{{ number_format($currentBalance, 2) }}</p>
                </div>
            </div>
        </section>

        @if(isset($allocations) && is_iterable($allocations) && count($allocations) > 0)
            <div style="margin-top: 18px;">
                <h3 style="margin:0 0 8px; font-size:13px; color:#475569; text-transform:uppercase; letter-spacing:.06em;">
                    Allocation Breakdown
                </h3>
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th class="money">Allocated (KES)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($allocations as $i => $allocation)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $allocation['category'] ?? $allocation->category ?? 'General' }}</td>
                            <td class="money">
                                {{ number_format((float) ($allocation['amount'] ?? $allocation->amount ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $payment->notes ?: 'No additional notes provided.' }}</p>
        </div>

        <div class="signatures">
            <div class="sig">School Stamp / Authorized Signature</div>
            <div class="sig">Parent / Student Signature</div>
        </div>
    </main>

    <footer class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }} • Keep this receipt for reference.
    </footer>
</div>

<div class="print-actions">
    <button class="btn" onclick="window.history.back()">Back</button>
    <button class="btn primary" onclick="window.print()">Print</button>
</div>
</body>
</html>