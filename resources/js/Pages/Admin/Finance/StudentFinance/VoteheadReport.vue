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
</script>

<template>
  <Head title="Votehead Report" />
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <section class="mb-6 flex items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-white">Votehead Collections Report</h1>
          <p class="mt-1 text-sm text-slate-300">
            Period: <strong>{{ period }}</strong> |
            From: <strong>{{ from || '-' }}</strong> |
            To: <strong>{{ to || '-' }}</strong>
          </p>
        </div>

        <div class="flex gap-2">
          <Link
            href="/admin/student-finance"
            class="rounded-md border border-white/20 px-3 py-2 text-sm text-slate-200 hover:bg-white/5"
          >
            Back
          </Link>
          <button
            type="button"
            class="rounded-md bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
            @click="window.print()"
          >
            Print
          </button>
        </div>
      </section>

      <section class="mb-6 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <p class="text-sm text-slate-300">Grand Total</p>
        <p class="mt-2 text-2xl font-bold text-emerald-300">{{ formatKES(grand_total) }}</p>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 overflow-x-auto">
        <table class="min-w-full divide-y divide-white/10 text-sm">
          <thead class="bg-slate-950/50 text-left text-slate-300">
            <tr>
              <th class="px-3 py-2">Votehead</th>
              <th class="px-3 py-2">Profile</th>
              <th class="px-3 py-2 text-right">Collected</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="row in totalRows" :key="`vh-r-${row.vote_head_id}-${row.fee_profile_id}`">
              <td class="px-3 py-2 text-white">{{ row.vote_head_name }}</td>
              <td class="px-3 py-2 text-slate-300">{{ row.fee_profile_name || row.fee_profile_code || 'Unassigned' }}</td>
              <td class="px-3 py-2 text-right font-semibold text-cyan-300">{{ formatKES(row.total_collected) }}</td>
            </tr>
            <tr v-if="!totalRows.length">
              <td colspan="3" class="px-3 py-8 text-center text-slate-400">No votehead collection data for selected period.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>