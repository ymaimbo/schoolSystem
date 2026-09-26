<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  summary: {
    default: () => ({
      students_count: 0,
      accounts_count: 0,
      total_billed: 0,
      total_paid: 0,
      total_balance: 0,
    }),
  },
  students: { default: () => [] },
  accounts: { default: () => [] },
  recentPayments: { default: () => [] },
  payments: { default: () => [] },
  feeStructures: { default: () => [] },
  structures: { default: () => [] },
  fee_structures: { default: () => [] },
  paymentMethods: { default: () => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'] },
  filters: { default: () => ({ search: '', class_level: '' }) },
})

const page = usePage()
const flash = computed(() => page.props?.flash ?? {})
const isRefreshing = ref(false)

const endpoints = {
  index: '/admin/student-finance',
  accountUpsert: '/admin/student-finance/account',
  paymentStore: '/admin/student-finance/payment',
  paymentDestroy: (id) => `/admin/student-finance/payment/${id}`,
  receiptPrint: (id) => `/admin/student-finance/receipt/${id}`,
  feeStructuresIndex: '/admin/finance/fee-structures',
}

const normalizeCollection = (input) => {
  if (Array.isArray(input)) return input
  if (!input || typeof input !== 'object') return []
  if (Array.isArray(input.data)) return input.data
  if (Array.isArray(input.items)) return input.items
  const values = Object.values(input)
  if (values.every((v) => typeof v === 'object' && v !== null)) return values
  return []
}

const toNumber = (value) => {
  const n = Number(value ?? 0)
  return Number.isFinite(n) ? n : 0
}

const normalizeId = (value) => {
  if (value === null || value === undefined) return null
  if (typeof value === 'number' && Number.isFinite(value)) return value
  const raw = String(value).trim()
  if (!raw) return null
  const n = Number(raw)
  if (Number.isFinite(n)) return n
  const match = raw.match(/\d+/)
  if (!match) return null
  const parsed = Number(match[0])
  return Number.isFinite(parsed) ? parsed : null
}

const formatKES = (value) => {
  const amount = toNumber(value)
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(amount)
}

/** Students helpers */
const getStudentId = (student) =>
  normalizeId(student?.id ?? student?.student_id ?? student?.user_id ?? null)

const getStudentName = (student) => {
  if (!student || typeof student !== 'object') return ''
  const explicit =
    student.name ??
    student.full_name ??
    student.student_name ??
    student.user?.name ??
    student.user?.full_name
  if (explicit && String(explicit).trim()) return String(explicit).trim()

  const first = student.first_name ?? student.firstname ?? student.user?.first_name ?? ''
  const last = student.last_name ?? student.lastname ?? student.user?.last_name ?? ''
  return `${first} ${last}`.trim()
}

const getAdmissionNo = (student) =>
  student?.admission_no ?? student?.admissionNumber ?? student?.adm_no ?? ''

const buildStudentLabel = (student) => {
  const id = getStudentId(student)
  const name = getStudentName(student) || `Student #${id ?? 'N/A'}`
  const admission = getAdmissionNo(student) || id || 'N/A'
  return `${name} (${admission})`
}

/** Structure helpers */
const getStructureId = (structure) =>
  normalizeId(structure?.id ?? structure?.fee_structure_id ?? structure?.structure_id ?? null)

const getStructureName = (structure) =>
  structure?.name ??
  structure?.title ??
  structure?.structure_name ??
  structure?.fee_name ??
  structure?.category ??
  ''

const getStructureAmount = (structure) =>
  structure?.total_amount ??
  structure?.amount ??
  structure?.total ??
  structure?.expected_amount ??
  structure?.fee_amount ??
  null

/** Raw prop rows */
const studentsRows = computed(() => normalizeCollection(props.students))

const feeStructureRows = computed(() => {
  const primary = normalizeCollection(props.feeStructures)
  if (primary.length) return primary
  const alt1 = normalizeCollection(props.structures)
  if (alt1.length) return alt1
  return normalizeCollection(props.fee_structures)
})

const studentPickerOptions = computed(() =>
  studentsRows.value
    .map((student) => {
      const id = getStudentId(student)
      if (id === null) return null
      return {
        id,
        label: buildStudentLabel(student),
        name: (getStudentName(student) || '').toLowerCase(),
        admission: String(getAdmissionNo(student) || '').toLowerCase(),
      }
    })
    .filter(Boolean)
    .sort((a, b) => a.label.localeCompare(b.label))
)

/** Local reactive rows so UI updates instantly after success */
const localAccounts = ref([])
const localPayments = ref([])

const normalizeAccountRow = (row) => {
  // In your backend, total_fee_due is remaining balance
  const balance = toNumber(row.total_fee_due ?? row.balance ?? 0)
  const totalPaid = toNumber(row.total_paid ?? row.paid ?? 0)
  const expected = toNumber(row.expected_amount ?? (balance + totalPaid))

  return {
    ...row,
    id: row.id ?? `acc-${Date.now()}-${Math.random()}`,
    student_id: normalizeId(row.student_id ?? row.student?.id ?? null),
    student_name:
      row.student_name ??
      row.name ??
      row.student?.name ??
      row.student?.full_name ??
      `${row.student?.first_name ?? ''} ${row.student?.last_name ?? ''}`.trim(),
    admission_no: row.admission_no ?? row.student?.admission_no ?? '',
    class_level: row.class_level ?? row.student?.class_level ?? '',
    stream: row.stream ?? row.student?.stream ?? '',
    expected_amount: expected,
    total_paid: totalPaid,
    balance,
    total_fee_due: balance,
  }
}

const normalizePaymentRow = (row) => ({
  ...row,
  id: row.id ?? `pay-${Date.now()}-${Math.random()}`,
  amount: toNumber(row.amount ?? row.paid_amount ?? 0),
  student_id: normalizeId(row.student_id ?? row.student?.id ?? null),
  student_name:
    row.student_name ??
    row.student?.name ??
    row.student?.full_name ??
    `${row.student?.first_name ?? ''} ${row.student?.last_name ?? ''}`.trim(),
  organization_id: row.organization_id ?? row.reference_no ?? row.receipt_no ?? null,
})

const syncFromProps = () => {
  localAccounts.value = normalizeCollection(props.accounts).map(normalizeAccountRow)

  const p = normalizeCollection(props.payments)
  const source = p.length ? p : normalizeCollection(props.recentPayments)
  localPayments.value = source.map(normalizePaymentRow)
}

syncFromProps()

watch(
  () => [props.accounts, props.payments, props.recentPayments],
  () => syncFromProps()
)

const accountsRows = computed(() => localAccounts.value)
const paymentsRows = computed(() => localPayments.value)

/** Filters */
const search = ref(props.filters?.search ?? '')
const classFilter = ref(props.filters?.class_level ?? '')

const classLevels = computed(() => {
  const values = studentsRows.value.map((s) => s.class_level).filter(Boolean)
  return [...new Set(values)]
})

const filteredAccounts = computed(() => {
  const q = search.value.trim().toLowerCase()
  const level = classFilter.value

  return accountsRows.value.filter((row) => {
    const studentName = row.student_name ?? ''
    const admissionNo = row.admission_no ?? ''
    const rowClassLevel = row.class_level ?? ''

    const matchesQ =
      !q ||
      String(studentName).toLowerCase().includes(q) ||
      String(admissionNo).toLowerCase().includes(q)

    const matchesClass = !level || String(rowClassLevel) === level
    return matchesQ && matchesClass
  })
})

/** Forms */
const upsertAccountForm = useForm({
  student_id: '',
  fee_structure_id: '',
  opening_balance: '',
  expected_amount: '',
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
  paid_at: new Date().toISOString().slice(0, 10),
  notes: '',
})

/** Searchable student pickers */
const accountStudentQuery = ref('')
const paymentStudentQuery = ref('')

const selectedAccountStudent = computed(() => {
  const id = normalizeId(upsertAccountForm.student_id)
  if (id === null) return null
  return studentsRows.value.find((s) => getStudentId(s) === id) ?? null
})

const selectedPaymentStudent = computed(() => {
  const id = normalizeId(paymentForm.student_id)
  if (id === null) return null
  return studentsRows.value.find((s) => getStudentId(s) === id) ?? null
})

const resolveStudentByQuery = (query) => {
  const q = String(query || '').trim().toLowerCase()
  if (!q) return null

  const exact = studentPickerOptions.value.find((opt) => opt.label.toLowerCase() === q)
  if (exact) return exact

  const matches = studentPickerOptions.value.filter(
    (opt) =>
      opt.name.includes(q) ||
      opt.admission.includes(q) ||
      String(opt.id).toLowerCase().includes(q)
  )

  return matches.length === 1 ? matches[0] : null
}

const onAccountStudentInput = () => {
  if (accountStudentQuery.value !== (selectedAccountStudent.value ? buildStudentLabel(selectedAccountStudent.value) : '')) {
    upsertAccountForm.student_id = ''
  }
}

const onPaymentStudentInput = () => {
  if (paymentStudentQuery.value !== (selectedPaymentStudent.value ? buildStudentLabel(selectedPaymentStudent.value) : '')) {
    paymentForm.student_id = ''
  }
}

const resolveAccountStudentQuery = () => {
  const resolved = resolveStudentByQuery(accountStudentQuery.value)
  if (resolved) {
    upsertAccountForm.student_id = String(resolved.id)
    accountStudentQuery.value = resolved.label
    upsertAccountForm.clearErrors('student_id')
    return
  }
  upsertAccountForm.student_id = ''
}

const resolvePaymentStudentQuery = () => {
  const resolved = resolveStudentByQuery(paymentStudentQuery.value)
  if (resolved) {
    paymentForm.student_id = String(resolved.id)
    paymentStudentQuery.value = resolved.label
    paymentForm.clearErrors('student_id')
    return
  }
  paymentForm.student_id = ''
}

watch(
  () => upsertAccountForm.student_id,
  () => {
    if (selectedAccountStudent.value) {
      accountStudentQuery.value = buildStudentLabel(selectedAccountStudent.value)
    } else if (!accountStudentQuery.value.trim()) {
      accountStudentQuery.value = ''
    }
  },
  { immediate: true }
)

watch(
  () => paymentForm.student_id,
  () => {
    if (selectedPaymentStudent.value) {
      paymentStudentQuery.value = buildStudentLabel(selectedPaymentStudent.value)
    } else if (!paymentStudentQuery.value.trim()) {
      paymentStudentQuery.value = ''
    }
  },
  { immediate: true }
)

const selectedFeeStructure = computed(() => {
  const id = normalizeId(upsertAccountForm.fee_structure_id)
  if (id === null) return null
  return feeStructureRows.value.find((f) => getStructureId(f) === id) ?? null
})

watch(
  () => selectedFeeStructure.value,
  (structure) => {
    if (!structure) return
    if (upsertAccountForm.expected_amount) return
    const amount = getStructureAmount(structure)
    if (amount !== null && amount !== undefined && amount !== '') {
      upsertAccountForm.expected_amount = String(amount)
    }
  }
)

/** Summary from current visible local data */
const summaryDisplay = computed(() => {
  const totalBilled = accountsRows.value.reduce((sum, row) => sum + toNumber(row.expected_amount), 0)
  const totalPaid = accountsRows.value.reduce((sum, row) => sum + toNumber(row.total_paid), 0)
  const totalBalance = accountsRows.value.reduce((sum, row) => sum + toNumber(row.balance), 0)

  return {
    students_count: props.summary?.students_count ?? studentsRows.value.length,
    accounts_count: accountsRows.value.length,
    total_billed: totalBilled,
    total_paid: totalPaid,
    total_balance: totalBalance,
  }
})

const refreshFinanceData = () => {
  router.get(
    endpoints.index,
    {
      search: search.value || undefined,
      class_level: classFilter.value || undefined,
    },
    {
      preserveScroll: true,
      preserveState: false,
      replace: true,
      onStart: () => (isRefreshing.value = true),
      onFinish: () => (isRefreshing.value = false),
    }
  )
}

/** Immediate local state updaters on success */
const applyLocalAccountUpsert = ({ studentId, totalFeeDue }) => {
  const sid = normalizeId(studentId)
  if (sid === null) return

  const student = studentsRows.value.find((s) => getStudentId(s) === sid)
  const existingIndex = localAccounts.value.findIndex((a) => normalizeId(a.student_id) === sid)
  const due = Math.max(toNumber(totalFeeDue), 0)

  if (existingIndex >= 0) {
    const current = localAccounts.value[existingIndex]
    const paid = toNumber(current.total_paid)
    localAccounts.value[existingIndex] = normalizeAccountRow({
      ...current,
      total_fee_due: due,
      balance: due,
      expected_amount: due + paid,
      student_name: current.student_name || getStudentName(student),
      admission_no: current.admission_no || getAdmissionNo(student),
      class_level: current.class_level || student?.class_level || '',
      stream: current.stream || student?.stream || '',
    })
  } else {
    localAccounts.value.unshift(
      normalizeAccountRow({
        id: `local-account-${Date.now()}`,
        student_id: sid,
        total_fee_due: due,
        balance: due,
        total_paid: 0,
        expected_amount: due,
        student_name: getStudentName(student),
        admission_no: getAdmissionNo(student),
        class_level: student?.class_level ?? '',
        stream: student?.stream ?? '',
      })
    )
  }
}

const applyLocalPayment = ({ studentId, amount, method, paidAt, orgName, orgId }) => {
  const sid = normalizeId(studentId)
  const paidAmount = Math.max(toNumber(amount), 0)
  if (sid === null || paidAmount <= 0) return

  const student = studentsRows.value.find((s) => getStudentId(s) === sid)

  localPayments.value.unshift(
    normalizePaymentRow({
      id: `local-payment-${Date.now()}`,
      student_id: sid,
      student_name: getStudentName(student),
      amount: paidAmount,
      payment_method: method || 'cash',
      organization_name: orgName || null,
      organization_id: orgId || null,
      paid_at: paidAt || new Date().toISOString().slice(0, 10),
      created_at: new Date().toISOString(),
    })
  )

  const accountIndex = localAccounts.value.findIndex((a) => normalizeId(a.student_id) === sid)
  if (accountIndex >= 0) {
    const acc = localAccounts.value[accountIndex]
    const oldBalance = Math.max(toNumber(acc.balance ?? acc.total_fee_due), 0)
    const oldPaid = Math.max(toNumber(acc.total_paid), 0)
    const newBalance = Math.max(oldBalance - paidAmount, 0)
    const newPaid = oldPaid + paidAmount
    const expected = newBalance + newPaid

    localAccounts.value[accountIndex] = normalizeAccountRow({
      ...acc,
      total_fee_due: newBalance,
      balance: newBalance,
      total_paid: newPaid,
      expected_amount: expected,
    })
  }
}

const submitAccount = () => {
  resolveAccountStudentQuery()
  if (normalizeId(upsertAccountForm.student_id) === null) {
    upsertAccountForm.setError('student_id', 'Please select a valid student from the list.')
    return
  }

  const expected = toNumber(upsertAccountForm.expected_amount || getStructureAmount(selectedFeeStructure.value) || 0)
  const opening = toNumber(upsertAccountForm.opening_balance)
  const totalFeeDue = expected + opening

  upsertAccountForm
    .transform((data) => ({
      student_id: normalizeId(data.student_id),
      fee_structure_id: normalizeId(data.fee_structure_id),
      total_fee_due: totalFeeDue,
      sponsor_org_name: data.sponsor_org_name || null,
      sponsor_org_id: data.sponsor_org_id || null,
      notes: data.notes || null,
    }))
    .post(endpoints.accountUpsert, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        applyLocalAccountUpsert({
          studentId: upsertAccountForm.student_id,
          totalFeeDue,
        })

        upsertAccountForm.reset('opening_balance', 'expected_amount', 'notes')
        refreshFinanceData()
      },
      onError: (errors) => {
        console.error('Account save validation failed:', errors)
      },
    })
}

const submitPayment = () => {
  resolvePaymentStudentQuery()
  if (normalizeId(paymentForm.student_id) === null) {
    paymentForm.setError('student_id', 'Please select a valid student from the list.')
    return
  }

  const payload = {
    student_id: normalizeId(paymentForm.student_id),
    amount: toNumber(paymentForm.amount),
    payment_method: paymentForm.payment_method,
    organization_name: paymentForm.organization_name || null,
    organization_id: paymentForm.organization_id || null,
    paid_at: paymentForm.paid_at,
    notes: paymentForm.notes || null,
  }

  paymentForm
    .transform(() => payload)
    .post(endpoints.paymentStore, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        applyLocalPayment({
          studentId: payload.student_id,
          amount: payload.amount,
          method: payload.payment_method,
          paidAt: payload.paid_at,
          orgName: payload.organization_name,
          orgId: payload.organization_id,
        })

        paymentForm.reset('amount', 'organization_name', 'organization_id', 'notes')
        refreshFinanceData()
      },
      onError: (errors) => {
        console.error('Payment validation failed:', errors)
      },
    })
}

const deletePayment = (paymentId) => {
  if (!window.confirm('Delete this payment record?')) return

  router.delete(endpoints.paymentDestroy(paymentId), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      localPayments.value = localPayments.value.filter((p) => p.id !== paymentId)
      refreshFinanceData()
    },
  })
}
</script>

<template>
  <Head title="Student Finance" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <section class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">Student Finance</h1>
          <p class="mt-1 text-sm text-slate-300">
            Manage student fee accounts, fee structures, and payment records.
          </p>
        </div>

        <button
          type="button"
          class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-50"
          :disabled="isRefreshing"
          @click="refreshFinanceData"
        >
          {{ isRefreshing ? 'Refreshing...' : 'Refresh Data' }}
        </button>
      </section>

      <section v-if="flash.success || flash.error" class="mb-6 space-y-2">
        <div v-if="flash.success" class="rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
          {{ flash.success }}
        </div>
        <div v-if="flash.error" class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
          {{ flash.error }}
        </div>
      </section>

      <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Students</p>
          <p class="mt-2 text-xl font-bold text-white">{{ summaryDisplay.students_count }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Accounts</p>
          <p class="mt-2 text-xl font-bold text-white">{{ summaryDisplay.accounts_count }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Total Billed</p>
          <p class="mt-2 text-xl font-bold text-cyan-300">{{ formatKES(summaryDisplay.total_billed) }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Total Paid</p>
          <p class="mt-2 text-xl font-bold text-emerald-300">{{ formatKES(summaryDisplay.total_paid) }}</p>
        </article>
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-400">Outstanding</p>
          <p class="mt-2 text-xl font-bold text-amber-300">{{ formatKES(summaryDisplay.total_balance) }}</p>
        </article>
      </section>

      <section class="mb-8 grid gap-6 lg:grid-cols-2">
        <!-- Account Form -->
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Create / Update Student Fee Account</h2>

          <form class="mt-4 space-y-3" @submit.prevent="submitAccount">
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Student</label>

              <input
                v-model="accountStudentQuery"
                list="account-students-list"
                type="text"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
                placeholder="Type student name or admission number..."
                required
                @input="onAccountStudentInput"
                @change="resolveAccountStudentQuery"
                @blur="resolveAccountStudentQuery"
              />
              <datalist id="account-students-list">
                <option
                  v-for="opt in studentPickerOptions"
                  :key="`acc-opt-${opt.id}`"
                  :value="opt.label"
                />
              </datalist>

              <p class="mt-1 text-[11px] text-slate-400">Search by student name or admission number, then select from suggestions.</p>
              <p v-if="upsertAccountForm.errors.student_id" class="mt-1 text-xs text-rose-300">{{ upsertAccountForm.errors.student_id }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Student Name</label>
              <input :value="selectedAccountStudent ? (getStudentName(selectedAccountStudent) || '—') : '—'" type="text" readonly class="w-full rounded-md border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200" />
            </div>

            <div>
              <div class="mb-1 flex items-center justify-between">
                <label class="block text-xs font-medium uppercase tracking-wider text-slate-400">Fee Structure</label>
                <Link :href="endpoints.feeStructuresIndex" class="text-xs text-cyan-300 hover:text-cyan-200">Manage structures</Link>
              </div>
              <select v-model="upsertAccountForm.fee_structure_id" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400">
                <option value="">Select available structure...</option>
                <option v-for="structure in feeStructureRows" :key="getStructureId(structure) ?? JSON.stringify(structure)" :value="getStructureId(structure) ?? ''">
                  {{ getStructureName(structure) || `Structure #${getStructureId(structure) ?? 'N/A'}` }}
                </option>
              </select>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Opening Balance</label>
                <input v-model="upsertAccountForm.opening_balance" type="number" min="0" step="0.01" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400" placeholder="0.00" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Expected Amount</label>
                <input v-model="upsertAccountForm.expected_amount" type="number" min="0" step="0.01" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400" placeholder="0.00" />
              </div>
            </div>

            <button type="submit" :disabled="upsertAccountForm.processing || isRefreshing" class="inline-flex items-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50">
              {{ upsertAccountForm.processing ? 'Saving...' : 'Save Account' }}
            </button>
          </form>
        </article>

        <!-- Payment Form -->
        <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Record Fee Payment</h2>

          <form class="mt-4 space-y-3" @submit.prevent="submitPayment">
            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Student</label>

              <input
                v-model="paymentStudentQuery"
                list="payment-students-list"
                type="text"
                class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Type student name or admission number..."
                required
                @input="onPaymentStudentInput"
                @change="resolvePaymentStudentQuery"
                @blur="resolvePaymentStudentQuery"
              />
              <datalist id="payment-students-list">
                <option
                  v-for="opt in studentPickerOptions"
                  :key="`pay-opt-${opt.id}`"
                  :value="opt.label"
                />
              </datalist>

              <p class="mt-1 text-[11px] text-slate-400">Search by student name or admission number, then select from suggestions.</p>
              <p v-if="paymentForm.errors.student_id" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.student_id }}</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Student Name</label>
              <input :value="selectedPaymentStudent ? (getStudentName(selectedPaymentStudent) || '—') : '—'" type="text" readonly class="w-full rounded-md border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200" />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Amount</label>
                <input v-model="paymentForm.amount" type="number" min="1" step="0.01" required class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="0.00" />
                <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.amount }}</p>
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Payment Method</label>
                <select v-model="paymentForm.payment_method" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
                  <option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option>
                </select>
                <p v-if="paymentForm.errors.payment_method" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.payment_method }}</p>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Organization Name</label>
                <input v-model="paymentForm.organization_name" type="text" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
                <p v-if="paymentForm.errors.organization_name" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.organization_name }}</p>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Organization ID / Ref</label>
                <input v-model="paymentForm.organization_id" type="text" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
                <p v-if="paymentForm.errors.organization_id" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.organization_id }}</p>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-slate-400">Paid At</label>
              <input v-model="paymentForm.paid_at" type="date" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
              <p v-if="paymentForm.errors.paid_at" class="mt-1 text-xs text-rose-300">{{ paymentForm.errors.paid_at }}</p>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-white/10 pt-4">
              <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="paymentForm.reset('amount', 'organization_name', 'organization_id', 'notes')">
                Reset
              </button>

              <button type="submit" :disabled="paymentForm.processing || isRefreshing" class="inline-flex items-center rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-50">
                {{ paymentForm.processing ? 'Saving Payment...' : 'Record Payment' }}
              </button>
            </div>
          </form>
        </article>
      </section>

      <section class="mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-white">Student Fee Accounts</h2>
          <div class="flex flex-wrap gap-2">
            <input v-model="search" type="text" placeholder="Search name or admission no..." class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400" />
            <select v-model="classFilter" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400">
              <option value="">All classes</option>
              <option v-for="level in classLevels" :key="level" :value="level">{{ level }}</option>
            </select>
            <button type="button" class="rounded-md border border-white/20 px-3 py-2 text-xs font-medium text-slate-200 hover:bg-white/5" @click="refreshFinanceData">
              Apply
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Admission No</th>
                <th class="px-3 py-2">Student</th>
                <th class="px-3 py-2">Class</th>
                <th class="px-3 py-2">Expected</th>
                <th class="px-3 py-2">Paid</th>
                <th class="px-3 py-2">Balance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="account in filteredAccounts" :key="account.id ?? account.student_id ?? account.admission_no">
                <td class="px-3 py-2 text-slate-200">{{ account.admission_no || '—' }}</td>
                <td class="px-3 py-2 text-white">{{ account.student_name || '—' }}</td>
                <td class="px-3 py-2 text-slate-300">
                  {{ account.class_level || '—' }}
                  <span v-if="account.stream">- {{ account.stream }}</span>
                </td>
                <td class="px-3 py-2 text-cyan-300">{{ formatKES(account.expected_amount) }}</td>
                <td class="px-3 py-2 text-emerald-300">{{ formatKES(account.total_paid) }}</td>
                <td class="px-3 py-2 text-amber-300">{{ formatKES(account.balance) }}</td>
              </tr>

              <tr v-if="!filteredAccounts.length">
                <td colspan="6" class="px-3 py-6 text-center text-slate-400">No student accounts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-4 text-lg font-semibold text-white">Recent Payments</h2>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Date</th>
                <th class="px-3 py-2">Student</th>
                <th class="px-3 py-2">Method</th>
                <th class="px-3 py-2">Reference/Org</th>
                <th class="px-3 py-2">Amount</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="payment in paymentsRows" :key="payment.id ?? payment.receipt_no ?? payment.created_at">
                <td class="px-3 py-2 text-slate-200">{{ payment.paid_at || payment.created_at || '—' }}</td>
                <td class="px-3 py-2 text-white">{{ payment.student_name || '—' }}</td>
                <td class="px-3 py-2 text-slate-300">{{ payment.payment_method || payment.method || '—' }}</td>
                <td class="px-3 py-2 text-slate-300">{{ payment.organization_id || payment.organization_name || payment.receipt_no || '—' }}</td>
                <td class="px-3 py-2 font-semibold text-emerald-300">{{ formatKES(payment.amount) }}</td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-2">
                    <Link v-if="payment.id && !String(payment.id).startsWith('local-payment-')" :href="endpoints.receiptPrint(payment.id)" class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10">
                      Print Receipt
                    </Link>

                    <button v-if="payment.id && !String(payment.id).startsWith('local-payment-')" type="button" class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10" @click="deletePayment(payment.id)">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!paymentsRows.length">
                <td colspan="6" class="px-3 py-6 text-center text-slate-400">No payments recorded yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>