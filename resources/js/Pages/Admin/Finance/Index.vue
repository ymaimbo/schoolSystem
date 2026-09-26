<!-- resources/js/Pages/Admin/Finance/Index.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], links: [] }) },
  summary: {
    type: Object,
    default: () => ({
      income: 0,
      expense: 0,
      balance: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      type: '',
      payment_method: '',
      from: '',
      to: '',
    }),
  },
})

const filterForm = useForm({
  search: props.filters.search ?? '',
  type: props.filters.type ?? '',
  payment_method: props.filters.payment_method ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
})

const createForm = useForm({
  transaction_date: '',
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: 'cash',
  reference: '',
})

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  transaction_date: '',
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: 'cash',
  reference: '',
})

const links = computed(() =>
  (props.transactions.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '<<').replace('&raquo;', '>>'),
  }))
)

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
    onSuccess: () => createForm.reset(),
  })
}

const openEdit = (transaction) => {
  editId.value = transaction.id
  editForm.transaction_date = transaction.transaction_date ?? ''
  editForm.type = transaction.type ?? 'income'
  editForm.category = transaction.category ?? ''
  editForm.description = transaction.description ?? ''
  editForm.amount = transaction.amount ?? ''
  editForm.payment_method = transaction.payment_method ?? 'cash'
  editForm.reference = transaction.reference ?? ''
  editOpen.value = true
}

const closeEdit = () => {
  editOpen.value = false
  editId.value = null
  editForm.clearErrors()
}

const submitEdit = () => {
  if (!editId.value) return
  editForm.put(route('admin.finance.update', editId.value), {
    preserveScroll: true,
    onSuccess: () => closeEdit(),
  })
}

const removeItem = (id) => {
  if (!confirm('Delete this transaction?')) return
  router.delete(route('admin.finance.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Finance" />
  <AdminLayout>
    <div class="space-y-6">
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h1 class="text-2xl font-bold tracking-tight text-white">Finance</h1>
          <div class="text-sm text-slate-300">Track income, expenses, and cashflow.</div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Income</p>
            <p class="mt-2 text-xl font-semibold text-emerald-300">{{ summary?.income ?? 0 }}</p>
          </article>
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Expense</p>
            <p class="mt-2 text-xl font-semibold text-rose-300">{{ summary?.expense ?? 0 }}</p>
          </article>
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Balance</p>
            <p class="mt-2 text-xl font-semibold text-cyan-300">{{ summary?.balance ?? 0 }}</p>
          </article>
        </div>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Filter Transactions</h2>

        <form class="mt-4 grid gap-3 md:grid-cols-6" @submit.prevent="applyFilters">
          <input
            v-model="filterForm.search"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-emerald-400 md:col-span-2"
            placeholder="Search by reference, category, description..."
          />

          <select
            v-model="filterForm.type"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
          >
            <option value="">All types</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
          </select>

          <select
            v-model="filterForm.payment_method"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
          >
            <option value="">All methods</option>
            <option value="cash">Cash</option>
            <option value="bank">Bank</option>
            <option value="mpesa">M-Pesa</option>
            <option value="cheque">Cheque</option>
            <option value="transfer">Transfer</option>
          </select>

          <input
            v-model="filterForm.from"
            type="date"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
          />

          <input
            v-model="filterForm.to"
            type="date"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
          />

          <div class="md:col-span-6 flex flex-wrap items-center gap-2">
            <button type="submit" class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">
              Apply Filters
            </button>
            <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="clearFilters">
              Clear
            </button>
          </div>
        </form>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Add Transaction</h2>

        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitCreate">
          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Transaction Date</label>
            <input v-model="createForm.transaction_date" type="date" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Type</label>
            <select v-model="createForm.type" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="income">Income</option>
              <option value="expense">Expense</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Category</label>
            <input v-model="createForm.category" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="e.g. Fees, Utilities" />
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Payment Method</label>
            <select v-model="createForm.payment_method" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="mpesa">M-Pesa</option>
              <option value="cheque">Cheque</option>
              <option value="transfer">Transfer</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Description</label>
            <textarea v-model="createForm.description" rows="2" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Transaction description" />
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Amount</label>
            <input v-model="createForm.amount" type="number" step="0.01" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="0.00" />
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Reference</label>
            <input v-model="createForm.reference" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Receipt/Ref no." />
          </div>

          <div class="md:col-span-2">
            <button class="rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400" type="submit">
              Save Transaction
            </button>
          </div>
        </form>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-4 text-lg font-semibold text-white">Transactions</h2>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Date</th>
                <th class="px-3 py-2">Type</th>
                <th class="px-3 py-2">Category</th>
                <th class="px-3 py-2">Description</th>
                <th class="px-3 py-2">Amount</th>
                <th class="px-3 py-2">Method</th>
                <th class="px-3 py-2">Reference</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="t in transactions.data" :key="t.id">
                <td class="px-3 py-2 text-slate-200">{{ t.transaction_date }}</td>
                <td class="px-3 py-2" :class="t.type === 'income' ? 'text-emerald-300' : 'text-rose-300'">{{ t.type }}</td>
                <td class="px-3 py-2 text-slate-200">{{ t.category }}</td>
                <td class="px-3 py-2 text-slate-300">{{ t.description }}</td>
                <td class="px-3 py-2 font-semibold text-cyan-300">{{ t.amount }}</td>
                <td class="px-3 py-2 text-slate-300">{{ t.payment_method }}</td>
                <td class="px-3 py-2 text-slate-300">{{ t.reference || '-' }}</td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10" @click="openEdit(t)">
                      Edit
                    </button>
                    <button type="button" class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10" @click="removeItem(t.id)">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!transactions.data?.length">
                <td colspan="8" class="px-3 py-6 text-center text-slate-400">No transactions found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button
            v-for="l in links"
            :key="l.label + String(l.url)"
            class="rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-40"
            :disabled="!l.url"
            @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
            v-html="l.label"
          />
        </div>
      </section>

      <div v-if="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-2xl rounded-xl border border-white/10 bg-slate-900 p-5">
          <h3 class="text-lg font-semibold text-white">Edit Transaction</h3>

          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
            <input v-model="editForm.transaction_date" type="date" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
            <select v-model="editForm.type" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="income">Income</option>
              <option value="expense">Expense</option>
            </select>
            <input v-model="editForm.category" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Category" />
            <select v-model="editForm.payment_method" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="mpesa">M-Pesa</option>
              <option value="cheque">Cheque</option>
              <option value="transfer">Transfer</option>
            </select>
            <textarea v-model="editForm.description" rows="2" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 md:col-span-2" placeholder="Description" />
            <input v-model="editForm.amount" type="number" step="0.01" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Amount" />
            <input v-model="editForm.reference" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Reference" />

            <div class="md:col-span-2 flex justify-end gap-2 pt-2">
              <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="closeEdit">Cancel</button>
              <button type="submit" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>