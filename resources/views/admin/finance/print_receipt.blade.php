{{-- resources/views/admin/finance/print_receipt.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Fee Receipt - {{ $payment->receipt_no ?? 'N/A' }}</title>
    <style>
        :root { --text:#0f172a; --muted:#475569; --line:#cbd5e1; --accent:#0ea5e9; --soft:#f8fafc; }
        * { box-sizing: border-box; }
        body { margin:0; padding:24px; font-family:Arial, Helvetica, sans-serif; color:var(--text); background:#fff; }
        .container { max-width:860px; margin:0 auto; border:2px solid var(--line); padding:20px; }
        .header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; border-bottom:2px solid var(--line); padding-bottom:12px; margin-bottom:16px; }
        .brand { display:flex; align-items:flex-start; gap:12px; }
        .school-logo { width:68px; height:68px; object-fit:contain; border:1px solid #e2e8f0; border-radius:8px; background:#fff; padding:4px; }
        .title { margin:0; font-size:22px; letter-spacing:.2px; }
        .subtitle { margin:6px 0 0; color:var(--muted); font-size:13px; }
        .receipt-badge { border:1px solid var(--accent); color:var(--accent); padding:6px 10px; font-size:12px; font-weight:700; border-radius:4px; white-space:nowrap; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .card { border:1px solid var(--line); padding:12px; min-height:110px; }
        .card h3 { margin:0 0 10px; font-size:13px; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); }
        .row { display:flex; justify-content:space-between; gap:8px; margin-bottom:6px; font-size:14px; }
        .label { color:var(--muted); }
        .value { text-align:right; font-weight:600; }
        .amount-box { border:2px solid var(--line); padding:14px; margin:18px 0 16px; background:var(--soft); }
        .amount { font-size:26px; font-weight:800; margin:4px 0 0; color:#065f46; }
        .breakdown { border:1px solid var(--line); margin-top:10px; margin-bottom:16px; }
        .breakdown h3 { margin:0; padding:10px 12px; font-size:13px; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); border-bottom:1px solid var(--line); background:var(--soft); }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th, td { padding:10px 12px; border-bottom:1px solid #e2e8f0; }
        th { text-align:left; color:#334155; }
        .amount-col { text-align:right; white-space:nowrap; font-weight:600; }
        .total-row td { font-weight:700; border-top:2px solid var(--line); }
        .credit-row td { font-weight:700; color:#9a3412; background:#fff7ed; }
        .note { border-top:1px dashed var(--line); padding-top:12px; color:var(--muted); font-size:13px; }
        .footer { margin-top:20px; display:flex; justify-content:space-between; gap:12px; color:var(--muted); font-size:12px; }
        .actions { margin-top:16px; text-align:right; }
        .btn { border:1px solid #0f172a; background:#0f172a; color:#fff; padding:8px 13px; font-size:13px; cursor:pointer; }

        /* FIX: Reversed payment banner styling */
        .reversed-banner { border:2px solid #dc2626; background:#fef2f2; color:#991b1b; padding:12px 16px; margin-bottom:16px; font-size:14px; font-weight:700; text-align:center; text-transform:uppercase; letter-spacing:.5px; }
        .reversed-banner small { display:block; font-weight:400; font-size:12px; text-transform:none; letter-spacing:normal; margin-top:4px; color:#b91c1c; }

        @media print {
            body { padding:0; }
            .container { border:none !important; }
            .actions { display:none !important; }
        }
    </style>
</head>
<body>
@php
    $school = app('currentSchool');
    $schoolName = $school->name ?? config('app.name', 'School');
    $schoolLogoUrl = null;

    if (!empty($school?->logo_path)) {
        $schoolLogoUrl = \Illuminate\Support\Facades\Storage::url($school->logo_path);
    } elseif (!empty($school?->logo_url)) {
        $schoolLogoUrl = $school->logo_url;
    } elseif (file_exists(public_path('images/school-logo.png'))) {
        $schoolLogoUrl = asset('images/school-logo.png');
    }

    $isReversed = ($payment->status ?? 'active') === 'reversed';

    // Normalize breakdown amounts
    $normalizedBreakdown = collect($breakdown ?? collect())->map(function ($line) {
        $amount = (float) (($line->allocated_amount ?? null) ?? ($line->amount ?? 0));
        return (object) [
            'vote_head_name' => $line->vote_head_name ?? 'Unspecified Votehead',
            'vote_head_code' => $line->vote_head_code ?? null,
            'amount'         => round($amount, 2),
            'status'         => $line->status ?? 'active',
        ];
    });

    $totalAllocatedComputed = round((float) ($totalAllocated ?? $normalizedBreakdown->sum('amount')), 2);
    $unallocatedComputed = round((float) ($unallocated ?? max(((float)($payment->amount ?? 0) - $totalAllocatedComputed), 0)), 2);
@endphp

<div class="container">

    {{-- FIX: REVERSED BANNER --}}
    @if($isReversed)
        <div class="reversed-banner">
            ⚠ THIS PAYMENT HAS BEEN REVERSED — RECEIPT IS VOID
            <small>
                Reversed on {{ optional($payment->reversed_at)->format('Y-m-d H:i') ?? 'N/A' }}
                @if($payment->reversal_reason) — Reason: {{ $payment->reversal_reason }} @endif
            </small>
        </div>
    @endif

    <div class="header">
        <div class="brand">
            @if($schoolLogoUrl)
                <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="school-logo">
            @endif
            <div>
                <h1 class="title">{{ $schoolName }} - Student Fee Payment Receipt</h1>
                <p class="subtitle">Official acknowledgement of school fee payment</p>
            </div>
        </div>
        <div class="receipt-badge">#{{ $payment->receipt_no ?? 'N/A' }}</div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Student Details</h3>
            <div class="row"><span class="label">Name</span><span class="value">{{ (trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'N/A') }}</span></div>
            <div class="row"><span class="label">Admission No</span><span class="value">{{ $student->admission_no ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">Class</span><span class="value">{{ $student->class_level ?? 'N/A' }} @if(!empty($student->stream)) ({{ $student->stream }}) @endif</span></div>
        </div>

        <div class="card">
            <h3>Payment Details</h3>
            <div class="row"><span class="label">Date Paid</span><span class="value">{{ optional($payment->paid_at)->format('Y-m-d') ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">Method</span><span class="value">{{ strtoupper($payment->payment_method ?? 'N/A') }}</span></div>
            <div class="row"><span class="label">Reference</span><span class="value">{{ $payment->organization_id ?: ($payment->receipt_no ?? 'N/A') }}</span></div>
            {{-- FIX: Show recorder name instead of ID if available --}}
            <div class="row"><span class="label">Recorded By</span><span class="value">{{ optional($payment->recorder)->name ?? ('User #' . ($payment->recorded_by ?? 'N/A')) }}</span></div>
        </div>
    </div>

    <div class="amount-box" @if($isReversed) style="border-color:#dc2626;background:#fef2f2;" @endif>
        <div class="row" style="margin:0;"><span class="label" style="font-size:15px;">Amount Received (KES)</span></div>
        <p class="amount" @if($isReversed) style="color:#dc2626;text-decoration:line-through;" @endif>{{ number_format((float)($payment->amount ?? 0), 2) }}</p>
    </div>

    <div class="breakdown">
        <h3>Payment Breakdown by Votehead</h3>
        <table>
            <thead>
            <tr>
                <th>Votehead</th>
                <th class="amount-col">Amount Paid (KES)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($normalizedBreakdown as $line)
                {{-- FIX: Strikethrough reversed allocations --}}
                <tr @if(($line->status ?? 'active') === 'reversed') style="color:#94a3b8;text-decoration:line-through;" @endif>
                    <td>
                        @if(!empty($line->vote_head_code))
                            {{ $line->vote_head_code }} -
                        @endif
                        {{ $line->vote_head_name }}
                    </td>
                    <td class="amount-col">{{ number_format((float)$line->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>General Payment</td>
                    <td class="amount-col">{{ number_format((float)($payment->amount ?? 0), 2) }}</td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td>Total Allocated to Voteheads</td>
                <td class="amount-col">{{ number_format($totalAllocatedComputed, 2) }}</td>
            </tr>

            @if($unallocatedComputed > 0)
                <tr class="credit-row">
                    <td>Unallocated / Credit Carried Forward</td>
                    <td class="amount-col">{{ number_format($unallocatedComputed, 2) }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <td>Total Payment Received</td>
                <td class="amount-col">{{ number_format((float)($payment->amount ?? 0), 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    @if(!empty($payment->notes))
        <div class="note"><strong>Notes:</strong> {{ $payment->notes }}</div>
    @endif

    <div class="footer">
        <div>Generated on: {{ now()->format('Y-m-d H:i:s') }}</div>
        <div>Keep this receipt for your records.</div>
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">Print</button>
    </div>
</div>
</body>
</html>