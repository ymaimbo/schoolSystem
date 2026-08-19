<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  students: { type: Array, default: () => [] },
  accounts: { type: Object, default: () => ({ data: [] }) },
  recentPayments: { type: Object, default: () => ({ data: [] }) },
  filters: {
    type: Object,
    default: () => ({ search_student: '', search_receipt: '' }),
  },
  paymentMethods: { type: Array, default: () => [] },
  feeStructures: { type: Array, default: () => [] },
  voteHeads: { type: Array, default: () => [] },
  voteHeadsEnabled: { type: Boolean, default: false },
})

const expanded = ref({})

const accountForm = useForm({
  student_id: '',
  total_fee_due: '',
  fee_structure_id: '',
  sponsor_org_name: '',
  sponsor_org_id: '',
  notes: '',
})

const paymentForm = useForm({
  student_id: '',
  amount: '',
  payment_method: 'cash',
  organization_name: '',
  organization_id: '',
  receipt_no: '',
  paid_at: new Date().toISOString().slice(0, 10),
  notes: '',
  use_priority_allocations: false,
  priority_allocations: [{ vote_head_id: '', amount: '' }],
})

const filterForm = useForm({
  search_student: props.filters?.search_student || '',
  search_receipt: props.filters?.search_receipt || '',
})

const filteredAccounts = computed(() => {
  const q = String(filterForm.search_student || '').trim().toLowerCase()
  const rows = Array.isArray(props.accounts?.data) ? props.accounts.data : []

  if (!q) return rows

  return rows.filter((account) => {
    const adm = String(account?.student?.admission_no || '').toLowerCase()
    const first = String(account?.student?.first_name || '').toLowerCase()
    const last = String(account?.student?.last_name || '').toLowerCase()
    const full = `${first} ${last}`.trim()
    return adm.includes(q) || first.includes(q) || last.includes(q) || full.includes(q)
  })
})

const filteredPayments = computed(() => {
  const studentQ = String(filterForm.search_student || '').trim().toLowerCase()
  const receiptQ = String(filterForm.search_receipt || '').trim().toLowerCase()
  const rows = Array.isArray(props.recentPayments?.data) ? props.recentPayments.data : []

  return rows.filter((payment) => {
    const adm = String(payment?.student?.admission_no || '').toLowerCase()
    const first = String(payment?.student?.first_name || '').toLowerCase()
    const last = String(payment?.student?.last_name || '').toLowerCase()
    const full = `${first} ${last}`.trim()
    const receipt = String(payment?.receipt_no || '').toLowerCase()

    const studentMatch = !studentQ || adm.includes(studentQ) || first.includes(studentQ) || last.includes(studentQ) || full.includes(studentQ)
    const receiptMatch = !receiptQ || receipt.includes(receiptQ)

    return studentMatch && receiptMatch
  })
})

const hasFeeStructures = computed(() => Array.isArray(props.feeStructures) && props.feeStructures.length > 0)

const priorityTotal = computed(() => {
  return (paymentForm.priority_allocations || []).reduce((sum, row) => sum + Number(row.amount || 0), 0)
})

const allocationDifference = computed(() => {
  return Number(paymentForm.amount || 0) - priorityTotal.value
})

const breakdownLines = (account) => {
  return Array.isArray(account?.vote_breakdown?.lines) ? account.vote_breakdown.lines : []
}

const hasBreakdownData = (account) => {
  const lines = breakdownLines(account)
  if (lines.length) return true

  const t = account?.vote_breakdown?.totals
  if (!t) return false

  return Number(t.parent_expected || 0) > 0
    || Number(t.capitation_expected || 0) > 0
    || Number(t.t1_expected || 0) > 0
    || Number(t.t2_expected || 0) > 0
    || Number(t.t3_expected || 0) > 0
    || Number(t.annual_expected || 0) > 0
}

const addPriorityRow = () => {
  paymentForm.priority_allocations.push({ vote_head_id: '', amount: '' })
}

const removePriorityRow = (idx) => {
  paymentForm.priority_allocations.splice(idx, 1)
  if (!paymentForm.priority_allocations.length) {
    paymentForm.priority_allocations.push({ vote_head_id: '', amount: '' })
  }
}

const resetPriorityAllocations = () => {
  paymentForm.use_priority_allocations = false
  paymentForm.priority_allocations = [{ vote_head_id: '', amount: '' }]
}

const refreshFinanceData = () => {
  router.reload({
    only: ['accounts', 'recentPayments'],
    preserveScroll: true,
  })
}

const saveAccount = () => {
  accountForm.post(route('admin.student-finance.account.upsert'), {
    preserveScroll: true,
    onSuccess: () => {
      refreshFinanceData()
    },
  })
}

const savePayment = () => {
  paymentForm
    .transform((data) => ({
      ...data,
      priority_allocations: data.use_priority_allocations
        ? (data.priority_allocations || []).filter(
            (r) => Number(r.vote_head_id) > 0 && Number(r.amount || 0) > 0,
          )
        : [],
    }))
    .post(route('admin.student-finance.payment.store'), {
      preserveScroll: true,
      onSuccess: () => {
        paymentForm.reset('amount', 'organization_name', 'organization_id', 'receipt_no', 'notes')
        resetPriorityAllocations()
        refreshFinanceData()
      },
    })
}

const deletePayment = (id) => {
  if (!confirm('Delete this payment record?')) return
  useForm({}).delete(route('admin.student-finance.payment.destroy', id), {
    preserveScroll: true,
    onSuccess: () => refreshFinanceData(),
  })
}

const applyFilters = () => {
  router.get(
    route('admin.student-finance.index'),
    {
      search_student: filterForm.search_student,
      search_receipt: filterForm.search_receipt,
    },
    { preserveState: true, replace: true, preserveScroll: true },
  )
}

const clearFilters = () => {
  filterForm.search_student = ''
  filterForm.search_receipt = ''
  applyFilters()
}

const toggleBreakdown = (accountId) => {
  expanded.value[accountId] = !expanded.value[accountId]
}

const fmt = (v) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
  <Head title="Student Finance" />

  <AdminLayout>
    <div class="space-y-8">
      <section class="space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Student Finance</h1>
            <p class="text-sm text-slate-600">Manage student fee accounts, vote heads, receipts, and statements.</p>
          </div>

          <div class="flex gap-2">
            <Link
              :href="route('admin.finance.fee-structures.index')"
              class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800"
            >
              Manage Fee Structures
            </Link>
            <button
              type="button"
              class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700"
              @click="refreshFinanceData"
            >
              Refresh
            </button>
          </div>
        </div>

        <div
          v-if="!voteHeadsEnabled"
          class="rounded border border-orange-300 bg-orange-50 px-3 py-2 text-sm text-orange-800"
        >
          Vote-head tables are not yet fully available. System is running in basic mode until all migrations are complete.
        </div>
      </section>

      <section class="grid gap-4 rounded border border-slate-200 bg-white p-4 md:grid-cols-2">
        <input
          v-model="filterForm.search_student"
          type="text"
          placeholder="Search student name / admission number"
          class="rounded border border-slate-300 px-3 py-2 text-sm"
          @keyup.enter="applyFilters"
        />
        <input
          v-model="filterForm.search_receipt"
          type="text"
          placeholder="Search receipt number"
          class="rounded border border-slate-300 px-3 py-2 text-sm"
          @keyup.enter="applyFilters"
        />
        <div class="md:col-span-2 flex gap-2">
          <button type="button" class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800" @click="applyFilters">
            Apply Filters
          </button>
          <button type="button" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700" @click="clearFilters">
            Clear Filters
          </button>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 rounded border border-slate-200 bg-white p-5" @submit.prevent="saveAccount">
          <h2 class="text-lg font-semibold">Set / Update Student Fee Account</h2>

          <select v-model="accountForm.student_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <select
            v-if="hasFeeStructures"
            v-model="accountForm.fee_structure_id"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select Fee Structure (optional)</option>
            <option v-for="f in feeStructures" :key="f.id" :value="f.id">
              {{ f.year }} - {{ f.name }} ({{ f.category }})
            </option>
          </select>

          <input v-model="accountForm.total_fee_due" type="number" step="0.01" min="0" placeholder="Total fee due (fallback/manual)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="accountForm.sponsor_org_name" type="text" placeholder="Sponsor organization name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="accountForm.sponsor_org_id" type="text" placeholder="Sponsor organization ID" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="accountForm.notes" rows="2" placeholder="Notes" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />

          <button type="submit" class="rounded border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
            Save Fee Account
          </button>
        </form>

        <form class="space-y-3 rounded border border-slate-200 bg-white p-5" @submit.prevent="savePayment">
          <h2 class="text-lg font-semibold">Record Student Payment</h2>

          <select v-model="paymentForm.student_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" placeholder="Amount" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />

          <select v-model="paymentForm.payment_method" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option>
          </select>

          <input v-model="paymentForm.organization_name" type="text" placeholder="Organization name (if applicable)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="paymentForm.organization_id" type="text" placeholder="Organization ID (if applicable)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="paymentForm.receipt_no" type="text" placeholder="Receipt number (optional; auto if empty)" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="paymentForm.paid_at" type="date" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="paymentForm.notes" rows="2" placeholder="Notes" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" />

          <div v-if="voteHeadsEnabled" class="rounded border border-slate-200 p-3">
            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
              <input v-model="paymentForm.use_priority_allocations" type="checkbox" />
              Use vote-head priority allocation for this payment
            </label>

            <div v-if="paymentForm.use_priority_allocations" class="space-y-3">
              <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                  <thead class="bg-slate-50 text-left text-slate-700">
                    <tr>
                      <th class="px-2 py-1.5">Vote Head</th>
                      <th class="px-2 py-1.5">Amount</th>
                      <th class="px-2 py-1.5">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(row, idx) in paymentForm.priority_allocations"
                      :key="idx"
                      class="border-t border-slate-100"
                    >
                      <td class="px-2 py-1.5">
                        <select v-model="row.vote_head_id" class="w-56 rounded border border-slate-300 px-2 py-1 text-xs">
                          <option value="">Select vote head</option>
                          <option v-for="vh in voteHeads" :key="vh.id" :value="vh.id">{{ vh.code }} - {{ vh.name }}</option>
                        </select>
                      </td>
                      <td class="px-2 py-1.5">
                        <input v-model.number="row.amount" type="number" min="0.01" step="0.01" class="w-28 rounded border border-slate-300 px-2 py-1 text-xs" />
                      </td>
                      <td class="px-2 py-1.5">
                        <button type="button" class="text-rose-600 hover:underline" @click="removePriorityRow(idx)">Remove</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="flex items-center justify-between text-xs">
                <button type="button" class="rounded border border-slate-300 px-2 py-1 text-slate-700" @click="addPriorityRow">
                  + Add Priority Row
                </button>
                <div>
                  Split Total:
                  <strong>{{ fmt(priorityTotal) }}</strong>
                  |
                  Payment:
                  <strong>{{ fmt(paymentForm.amount) }}</strong>
                  |
                  Difference:
                  <strong :class="Math.abs(allocationDifference) < 0.01 ? 'text-emerald-700' : 'text-rose-700'">
                    {{ fmt(allocationDifference) }}
                  </strong>
                </div>
              </div>
            </div>
          </div>

          <button type="submit" class="rounded border border-emerald-700 bg-emerald-700 px-4 py-2 text-sm text-white hover:bg-emerald-800">
            Record Payment
          </button>
        </form>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-lg font-semibold">Student Fee Accounts</h2>
          <p class="text-xs text-slate-500">Showing {{ filteredAccounts.length }} account(s)</p>
        </div>

        <div class="overflow-x-auto rounded border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Due</th>
                <th class="px-4 py-3">Paid</th>
                <th class="px-4 py-3">Balance</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="account in filteredAccounts" :key="account.id">
                <tr class="border-t border-slate-100">
                  <td class="px-4 py-3">
                    {{ account.student?.admission_no }} - {{ account.student?.first_name }} {{ account.student?.last_name }}
                  </td>
                  <td class="px-4 py-3">{{ fmt(account.total_fee_due) }}</td>
                  <td class="px-4 py-3">{{ fmt(account.paid_total) }}</td>
                  <td class="px-4 py-3" :class="Number(account.balance ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700'">
                    {{ fmt(account.balance) }}
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-3">
                      <button type="button" class="text-indigo-700 hover:underline" @click="toggleBreakdown(account.id)">
                        {{ expanded[account.id] ? 'Hide Vote Heads' : 'View Vote Heads' }}
                      </button>
                      <Link :href="route('admin.student-finance.statement.print', account.student?.id)" target="_blank" class="text-sky-700 hover:underline">
                        Print Statement
                      </Link>
                    </div>
                  </td>
                </tr>

                <tr v-if="expanded[account.id]" class="border-t border-slate-100 bg-slate-50/40">
                  <td colspan="5" class="px-4 py-4">
                    <div class="space-y-3">
                      <h3 class="text-sm font-semibold text-slate-800">Vote Head Breakdown</h3>

                      <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                          <thead class="bg-slate-100 text-left text-slate-700">
                            <tr>
                              <th class="px-3 py-2">Vote Head</th>
                              <th class="px-3 py-2">Parent Due</th>
                              <th class="px-3 py-2">Parent Paid</th>
                              <th class="px-3 py-2">Parent Bal</th>
                              <th class="px-3 py-2">Capitation Due</th>
                              <th class="px-3 py-2">T1 Due</th>
                              <th class="px-3 py-2">T2 Due</th>
                              <th class="px-3 py-2">T3 Due</th>
                              <th class="px-3 py-2">Annual Due</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr
                              v-for="line in breakdownLines(account)"
                              :key="line.vote_head_id"
                              class="border-t border-slate-200"
                            >
                              <td class="px-3 py-2 font-medium">{{ line.vote_code }} - {{ line.vote_name }}</td>
                              <td class="px-3 py-2">{{ fmt(line.parent_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.parent_paid) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.parent_balance) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.capitation_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.t1_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.t2_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.t3_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(line.annual_expected) }}</td>
                            </tr>

                            <tr v-if="!hasBreakdownData(account)">
                              <td colspan="9" class="px-3 py-3 text-center text-slate-500">
                                No vote-head details found for this account yet.
                                Ensure the student account has a fee structure assigned, then refresh.
                              </td>
                            </tr>
                          </tbody>

                          <tfoot v-if="account.vote_breakdown?.totals" class="bg-slate-100 font-semibold text-slate-800">
                            <tr>
                              <td class="px-3 py-2">TOTAL</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.parent_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.parent_paid) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.parent_balance) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.capitation_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.t1_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.t2_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.t3_expected) }}</td>
                              <td class="px-3 py-2">{{ fmt(account.vote_breakdown.totals.annual_expected) }}</td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!filteredAccounts.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                  No student accounts found for this search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-lg font-semibold">Payment Receipts</h2>
          <p class="text-xs text-slate-500">Showing {{ filteredPayments.length }} payment(s)</p>
        </div>

        <div class="overflow-x-auto rounded border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-700">
              <tr>
                <th class="px-4 py-3">Receipt #</th>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in filteredPayments" :key="payment.id" class="border-t border-slate-100">
                <td class="px-4 py-3 font-medium">{{ payment.receipt_no }}</td>
                <td class="px-4 py-3">
                  {{ payment.student?.admission_no }} - {{ payment.student?.first_name }} {{ payment.student?.last_name }}
                </td>
                <td class="px-4 py-3">{{ fmt(payment.amount) }}</td>
                <td class="px-4 py-3">{{ payment.payment_method }}</td>
                <td class="px-4 py-3">{{ payment.paid_at }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-3">
                    <Link :href="route('admin.student-finance.receipt.print', payment.id)" target="_blank" class="text-sky-700 hover:underline">
                      Print
                    </Link>
                    <button type="button" class="text-rose-600 hover:underline" @click="deletePayment(payment.id)">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filteredPayments.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                  No payments found for this search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>