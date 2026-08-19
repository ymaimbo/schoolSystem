<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\PaymentVoucher;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $searchVoucher = trim((string) $request->get('search_voucher', ''));
        $type = trim((string) $request->get('type', ''));

        $transactions = FinanceTransaction::query()
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20, ['*'], 'transactions_page')
            ->withQueryString();

        $vouchers = PaymentVoucher::query()
            ->with(['preparer:id,name', 'approver:id,name'])
            ->when($searchVoucher !== '', fn ($q) => $q->where('voucher_no', 'like', "%{$searchVoucher}%"))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20, ['*'], 'vouchers_page')
            ->withQueryString();

        $income = (float) FinanceTransaction::query()->where('type', 'income')->sum('amount');
        $expense = (float) FinanceTransaction::query()->where('type', 'expense')->sum('amount');

        return Inertia::render('Admin/Finance/Index', [
            'transactions' => $transactions,
            'vouchers' => $vouchers,
            'filters' => [
                'search_voucher' => $searchVoucher,
                'type' => $type,
            ],
            'voucherMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:64'],
            'reference_no' => ['nullable', 'string', 'max:255', 'unique:finance_transactions,reference_no'],
        ]);

        if (empty($data['reference_no'])) {
            $data['reference_no'] = $this->generateRunningNumber(FinanceTransaction::class, 'reference_no', 'FIN');
        }

        FinanceTransaction::create($data);

        return back()->with('success', 'Finance transaction recorded.');
    }

    public function update(Request $request, FinanceTransaction $financeTransaction): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:64'],
            'reference_no' => ['nullable', 'string', 'max:255', 'unique:finance_transactions,reference_no,' . $financeTransaction->id],
        ]);

        $financeTransaction->update($data);

        return back()->with('success', 'Finance transaction updated.');
    }

    public function destroy(FinanceTransaction $financeTransaction): RedirectResponse
    {
        $financeTransaction->delete();

        return back()->with('success', 'Finance transaction deleted.');
    }

    public function storeVoucher(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank,mpesa,cheque,transfer'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $voucherNo = $this->generateRunningNumber(PaymentVoucher::class, 'voucher_no', 'PV');

        $principalId = User::query()
            ->where('role', 'principal')
            ->orderBy('id')
            ->value('id');

        PaymentVoucher::create([
            'voucher_no' => $voucherNo,
            'supplier_name' => $data['supplier_name'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'purpose' => $data['purpose'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'paid_at' => $data['paid_at'],
            'prepared_by' => auth()->id(),
            'approved_by' => $principalId,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', "Payment voucher created. Voucher No: {$voucherNo}");
    }

    public function destroyVoucher(PaymentVoucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return back()->with('success', 'Voucher deleted.');
    }

    public function printVoucher(PaymentVoucher $voucher)
    {
        $school = $this->schoolInfo();
        $voucher->load(['preparer', 'approver']);

        return view('admin.finance.print-voucher', [
            'school' => $school,
            'voucher' => $voucher,
        ]);
    }

    private function schoolInfo(): array
    {
        $school = class_exists(School::class) ? School::query()->first() : null;

        return [
            'name' => $school?->name ?? config('app.name', 'Vigurungani Senior School'),
            'code' => $school?->code ?? '2109104',
            'location' => $school?->location ?? 'Kinango Constituency, Kwale County, Kenya',
            'logo' => '/images/logo.png',
        ];
    }

    private function generateRunningNumber(string $modelClass, string $column, string $prefix): string
    {
        $date = now()->format('Ymd');
        $base = "{$prefix}-{$date}-";

        $latest = $modelClass::query()
            ->where($column, 'like', "{$base}%")
            ->orderByDesc($column)
            ->value($column);

        $next = 1;
        if ($latest && Str::contains($latest, $base)) {
            $lastDigits = (int) substr($latest, -4);
            $next = $lastDigits + 1;
        }

        return $base . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}