<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], links: [] }) },
  vouchers: { type: Object, default: () => ({ data: [], links: [] }) },
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
      search_voucher: '',
      search: '',
      type: '',
      payment_method: '',
      from: '',
      to: '',
    }),
  },
  voucherMethods: {
    type: Array,
    default: () => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
  },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})

/* ── Utility helpers ──────────────────────────────────────────────── */

const toNumber = (value) => {
  const n = Number(value ?? 0)
  return Number.isFinite(n) ? n : 0
}

const formatKES = (value) =>
  new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(toNumber(value))

const today = () => new Date().toISOString().slice(0, 10)

/* ── Active tab ───────────────────────────────────────────────────── */

const activeTab = ref('transactions')

/* ── Income categories for quick entry ────────────────────────────── */

const incomeCategories = [
  'Student Fees',
  'Government Grant',
  'Capitation',
  'Donation',
  'Fundraising',
  'Rental Income',
  'Activity Fee',
  'Registration Fee',
  'Other Income',
]

const expenseCategories = [
  'Salaries',
  'Utilities',
  'Supplies',
  'Maintenance',
  'Transport',
  'Food & Catering',
  'Books & Materials',
  'Equipment',
  'Communication',
  'Insurance',
  'Other Expense',
]

const categoryOptions = computed(() =>
  createForm.type === 'income' ? incomeCategories : expenseCategories
)

const editCategoryOptions = computed(() =>
  editForm.type === 'income' ? incomeCategories : expenseCategories
)

/* ── Filter form ──────────────────────────────────────────────────── */

const filterForm = useForm({
  search: props.filters.search ?? '',
  type: props.filters.type ?? '',
  payment_method: props.filters.payment_method ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
})

const applyFilters = () => {
  router.get(route('admin.finance.index'), filterForm.data(), {
    preserveState: true,
    replace: true,
  })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
}

/* ── Create transaction form ──────────────────────────────────────── */

const createForm = useForm({
  entry_date: today(),
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: 'cash',
  reference_no: '',
})

const submitCreate = () => {
  createForm.post(route('admin.finance.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset()
      createForm.entry_date = today()
    },
  })
}

/* ── Edit transaction ─────────────────────────────────────────────── */

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  entry_date: '',
  type: 'income',
  category: '',
  description: '',
  amount: '',
  payment_method: 'cash',
  reference_no: '',
})

const openEdit = (transaction) => {
  editId.value = transaction.id
  editForm.entry_date = transaction.entry_date ?? transaction.transaction_date ?? ''
  editForm.type = transaction.type ?? 'income'
  editForm.category = transaction.category ?? ''
  editForm.description = transaction.description ?? ''
  editForm.amount = transaction.amount ?? ''
  editForm.payment_method = transaction.payment_method ?? 'cash'
  editForm.reference_no = transaction.reference_no ?? transaction.reference ?? ''
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
  if (!confirm('Delete this transaction? This action cannot be undone.')) return
  router.delete(route('admin.finance.destroy', id), { preserveScroll: true })
}

/* ── Voucher form ─────────────────────────────────────────────────── */

const voucherForm = useForm({
  supplier_name: '',
  supplier_id: '',
  purpose: '',
  amount: '',
  payment_method: 'cash',
  paid_at: today(),
  notes: '',
})

const submitVoucher = () => {
  voucherForm.post(route('admin.finance.voucher.store'), {
    preserveScroll: true,
    onSuccess: () => {
      voucherForm.reset()
      voucherForm.paid_at = today()
    },
  })
}

const deleteVoucher = (id) => {
  if (!confirm('Delete this payment voucher? This action cannot be undone.')) return
  router.delete(route('admin.finance.voucher.destroy', id), { preserveScroll: true })
}

/* ── Pagination links ─────────────────────────────────────────────── */

const transactionLinks = computed(() =>
  (props.transactions.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  }))
)

const voucherLinks = computed(() =>
  (props.vouchers?.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  }))
)

/* ── Voucher data ─────────────────────────────────────────────────── */

const voucherRows = computed(() => {
  if (Array.isArray(props.vouchers?.data)) return props.vouchers.data
  if (Array.isArray(props.vouchers)) return props.vouchers
  return []
})

/* ── Category breakdown ───────────────────────────────────────────── */

const categoryBreakdown = computed(() => {
  const rows = props.transactions?.data ?? []
  const map = {}

  rows.forEach((row) => {
    const cat = row.category || 'Uncategorized'
    const type = row.type ?? 'income'
    const amount = toNumber(row.amount)
    const key = `${type}:${cat}`

    if (!map[key]) {
      map[key] = { type, category: cat, total: 0, count: 0 }
    }
    map[key].total += amount
    map[key].count += 1
  })

  return Object.values(map).sort((a, b) => b.total - a.total)
})

/* ── Print ────────────────────────────────────────────────────────── */

const printFinanceSummary = () => window.print()
</script>

<template>
  <Head title="School Finance" />

  <AdminLayout>
    <div class="space-y-6">

      <!-- Flash messages -->
      <section v-if="flash.success || flash.error" class="space-y-2">
        <div
          v-if="flash.success"
          class="rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
        >
          {{ flash.success }}
        </div>
        <div
          v-if="flash.error"
          class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200"
        >
          {{ flash.error }}
        </div>
      </section>

      <!-- Header + Summary -->
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 no-print">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">School Finance</h1>
            <p class="mt-1 text-sm text-slate-300">
              Track income, expenses, payment vouchers, and cash flow.
            </p>
          </div>
          <button
            type="button"
            class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5"
            @click="printFinanceSummary"
          >
            Print Summary
          </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Income</p>
            <p class="mt-2 text-xl font-bold text-emerald-300">
              {{ formatKES(summary?.income) }}
            </p>
          </article>
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Expense</p>
            <p class="mt-2 text-xl font-bold text-rose-300">
              {{ formatKES(summary?.expense) }}
            </p>
          </article>
          <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Net Balance</p>
            <p
              class="mt-2 text-xl font-bold"
              :class="toNumber(summary?.balance) >= 0 ? 'text-cyan-300' : 'text-rose-300'"
            >
              {{ formatKES(summary?.balance) }}
            </p>
          </article>
        </div>
      </section>

      <!-- Tab navigation -->
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-2 no-print">
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="activeTab === 'transactions' ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/5'"
            @click="activeTab = 'transactions'"
          >
            Transactions
          </button>
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="activeTab === 'vouchers' ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/5'"
            @click="activeTab = 'vouchers'"
          >
            Payment Vouchers
          </button>
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="activeTab === 'breakdown' ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-white/5'"
            @click="activeTab = 'breakdown'"
          >
            Category Breakdown
          </button>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!--  TRANSACTIONS TAB                                              -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <template v-if="activeTab === 'transactions'">

        <!-- Filters -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 no-print">
          <h2 class="text-lg font-semibold text-white">Filter Transactions</h2>

          <form class="mt-4 grid gap-3 md:grid-cols-6" @submit.prevent="applyFilters">
            <input
              v-model="filterForm.search"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-emerald-400 md:col-span-2"
              placeholder="Search reference, category, description..."
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
              <button
                type="submit"
                class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
              >
                Apply Filters
              </button>
              <button
                type="button"
                class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
                @click="clearFilters"
              >
                Clear
              </button>
            </div>
          </form>
        </section>

        <!-- Add Transaction -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 no-print">
          <h2 class="text-lg font-semibold text-white">Record Transaction</h2>
          <p class="mt-1 text-xs text-slate-400">
            Record all school income (fees, grants, donations) and expenses (salaries, supplies, utilities).
          </p>

          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitCreate">
            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Entry Date
              </label>
              <input
                v-model="createForm.entry_date"
                type="date"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
              <p v-if="createForm.errors.entry_date" class="mt-1 text-xs text-rose-300">
                {{ createForm.errors.entry_date }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Type</label>
              <select
                v-model="createForm.type"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Category
              </label>
              <select
                v-model="createForm.category"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="">Select category...</option>
                <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ cat }}</option>
              </select>
              <p v-if="createForm.errors.category" class="mt-1 text-xs text-rose-300">
                {{ createForm.errors.category }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Payment Method
              </label>
              <select
                v-model="createForm.payment_method"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="mpesa">M-Pesa</option>
                <option value="cheque">Cheque</option>
                <option value="transfer">Transfer</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Description
              </label>
              <textarea
                v-model="createForm.description"
                rows="2"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Description of this transaction..."
              />
              <p v-if="createForm.errors.description" class="mt-1 text-xs text-rose-300">
                {{ createForm.errors.description }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Amount (KES)
              </label>
              <input
                v-model="createForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="0.00"
              />
              <p v-if="createForm.errors.amount" class="mt-1 text-xs text-rose-300">
                {{ createForm.errors.amount }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Reference No
              </label>
              <input
                v-model="createForm.reference_no"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Receipt / M-Pesa code / Bank ref..."
              />
            </div>

            <div class="md:col-span-2">
              <button
                :disabled="createForm.processing"
                class="rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 disabled:opacity-50"
                type="submit"
              >
                {{ createForm.processing ? 'Saving...' : 'Save Transaction' }}
              </button>
            </div>
          </form>
        </section>

        <!-- Transactions table -->
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
                  <th class="px-3 py-2 no-print">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr
                  v-for="t in transactions.data"
                  :key="t.id"
                  class="hover:bg-white/5 transition-colors"
                >
                  <td class="px-3 py-2 text-slate-200">
                    {{ t.entry_date ?? t.transaction_date ?? '—' }}
                  </td>
                  <td class="px-3 py-2">
                    <span
                      class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="t.type === 'income'
                        ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-400/40'
                        : 'bg-rose-500/10 text-rose-300 border border-rose-400/40'"
                    >
                      {{ t.type === 'income' ? '↑ Income' : '↓ Expense' }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-slate-200">{{ t.category || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300 max-w-[200px] truncate">
                    {{ t.description || '—' }}
                  </td>
                  <td
                    class="px-3 py-2 font-semibold"
                    :class="t.type === 'income' ? 'text-emerald-300' : 'text-rose-300'"
                  >
                    {{ formatKES(t.amount) }}
                  </td>
                  <td class="px-3 py-2 text-slate-300 capitalize">{{ t.payment_method || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ t.reference_no || t.reference || '—' }}</td>
                  <td class="px-3 py-2 no-print">
                    <div class="flex flex-wrap gap-2">
                      <button
                        type="button"
                        class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10"
                        @click="openEdit(t)"
                      >
                        Edit
                      </button>
                      <button
                        type="button"
                        class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10"
                        @click="removeItem(t.id)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="!transactions.data?.length">
                  <td colspan="8" class="px-3 py-8 text-center text-slate-400 italic">
                    No transactions found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="transactionLinks.length > 3" class="mt-4 flex flex-wrap gap-2 no-print">
            <button
              v-for="l in transactionLinks"
              :key="l.label + String(l.url)"
              class="rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-40"
              :class="{ 'bg-cyan-500/20 border-cyan-400/40': l.active }"
              :disabled="!l.url"
              @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
              v-html="l.label"
            />
          </div>
        </section>
      </template>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!--  PAYMENT VOUCHERS TAB                                          -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <template v-if="activeTab === 'vouchers'">

        <!-- Create Voucher -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5 no-print">
          <h2 class="text-lg font-semibold text-white">Create Payment Voucher</h2>
          <p class="mt-1 text-xs text-slate-400">
            Issue a payment voucher when the school makes any outgoing payment to a supplier,
            contractor, or service provider.
          </p>

          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitVoucher">
            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Supplier / Payee Name
              </label>
              <input
                v-model="voucherForm.supplier_name"
                type="text"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Who is being paid..."
              />
              <p v-if="voucherForm.errors.supplier_name" class="mt-1 text-xs text-rose-300">
                {{ voucherForm.errors.supplier_name }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Supplier ID / KRA PIN
              </label>
              <input
                v-model="voucherForm.supplier_id"
                type="text"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Optional identification..."
              />
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Purpose of Payment
              </label>
              <input
                v-model="voucherForm.purpose"
                type="text"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="What is this payment for..."
              />
              <p v-if="voucherForm.errors.purpose" class="mt-1 text-xs text-rose-300">
                {{ voucherForm.errors.purpose }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Amount (KES)
              </label>
              <input
                v-model="voucherForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="0.00"
              />
              <p v-if="voucherForm.errors.amount" class="mt-1 text-xs text-rose-300">
                {{ voucherForm.errors.amount }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Payment Method
              </label>
              <select
                v-model="voucherForm.payment_method"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option v-for="method in voucherMethods" :key="method" :value="method">
                  {{ method }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Date Paid
              </label>
              <input
                v-model="voucherForm.paid_at"
                type="date"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
              <p v-if="voucherForm.errors.paid_at" class="mt-1 text-xs text-rose-300">
                {{ voucherForm.errors.paid_at }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Notes</label>
              <textarea
                v-model="voucherForm.notes"
                rows="2"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Additional notes..."
              />
            </div>

            <div class="md:col-span-2">
              <button
                :disabled="voucherForm.processing"
                class="rounded-md bg-emerald-500 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                type="submit"
              >
                {{ voucherForm.processing ? 'Creating...' : 'Create Payment Voucher' }}
              </button>
            </div>
          </form>
        </section>

        <!-- Vouchers table -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-4 text-lg font-semibold text-white">Payment Vouchers</h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Voucher No</th>
                  <th class="px-3 py-2">Date</th>
                  <th class="px-3 py-2">Supplier</th>
                  <th class="px-3 py-2">Purpose</th>
                  <th class="px-3 py-2">Amount</th>
                  <th class="px-3 py-2">Method</th>
                  <th class="px-3 py-2">Prepared By</th>
                  <th class="px-3 py-2">Approved By</th>
                  <th class="px-3 py-2 no-print">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr
                  v-for="v in voucherRows"
                  :key="v.id"
                  class="hover:bg-white/5 transition-colors"
                >
                  <td class="px-3 py-2 text-cyan-300 font-mono text-xs">{{ v.voucher_no || '—' }}</td>
                  <td class="px-3 py-2 text-slate-200">{{ v.paid_at || '—' }}</td>
                  <td class="px-3 py-2 text-white font-medium">
                    <div>{{ v.supplier_name || '—' }}</div>
                    <div v-if="v.supplier_id" class="text-xs text-slate-400">{{ v.supplier_id }}</div>
                  </td>
                  <td class="px-3 py-2 text-slate-300 max-w-[200px] truncate">{{ v.purpose || '—' }}</td>
                  <td class="px-3 py-2 font-semibold text-amber-300">{{ formatKES(v.amount) }}</td>
                  <td class="px-3 py-2 text-slate-300 capitalize">{{ v.payment_method || '—' }}</td>
                  <td class="px-3 py-2 text-slate-400 text-xs">{{ v.preparer?.name ?? '—' }}</td>
                  <td class="px-3 py-2 text-slate-400 text-xs">{{ v.approver?.name ?? '—' }}</td>
                  <td class="px-3 py-2 no-print">
                    <div class="flex flex-wrap gap-2">
                      <Link
                        :href="route('admin.finance.voucher.print', v.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10"
                      >
                        Print
                      </Link>
                      <button
                        type="button"
                        class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10"
                        @click="deleteVoucher(v.id)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="!voucherRows.length">
                  <td colspan="9" class="px-3 py-8 text-center text-slate-400 italic">
                    No payment vouchers found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Voucher pagination -->
          <div v-if="voucherLinks.length > 3" class="mt-4 flex flex-wrap gap-2 no-print">
            <button
              v-for="l in voucherLinks"
              :key="l.label + String(l.url)"
              class="rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-40"
              :class="{ 'bg-cyan-500/20 border-cyan-400/40': l.active }"
              :disabled="!l.url"
              @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
              v-html="l.label"
            />
          </div>
        </section>
      </template>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!--  CATEGORY BREAKDOWN TAB                                        -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <template v-if="activeTab === 'breakdown'">
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-4 text-lg font-semibold text-white">Category Breakdown (This Page)</h2>
          <p class="mb-4 text-xs text-slate-400">
            Summary of income and expense totals grouped by category from the currently loaded transactions.
          </p>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Type</th>
                  <th class="px-3 py-2">Category</th>
                  <th class="px-3 py-2">Transactions</th>
                  <th class="px-3 py-2 text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr
                  v-for="row in categoryBreakdown"
                  :key="`${row.type}:${row.category}`"
                  class="hover:bg-white/5 transition-colors"
                >
                  <td class="px-3 py-2">
                    <span
                      class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="row.type === 'income'
                        ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-400/40'
                        : 'bg-rose-500/10 text-rose-300 border border-rose-400/40'"
                    >
                      {{ row.type === 'income' ? '↑ Income' : '↓ Expense' }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-white font-medium">{{ row.category }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ row.count }}</td>
                  <td
                    class="px-3 py-2 text-right font-semibold"
                    :class="row.type === 'income' ? 'text-emerald-300' : 'text-rose-300'"
                  >
                    {{ formatKES(row.total) }}
                  </td>
                </tr>

                <tr v-if="!categoryBreakdown.length">
                  <td colspan="4" class="px-3 py-8 text-center text-slate-400 italic">
                    No category data available.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!--  EDIT MODAL                                                    -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div
        v-if="editOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
      >
        <div class="w-full max-w-2xl rounded-xl border border-white/10 bg-slate-900 p-5">
          <h3 class="text-lg font-semibold text-white">Edit Transaction</h3>

          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Entry Date
              </label>
              <input
                v-model="editForm.entry_date"
                type="date"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              />
              <p v-if="editForm.errors.entry_date" class="mt-1 text-xs text-rose-300">
                {{ editForm.errors.entry_date }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">Type</label>
              <select
                v-model="editForm.type"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Category
              </label>
              <select
                v-model="editForm.category"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="">Select category...</option>
                <option v-for="cat in editCategoryOptions" :key="cat" :value="cat">
                  {{ cat }}
                </option>
              </select>
              <p v-if="editForm.errors.category" class="mt-1 text-xs text-rose-300">
                {{ editForm.errors.category }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Payment Method
              </label>
              <select
                v-model="editForm.payment_method"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
              >
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="mpesa">M-Pesa</option>
                <option value="cheque">Cheque</option>
                <option value="transfer">Transfer</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Description
              </label>
              <textarea
                v-model="editForm.description"
                rows="2"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Description"
              />
              <p v-if="editForm.errors.description" class="mt-1 text-xs text-rose-300">
                {{ editForm.errors.description }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Amount (KES)
              </label>
              <input
                v-model="editForm.amount"
                type="number"
                step="0.01"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Amount"
              />
              <p v-if="editForm.errors.amount" class="mt-1 text-xs text-rose-300">
                {{ editForm.errors.amount }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-xs uppercase tracking-wider text-slate-400">
                Reference No
              </label>
              <input
                v-model="editForm.reference_no"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Reference"
              />
            </div>

            <div class="md:col-span-2 flex justify-end gap-2 pt-2">
              <button
                type="button"
                class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
                @click="closeEdit"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 disabled:opacity-50"
              >
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
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