<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  period: { type: String, default: 'month' },
  from: { type: String, default: null },
  to: { type: String, default: null },
  grand_total: { type: Number, default: 0 },
  rows: { type: Array, default: () => [] },
})

const formatKES = (value) => {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

const totalRows = computed(() => props.rows ?? [])

const grandTotal = computed(() =>
  totalRows.value.reduce((sum, row) => sum + Number(row.total_collected ?? 0), 0)
)

// FIX: window.print() must be called from a method, not inline in template.
// In Vue 3, template expressions do not resolve window — it throws undefined.
const printReport = () => window.print()
</script>

<template>
  <Head title="Votehead Collections Report" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

      <!-- Header -->
      <section class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-white">Votehead Collections Report</h1>
          <p class="mt-1 text-sm text-slate-300">
            Period: <strong class="text-slate-100">{{ period }}</strong>
            <span class="mx-2 text-slate-600">|</span>
            From: <strong class="text-slate-100">{{ from || '-' }}</strong>
            <span class="mx-2 text-slate-600">|</span>
            To: <strong class="text-slate-100">{{ to || '-' }}</strong>
          </p>
        </div>

        <div class="flex gap-2 no-print">
          <Link
            href="/admin/student-finance"
            class="rounded-md border border-white/20 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
          >
            ← Back
          </Link>
          <button
            type="button"
            class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
            @click="printReport"
          >
            Print
          </button>
        </div>
      </section>

      <!-- Grand total card -->
      <section class="mb-6 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-wider text-slate-400">Grand Total Collected</p>
            <p class="mt-2 text-3xl font-bold text-emerald-300">{{ formatKES(grand_total) }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs uppercase tracking-wider text-slate-400">Votehead Lines</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ totalRows.length }}</p>
          </div>
        </div>
      </section>

      <!-- Report table -->
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 overflow-x-auto">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">
          Breakdown by Votehead &amp; Fee Profile
        </h2>

        <table class="min-w-full divide-y divide-white/10 text-sm">
          <thead class="bg-slate-950/50 text-left text-slate-300">
            <tr>
              <th class="px-3 py-3 font-semibold">Votehead</th>
              <th class="px-3 py-3 font-semibold">Profile</th>
              <th class="px-3 py-3 text-right font-semibold">Collected (KES)</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-white/5">
            <tr
              v-for="row in totalRows"
              :key="`vh-r-${row.vote_head_id}-${row.fee_profile_id}`"
              class="hover:bg-white/5 transition-colors"
            >
              <td class="px-3 py-3 text-white font-medium">
                {{ row.vote_head_name }}
              </td>
              <td class="px-3 py-3 text-slate-300">
                <span v-if="row.fee_profile_name || row.fee_profile_code">
                  {{ row.fee_profile_code ? `${row.fee_profile_code} — ` : '' }}{{ row.fee_profile_name }}
                </span>
                <span v-else class="italic text-slate-500">Unassigned</span>
              </td>
              <td class="px-3 py-3 text-right font-semibold text-cyan-300">
                {{ formatKES(row.total_collected) }}
              </td>
            </tr>

            <tr v-if="!totalRows.length">
              <td colspan="3" class="px-3 py-10 text-center text-slate-400 italic">
                No votehead collection data for the selected period.
              </td>
            </tr>
          </tbody>

          <!-- Grand total footer -->
          <tfoot v-if="totalRows.length" class="border-t-2 border-white/20 bg-slate-950/50">
            <tr>
              <td class="px-3 py-3 font-bold text-slate-200" colspan="2">Grand Total</td>
              <td class="px-3 py-3 text-right font-bold text-emerald-300">
                {{ formatKES(grandTotal) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </section>

    </main>
  </div>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>