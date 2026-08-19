<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Fee Receipt - {{ $payment->receipt_no ?? 'N/A' }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 24px;
            color: #111827;
            font-size: 12px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .school-meta h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
            letter-spacing: .3px;
        }

        .school-meta p {
            margin: 2px 0;
            color: #374151;
        }

        .logo {
            width: 86px;
            height: 86px;
            object-fit: contain;
            border: 1px solid #ddd;
            padding: 4px;
            background: #fff;
        }

        .doc-title {
            margin: 0 0 14px 0;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: .4px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .card {
            border: 1px solid #333;
            padding: 10px;
        }

        .card h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px dashed #ddd;
            padding: 4px 0;
        }

        .row:last-child { border-bottom: 0; }

        .label { color: #4b5563; }

        .value {
            font-weight: 600;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mt-16 { margin-top: 16px; }
        .mt-20 { margin-top: 20px; }
        .mt-24 { margin-top: 24px; }

        .note {
            margin-top: 10px;
            color: #4b5563;
            font-size: 11px;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 32px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-line {
            border-top: 1px solid #111;
            margin-top: 30px;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .print-actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            border: 1px solid #111;
            background: #111;
            color: #fff;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn.secondary {
            background: #fff;
            color: #111;
        }

        @media print {
            body { margin: 0; }
            .print-actions { display: none !important; }
            .container { max-width: 100%; }
            a { text-decoration: none; color: inherit; }
        }
    </style>
</head>
<body>
<div class="container">

    @php
        $allocations = $allocations ?? collect();

        // Grouped totals by vote head for cleaner accountant summary
        $allocationByVoteHead = $allocations->groupBy(function ($a) {
            return ($a->vote_code ?? 'N/A') . '||' . ($a->vote_name ?? 'Unknown');
        })->map(function ($group, $key) {
            [$code, $name] = explode('||', $key);
            return (object) [
                'vote_code' => $code,
                'vote_name' => $name,
                'parent_total' => (float) $group->where('source', 'parent')->sum('allocated_amount'),
                'capitation_total' => (float) $group->where('source', 'capitation')->sum('allocated_amount'),
                'total' => (float) $group->sum('allocated_amount'),
            ];
        })->sortBy('vote_code')->values();

        $grandParentAllocated = (float) $allocations->where('source', 'parent')->sum('allocated_amount');
        $grandCapitationAllocated = (float) $allocations->where('source', 'capitation')->sum('allocated_amount');
        $grandAllocated = (float) $allocations->sum('allocated_amount');
    @endphp

    <div class="header">
        <div class="school-meta">
            <h2>{{ $school['name'] ?? config('app.name') }}</h2>

            @if(!empty($school['code']))
                <p><strong>School Code:</strong> {{ $school['code'] }}</p>
            @endif

            @if(!empty($school['location']))
                <p><strong>Location:</strong> {{ $school['location'] }}</p>
            @endif

            <p><strong>Date Printed:</strong> {{ now()->format('Y-m-d H:i') }}</p>
        </div>

        @if(!empty($school['logo']))
            <img src="{{ asset($school['logo']) }}" alt="School Logo" class="logo">
        @endif
    </div>

    <h3 class="doc-title">Official Student Fee Receipt</h3>

    <div class="grid">
        <div class="card">
            <h4>Receipt Details</h4>
            <div class="row">
                <span class="label">Receipt No</span>
                <span class="value">{{ $payment->receipt_no ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Payment Date</span>
                <span class="value">{{ optional($payment->paid_at)->format('Y-m-d') ?? $payment->paid_at ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Payment Method</span>
                <span class="value">{{ ucfirst($payment->payment_method ?? '-') }}</span>
            </div>
            <div class="row">
                <span class="label">Recorded By</span>
                <span class="value">{{ optional($payment->recorder)->name ?? 'System' }}</span>
            </div>
            <div class="row">
                <span class="label">Organization</span>
                <span class="value">
                    {{ $payment->organization_name ?: '-' }}
                    @if(!empty($payment->organization_id))
                        ({{ $payment->organization_id }})
                    @endif
                </span>
            </div>
        </div>

        <div class="card">
            <h4>Student Details</h4>
            <div class="row">
                <span class="label">Admission No</span>
                <span class="value">{{ $payment->student->admission_no ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Student Name</span>
                <span class="value">
                    {{ trim(($payment->student->first_name ?? '') . ' ' . ($payment->student->last_name ?? '')) ?: '-' }}
                </span>
            </div>
            <div class="row">
                <span class="label">Class / Stream</span>
                <span class="value">
                    {{ $payment->student->class_level ?? '-' }}
                    @if(!empty($payment->student->stream))
                        / {{ $payment->student->stream }}
                    @endif
                </span>
            </div>
            <div class="row">
                <span class="label">Amount Paid (This Receipt)</span>
                <span class="value">{{ number_format((float) ($payment->amount ?? 0), 2) }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 45%;">Description</th>
            <th class="text-right" style="width: 18%;">Due</th>
            <th class="text-right" style="width: 18%;">Paid Total</th>
            <th class="text-right" style="width: 19%;">Balance</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Student Fee Account Summary</td>
            <td class="text-right">{{ number_format((float) ($due ?? 0), 2) }}</td>
            <td class="text-right">{{ number_format((float) ($paidTotal ?? 0), 2) }}</td>
            <td class="text-right">{{ number_format((float) ($balance ?? 0), 2) }}</td>
        </tr>
        </tbody>
    </table>

    {{-- Detailed allocation lines (audit trail) --}}
    @if($allocations->count())
        <div class="mt-20">
            <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700;">
                Allocated By Vote Head (Detailed Lines)
            </h4>

            <table>
                <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 34%;">Vote Head</th>
                    <th style="width: 14%;">Source</th>
                    <th style="width: 12%;">Term</th>
                    <th style="width: 14%;">Order</th>
                    <th class="text-right" style="width: 20%;">Allocated Amount</th>
                </tr>
                </thead>
                <tbody>
                @foreach($allocations as $i => $a)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $a->vote_code }} - {{ $a->vote_name }}</td>
                        <td>{{ ucfirst($a->source) }}</td>
                        <td>{{ $a->term }}</td>
                        <td>{{ $a->allocation_order }}</td>
                        <td class="text-right">{{ number_format((float) $a->allocated_amount, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Grouped summary by vote head (clean accountant summary) --}}
        <div class="mt-20">
            <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700;">
                Allocation Summary By Vote Head
            </h4>

            <table>
                <thead>
                <tr>
                    <th style="width: 40%;">Vote Head</th>
                    <th class="text-right" style="width: 20%;">Parent Allocated</th>
                    <th class="text-right" style="width: 20%;">Capitation Allocated</th>
                    <th class="text-right" style="width: 20%;">Total Allocated</th>
                </tr>
                </thead>
                <tbody>
                @foreach($allocationByVoteHead as $g)
                    <tr>
                        <td>{{ $g->vote_code }} - {{ $g->vote_name }}</td>
                        <td class="text-right">{{ number_format($g->parent_total, 2) }}</td>
                        <td class="text-right">{{ number_format($g->capitation_total, 2) }}</td>
                        <td class="text-right">{{ number_format($g->total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th class="text-right">GRAND TOTAL</th>
                    <th class="text-right">{{ number_format($grandParentAllocated, 2) }}</th>
                    <th class="text-right">{{ number_format($grandCapitationAllocated, 2) }}</th>
                    <th class="text-right">{{ number_format($grandAllocated, 2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="note">No vote-head allocation entries recorded for this receipt.</p>
    @endif

    @if(!empty($payment->notes))
        <div class="mt-16">
            <strong>Notes:</strong>
            <p style="margin-top: 4px;">{{ $payment->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="sign-box">
            <div class="sign-line">Accountant Signature</div>
        </div>
        <div class="sign-box">
            <div class="sign-line">Principal Signature</div>
        </div>
        <div class="sign-box">
            <div class="sign-line">Parent/Payee Signature</div>
        </div>
    </div>

    <div class="print-actions">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <button class="btn secondary" onclick="window.close()">Close</button>
    </div>
</div>
</body>
</html>