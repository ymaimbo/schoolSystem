<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  receipt: {
    type: Object,
    required: true,
  },
})

const doPrint = () => {
  window.print()
}
</script>

<template>
  <Head :title="`Receipt ${receipt.number}`" />

  <div class="min-h-screen bg-slate-100 text-slate-900 print:bg-white">
    <main class="mx-auto w-full max-w-3xl px-4 py-8 print:px-0">
      <div class="mb-6 flex items-center justify-between print:hidden">
        <Link :href="route('admin.billing.index', { tab: 'receipts' })" class="border border-slate-300 px-4 py-2 text-sm hover:bg-slate-200">
          Back To Billing
        </Link>
        <button type="button" class="border border-emerald-500 bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600" @click="doPrint">
          Print Receipt
        </button>
      </div>

      <section class="border border-slate-300 bg-white p-6 shadow-sm print:shadow-none">
        <header class="border-b border-slate-200 pb-4">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs uppercase tracking-[0.22em] text-slate-500">School Billing Receipt</p>
              <h1 class="mt-1 text-2xl font-bold">{{ receipt.school.name }}</h1>
              <p class="mt-1 text-sm text-slate-600">
                {{ receipt.school.location || '-' }}, {{ receipt.school.county || '-' }}
              </p>
              <p class="mt-1 text-sm text-slate-600">School Code: {{ receipt.school.code || '-' }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-slate-600">Receipt No</p>
              <p class="text-xl font-bold">{{ receipt.number }}</p>
              <p class="mt-2 text-sm text-slate-600">Date</p>
              <p class="font-semibold">{{ receipt.paid_at }}</p>
            </div>
          </div>
        </header>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div>
            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Payment Details</p>
            <div class="mt-2 space-y-1 text-sm">
              <p><span class="text-slate-600">Method:</span> <span class="font-semibold">{{ receipt.method }}</span></p>
              <p><span class="text-slate-600">Reference:</span> <span class="font-semibold">{{ receipt.reference_no || '-' }}</span></p>
              <p><span class="text-slate-600">Received By:</span> <span class="font-semibold">{{ receipt.received_by || '-' }}</span></p>
            </div>
          </div>

          <div>
            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Invoice Link</p>
            <div class="mt-2 space-y-1 text-sm">
              <p><span class="text-slate-600">Invoice Ref:</span> <span class="font-semibold">{{ receipt.invoice.reference_no || '-' }}</span></p>
              <p><span class="text-slate-600">Item:</span> <span class="font-semibold">{{ receipt.invoice.item || '-' }}</span></p>
              <p><span class="text-slate-600">Category:</span> <span class="font-semibold">{{ receipt.invoice.category || '-' }}</span></p>
            </div>
          </div>
        </div>

        <div class="mt-6 border border-slate-300">
          <div class="grid grid-cols-3 border-b border-slate-300 bg-slate-100 text-sm font-semibold">
            <p class="px-3 py-2">Description</p>
            <p class="px-3 py-2">Receipt Ref</p>
            <p class="px-3 py-2 text-right">Amount (KES)</p>
          </div>
          <div class="grid grid-cols-3 text-sm">
            <p class="px-3 py-2">{{ receipt.invoice.item || 'School billing payment' }}</p>
            <p class="px-3 py-2">{{ receipt.reference_no || receipt.number }}</p>
            <p class="px-3 py-2 text-right font-semibold">{{ receipt.amount }}</p>
          </div>
        </div>

        <div class="mt-4 flex justify-end">
          <div class="w-full max-w-sm border border-slate-300">
            <div class="flex items-center justify-between border-b border-slate-300 px-3 py-2 text-sm">
              <span class="text-slate-600">Amount Received</span>
              <span class="font-semibold">KES {{ receipt.amount }}</span>
            </div>
            <div class="px-3 py-2 text-xs text-slate-600">
              This is a system-generated receipt and is valid without signature.
            </div>
          </div>
        </div>

        <div v-if="receipt.notes" class="mt-5 border-t border-slate-200 pt-3">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Notes</p>
          <p class="mt-1 text-sm text-slate-700">{{ receipt.notes }}</p>
        </div>
      </section>
    </main>
  </div>
</template>