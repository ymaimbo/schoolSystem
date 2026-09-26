<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  schools: { type: Array, default: () => [] },
  invoice: { type: Object, required: true },
})

const form = useForm({
  school_id: props.invoice.school_id || '',
  reference_no: props.invoice.reference_no || '',
  item: props.invoice.item || '',
  category: props.invoice.category || 'subscription',
  period_start: props.invoice.period_start || '',
  period_end: props.invoice.period_end || '',
  due_date: props.invoice.due_date || '',
  invoiced_amount: props.invoice.invoiced_amount || '',
  balance_amount: props.invoice.balance_amount || '',
  status: props.invoice.status || 'unpaid',
  notes: props.invoice.notes || '',
})

const submit = () => {
  form.put(route('superadmin.billing.update', props.invoice.id))
}
</script>

<template>
  <Head title="Edit Invoice" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-4xl px-6 py-10">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Invoice</h1>
        <Link :href="route('superadmin.billing.index')" class="border border-white/20 px-4 py-2 hover:bg-white/10">
          Back
        </Link>
      </div>

      <form class="mt-6 grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
        <select v-model="form.school_id" class="border border-white/20 bg-slate-900 px-3 py-2">
          <option value="">Select School</option>
          <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
        </select>
        <input v-model="form.reference_no" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Reference No" />
        <input v-model="form.item" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Item" />
        <select v-model="form.category" class="border border-white/20 bg-slate-900 px-3 py-2">
          <option value="subscription">Subscription</option>
          <option value="sms">SMS</option>
          <option value="setup">Setup</option>
          <option value="support">Support</option>
        </select>
        <input v-model="form.period_start" type="date" class="border border-white/20 bg-slate-900 px-3 py-2" />
        <input v-model="form.period_end" type="date" class="border border-white/20 bg-slate-900 px-3 py-2" />
        <input v-model="form.due_date" type="date" class="border border-white/20 bg-slate-900 px-3 py-2" />
        <input v-model="form.invoiced_amount" type="number" step="0.01" min="1" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Invoiced Amount" />
        <input v-model="form.balance_amount" type="number" step="0.01" min="0" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Balance Amount" />
        <select v-model="form.status" class="border border-white/20 bg-slate-900 px-3 py-2">
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partial</option>
          <option value="paid">Paid</option>
          <option value="overdue">Overdue</option>
        </select>

        <textarea v-model="form.notes" rows="3" class="border border-white/20 bg-slate-900 px-3 py-2 sm:col-span-2" placeholder="Notes" />

        <div class="sm:col-span-2">
          <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 font-semibold text-slate-950" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Update Invoice' }}
          </button>
        </div>

        <p v-for="(msg, key) in form.errors" :key="key" class="sm:col-span-2 text-sm text-rose-300">{{ msg }}</p>
      </form>
    </main>
  </div>
</template>