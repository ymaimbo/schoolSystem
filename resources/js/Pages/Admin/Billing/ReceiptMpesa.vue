<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  receipt: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head :title="`MPesa Style Receipt ${receipt.number}`" />

  <div class="min-h-screen bg-slate-100 px-4 py-8 text-slate-900">
    <main class="mx-auto w-full max-w-xl">
      <div class="mb-4 flex items-center justify-between print:hidden">
        <Link :href="route('admin.billing.show', receipt.invoice.reference_no ? undefined : undefined)" class="hidden" />
        <Link :href="route('admin.billing.index', { tab: 'receipts' })" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
          Back
        </Link>
        <a
          :href="route('admin.billing.receipts.pdf', receipt.id)"
          class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
        >
          Download PDF
        </a>
      </div>

      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow">
        <header class="bg-emerald-600 px-6 py-5 text-white">
          <p class="text-xs uppercase tracking-[0.2em]">Payment Receipt</p>
          <h1 class="mt-1 text-2xl font-bold">{{ receipt.school.name }}</h1>
          <p class="mt-1 text-sm text-emerald-100">Receipt No: {{ receipt.number }}</p>
        </header>

        <div class="px-6 py-6">
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Amount Received</p>
            <p class="mt-1 text-3xl font-bold text-emerald-800">KES {{ receipt.amount }}</p>
            <p class="mt-1 text-sm text-emerald-700">{{ receipt.paid_at }}</p>
          </div>

          <div class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500">Payment Method</span>
              <span class="font-semibold">{{ receipt.method }}</span>
            </div>
            <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500">Transaction Ref</span>
              <span class="font-semibold">{{ receipt.reference_no || '-' }}</span>
            </div>
            <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500">Invoice Ref</span>
              <span class="font-semibold">{{ receipt.invoice.reference_no || '-' }}</span>
            </div>
            <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500">Invoice Item</span>
              <span class="font-semibold">{{ receipt.invoice.item || '-' }}</span>
            </div>
            <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
              <span class="text-slate-500">Received By</span>
              <span class="font-semibold">{{ receipt.received_by || '-' }}</span>
            </div>
            <div class="flex justify-between gap-3">
              <span class="text-slate-500">School Code</span>
              <span class="font-semibold">{{ receipt.school.code || '-' }}</span>
            </div>
          </div>

          <p v-if="receipt.notes" class="mt-5 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
            {{ receipt.notes }}
          </p>
        </div>

        <footer class="border-t border-slate-200 bg-slate-50 px-6 py-3 text-xs text-slate-500">
          Official system-generated receipt. Shareable for finance records.
        </footer>
      </section>
    </main>
  </div>
</template>