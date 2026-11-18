<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  summary: {
    default: () => ({
      students_count: 0,
      accounts_count: 0,
      total_held: 0,
      deposits_today: 0,
      withdrawals_today: 0,
      net_today: 0,
    }),
  },
  students: { default: () => [] },
  accounts: { default: () => [] },
  transactions: { default: () => [] },
  filters: {
    default: () => ({
      search: '',
      class_level: '',
      type: '',
      status: '',
      from: '',
      to: '',
    }),
  },
  paymentMethods: { default: () => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'] },
  transactionTypes: { default: () => ['deposit', 'withdrawal'] },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})
const isRefreshing = ref(false)

const endpoints = {
  index: '/admin/pocket-money',
  accountStore: '/admin/pocket-money/account',
  transactionStore: '/admin/pocket-money/transaction',
  transactionReverse: (id) => `/admin/pocket-money/transaction/${id}`,
  statementPrint: (id) => `/admin/pocket-money/statement/${id}`,
}

/* ── Utility helpers ──────────────────────────────────────────────── */

const toNumber = (value) => {
  const n = Number(value ?? 0)
  return Number.isFinite(n) ? n : 0
}

const normalizeId = (value) => {
  if (value === null || value === undefined || value === '') return null
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

const formatKES = (value) =>
  new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(toNumber(value))

/* ── Derived collections ──────────────────────────────────────────── */

const studentsRows = computed(() => (Array.isArray(props.students) ? props.students : []))
const accountsRows = computed(() => (Array.isArray(props.accounts) ? props.accounts : []))
const transactionsRows = computed(() => (Array.isArray(props.transactions) ? props.transactions : []))

const getStudentName = (student) => {
  if (!student) return ''
  return (
    student.full_name ??
    student.name ??
    `${student.first_name ?? ''} ${student.last_name ?? ''}`.trim()
  )
}

const getStudentAdmission = (student) => student?.admission_no ?? 'N/A'

const classLevels = computed(() => [
  ...new Set(studentsRows.value.map((s) => s.class_level).filter(Boolean)),
])

/* ── Filters ──────────────────────────────────────────────────────── */

const search = ref(props.filters?.search ?? '')
const classFilter = ref(props.filters?.class_level ?? '')
const typeFilter = ref(props.filters?.type ?? '')
const statusFilter = ref(props.filters?.status ?? '')
const fromFilter = ref(props.filters?.from ?? '')
const toFilter = ref(props.filters?.to ?? '')

const filteredAccounts = computed(() => {
  const q = search.value.trim().toLowerCase()
  return accountsRows.value.filter((row) => {
    const matchesSearch =
      !q ||
      String(row.student_name ?? '').toLowerCase().includes(q) ||
      String(row.admission_no ?? '').toLowerCase().includes(q)
    const matchesClass =
      !classFilter.value || String(row.class_level ?? '') === classFilter.value
    return matchesSearch && matchesClass
  })
})

const filteredTransactions = computed(() => {
  const q = search.value.trim().toLowerCase()
  return transactionsRows.value.filter((row) => {
    const matchesSearch =
      !q ||
      String(row.student_name ?? '').toLowerCase().includes(q) ||
      String(row.admission_no ?? '').toLowerCase().includes(q) ||
      String(row.transaction_no ?? '').toLowerCase().includes(q)
    const matchesClass =
      !classFilter.value || String(row.class_level ?? '') === classFilter.value
    const matchesType = !typeFilter.value || row.type === typeFilter.value
    const matchesStatus = !statusFilter.value || row.status === statusFilter.value
    const matchesFrom =
      !fromFilter.value || String(row.transaction_date ?? '') >= fromFilter.value
    const matchesTo =
      !toFilter.value || String(row.transaction_date ?? '') <= toFilter.value
    return matchesSearch && matchesClass && matchesType && matchesStatus && matchesFrom && matchesTo
  })
})

/* ── Account form ─────────────────────────────────────────────────── */

const accountForm = useForm({
  student_id: '',
  opening_balance: '',
  notes: '',
})

const submitAccount = () => {
  accountForm
    .transform((data) => ({
      student_id: normalizeId(data.student_id),
      opening_balance: toNumber(data.opening_balance),
      notes: data.notes || null,
    }))
    .post(endpoints.accountStore, {
      preserveScroll: true,
      onSuccess: () => {
        accountForm.reset('opening_balance', 'notes')
        refreshPage()
      },
    })
}

/* ── Transaction form ─────────────────────────────────────────────── */

const transactionForm = useForm({
  student_id: '',
  student_pocket_money_account_id: '',
  type: 'deposit',
  amount: '',
  payment_method: 'cash',
  reference_no: '',
  depositor_name: '',
  depositor_phone: '',
  transaction_date: new Date().toISOString().slice(0, 10),
  notes: '',
})

const selectedTransactionStudentId = computed(() => normalizeId(transactionForm.student_id))

const selectedAccount = computed(() =>
  accountsRows.value.find(
    (a) => normalizeId(a.student_id) === selectedTransactionStudentId.value
  ) ?? null
)

watch(selectedAccount, (account) => {
  transactionForm.student_pocket_money_account_id = account?.id ? String(account.id) : ''
})

const withdrawalExceedsBalance = computed(() => {
  if (transactionForm.type !== 'withdrawal') return false
  if (!selectedAccount.value) return false
  const amount = toNumber(transactionForm.amount)
  const balance = toNumber(selectedAccount.value.current_balance)
  return amount > 0 && amount > balance
})

const submitTransaction = () => {
  transactionForm
    .transform((data) => ({
      student_id: normalizeId(data.student_id),
      student_pocket_money_account_id: normalizeId(data.student_pocket_money_account_id),
      type: data.type,
      amount: toNumber(data.amount),
      payment_method: data.payment_method || null,
      reference_no: data.reference_no || null,
      depositor_name: data.depositor_name || null,
      depositor_phone: data.depositor_phone || null,
      transaction_date: data.transaction_date || null,
      notes: data.notes || null,
    }))
    .post(endpoints.transactionStore, {
      preserveScroll: true,
      onSuccess: () => {
        transactionForm.reset(
          'amount',
          'reference_no',
          'depositor_name',
          'depositor_phone',
          'notes'
        )
        refreshPage()
      },
    })
}

/* ── Reversal ─────────────────────────────────────────────────────── */

const reverseTransaction = (transactionId) => {
  const reason = window.prompt('Reason for reversal (optional):')
  if (reason === null) return

  router.delete(endpoints.transactionReverse(transactionId), {
    data: { reason: reason || 'User requested reversal' },
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => refreshPage(),
  })
}

/* ── Navigation ───────────────────────────────────────────────────── */

const refreshPage = () => {
  router.get(
    endpoints.index,
    {
      search: search.value || undefined,
      class_level: classFilter.value || undefined,
      type: typeFilter.value || undefined,
      status: statusFilter.value || undefined,
      from: fromFilter.value || undefined,
      to: toFilter.value || undefined,
    },
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onStart: () => (isRefreshing.value = true),
      onFinish: () => (isRefreshing.value = false),
    }
  )
}

/* ── Print ────────────────────────────────────────────────────────── */

const printPage = () => window.print()
</script>

<template>
  <Head title="Student Pocket Money — Bursar" />

  <AdminLayout>
    <main class="min-h-screen bg-slate-950 text-slate-100">
      <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

        <!-- Page heading -->
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold tracking-tight text-white">Student Pocket Money</h1>
              <p class="mt-1 text-sm text-slate-300">
                Receive, hold, withdraw, reverse, and report student pocket money with
                audited balances. Pocket money is separate from school fee accounts.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5"
                @click="printPage"
              >
                Print
              </button>
              <button
                type="button"
                class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-50"
                :disabled="isRefreshing"
                @click="refreshPage"
              >
                {{ isRefreshing ? 'Refreshing...' : 'Refresh Data' }}
              </button>
            </div>
          </div>
        </section>

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

        <!-- Summary cards -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Students</p>
            <p class="mt-2 text-xl font-bold text-white">{{ summary.students_count }}</p>
          </article>

          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Wallets</p>
            <p class="mt-2 text-xl font-bold text-white">{{ summary.accounts_count }}</p>
          </article>

          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total Held</p>
            <p class="mt-2 text-xl font-bold text-cyan-300">{{ formatKES(summary.total_held) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">School liability to students</p>
          </article>

          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Deposits Today</p>
            <p class="mt-2 text-xl font-bold text-emerald-300">
              {{ formatKES(summary.deposits_today) }}
            </p>
          </article>

          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Withdrawals Today</p>
            <p class="mt-2 text-xl font-bold text-amber-300">
              {{ formatKES(summary.withdrawals_today) }}
            </p>
          </article>

          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Net Today</p>
            <p
              class="mt-2 text-xl font-bold"
              :class="summary.net_today >= 0 ? 'text-emerald-300' : 'text-rose-300'"
            >
              {{ formatKES(summary.net_today) }}
            </p>
          </article>
        </section>

        <!-- Forms -->
        <section class="grid gap-6 lg:grid-cols-2 no-print">

          <!-- Account form -->
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h2 class="text-lg font-semibold text-white">Create / Update Pocket Money Wallet</h2>
            <p class="mt-1 text-xs text-slate-400">
              Every student needs a wallet before money can be received or withdrawn.
            </p>

            <form class="mt-4 space-y-3" @submit.prevent="submitAccount">
              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                  Student
                </label>
                <select
                  v-model="accountForm.student_id"
                  class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
                >
                  <option value="">Select student...</option>
                  <option v-for="student in studentsRows" :key="student.id" :value="student.id">
                    {{ getStudentName(student) }} ({{ getStudentAdmission(student) }})
                  </option>
                </select>
                <p v-if="accountForm.errors.student_id" class="mt-1 text-xs text-rose-300">
                  {{ accountForm.errors.student_id }}
                </p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                  Opening Balance
                </label>
                <input
                  v-model="accountForm.opening_balance"
                  type="number"
                  step="0.01"
                  class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
                  placeholder="0.00"
                />
                <p class="mt-1 text-[11px] text-slate-400">
                  Optional: amount already held before system was set up.
                </p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                  Notes
                </label>
                <textarea
                  v-model="accountForm.notes"
                  rows="2"
                  class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
                  placeholder="Optional bursar notes..."
                />
              </div>

              <button
                type="submit"
                :disabled="accountForm.processing"
                class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ accountForm.processing ? 'Saving...' : 'Save Wallet' }}
              </button>
            </form>
          </article>

          <!-- Transaction form -->
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
            <h2 class="text-lg font-semibold text-white">Record Deposit / Withdrawal</h2>
            <p class="mt-1 text-xs text-slate-400">
              Withdrawals are blocked if the student has insufficient funds.
            </p>

            <form class="mt-4 space-y-3" @submit.prevent="submitTransaction">
              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Student
                  </label>
                  <select
                    v-model="transactionForm.student_id"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                  >
                    <option value="">Select student...</option>
                    <option v-for="student in studentsRows" :key="student.id" :value="student.id">
                      {{ getStudentName(student) }} ({{ getStudentAdmission(student) }})
                    </option>
                  </select>
                  <p v-if="transactionForm.errors.student_id" class="mt-1 text-xs text-rose-300">
                    {{ transactionForm.errors.student_id }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Transaction Type
                  </label>
                  <select
                    v-model="transactionForm.type"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                  >
                    <option v-for="type in transactionTypes" :key="type" :value="type">
                      {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Current balance preview -->
              <div
                v-if="selectedAccount"
                class="rounded-md border border-white/10 bg-slate-950/60 px-3 py-2 text-xs"
              >
                <span class="text-slate-400">Current pocket money balance: </span>
                <span
                  class="font-semibold"
                  :class="toNumber(selectedAccount.current_balance) > 0 ? 'text-cyan-300' : 'text-rose-300'"
                >
                  {{ formatKES(selectedAccount.current_balance) }}
                </span>
              </div>

              <!-- Insufficient funds warning -->
              <div
                v-if="withdrawalExceedsBalance"
                class="rounded-md border border-rose-400/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-300"
              >
                ⚠ Withdrawal amount exceeds available balance.
                Maximum available: {{ formatKES(selectedAccount?.current_balance ?? 0) }}
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Amount (KES)
                  </label>
                  <input
                    v-model="transactionForm.amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                    placeholder="0.00"
                  />
                  <p v-if="transactionForm.errors.amount" class="mt-1 text-xs text-rose-300">
                    {{ transactionForm.errors.amount }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Payment Method
                  </label>
                  <select
                    v-model="transactionForm.payment_method"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                  >
                    <option v-for="method in paymentMethods" :key="method" :value="method">
                      {{ method }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Reference / M-Pesa Code
                  </label>
                  <input
                    v-model="transactionForm.reference_no"
                    type="text"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                    placeholder="Optional reference..."
                  />
                </div>

                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Transaction Date
                  </label>
                  <input
                    v-model="transactionForm.transaction_date"
                    type="date"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                  />
                </div>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Depositor Name
                  </label>
                  <input
                    v-model="transactionForm.depositor_name"
                    type="text"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                    placeholder="Parent or guardian name..."
                  />
                </div>

                <div>
                  <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                    Depositor Phone
                  </label>
                  <input
                    v-model="transactionForm.depositor_phone"
                    type="text"
                    class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                    placeholder="0712 345 678"
                  />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">
                  Notes
                </label>
                <textarea
                  v-model="transactionForm.notes"
                  rows="2"
                  class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                  placeholder="Optional bursar notes..."
                />
              </div>

              <div class="flex items-center gap-2 border-t border-white/10 pt-4">
                <button
                  type="button"
                  class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
                  @click="transactionForm.reset('amount', 'reference_no', 'depositor_name', 'depositor_phone', 'notes')"
                >
                  Reset
                </button>
                <button
                  type="submit"
                  :disabled="transactionForm.processing || withdrawalExceedsBalance"
                  class="rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {{ transactionForm.processing ? 'Saving...' : 'Record Transaction' }}
                </button>
              </div>
            </form>
          </article>
        </section>

        <!-- Filters -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-4 no-print">
          <div class="flex flex-wrap items-center gap-3">
            <input
              v-model="search"
              type="text"
              placeholder="Search student / admission / transaction no..."
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 w-72"
            />
            <select
              v-model="classFilter"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">All Classes</option>
              <option v-for="level in classLevels" :key="level" :value="level">{{ level }}</option>
            </select>
            <select
              v-model="typeFilter"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">All Types</option>
              <option value="deposit">Deposit</option>
              <option value="withdrawal">Withdrawal</option>
            </select>
            <select
              v-model="statusFilter"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="reversed">Reversed</option>
            </select>
            <input
              v-model="fromFilter"
              type="date"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            />
            <input
              v-model="toFilter"
              type="date"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            />
            <button
              type="button"
              class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5"
              @click="refreshPage"
            >
              Apply
            </button>
          </div>
        </section>

        <!-- Accounts table -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-4 text-lg font-semibold text-white">Student Wallets</h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Admission No</th>
                  <th class="px-3 py-2">Student</th>
                  <th class="px-3 py-2">Class</th>
                  <th class="px-3 py-2">Opening</th>
                  <th class="px-3 py-2">Deposited</th>
                  <th class="px-3 py-2">Withdrawn</th>
                  <th class="px-3 py-2">Current Balance</th>
                  <th class="px-3 py-2">Txns</th>
                  <th class="px-3 py-2 no-print">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr
                  v-for="account in filteredAccounts"
                  :key="account.id"
                  class="hover:bg-white/5 transition-colors"
                >
                  <td class="px-3 py-2 text-slate-200">{{ account.admission_no || '—' }}</td>
                  <td class="px-3 py-2 text-white font-medium">{{ account.student_name || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300">
                    {{ account.class_level || '—' }}
                    <span v-if="account.stream"> - {{ account.stream }}</span>
                  </td>
                  <td class="px-3 py-2 text-slate-200">{{ formatKES(account.opening_balance) }}</td>
                  <td class="px-3 py-2 text-emerald-300">{{ formatKES(account.total_deposited) }}</td>
                  <td class="px-3 py-2 text-amber-300">{{ formatKES(account.total_withdrawn) }}</td>
                  <td class="px-3 py-2 font-bold text-cyan-300">
                    {{ formatKES(account.current_balance) }}
                  </td>
                  <td class="px-3 py-2 text-slate-400">{{ account.transactions_count }}</td>
                  <td class="px-3 py-2 no-print">
                    <Link
                      :href="endpoints.statementPrint(account.id)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10"
                    >
                      Print Statement
                    </Link>
                  </td>
                </tr>
                <tr v-if="!filteredAccounts.length">
                  <td colspan="9" class="px-3 py-8 text-center text-slate-400 italic">
                    No pocket money wallets found. Create one above.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Transactions table -->
        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-4 text-lg font-semibold text-white">Transaction History</h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Date</th>
                  <th class="px-3 py-2">Txn No</th>
                  <th class="px-3 py-2">Student</th>
                  <th class="px-3 py-2">Type</th>
                  <th class="px-3 py-2">Reference / Depositor</th>
                  <th class="px-3 py-2">Amount</th>
                  <th class="px-3 py-2">Recorded By</th>
                  <th class="px-3 py-2">Status</th>
                  <th class="px-3 py-2 no-print">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr
                  v-for="txn in filteredTransactions"
                  :key="txn.id"
                  :class="{ 'opacity-50': txn.status === 'reversed' }"
                  class="hover:bg-white/5 transition-colors"
                >
                  <td class="px-3 py-2 text-slate-200">{{ txn.transaction_date || '—' }}</td>
                  <td class="px-3 py-2 text-slate-300 font-mono text-xs">
                    {{ txn.transaction_no || '—' }}
                  </td>
                  <td class="px-3 py-2 text-white">
                    <div class="font-medium">{{ txn.student_name || '—' }}</div>
                    <div class="text-xs text-slate-400">{{ txn.admission_no }}</div>
                  </td>
                  <td class="px-3 py-2">
                    <span
                      class="capitalize font-medium"
                      :class="txn.type === 'deposit' ? 'text-emerald-300' : 'text-amber-300'"
                    >
                      {{ txn.type }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-slate-300">
                    <div>{{ txn.reference_no || txn.depositor_name || '—' }}</div>
                    <div v-if="txn.depositor_phone" class="text-xs text-slate-400">
                      {{ txn.depositor_phone }}
                    </div>
                  </td>
                  <td
                    class="px-3 py-2 font-semibold"
                    :class="[
                      txn.status === 'reversed'
                        ? 'line-through text-slate-500'
                        : txn.type === 'deposit'
                          ? 'text-emerald-300'
                          : 'text-amber-300',
                    ]"
                  >
                    {{ formatKES(txn.amount) }}
                  </td>
                  <td class="px-3 py-2 text-slate-400 text-xs">
                    {{ txn.recorded_by_name || '—' }}
                  </td>
                  <td class="px-3 py-2">
                    <span
                      v-if="txn.status === 'reversed'"
                      class="rounded border border-rose-400/40 bg-rose-500/10 px-2 py-0.5 text-xs font-medium text-rose-300"
                      :title="txn.reversal_reason ?? ''"
                    >
                      Reversed
                    </span>
                    <span
                      v-else
                      class="rounded border border-emerald-400/40 bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-300"
                    >
                      Active
                    </span>
                  </td>
                  <td class="px-3 py-2 no-print">
                    <button
                      v-if="txn.status !== 'reversed'"
                      type="button"
                      class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10"
                      @click="reverseTransaction(txn.id)"
                    >
                      Reverse
                    </button>
                    <span v-else class="text-xs text-slate-500 italic">
                      by {{ txn.reversed_by_name || 'system' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!filteredTransactions.length">
                  <td colspan="9" class="px-3 py-8 text-center text-slate-400 italic">
                    No pocket money transactions found for this filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </main>
  </AdminLayout>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>