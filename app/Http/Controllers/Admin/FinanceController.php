<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['type', 'category', 'from', 'to', 'payment_method']);

        $query = FinanceTransaction::query()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', 'like', "%{$category}%"))
            ->when($filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('entry_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('entry_date', '<=', $to));

        $transactions = (clone $query)->latest('entry_date')->latest('id')->paginate(20)->withQueryString();

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return Inertia::render('Admin/Finance/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'totals' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'balance' => (float) ($income - $expense),
            ],
            'overall' => [
                'income' => (float) FinanceTransaction::where('type', 'income')->sum('amount'),
                'expense' => (float) FinanceTransaction::where('type', 'expense')->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'reference_no' => ['nullable', 'string', 'max:64'],
        ]);

        FinanceTransaction::create($validated);

        return back()->with('success', 'Transaction created successfully.');
    }

    public function update(Request $request, FinanceTransaction $financeTransaction): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'reference_no' => ['nullable', 'string', 'max:64'],
        ]);

        $financeTransaction->update($validated);

        return back()->with('success', 'Transaction updated successfully.');
    }

    public function destroy(FinanceTransaction $financeTransaction): RedirectResponse
    {
        $financeTransaction->delete();

        return back()->with('success', 'Transaction deleted successfully.');
    }
}