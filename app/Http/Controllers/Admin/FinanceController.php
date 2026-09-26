<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\PaymentVoucher;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();

        $searchVoucher = trim((string) $request->get('search_voucher', ''));
        $type = trim((string) $request->get('type', ''));

        $transactions = FinanceTransaction::query()
            ->where('school_id', $schoolId)
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20, ['*'], 'transactions_page')
            ->withQueryString();

        $vouchers = PaymentVoucher::query()
            ->where('school_id', $schoolId)
            ->with(['preparer:id,name', 'approver:id,name'])
            ->when($searchVoucher !== '', fn ($q) => $q->where('voucher_no', 'like', "%{$searchVoucher}%"))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20, ['*'], 'vouchers_page')
            ->withQueryString();

        $income = (float) FinanceTransaction::query()
            ->where('school_id', $schoolId)
            ->where('type', 'income')
            ->sum('amount');

        $expense = (float) FinanceTransaction::query()
            ->where('school_id', $schoolId)
            ->where('type', 'expense')
            ->sum('amount');

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
        $schoolId = $this->currentSchoolId();

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:64'],
            'reference_no' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['reference_no'])) {
            $data['reference_no'] = $this->generateSchoolRunningNumber(
                FinanceTransaction::class,
                'reference_no',
                'FIN',
                $schoolId
            );
        }

        $data['school_id'] = $schoolId;

        FinanceTransaction::create($data);

        return back()->with('success', 'Finance transaction recorded.');
    }

    public function update(Request $request, FinanceTransaction $financeTransaction): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $financeTransaction->school_id !== $schoolId, 404);

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:64'],
            'reference_no' => ['nullable', 'string', 'max:255'],
        ]);

        $financeTransaction->update($data);

        return back()->with('success', 'Finance transaction updated.');
    }

    public function destroy(FinanceTransaction $financeTransaction): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $financeTransaction->school_id !== $schoolId, 404);

        $financeTransaction->delete();

        return back()->with('success', 'Finance transaction deleted.');
    }

    public function storeVoucher(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank,mpesa,cheque,transfer'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $voucherNo = $this->generateSchoolRunningNumber(
            PaymentVoucher::class,
            'voucher_no',
            'PV',
            $schoolId,
            $data['paid_at']
        );

        $principalId = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'principal')
            ->orderBy('id')
            ->value('id');

        PaymentVoucher::create([
            'school_id' => $schoolId,
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
        $schoolId = $this->currentSchoolId();
        abort_if((int) $voucher->school_id !== $schoolId, 404);

        $voucher->delete();

        return back()->with('success', 'Voucher deleted.');
    }

    public function printVoucher(PaymentVoucher $voucher)
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $voucher->school_id !== $schoolId, 404);

        $school = $this->schoolInfo();
        $voucher->load(['preparer', 'approver']);

        return view('admin.finance.print-voucher', [
            'school' => $school,
            'voucher' => $voucher,
        ]);
    }

    private function schoolInfo(): array
    {
        $school = app()->bound('currentSchool')
            ? app('currentSchool')
            : School::query()->find(session('school_id'));

        return [
            'name' => $school?->name ?? config('app.name', 'School ERP'),
            'code' => $school?->code ?? 'N/A',
            'location' => $school?->location ?? 'N/A',
            'logo' => $school?->logo_path ?? '/images/logo.png',
        ];
    }

    private function currentSchoolId(): int
    {
        $school = app('currentSchool');

        if (! $school || empty($school->id)) {
            abort(403, 'No school context found.');
        }

        return (int) $school->id;
    }

    private function generateSchoolRunningNumber(
        string $modelClass,
        string $column,
        string $prefix,
        int $schoolId,
        ?string $dateString = null
    ): string {
        $date = $dateString ? now()->parse($dateString)->format('Ymd') : now()->format('Ymd');
        $base = sprintf('%s-S%03d-%s-', $prefix, $schoolId, $date);

        return DB::transaction(function () use ($modelClass, $column, $schoolId, $base): string {
            $latest = $modelClass::query()
                ->where('school_id', $schoolId)
                ->where($column, 'like', "{$base}%")
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $next = 1;
            if ($latest && Str::startsWith($latest, $base)) {
                $suffix = (int) substr($latest, -4);
                $next = $suffix + 1;
            }

            return $base . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }
}