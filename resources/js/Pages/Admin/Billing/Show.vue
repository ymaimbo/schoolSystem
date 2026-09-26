<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  invoice: {
    type: Object,
    required: true,
  },
})

const form = useForm({
  amount: '',
  method: 'bank',
  reference_no: '',
  paid_at: new Date().toISOString().slice(0, 16),
  notes: '',
})

const submitPayment = () => {
  form.post(route('admin.billing.payments.store', props.invoice.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('amount', 'reference_no', 'notes')
      form.paid_at = new Date().toISOString().slice(0, 16)
      form.method = 'bank'
    },
  })
}
</script>

<template>
  <Head :title="`Invoice ${invoice.reference_no}`" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-5xl px-6 py-10">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Invoice Details</h1>
        <Link :href="route('admin.billing.index')" class="border border-white/20 px-4 py-2 hover:bg-white/10">
          Back To Billing
        </Link>
      </div>

      <section class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="border border-white/10 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Reference</p>
          <p class="mt-2 font-semibold">{{ invoice.reference_no }}</p>
        </div>
        <div class="border border-white/10 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Invoiced</p>
          <p class="mt-2 font-semibold">KES {{ invoice.invoiced_amount }}</p>
        </div>
        <div class="border border-white/10 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Balance</p>
          <p class="mt-2 font-semibold text-amber-300">KES {{ invoice.balance_amount }}</p>
        </div>
        <div class="border border-white/10 p-4">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Due Date</p>
          <p class="mt-2 font-semibold">{{ invoice.due_date || '-' }}</p>
        </div>
      </section>

      <section class="mt-6 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Invoice Metadata</h2>
        <p class="mt-3 text-sm text-slate-300"><span class="text-slate-400">Item:</span> {{ invoice.item }}</p>
        <p class="mt-1 text-sm text-slate-300"><span class="text-slate-400">Category:</span> {{ invoice.category }}</p>
        <p class="mt-1 text-sm text-slate-300">
          <span class="text-slate-400">Period:</span> {{ invoice.period_start || '-' }} - {{ invoice.period_end || '-' }}
        </p>
        <p class="mt-1 text-sm text-slate-300"><span class="text-slate-400">Status:</span> {{ invoice.status }}</p>
        <p class="mt-2 text-sm text-slate-300"><span class="text-slate-400">Notes:</span> {{ invoice.notes || 'No notes' }}</p>
      </section>

      <section class="mt-6 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Record Payment</h2>

        <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="submitPayment">
          <input v-model="form.amount" type="number" step="0.01" min="1" placeholder="Amount" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <select v-model="form.method" class="border border-white/20 bg-slate-900 px-3 py-2">
            <option value="bank">Bank</option>
            <option value="mpesa">M-Pesa</option>
            <option value="cash">Cash</option>
            <option value="cheque">Cheque</option>
          </select>
          <input v-model="form.reference_no" type="text" placeholder="Payment Reference" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.paid_at" type="datetime-local" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <textarea v-model="form.notes" rows="2" placeholder="Notes" class="border border-white/20 bg-slate-900 px-3 py-2 sm:col-span-2" />

          <div class="sm:col-span-2 flex items-center gap-2">
            <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 font-semibold text-slate-950" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Post Payment' }}
            </button>
          </div>

          <p v-for="(message, key) in form.errors" :key="key" class="sm:col-span-2 text-sm text-rose-300">{{ message }}</p>
        </form>
      </section>

      <section class="mt-6 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Payment History</h2>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-white/10 text-slate-300">
              <tr>
                <th class="px-3 py-2 font-medium">Date</th>
                <th class="px-3 py-2 font-medium">Method</th>
                <th class="px-3 py-2 font-medium">Reference</th>
                <th class="px-3 py-2 font-medium">Amount</th>
                <th class="px-3 py-2 font-medium">Notes</th>
                <th class="px-3 py-2 font-medium">Receipt</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in invoice.payments" :key="payment.id" class="border-b border-white/5">
                <td class="px-3 py-2">{{ payment.paid_at }}</td>
                <td class="px-3 py-2">{{ payment.method }}</td>
                <td class="px-3 py-2">{{ payment.reference_no || '-' }}</td>
                <td class="px-3 py-2">KES {{ payment.amount }}</td>
                <td class="px-3 py-2">{{ payment.notes || '-' }}</td>
                <td class="px-3 py-2">
                  <div class="flex gap-2">
                    <Link :href="route('admin.billing.receipts.mpesa', payment.id)" class="border border-white/20 px-3 py-1 hover:bg-white/10">
                      MPesa View
                    </Link>
                    <a :href="route('admin.billing.receipts.pdf', payment.id)" class="border border-emerald-400/40 px-3 py-1 text-emerald-200 hover:bg-emerald-500/10">
                      PDF
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="!invoice.payments?.length">
                <td colspan="6" class="px-3 py-6 text-center text-slate-400">No payments posted for this invoice yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>