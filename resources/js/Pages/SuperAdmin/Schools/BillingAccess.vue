<!-- resources/js/Pages/SuperAdmin/Schools/BillingAccess.vue -->
<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
  school: Object,
  rules: Object,
})

const form = useForm({
  billing_cycle_amount_due: props.school.billing_cycle_amount_due ?? 0,
  billing_cycle_amount_paid: props.school.billing_cycle_amount_paid ?? 0,
  billing_grace_until: props.school.billing_grace_until ?? '',
  billing_access_override: !!props.school.billing_access_override,
  billing_access_override_reason: props.school.billing_access_override_reason ?? '',
  reason: 'manual_update',
})

const submit = () => {
  form.put(route('superadmin.schools.billing-access.update', props.school.id))
}

const recalc = () => {
  form.post(route('superadmin.schools.billing-access.recalculate', props.school.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head :title="`Billing Access - ${school.name}`" />
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-4xl px-4 py-8">
      <h1 class="text-2xl font-bold text-white">Billing Access: {{ school.name }}</h1>
      <p class="mt-1 text-sm text-slate-300">
        Current stage: <span class="font-semibold text-cyan-300">{{ school.billing_access_stage }}</span>
        | Paid %: <span class="font-semibold text-emerald-300">{{ school.billing_paid_percent }}%</span>
      </p>

      <form class="mt-6 space-y-4 rounded-xl border border-white/10 bg-slate-900/70 p-5" @submit.prevent="submit">
        <div class="grid gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs uppercase text-slate-400">Cycle Amount Due</label>
            <input v-model="form.billing_cycle_amount_due" type="number" step="0.01" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="mb-1 block text-xs uppercase text-slate-400">Cycle Amount Paid</label>
            <input v-model="form.billing_cycle_amount_paid" type="number" step="0.01" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-xs uppercase text-slate-400">Grace Until (optional)</label>
          <input v-model="form.billing_grace_until" type="datetime-local" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" />
        </div>

        <div class="flex items-center gap-2">
          <input id="override" v-model="form.billing_access_override" type="checkbox" />
          <label for="override" class="text-sm">Enable manual override (unlock access)</label>
        </div>

        <div>
          <label class="mb-1 block text-xs uppercase text-slate-400">Override Reason</label>
          <textarea v-model="form.billing_access_override_reason" rows="2" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm" />
        </div>

        <div class="flex gap-2">
          <button type="submit" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">
            Save Billing Access
          </button>
          <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm" @click="recalc">
            Recalculate Stage
          </button>
        </div>
      </form>

      <section class="mt-6 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold">Role Access Rules</h2>
        <pre class="mt-3 overflow-auto text-xs text-slate-300">{{ rules }}</pre>
      </section>
    </main>
  </div>
</template>