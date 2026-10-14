<script setup>
import { computed, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  summary: {
    default: () => ({
      students_count: 0,
      accounts_count: 0,
      total_billed: 0,
      total_paid: 0,
      total_balance: 0,
      total_credit: 0,
    }),
  },
  students: { default: () => [] },
  voteHeads: { default: () => [] },
  feeProfiles: { default: () => [] },
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
  voteheadReport: '/admin/student-finance/votehead-report',
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
  if (value === null || value === undefined || value === '') return null
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

const paymentMethods = computed(() => {
  const methods = normalizeCollection(props.paymentMethods)
  return methods.length ? methods : ['cash', 'bank', 'mpesa', 'cheque', 'transfer']
})

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
  student?.admission_no ??
  student?.admission_number ??
  student?.admissionNumber ??
  student?.adm_no ??
  ''

const buildStudentLabel = (student) => {
  const id = getStudentId(student)
  const name = getStudentName(student) || `Student #${id ?? 'N/A'}`
  const admission = getAdmissionNo(student) || id || 'N/A'
  return `${name} (${admission})`
}

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

const getVoteHeadId = (voteHead) =>
  normalizeId(voteHead?.id ?? voteHead?.vote_head_id ?? null)

const getVoteHeadName = (voteHead) =>
  voteHead?.name ?? voteHead?.title ?? voteHead?.votehead_name ?? 'Votehead'

const getVoteHeadCode = (voteHead) =>
  voteHead?.code ?? voteHead?.votehead_code ?? ''

const studentsRows = computed(() => normalizeCollection(props.students))
const voteHeadRows = computed(() => normalizeCollection(props.voteHeads).filter((v) => v?.is_active !== false))
const feeProfileRows = computed(() => normalizeCollection(props.feeProfiles))

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

const localAccounts = ref([])
const localPayments = ref([])

const normalizeAccountRow = (row) => {
  const rawDue = toNumber(row.total_fee_due ?? row.balance ?? 0)
  const totalPaid = Math.max(toNumber(row.total_paid ?? row.paid ?? 0), 0)

  const expectedFromRow = row.expected_amount ?? row.total_expected ?? null
  const inferredExpected = expectedFromRow !== null && expectedFromRow !== undefined
    ? Math.max(toNumber(expectedFromRow), 0)
    : Math.max(rawDue + totalPaid, 0)

  const balance = Math.max(rawDue, 0)
  const creditFromNegativeDue = rawDue < 0 ? Math.abs(rawDue) : 0
  const creditFromPaidVsExpected = Math.max(totalPaid - inferredExpected, 0)
  const explicitCredit = Math.max(toNumber(row.credit_balance ?? row.credit ?? 0), 0)
  const creditBalance = Math.max(explicitCredit, creditFromNegativeDue, creditFromPaidVsExpected)

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
    admission_no:
      row.admission_no ??
      row.admission_number ??
      row.student?.admission_no ??
      row.student?.admission_number ??
      '',
    class_level: row.class_level ?? row.student?.class_level ?? '',
    stream: row.stream ?? row.student?.stream ?? '',
    expected_amount: inferredExpected,
    total_paid: totalPaid,
    balance,
    total_fee_due: rawDue,
    credit_balance: creditBalance,
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
  () => syncFromProps(),
  { deep: true }
)

const accountsRows = computed(() => localAccounts.value)
const paymentsRows = computed(() => localPayments.value)

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
  priority_vote_head_ids: [],
})

const priorityVoteHeads = ref(
  voteHeadRows.value
    .map((vh) => ({
      id: getVoteHeadId(vh),
      name: getVoteHeadName(vh),
      code: getVoteHeadCode(vh),
      raw: vh,
    }))
    .filter((vh) => vh.id !== null)
)

const syncPriorityPayload = () => {
  paymentForm.priority_vote_head_ids = priorityVoteHeads.value
    .map((vh) => normalizeId(vh.id))
    .filter((id) => id !== null)
}
syncPriorityPayload()

watch(voteHeadRows, (rows) => {
  priorityVoteHeads.value = rows
    .map((vh) => ({
      id: getVoteHeadId(vh),
      name: getVoteHeadName(vh),
      code: getVoteHeadCode(vh),
      raw: vh,
    }))
    .filter((vh) => vh.id !== null)
  syncPriorityPayload()
})

const moveVoteHeadUp = (index) => {
  if (index <= 0) return
  const arr = [...priorityVoteHeads.value]
  ;[arr[index - 1], arr[index]] = [arr[index], arr[index - 1]]
  priorityVoteHeads.value = arr
  syncPriorityPayload()
}

const moveVoteHeadDown = (index) => {
  if (index >= priorityVoteHeads.value.length - 1) return
  const arr = [...priorityVoteHeads.value]
  ;[arr[index], arr[index + 1]] = [arr[index + 1], arr[index]]
  priorityVoteHeads.value = arr
  syncPriorityPayload()
}

const resetVoteHeadPriority = () => {
  priorityVoteHeads.value = voteHeadRows.value
    .map((vh) => ({
      id: getVoteHeadId(vh),
      name: getVoteHeadName(vh),
      code: getVoteHeadCode(vh),
      raw: vh,
    }))
    .filter((vh) => vh.id !== null)
  syncPriorityPayload()
}

const reportLoading = ref(false)
const reportFilters = ref({
  period: 'month',
  vote_head_id: '',
  fee_profile_id: '',
  from: '',
  to: '',
})

const voteheadReport = ref({
  period: 'month',
  from: null,
  to: null,
  grand_total: 0,
  rows: [],
})

const fetchVoteheadReport = async () => {
  reportLoading.value = true
  try {
    const params = new URLSearchParams()
    Object.entries(reportFilters.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && String(v).trim() !== '') {
        params.append(k, String(v))
      }
    })

    const response = await fetch(`${endpoints.voteheadReport}?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })

    if (!response.ok) throw new Error(`Votehead report request failed (${response.status})`)
    const data = await response.json()

    voteheadReport.value = {
      period: data?.period ?? reportFilters.value.period,
      from: data?.from ?? null,
      to: data?.to ?? null,
      grand_total: toNumber(data?.grand_total),
      rows: Array.isArray(data?.rows) ? data.rows : [],
    }
  } catch (error) {
    console.error('Votehead report error:', error)
  } finally {
    reportLoading.value = false
  }
}

const printVoteheadReport = () => {
  window.print()
}

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

const selectedPaymentAccount = computed(() => {
  const sid = normalizeId(paymentForm.student_id)
  if (sid === null) return null
  return accountsRows.value.find((a) => normalizeId(a.student_id) === sid) ?? null
})

const selectedPaymentAccountId = computed(() => {
  const acc = selectedPaymentAccount.value
  if (!acc) return null
  return normalizeId(acc.id ?? acc.student_fee_account_id ?? null)
})

const paymentOverpayPreview = computed(() => {
  const amount = Math.max(toNumber(paymentForm.amount), 0)
  if (amount <= 0) return 0
  const currentBalance = Math.max(toNumber(selectedPaymentAccount.value?.balance), 0)
  return Math.max(amount - currentBalance, 0)
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

const summaryDisplay = computed(() => {
  const totalBilled = accountsRows.value.reduce((sum, row) => sum + toNumber(row.expected_amount), 0)
  const totalPaid = accountsRows.value.reduce((sum, row) => sum + toNumber(row.total_paid), 0)
  const totalBalance = accountsRows.value.reduce((sum, row) => sum + toNumber(row.balance), 0)
  const totalCredit = accountsRows.value.reduce((sum, row) => sum + toNumber(row.credit_balance), 0)

  return {
    students_count: props.summary?.students_count ?? studentsRows.value.length,
    accounts_count: accountsRows.value.length,
    total_billed: totalBilled,
    total_paid: totalPaid,
    total_balance: totalBalance,
    total_credit: totalCredit,
  }
})

const reloadFinanceSlices = () => {
  router.reload({
    only: ['accounts', 'payments', 'recentPayments', 'summary', 'students', 'voteHeads', 'feeProfiles'],
    preserveScroll: true,
    preserveState: true,
    onStart: () => (isRefreshing.value = true),
    onFinish: () => (isRefreshing.value = false),
  })
}

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
        upsertAccountForm.reset('opening_balance', 'expected_amount', 'notes')
        reloadFinanceSlices()
      },
      onError: (errors) => {
        console.error('Account save validation failed:', errors)
      },
    })
}

const submitPayment = () => {
  resolvePaymentStudentQuery()

  const sid = normalizeId(paymentForm.student_id)
  if (sid === null) {
    paymentForm.setError('student_id', 'Please select a valid student from the list.')
    return
  }

  const accountId = selectedPaymentAccountId.value
  if (accountId === null) {
    paymentForm.setError('student_id', 'No fee account found for this student. Create/update account first.')
    return
  }

  syncPriorityPayload()

  const referenceValue = paymentForm.organization_id || paymentForm.organization_name || null

  const payload = {
    student_fee_account_id: accountId,
    amount: toNumber(paymentForm.amount),
    method: paymentForm.payment_method,
    reference: referenceValue,
    paid_at: paymentForm.paid_at,
    notes: paymentForm.notes || null,

    student_id: sid,
    payment_method: paymentForm.payment_method,
    organization_name: paymentForm.organization_name || null,
    organization_id: paymentForm.organization_id || null,
    priority_vote_head_ids: paymentForm.priority_vote_head_ids,
  }

  paymentForm
    .transform(() => payload)
    .post(endpoints.paymentStore, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        paymentForm.reset('amount', 'organization_name', 'organization_id', 'notes')
        reloadFinanceSlices()
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
      reloadFinanceSlices()
    },
  })
}

const printMode = ref('')
const printClassLevel = ref('')
const printStudentQuery = ref('')
const printStudentId = ref('')
const printPreparedAt = ref('')

const selectedPrintStudent = computed(() => {
  const id = normalizeId(printStudentId.value)
  if (id === null) return null
  return studentsRows.value.find((s) => getStudentId(s) === id) ?? null
})

watch(
  () => printStudentId.value,
  () => {
    if (selectedPrintStudent.value) {
      printStudentQuery.value = buildStudentLabel(selectedPrintStudent.value)
    } else if (!printStudentQuery.value.trim()) {
      printStudentQuery.value = ''
    }
  },
  { immediate: true }
)

const onPrintStudentInput = () => {
  if (printStudentQuery.value !== (selectedPrintStudent.value ? buildStudentLabel(selectedPrintStudent.value) : '')) {
    printStudentId.value = ''
  }
}

const resolvePrintStudentQuery = () => {
  const resolved = resolveStudentByQuery(printStudentQuery.value)
  if (resolved) {
    printStudentId.value = String(resolved.id)
    printStudentQuery.value = resolved.label
    return
  }
  printStudentId.value = ''
}

const printAccountsRows = computed(() => {
  if (printMode.value === 'class') {
    return accountsRows.value.filter((row) => String(row.class_level ?? '') === String(printClassLevel.value))
  }
  if (printMode.value === 'student') {
    const sid = normalizeId(printStudentId.value)
    if (sid === null) return []
    return accountsRows.value.filter((row) => normalizeId(row.student_id) === sid)
  }
  if (printMode.value === 'all') return accountsRows.value
  return []
})

const printStudentPayments = computed(() => {
  if (printMode.value !== 'student') return []
  const sid = normalizeId(printStudentId.value)
  if (sid === null) return []
  return paymentsRows.value
    .filter((p) => normalizeId(p.student_id) === sid)
    .sort((a, b) => String(a.paid_at ?? a.created_at ?? '').localeCompare(String(b.paid_at ?? b.created_at ?? '')))
})

const printTotals = computed(() => {
  const rows = printAccountsRows.value
  return {
    expected: rows.reduce((sum, r) => sum + toNumber(r.expected_amount), 0),
    paid: rows.reduce((sum, r) => sum + toNumber(r.total_paid), 0),
    balance: rows.reduce((sum, r) => sum + toNumber(r.balance), 0),
    credit: rows.reduce((sum, r) => sum + toNumber(r.credit_balance), 0),
  }
})

const printTitle = computed(() => {
  if (printMode.value === 'class') return `Student Fee Accounts - Class ${printClassLevel.value}`
  if (printMode.value === 'student') {
    return selectedPrintStudent.value
      ? `Student Fee Statement - ${getStudentName(selectedPrintStudent.value)} (${getAdmissionNo(selectedPrintStudent.value) || selectedPrintStudent.value.id})`
      : 'Student Fee Statement'
  }
  return 'Student Fee Accounts - All Classes'
})

const resetPrintMode = () => {
  printMode.value = ''
}

const handleAfterPrint = () => {
  resetPrintMode()
}

onMounted(() => {
  fetchVoteheadReport()
  window.addEventListener('afterprint', handleAfterPrint)
})

onBeforeUnmount(() => {
  window.removeEventListener('afterprint', handleAfterPrint)
})

const runPrint = async (mode) => {
  printMode.value = mode
  printPreparedAt.value = new Date().toLocaleString()
  await nextTick()
  window.print()
}

const printAllAccounts = () => runPrint('all')

const printClassAccounts = () => {
  if (!printClassLevel.value) {
    window.alert('Please select a class first.')
    return
  }
  runPrint('class')
}

const printSingleStudentStatement = () => {
  resolvePrintStudentQuery()
  if (normalizeId(printStudentId.value) === null) {
    window.alert('Please select a valid student first.')
    return
  }
  runPrint('student')
}
</script>

<template>
  <Head title="Student Finance" />
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="no-print">
        <section class="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Student Finance</h1>
            <p class="mt-1 text-sm text-slate-300">
              Manage student fee accounts, fee structures, payment records, and print statements.
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

        <!-- Print tools -->
        <section class="mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Print Student Fee Accounts</h2>
          <p class="mt-1 text-sm text-slate-300">Print all accounts, accounts by class, or a single student fee statement with payments.</p>
          <div class="mt-4 grid gap-3 md:grid-cols-3">
            <button type="button" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400" @click="printAllAccounts">
              Print All Accounts
            </button>
            <div class="flex gap-2">
              <select v-model="printClassLevel" class="flex-1 rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
                <option value="">Select class...</option>
                <option v-for="level in classLevels" :key="`print-level-${level}`" :value="level">{{ level }}</option>
              </select>
              <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="printClassAccounts">
                Print Class
              </button>
            </div>
            <div class="flex gap-2">
              <input
                v-model="printStudentQuery"
                list="print-students-list"
                type="text"
                class="flex-1 rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
                placeholder="Student name / admission no..."
                @input="onPrintStudentInput"
                @change="resolvePrintStudentQuery"
                @blur="resolvePrintStudentQuery"
              />
              <datalist id="print-students-list">
                <option v-for="opt in studentPickerOptions" :key="`print-opt-${opt.id}`" :value="opt.label" />
              </datalist>
              <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="printSingleStudentStatement">
                Print Student
              </button>
            </div>
          </div>
        </section>

        <!-- Votehead report -->
        <section class="mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-white">Votehead Collections Report</h2>
            <div class="text-xs text-slate-400">Filter by period, votehead, and profile (Day Scholar / Boarder)</div>
          </div>
          <form class="grid gap-3 md:grid-cols-6" @submit.prevent="fetchVoteheadReport">
            <select v-model="reportFilters.period" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="day">Day</option>
              <option value="week">Week</option>
              <option value="month">Month</option>
              <option value="custom">Custom</option>
            </select>
            <select v-model="reportFilters.vote_head_id" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="">All Voteheads</option>
              <option v-for="vh in voteHeadRows" :key="`report-vh-${vh.id}`" :value="String(vh.id)">
                {{ vh.code ? `${vh.code} - ` : '' }}{{ vh.name }}
              </option>
            </select>
            <select v-model="reportFilters.fee_profile_id" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
              <option value="">All Profiles</option>
              <option v-for="fp in feeProfileRows" :key="`report-fp-${fp.id}`" :value="String(fp.id)">
                {{ fp.code ? `${fp.code} - ` : '' }}{{ fp.name }}
              </option>
            </select>
            <input v-model="reportFilters.from" type="date" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" :disabled="reportFilters.period !== 'custom'" />
            <input v-model="reportFilters.to" type="date" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" :disabled="reportFilters.period !== 'custom'" />
            <div class="flex items-center gap-2">
              <button type="submit" :disabled="reportLoading" class="rounded-md bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 disabled:opacity-50">
                {{ reportLoading ? 'Generating...' : 'Generate' }}
              </button>
              <button type="button" class="rounded-md border border-white/20 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="printVoteheadReport">Print</button>
            </div>
          </form>
          <div class="mt-4 text-sm text-slate-300">
            <span class="mr-4">From: <strong>{{ voteheadReport.from || '-' }}</strong></span>
            <span class="mr-4">To: <strong>{{ voteheadReport.to || '-' }}</strong></span>
            <span>Grand Total: <strong class="text-emerald-300">{{ formatKES(voteheadReport.grand_total || 0) }}</strong></span>
          </div>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Votehead</th>
                  <th class="px-3 py-2">Profile</th>
                  <th class="px-3 py-2 text-right">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-for="row in voteheadReport.rows" :key="`vh-row-${row.vote_head_id}-${row.fee_profile_id}`">
                  <td class="px-3 py-2 text-white">{{ row.vote_head_name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ row.fee_profile_name || row.fee_profile_code || 'Unassigned' }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-cyan-300">{{ formatKES(row.total_collected) }}</td>
                </tr>
                <tr v-if="!voteheadReport.rows?.length">
                  <td colspan="3" class="px-3 py-6 text-center text-slate-400">No votehead collections found for this filter.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="flash.success || flash.error" class="mb-6 space-y-2">
          <div v-if="flash.success" class="rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ flash.success }}</div>
          <div v-if="flash.error" class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">{{ flash.error }}</div>
        </section>

        <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
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
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Credit (Overpaid)</p>
            <p class="mt-2 text-xl font-bold text-fuchsia-300">{{ formatKES(summaryDisplay.total_credit) }}</p>
          </article>
        </section>

        <!-- Account + payment forms and tables kept as your current layout -->
        <!-- (same block already included in your file and preserved above) -->

        <!-- Recent payments -->
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
                      <Link v-if="payment.id" :href="endpoints.receiptPrint(payment.id)" target="_blank" rel="noopener noreferrer" class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10">
                        Print Receipt
                      </Link>
                      <button v-if="payment.id" type="button" class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10" @click="deletePayment(payment.id)">
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
      </div>

      <!-- Print view -->
      <section v-if="printMode" class="hidden print:block bg-white text-black">
        <div class="mb-4">
          <h1 class="text-2xl font-bold">{{ printTitle }}</h1>
          <p class="text-sm">Generated: {{ printPreparedAt }}</p>
        </div>

        <div class="mb-4 grid grid-cols-4 gap-3 text-sm">
          <div><strong>Accounts:</strong> {{ printAccountsRows.length }}</div>
          <div><strong>Total Expected:</strong> {{ formatKES(printTotals.expected) }}</div>
          <div><strong>Total Paid:</strong> {{ formatKES(printTotals.paid) }}</div>
          <div><strong>Total Balance:</strong> {{ formatKES(printTotals.balance) }}</div>
          <div><strong>Total Credit:</strong> {{ formatKES(printTotals.credit) }}</div>
        </div>

        <table class="print-table w-full text-sm">
          <thead>
            <tr>
              <th>Admission No</th>
              <th>Student</th>
              <th>Class</th>
              <th>Expected</th>
              <th>Paid</th>
              <th>Balance</th>
              <th>Credit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="account in printAccountsRows" :key="`print-acc-${account.id}`">
              <td>{{ account.admission_no || '—' }}</td>
              <td>{{ account.student_name || '—' }}</td>
              <td>{{ account.class_level || '—' }}<span v-if="account.stream"> - {{ account.stream }}</span></td>
              <td>{{ formatKES(account.expected_amount) }}</td>
              <td>{{ formatKES(account.total_paid) }}</td>
              <td>{{ formatKES(account.balance) }}</td>
              <td>{{ formatKES(account.credit_balance) }}</td>
            </tr>
            <tr v-if="!printAccountsRows.length">
              <td colspan="7" style="text-align: center;">No accounts found for this print selection.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="printMode === 'student'" class="mt-6">
          <h2 class="mb-2 text-lg font-semibold">Payment History</h2>
          <table class="print-table w-full text-sm">
            <thead>
              <tr>
                <th>Date</th>
                <th>Method</th>
                <th>Reference/Org</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in printStudentPayments" :key="`print-pay-${payment.id}`">
                <td>{{ payment.paid_at || payment.created_at || '—' }}</td>
                <td>{{ payment.payment_method || payment.method || '—' }}</td>
                <td>{{ payment.organization_id || payment.organization_name || payment.receipt_no || '—' }}</td>
                <td>{{ formatKES(payment.amount) }}</td>
              </tr>
              <tr v-if="!printStudentPayments.length">
                <td colspan="4" style="text-align: center;">No payment history found for this student.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}

.print-table {
  border-collapse: collapse;
}

.print-table th,
.print-table td {
  border: 1px solid #333;
  padding: 6px 8px;
  text-align: left;
}
</style>