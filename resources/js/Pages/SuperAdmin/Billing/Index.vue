<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
  schools: { type: Array, default: () => [] },
  invoices: { type: Object, default: () => ({ data: [] }) },
})

const form = useForm({
  school_id: '',
  reference_no: '',
  item: '',
  category: 'subscription',
  period_start: '',
  period_end: '',
  due_date: '',
  invoiced_amount: '',
  notes: '',
})

const submit = () => {
  form.post(route('superadmin.billing.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('reference_no', 'item', 'period_start', 'period_end', 'due_date', 'invoiced_amount', 'notes'),
  })
}
</script>

<template>
  <Head title="Super Admin Billing" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-6 py-10">
      <h1 class="text-3xl font-semibold">Invoice Management</h1>
      <p class="mt-2 text-sm text-slate-300">Create and manage school invoices centrally.</p>

      <section class="mt-8 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">Create Invoice</h2>
        <form class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit">
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
          <input v-model="form.invoiced_amount" type="number" step="0.01" min="1" class="border border-white/20 bg-slate-900 px-3 py-2" placeholder="Amount" />
          <textarea v-model="form.notes" rows="2" class="border border-white/20 bg-slate-900 px-3 py-2 sm:col-span-2 lg:col-span-3" placeholder="Notes" />

          <div class="sm:col-span-2 lg:col-span-3">
            <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 font-semibold text-slate-950" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Create Invoice' }}
            </button>
          </div>

          <p v-for="(msg, key) in form.errors" :key="key" class="sm:col-span-2 lg:col-span-3 text-sm text-rose-300">{{ msg }}</p>
        </form>
      </section>

      <section class="mt-8 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Ref</th>
              <th class="px-4 py-3 font-medium">School</th>
              <th class="px-4 py-3 font-medium">Item</th>
              <th class="px-4 py-3 font-medium">Amount</th>
              <th class="px-4 py-3 font-medium">Balance</th>
              <th class="px-4 py-3 font-medium">Status</th>
              <th class="px-4 py-3 font-medium">Due Date</th>
              <th class="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices.data" :key="invoice.id" class="border-b border-white/5">
              <td class="px-4 py-3">{{ invoice.reference_no }}</td>
              <td class="px-4 py-3">{{ invoice.school?.name || '-' }}</td>
              <td class="px-4 py-3">{{ invoice.item }}</td>
              <td class="px-4 py-3">KES {{ Number(invoice.invoiced_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
              <td class="px-4 py-3">KES {{ Number(invoice.balance_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
              <td class="px-4 py-3">{{ invoice.status }}</td>
              <td class="px-4 py-3">{{ invoice.due_date || '-' }}</td>
              <td class="px-4 py-3">
                <Link :href="route('superadmin.billing.edit', invoice.id)" class="border border-white/20 px-3 py-1 hover:bg-white/10">
                  Edit
                </Link>
              </td>
            </tr>
            <tr v-if="!invoices.data?.length">
              <td colspan="8" class="px-4 py-8 text-center text-slate-400">No invoices found.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>