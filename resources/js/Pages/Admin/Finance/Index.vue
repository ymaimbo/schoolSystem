<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], links: [] }) },
  totals: { type: Object, default: () => ({ income: 0, expense: 0, balance: 0 }) },
  overall: { type: Object, default: () => ({ income: 0, expense: 0 }) },
  filters: { type: Object, default: () => ({ type: '', category: '', from: '', to: '', payment_method: '' }) },
})

const filterForm = useForm({
  type: props.filters.type ?? '',
  category: props.filters.category ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
  payment_method: props.filters.payment_method ?? '',
})

const createForm = useForm({
  entry_date: new Date().toISOString().slice(0, 10),
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: '',
  reference_no: '',
})

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  entry_date: '',
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: '',
  reference_no: '',
})

const links = computed(() =>
  (props.transactions.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  })),
)

const formatKES = (v) => `KES ${Number(v || 0).toLocaleString()}`

const applyFilters = () => {
  router.get(route('admin.finance.index'), filterForm.data(), { preserveState: true, replace: true })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
}

const submitCreate = () => {
  createForm.post(route('admin.finance.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset('category', 'description', 'amount', 'payment_method', 'reference_no'),
  })
}

const openEdit = (tx) => {
  editId.value = tx.id
  editForm.entry_date = tx.entry_date ?? ''
  editForm.type = tx.type ?? 'income'
  editForm.category = tx.category ?? ''
  editForm.description = tx.description ?? ''
  editForm.amount = tx.amount ?? ''
  editForm.payment_method = tx.payment_method ?? ''
  editForm.reference_no = tx.reference_no ?? ''
  editOpen.value = true
}

const closeEdit = () => {
  editOpen.value = false
  editId.value = null
  editForm.clearErrors()
}

const submitEdit = () => {
  editForm.put(route('admin.finance.update', editId.value), {
    preserveScroll: true,
    onSuccess: () => closeEdit(),
  })
}

const removeTx = (id) => {
  if (!confirm('Delete this transaction?')) return
  router.delete(route('admin.finance.destroy', id), { preserveScroll: true })
}

const printPage = () => {
  window.print()
}
</script>

<template>
  <Head title="Finance" />
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Finance Report</h1>
        <button type="button" class="no-print border px-4 py-2 text-sm font-medium hover:bg-slate-50" @click="printPage">
          Print Financial Report
        </button>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Income</p><p class="text-2xl font-semibold text-emerald-700">{{ formatKES(totals.income) }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Expense</p><p class="text-2xl font-semibold text-rose-700">{{ formatKES(totals.expense) }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Balance</p><p class="text-2xl font-semibold">{{ formatKES(totals.balance) }}</p></div>
      </div>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-6" @submit.prevent="applyFilters">
        <select v-model="filterForm.type" class="border px-3 py-2"><option value="">All Types</option><option value="income">income</option><option value="expense">expense</option></select>
        <input v-model="filterForm.category" class="border px-3 py-2" placeholder="Category" />
        <input v-model="filterForm.payment_method" class="border px-3 py-2" placeholder="Payment Method" />
        <input v-model="filterForm.from" type="date" class="border px-3 py-2" />
        <input v-model="filterForm.to" type="date" class="border px-3 py-2" />
        <div class="flex gap-2">
          <button class="w-full bg-slate-900 px-4 py-2 text-white">Filter</button>
          <button type="button" class="w-full border px-4 py-2" @click="clearFilters">Clear</button>
        </div>
      </form>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-3" @submit.prevent="submitCreate">
        <input v-model="createForm.entry_date" type="date" class="border px-3 py-2" />
        <select v-model="createForm.type" class="border px-3 py-2"><option value="income">income</option><option value="expense">expense</option></select>
        <input v-model="createForm.category" class="border px-3 py-2" placeholder="Category" />
        <input v-model="createForm.amount" type="number" step="0.01" class="border px-3 py-2" placeholder="Amount" />
        <input v-model="createForm.payment_method" class="border px-3 py-2" placeholder="Payment Method" />
        <input v-model="createForm.reference_no" class="border px-3 py-2" placeholder="Reference No" />
        <textarea v-model="createForm.description" rows="2" class="border px-3 py-2 md:col-span-3" placeholder="Description" />
        <button class="bg-slate-900 px-4 py-2 text-white md:col-span-3">Add Transaction</button>
      </form>

      <div class="overflow-x-auto border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left">Date</th>
              <th class="px-3 py-2 text-left">Type</th>
              <th class="px-3 py-2 text-left">Category</th>
              <th class="px-3 py-2 text-left">Amount</th>
              <th class="px-3 py-2 text-left">Payment</th>
              <th class="px-3 py-2 text-left">Reference</th>
              <th class="px-3 py-2 text-left">Description</th>
              <th class="no-print px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions.data" :key="tx.id" class="border-t">
              <td class="px-3 py-2">{{ tx.entry_date }}</td>
              <td class="px-3 py-2">{{ tx.type }}</td>
              <td class="px-3 py-2">{{ tx.category }}</td>
              <td class="px-3 py-2">{{ formatKES(tx.amount) }}</td>
              <td class="px-3 py-2">{{ tx.payment_method || '-' }}</td>
              <td class="px-3 py-2">{{ tx.reference_no || '-' }}</td>
              <td class="px-3 py-2">{{ tx.description || '-' }}</td>
              <td class="no-print px-3 py-2">
                <button class="mr-3 text-blue-700" @click="openEdit(tx)">Edit</button>
                <button class="text-rose-700" @click="removeTx(tx.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="no-print flex flex-wrap gap-2">
        <button
          v-for="l in links"
          :key="l.label + String(l.url)"
          class="border px-3 py-1 text-sm"
          :class="l.active ? 'bg-slate-900 text-white' : 'bg-white'"
          :disabled="!l.url"
          @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
          v-html="l.label"
        />
      </div>
    </div>

    <div v-if="editOpen" class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-2xl border bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Transaction</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
          <input v-model="editForm.entry_date" type="date" class="border px-3 py-2" />
          <select v-model="editForm.type" class="border px-3 py-2"><option value="income">income</option><option value="expense">expense</option></select>
          <input v-model="editForm.category" class="border px-3 py-2" placeholder="Category" />
          <input v-model="editForm.amount" type="number" step="0.01" class="border px-3 py-2" placeholder="Amount" />
          <input v-model="editForm.payment_method" class="border px-3 py-2" placeholder="Payment Method" />
          <input v-model="editForm.reference_no" class="border px-3 py-2" placeholder="Reference No" />
          <textarea v-model="editForm.description" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="Description" />
          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" class="border px-4 py-2" @click="closeEdit">Cancel</button>
            <button class="bg-slate-900 px-4 py-2 text-white">Save</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>