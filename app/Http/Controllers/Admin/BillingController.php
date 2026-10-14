<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolInvoicePaymentRequest;
use App\Models\School;
use App\Models\SchoolInvoice;
use App\Models\SchoolInvoicePayment;
use App\Support\SyncsSchoolBillingAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingController extends Controller
{
    use SyncsSchoolBillingAccess;

    public function index(Request $request): Response|InertiaResponse
    {
        $school = app('currentSchool');
        $tab = $request->string('tab', 'invoices')->toString();

        $invoices = SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $payments = SchoolInvoicePayment::query()
            ->where('school_id', $school->id)
            ->with('invoice:id,reference_no,item')
            ->orderByDesc('paid_at')
            ->paginate(25, ['*'], 'payments_page')
            ->withQueryString();

        $totalInvoiced = (float) SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->sum('invoiced_amount');

        $totalBalance = (float) SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->sum('balance_amount');

        $totalPaid = (float) SchoolInvoicePayment::query()
            ->where('school_id', $school->id)
            ->sum('amount');

        $unallocated = (float) SchoolInvoicePayment::query()
            ->where('school_id', $school->id)
            ->whereNull('school_invoice_id')
            ->sum('amount');

        $dueIn7Days = (int) SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $overdueCount = (int) SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        return Inertia::render('Admin/Billing/Index', [
            'activeTab' => $tab,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => [
                'total_invoiced' => number_format($totalInvoiced, 2),
                'total_paid' => number_format($totalPaid, 2),
                'total_balance' => number_format($totalBalance, 2),
                'unallocated' => number_format($unallocated, 2),
                'due_in_7_days' => $dueIn7Days,
                'overdue_count' => $overdueCount,
            ],
        ]);
    }

    public function show(SchoolInvoice $invoice): Response|InertiaResponse
    {
        $school = app('currentSchool');
        abort_if((int) $invoice->school_id !== (int) $school->id, 404);

        $invoice->load([
            'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('id'),
        ]);

        return Inertia::render('Admin/Billing/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'reference_no' => $invoice->reference_no,
                'item' => $invoice->item,
                'category' => $invoice->category,
                'period_start' => optional($invoice->period_start)->toDateString(),
                'period_end' => optional($invoice->period_end)->toDateString(),
                'due_date' => optional($invoice->due_date)->toDateString(),
                'invoiced_amount' => number_format((float) $invoice->invoiced_amount, 2),
                'balance_amount' => number_format((float) $invoice->balance_amount, 2),
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'payments' => $invoice->payments->map(function (SchoolInvoicePayment $payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => number_format((float) $payment->amount, 2),
                        'method' => $payment->method,
                        'reference_no' => $payment->reference_no,
                        'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                        'notes' => $payment->notes,
                    ];
                }),
            ],
        ]);
    }

    public function storePayment(StoreSchoolInvoicePaymentRequest $request, SchoolInvoice $invoice): RedirectResponse
    {
        $school = app('currentSchool');
        abort_if((int) $invoice->school_id !== (int) $school->id, 404);

        $data = $request->validated();

        DB::transaction(function () use ($request, $invoice, $school, $data): void {
            SchoolInvoicePayment::query()->create([
                'school_id' => $school->id,
                'school_invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $request->user()->id,
            ]);

            $paidSoFar = (float) SchoolInvoicePayment::query()
                ->where('school_invoice_id', $invoice->id)
                ->sum('amount');

            $invoicedAmount = (float) $invoice->invoiced_amount;
            $newBalance = max(0.0, round($invoicedAmount - $paidSoFar, 2));
            $status = $this->resolveInvoiceStatus($newBalance, optional($invoice->due_date)->toDateString());

            $invoice->update([
                'balance_amount' => $newBalance,
                'status' => $status,
            ]);

            $this->syncSchoolBillingAccessFromInvoices(
                school: School::query()->findOrFail((int) $school->id),
                changedBy: $request->user()?->id,
                reason: 'payment_posted'
            );
        });

        return redirect()
            ->route('admin.billing.show', $invoice->id)
            ->with('success', 'Payment posted successfully.');
    }

    public function printReceipt(SchoolInvoicePayment $payment): Response|InertiaResponse
    {
        $school = app('currentSchool');
        abort_if((int) $payment->school_id !== (int) $school->id, 404);

        return Inertia::render('Admin/Billing/ReceiptPrint', [
            'receipt' => $this->transformForReceipt($payment),
        ]);
    }

    public function mpesaReceipt(SchoolInvoicePayment $payment): Response|InertiaResponse
    {
        $school = app('currentSchool');
        abort_if((int) $payment->school_id !== (int) $school->id, 404);

        return Inertia::render('Admin/Billing/ReceiptMpesa', [
            'receipt' => $this->transformForReceipt($payment),
        ]);
    }

    public function receiptPdf(SchoolInvoicePayment $payment): BinaryFileResponse
    {
        $school = app('currentSchool');
        abort_if((int) $payment->school_id !== (int) $school->id, 404);

        $receipt = $this->transformForReceipt($payment);

        $canonical = implode('|', [
            $receipt['number'],
            $receipt['raw_amount'],
            $receipt['paid_at'] ?? '',
            $receipt['reference_no'] ?? '',
            $receipt['invoice']['reference_no'] ?? '',
            $receipt['school']['code'] ?? '',
        ]);

        $hash = strtoupper(substr(hash_hmac('sha256', $canonical, (string) config('app.key')), 0, 24));

        $pdf = Pdf::view('pdf.receipts.mpesa', [
            'receipt' => $receipt,
            'verificationHash' => $hash,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->setPaper('A4');

        $filename = 'Receipt-'.$receipt['number'].'.pdf';

        return $pdf->download($filename);
    }

    private function transformForReceipt(SchoolInvoicePayment $payment): array
    {
        $payment->load([
            'invoice:id,reference_no,item,category,due_date',
            'school:id,name,code,logo_path,slug,location,finance_signatory_name,finance_signatory_title,finance_signature_path,stamp_path',
            'receivedBy:id,name,email',
        ]);

        return [
            'id' => $payment->id,
            'number' => 'RCT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
            'raw_amount' => number_format((float) $payment->amount, 2, '.', ''),
            'amount' => number_format((float) $payment->amount, 2, '.', '').' KES',
            'reference_no' => $payment->reference_no,
            'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
            'method' => strtoupper((string) $payment->method),
            'received_by' => optional($payment->receivedBy)->name,
            'invoice' => [
                'reference_no' => optional($payment->invoice)->reference_no,
                'item' => optional($payment->invoice)->item,
                'category' => optional($payment->invoice)->category,
                'due_date' => optional(optional($payment->invoice)->due_date)->toDateString(),
            ],
            'school' => [
                'name' => optional($payment->school)->name,
                'code' => optional($payment->school)->code,
                'location' => optional($payment->school)->location,
                'logo_path' => $this->resolvePublicAssetPath(optional($payment->school)->logo_path),
                'stamp_path' => $this->resolvePublicAssetPath(optional($payment->school)->stamp_path),
                'finance_signatory_name' => optional($payment->school)->finance_signatory_name,
                'finance_signatory_title' => optional($payment->school)->finance_signatory_title,
                'finance_signature_path' => $this->resolvePublicAssetPath(optional($payment->school)->finance_signature_path),
            ],
        ];
    }

    private function resolvePublicAssetPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $clean = ltrim($path, '/');
        $fullPath = public_path($clean);

        return file_exists($fullPath) ? $fullPath : null;
    }
}