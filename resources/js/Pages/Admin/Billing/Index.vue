<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  activeTab: { type: String, default: 'invoices' },
  invoices: { type: Object, default: () => ({ data: [] }) },
  payments: { type: Object, default: () => ({ data: [] }) },
  summary: {
    type: Object,
    default: () => ({
      total_invoiced: '0.00',
      total_paid: '0.00',
      total_balance: '0.00',
      unallocated: '0.00',
      due_in_7_days: 0,
      overdue_count: 0,
    }),
  },
})

const badgeClass = (status) => {
  if (status === 'paid') return 'bg-emerald-500/15 text-emerald-300 border-emerald-400/30'
  if (status === 'partial') return 'bg-amber-500/15 text-amber-300 border-amber-400/30'
  if (status === 'overdue') return 'bg-rose-500/15 text-rose-300 border-rose-400/30'
  return 'bg-slate-500/15 text-slate-300 border-slate-400/30'
}
</script>

<template>
  <Head title="Billing Center" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-6 py-10">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Billing Center</p>
          <h1 class="mt-2 text-3xl font-semibold">School Billing Workspace</h1>
          <p class="mt-2 text-sm text-slate-300">
            Track subscriptions, SMS usage charges, receipts, due dates, and account health in one view.
          </p>
        </div>

        <div class="grid gap-2 text-right text-sm">
          <p>Total Invoiced: <span class="font-semibold">KES {{ summary.total_invoiced }}</span></p>
          <p>Total Paid: <span class="font-semibold text-emerald-300">KES {{ summary.total_paid }}</span></p>
          <p>Total Balance: <span class="font-semibold text-amber-300">KES {{ summary.total_balance }}</span></p>
          <p>Unallocated Balance: <span class="font-semibold">KES {{ summary.unallocated }}</span></p>
        </div>
      </header>

      <section class="mt-8 grid gap-3 sm:grid-cols-3">
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Due Next 7 Days</p>
          <p class="mt-2 text-2xl font-semibold">{{ summary.due_in_7_days }}</p>
        </div>
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Overdue Invoices</p>
          <p class="mt-2 text-2xl font-semibold text-rose-300">{{ summary.overdue_count }}</p>
        </div>
        <div class="border border-white/10 bg-white/[0.02] px-4 py-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Collection Rate</p>
          <p class="mt-2 text-2xl font-semibold">
            {{
              Number(summary.total_invoiced) > 0
                ? Math.round((Number(summary.total_paid) / Number(summary.total_invoiced)) * 100)
                : 0
            }}%
          </p>
        </div>
      </section>

      <nav class="mt-8 flex gap-2 border-b border-white/10 pb-3 text-sm">
        <Link
          :href="route('admin.billing.index', { tab: 'invoices' })"
          class="border px-4 py-2"
          :class="activeTab === 'invoices' ? 'border-emerald-300 bg-emerald-300 text-slate-950' : 'border-white/20 hover:bg-white/10'"
        >
          Invoices
        </Link>
        <Link
          :href="route('admin.billing.index', { tab: 'receipts' })"
          class="border px-4 py-2"
          :class="activeTab === 'receipts' ? 'border-emerald-300 bg-emerald-300 text-slate-950' : 'border-white/20 hover:bg-white/10'"
        >
          Receipts
        </Link>
      </nav>

      <section v-if="activeTab === 'invoices'" class="mt-5 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Ref</th>
              <th class="px-4 py-3 font-medium">Item</th>
              <th class="px-4 py-3 font-medium">Period</th>
              <th class="px-4 py-3 font-medium">Invoiced</th>
              <th class="px-4 py-3 font-medium">Balance</th>
              <th class="px-4 py-3 font-medium">Due Date</th>
              <th class="px-4 py-3 font-medium">Status</th>
              <th class="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices.data" :key="invoice.id" class="border-b border-white/5">
              <td class="px-4 py-3">{{ invoice.reference_no }}</td>
              <td class="px-4 py-3">{{ invoice.item }}</td>
              <td class="px-4 py-3 text-slate-300">
                {{ invoice.period_start || '-' }} - {{ invoice.period_end || '-' }}
              </td>
              <td class="px-4 py-3">KES {{ Number(invoice.invoiced_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
              <td class="px-4 py-3">KES {{ Number(invoice.balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
              <td class="px-4 py-3">{{ invoice.due_date || '-' }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center border px-2 py-1 text-xs" :class="badgeClass(invoice.status)">
                  {{ invoice.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <Link
                  :href="route('admin.billing.show', invoice.id)"
                  class="border border-white/20 px-3 py-1 hover:bg-white/10"
                >
                  View Details
                </Link>
              </td>
            </tr>
            <tr v-if="!invoices.data?.length">
              <td colspan="8" class="px-4 py-8 text-center text-slate-400">No invoices found.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-else class="mt-5 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Date</th>
              <th class="px-4 py-3 font-medium">Invoice Ref</th>
              <th class="px-4 py-3 font-medium">Item</th>
              <th class="px-4 py-3 font-medium">Method</th>
              <th class="px-4 py-3 font-medium">Reference</th>
              <th class="px-4 py-3 font-medium">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments.data" :key="payment.id" class="border-b border-white/5">
              <td class="px-4 py-3">{{ payment.paid_at }}</td>
              <td class="px-4 py-3">{{ payment.invoice?.reference_no || '-' }}</td>
              <td class="px-4 py-3">{{ payment.invoice?.item || 'Unallocated' }}</td>
              <td class="px-4 py-3">{{ payment.method }}</td>
              <td class="px-4 py-3">{{ payment.reference_no || '-' }}</td>
              <td class="px-4 py-3">KES {{ Number(payment.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
            </tr>
            <tr v-if="!payments.data?.length">
              <td colspan="6" class="px-4 py-8 text-center text-slate-400">No receipts found.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>