<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $receipt['number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
        .card { background: #ffffff; border: 1px solid #dbeafe; border-radius: 8px; overflow: hidden; }
        .header { background: #059669; color: #ffffff; padding: 18px 20px; }
        .header .small { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .header h1 { margin: 6px 0 2px 0; font-size: 22px; }
        .header p { margin: 0; font-size: 12px; }
        .content { padding: 20px; }
        .amount-box { border: 1px solid #a7f3d0; background: #ecfdf5; padding: 14px; border-radius: 6px; }
        .amount-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #047857; }
        .amount { font-size: 28px; font-weight: bold; color: #065f46; margin: 4px 0; }
        .meta { margin-top: 18px; border-top: 1px solid #e2e8f0; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .label { color: #64748b; }
        .value { font-weight: bold; color: #0f172a; }
        .notes { margin-top: 14px; padding: 10px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 12px; color: #334155; }
        .approvals { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 14px; }
        .approval-grid { width: 100%; border-collapse: collapse; }
        .approval-grid td { width: 50%; vertical-align: top; padding-right: 12px; }
        .box-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 8px; }
        .stamp-box, .signature-box {
            height: 110px;
            border: 1px dashed #94a3b8;
            background: #f8fafc;
            border-radius: 6px;
            position: relative;
            text-align: center;
        }
        .placeholder {
            position: absolute;
            left: 0;
            right: 0;
            top: 45%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #64748b;
        }
        .stamp-img, .signature-img {
            max-width: 90%;
            max-height: 90px;
            margin-top: 10px;
        }
        .sign-meta {
            margin-top: 8px;
            font-size: 11px;
            color: #334155;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px 20px;
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
        }
        .hash {
            font-family: DejaVu Sans Mono, monospace;
            color: #0f172a;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="small">Payment Receipt</div>
            <h1>{{ $receipt['school']['name'] }}</h1>
            <p>Receipt No: {{ $receipt['number'] }}</p>
        </div>

        <div class="content">
            <div class="amount-box">
                <div class="amount-label">Amount Received</div>
                <div class="amount">KES {{ $receipt['amount'] }}</div>
                <div style="font-size:12px;color:#047857;">{{ $receipt['paid_at'] }}</div>
            </div>

            <div class="meta">
                <div class="row"><span class="label">Payment Method</span><span class="value">{{ $receipt['method'] }}</span></div>
                <div class="row"><span class="label">Transaction Ref</span><span class="value">{{ $receipt['reference_no'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Invoice Ref</span><span class="value">{{ $receipt['invoice']['reference_no'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Invoice Item</span><span class="value">{{ $receipt['invoice']['item'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Received By</span><span class="value">{{ $receipt['received_by'] ?? '-' }}</span></div>
                <div class="row"><span class="label">School Code</span><span class="value">{{ $receipt['school']['code'] ?? '-' }}</span></div>
            </div>

            @if(!empty($receipt['notes']))
                <div class="notes">{{ $receipt['notes'] }}</div>
            @endif

            <div class="approvals">
                <table class="approval-grid">
                    <tr>
                        <td>
                            <div class="box-title">Official School Stamp</div>
                            <div class="stamp-box">
                                @if(!empty($receipt['school']['stamp_path']))
                                    <img class="stamp-img" src="{{ $receipt['school']['stamp_path'] }}" alt="School Stamp">
                                @else
                                    <div class="placeholder">STAMP PLACEHOLDER</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="box-title">Authorized Signature</div>
                            <div class="signature-box">
                                @if(!empty($receipt['school']['finance_signature_path']))
                                    <img class="signature-img" src="{{ $receipt['school']['finance_signature_path'] }}" alt="Authorized Signature">
                                @else
                                    <div class="placeholder">SIGNATURE PLACEHOLDER</div>
                                @endif
                            </div>
                            <div class="sign-meta">
                                {{ $receipt['school']['finance_signatory_name'] ?? 'Finance Signatory Name' }}<br>
                                {{ $receipt['school']['finance_signatory_title'] ?? 'Finance Office' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            Official system-generated receipt. Valid for school finance records.<br>
            Generated At: {{ $generatedAt }}<br>
            Verification Hash: <span class="hash">{{ $verificationHash }}</span><br>
            Integrity Rule: Any change to amount, date, reference, invoice or school code invalidates this hash.
        </div>
    </div>
</body>
</html>