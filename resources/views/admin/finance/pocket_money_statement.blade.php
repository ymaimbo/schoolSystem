<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pocket Money Statement - {{ $student->admission_no ?? 'Student' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #111827;
        }
        .box {
            border: 1px solid #d1d5db;
            padding: 16px;
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #d1d5db;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .school-logo {
            height: 72px;
            width: auto;
            object-fit: contain;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 8px 0 12px;
        }
        .muted {
            color: #6b7280;
            font-size: 12px;
        }
        .student-details {
            margin-bottom: 16px;
        }
        .student-details div {
            margin: 3px 0;
            font-size: 14px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .summary-card {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: center;
        }
        .summary-card .label {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .value {
            font-size: 20px;
            font-weight: 800;
            margin-top: 4px;
        }
        .summary-card .value.positive { color: #065f46; }
        .summary-card .value.negative { color: #92400e; }
        .summary-card .value.balance  { color: #0369a1; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #374151;
        }
        .right { text-align: right; }
        .deposit-text { color: #065f46; font-weight: 600; }
        .withdrawal-text { color: #92400e; font-weight: 600; }
        .reversed-row { color: #9ca3af; }
        .reversed-row td { text-decoration: line-through; }
        .reversal-note {
            font-size: 11px;
            color: #9ca3af;
            text-decoration: none !important;
            font-style: italic;
        }
        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            border-top: 1px dashed #d1d5db;
            padding-top: 12px;
        }
        .footer .muted { font-size: 11px; }
        .signatures {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .sig {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            text-align: center;
            color: #475569;
            font-size: 13px;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            button { display: none !important; }
            .box { border: none; }
        }
    </style>
</head>
<body>

<button onclick="window.print()" style="margin-bottom:12px;padding:8px 16px;border:1px solid #0f172a;background:#0f172a;color:#fff;cursor:pointer;font-size:13px;">
    Print Statement
</button>

@php
    $running = (float) ($openingBalance ?? 0);

    $totalDeposits = 0;
    $totalWithdrawals = 0;

    foreach (($transactions ?? []) as $t) {
        if (($t->status ?? 'active') !== 'reversed') {
            if ($t->type === 'deposit') $totalDeposits += (float) $t->amount;
            else $totalWithdrawals += (float) $t->amount;
        }
    }
@endphp

<div class="box">

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            @if(!empty($school['logo']))
                <img src="{{ $school['logo'] }}" alt="School Logo" class="school-logo">
            @endif
            <div>
                <div style="font-size:18px;font-weight:bold;">{{ $school['name'] }}</div>
                <div class="muted">School Code: {{ $school['code'] }}</div>
                <div class="muted">{{ $school['location'] }}</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div class="muted">POCKET MONEY STATEMENT</div>
            <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <div class="title">Student Pocket Money Statement</div>

    <!-- Student details -->
    <div class="student-details">
        <div><strong>Student:</strong> {{ $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</div>
        <div><strong>Admission No:</strong> {{ $student->admission_no ?? 'N/A' }}</div>
        <div><strong>Class:</strong> {{ $student->class_level ?? 'N/A' }} {{ !empty($student->stream) ? '(' . $student->stream . ')' : '' }}</div>
    </div>

    <!-- Summary cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="label">Opening Balance</div>
            <div class="value">{{ number_format((float) $openingBalance, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Deposits</div>
            <div class="value positive">+ {{ number_format($totalDeposits, 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Withdrawals</div>
            <div class="value negative">- {{ number_format($totalWithdrawals, 2) }}</div>
        </div>
    </div>

    <div class="summary-cards" style="grid-template-columns: 1fr;">
        <div class="summary-card">
            <div class="label">Current Available Balance</div>
            <div class="value balance">KES {{ number_format((float) $currentBalance, 2) }}</div>
        </div>
    </div>

    <!-- Transaction history -->
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Transaction No</th>
            <th>Type</th>
            <th>Reference / Depositor</th>
            <th class="right">Amount (KES)</th>
            <th>Status</th>
            <th class="right">Running Balance</th>
        </tr>
        </thead>
        <tbody>
        @forelse($transactions as $index => $transaction)
            @php
                $isReversed = ($transaction->status ?? 'active') === 'reversed';
                $type = $transaction->type ?? 'deposit';
                $amount = (float) ($transaction->amount ?? 0);

                if (!$isReversed) {
                    $running += $type === 'withdrawal' ? (-1 * $amount) : $amount;
                }
            @endphp
            <tr class="{{ $isReversed ? 'reversed-row' : '' }}">
                <td>{{ $index + 1 }}</td>
                <td>{{ optional($transaction->transaction_date)->format('Y-m-d') }}</td>
                <td>{{ $transaction->transaction_no ?? 'N/A' }}</td>
                <td class="{{ $type === 'withdrawal' ? 'withdrawal-text' : 'deposit-text' }}">
                    {{ ucfirst($type) }}
                </td>
                <td>
                    {{ $transaction->reference_no ?: ($transaction->depositor_name ?: '-') }}
                    @if(!empty($transaction->depositor_phone))
                        <div class="muted">{{ $transaction->depositor_phone }}</div>
                    @endif
                </td>
                <td class="right {{ $type === 'withdrawal' ? 'withdrawal-text' : 'deposit-text' }}">
                    {{ $type === 'withdrawal' ? '-' : '+' }} {{ number_format($amount, 2) }}
                </td>
                <td>
                    {{ ucfirst($transaction->status ?? 'active') }}
                    @if($isReversed && !empty($transaction->reversal_reason))
                        <div class="reversal-note">{{ $transaction->reversal_reason }}</div>
                    @endif
                    @if($isReversed && !empty($transaction->reversed_at))
                        <div class="reversal-note">on {{ optional($transaction->reversed_at)->format('Y-m-d H:i') }}</div>
                    @endif
                </td>
                <td class="right" style="font-weight:600;">
                    @if($isReversed)
                        —
                    @else
                        {{ number_format($running, 2) }}
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#9ca3af;padding:16px;">
                    No pocket money transactions found for this student.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="signatures">
        <div class="sig">Bursar / Accountant Signature & Stamp</div>
        <div class="sig">Parent / Student Signature</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="muted">Generated on {{ now()->format('Y-m-d H:i:s') }}</div>
        <div class="muted">This statement is official when signed and stamped.</div>
    </div>
</div>

</body>
</html>