<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
  exams: { type: Object, default: () => ({ data: [], links: [] }) },
  students: { type: Array, default: () => [] },
  filters: {
    type: Object,
    default: () => ({
      year: null,
      term: null,
      status: null,
      assessment_system: null,
      class_level: null,
    }),
  },
  role: { type: String, default: 'class_teacher' },
  permissions: {
    type: Object,
    default: () => ({
      canCreateExams: false,
      canDeleteExams: false,
      canUpdateAnyResult: false,
      canUpdateClassResults: false,
      canUpdateSubjectResults: false,
    }),
  },
  classAssignments: { type: Array, default: () => [] },
  subjectAssignments: { type: Array, default: () => [] },
})

const roleLabels = {
  principal: 'Principal',
  deputy_principal: 'Deputy Principal',
  dean: 'Dean',
  class_teacher: 'Class Teacher',
  subject_teacher: 'Subject Teacher',
}

const roleKey = computed(() => String(props.role || '').trim().toLowerCase().replace(/[\s-]+/g, '_'))
const roleLabel = computed(() => roleLabels[roleKey.value] || props.role || 'User')

const lifecycleConfig = {
  draft: { label: 'Draft', cls: 'bg-amber-100 text-amber-700 border-amber-300' },
  published: { label: 'Published', cls: 'bg-emerald-100 text-emerald-700 border-emerald-300' },
  closed: { label: 'Closed', cls: 'bg-slate-200 text-slate-700 border-slate-300' },
}

const canCreateExams = computed(() => !!props.permissions?.canCreateExams)
const canDeleteExams = computed(() => !!props.permissions?.canDeleteExams)
const canUpdateAny = computed(() => !!props.permissions?.canUpdateAnyResult)
const canUpdateClass = computed(() => !!props.permissions?.canUpdateClassResults)
const canUpdateSubject = computed(() => !!props.permissions?.canUpdateSubjectResults)

const flashSuccess = computed(() => page.props?.flash?.success || '')
const flashError = computed(() => page.props?.flash?.error || '')

const state = reactive({
  search: '',
  year: props.filters?.year || '',
  term: props.filters?.term || '',
  status: props.filters?.status || '',
  assessment_system: props.filters?.assessment_system || '',
  class_level: props.filters?.class_level || '',
  stream: '',
  subject: '',
})

const selectedExamId = ref(null)
const showCreateModal = ref(false)
const showMissingOnly = ref(false)
const isSavingAll = ref(false)
const localSuccess = ref('')
const localError = ref('')
const hasUnsavedChanges = ref(false)

const routeNames = {
  examsStore: ['admin.exams.store', 'exams.store'],
  examsUpdate: ['admin.exams.update', 'exams.update'],
  examsDestroy: ['admin.exams.destroy', 'exams.destroy'],
  resultsStore: ['admin.exams.results.store', 'exams.results.store'],
}

function safeRoute(name, params = undefined) {
  try {
    if (typeof route !== 'function') return null
    return params === undefined ? route(name) : route(name, params)
  } catch {
    return null
  }
}

function safeRouteAny(names, params = undefined) {
  for (const n of names) {
    const r = safeRoute(n, params)
    if (r) return r
  }
  return null
}

const createExam = useForm({
  title: '',
  term: props.filters?.term || 'Term 1',
  year: props.filters?.year || new Date().getFullYear(),
  exam_date: '',
  max_score: 100,
  status: 'draft',
  assessment_system: props.filters?.assessment_system || '844',
  class_level: props.filters?.class_level || '',
  stream: '',
  subject: 'General', // subject included
  pathway: '',
})

const allExams = computed(() => (Array.isArray(props.exams?.data) ? props.exams.data : []))

const filteredExams = computed(() =>
  allExams.value.filter((e) => {
    const s = `${e.title || ''} ${e.subject || ''} ${e.class_level || ''} ${e.stream || ''}`.toLowerCase()
    if (state.search && !s.includes(state.search.toLowerCase())) return false
    if (state.year && String(e.year || '') !== String(state.year)) return false
    if (state.term && String(e.term || '') !== String(state.term)) return false
    if (state.status && String(e.status || '').toLowerCase() !== String(state.status).toLowerCase()) return false
    if (state.assessment_system && String(e.assessment_system || '') !== String(state.assessment_system)) return false
    if (state.class_level && String(e.class_level || '') !== String(state.class_level)) return false
    if (state.stream && String(e.stream || '') !== String(state.stream)) return false
    if (state.subject && String(e.subject || '') !== String(state.subject)) return false
    return true
  }),
)

watch(
  filteredExams,
  (rows) => {
    if (!rows.length) {
      selectedExamId.value = null
      return
    }
    if (!selectedExamId.value || !rows.some((x) => String(x.id) === String(selectedExamId.value))) {
      selectedExamId.value = rows[0].id
    }
  },
  { immediate: true },
)

const selectedExam = computed(
  () => filteredExams.value.find((x) => String(x.id) === String(selectedExamId.value)) || filteredExams.value[0] || null,
)

function statusLabel(status) {
  const key = String(status || '').toLowerCase()
  return lifecycleConfig[key]?.label || (status || 'Unknown')
}
function statusClass(status) {
  const key = String(status || '').toLowerCase()
  return lifecycleConfig[key]?.cls || 'bg-slate-100 text-slate-700 border-slate-300'
}

function canEditResults(exam) {
  if (!exam) return false
  if (String(exam.status || '').toLowerCase() === 'closed') return false
  return canUpdateAny.value || canUpdateClass.value || canUpdateSubject.value
}

function canManageLifecycle() {
  return ['principal', 'deputy_principal', 'dean'].includes(roleKey.value)
}

const scopeBadges = computed(() => {
  const classChips = (props.classAssignments || []).map((x) => ({
    t: 'Class',
    v: `${x.class_level || '-'}${x.stream ? ` - ${x.stream}` : ''}`,
  }))
  const subjChips = (props.subjectAssignments || []).map((x) => ({
    t: 'Subject',
    v: `${x.subject || '-'} • ${x.class_level || '-'}${x.stream ? ` - ${x.stream}` : ''}`,
  }))
  return [...classChips, ...subjChips]
})

const resultMap = computed(() => {
  const m = new Map()
  ;(selectedExam.value?.results || []).forEach((r) => m.set(String(r.student_id), r))
  return m
})

const scopeStudents = computed(() => {
  if (!selectedExam.value) return []
  let rows = [...props.students]
  if (selectedExam.value.class_level) rows = rows.filter((s) => String(s.class_level || '') === String(selectedExam.value.class_level))
  if (selectedExam.value.stream) rows = rows.filter((s) => String(s.stream || '') === String(selectedExam.value.stream))
  return rows
})

const marksheetRows = ref([])
let snapshot = '[]'

function toRow(student) {
  const r = resultMap.value.get(String(student.id))
  return {
    id: r?.id || null,
    student_id: student.id,
    student_name: `${student.first_name || ''} ${student.last_name || ''}`.trim(),
    admission_no: student.admission_no || '',
    grading_system: r?.grading_system || selectedExam.value?.assessment_system || '844',
    score: r?.score ?? '',
    grade: r?.grade || '',
    points: r?.points ?? '',
    cbc_level: r?.cbc_level || '',
    cbc_comment: r?.cbc_comment || '',
    remarks: r?.remarks || '',
    updated_at: r?.updated_at || null,
    edited_by: r?.edited_by || null,
    dirty: false,
  }
}

watch(
  [selectedExam, scopeStudents],
  () => {
    marksheetRows.value = scopeStudents.value.map(toRow)
    snapshot = JSON.stringify(marksheetRows.value.map((x) => ({ ...x, dirty: false })))
    hasUnsavedChanges.value = false
    showMissingOnly.value = false
  },
  { immediate: true },
)

watch(
  marksheetRows,
  (rows) => {
    hasUnsavedChanges.value = JSON.stringify(rows.map((x) => ({ ...x, dirty: false }))) !== snapshot
  },
  { deep: true },
)

const missingRows = computed(() =>
  marksheetRows.value.filter((r) => !(r.score !== '' || r.grade || r.points !== '' || r.cbc_level || r.cbc_comment)),
)
const visibleRows = computed(() => (showMissingOnly.value ? missingRows.value : marksheetRows.value))

const kpis = computed(() => {
  const exams = filteredExams.value
  return {
    total: exams.length,
    draft: exams.filter((x) => String(x.status || '').toLowerCase() === 'draft').length,
    published: exams.filter((x) => String(x.status || '').toLowerCase() === 'published').length,
    closed: exams.filter((x) => String(x.status || '').toLowerCase() === 'closed').length,
    students: scopeStudents.value.length,
    withMarks: marksheetRows.value.length - missingRows.value.length,
    missing: missingRows.value.length,
  }
})

function clearFilter(k) {
  state[k] = ''
}
function clearAllFilters() {
  Object.keys(state).forEach((k) => {
    state[k] = ''
  })
}

function setLifecycle(exam, nextStatus) {
  if (!canManageLifecycle()) return
  const href = safeRouteAny(routeNames.examsUpdate, exam.id)
  if (!href) {
    localError.value = 'Lifecycle route not available.'
    return
  }
  router.put(
    href,
    { ...exam, status: nextStatus },
    {
      preserveScroll: true,
      onSuccess: () => {
        localSuccess.value = `Exam moved to ${statusLabel(nextStatus)}.`
        localError.value = ''
      },
      onError: () => (localError.value = 'Could not update exam lifecycle.'),
    },
  )
}

function deleteExam(examId) {
  const href = safeRouteAny(routeNames.examsDestroy, examId)
  if (!href) {
    localError.value = 'Delete route not available.'
    return
  }
  router.delete(href, {
    preserveScroll: true,
    onSuccess: () => {
      localSuccess.value = 'Exam deleted.'
      localError.value = ''
    },
    onError: () => (localError.value = 'Could not delete exam.'),
  })
}

function submitCreateExam() {
  const href = safeRouteAny(routeNames.examsStore)
  if (!href) {
    localError.value = 'Create exam route not available.'
    return
  }
  createExam.post(href, {
    preserveScroll: true,
    onSuccess: () => {
      localSuccess.value = 'Exam created.'
      localError.value = ''
      showCreateModal.value = false
      createExam.reset('title', 'exam_date', 'stream', 'subject', 'pathway')
      createExam.status = 'draft'
      createExam.max_score = 100
    },
    onError: () => (localError.value = 'Please fix form errors.'),
  })
}

function markDirty(row) {
  row.dirty = true
}

const csrfToken = computed(() => {
  const el = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null
  return el?.getAttribute('content') || ''
})

async function saveAllRows() {
  localSuccess.value = ''
  localError.value = ''
  if (!selectedExam.value) return (localError.value = 'Select an exam first.')
  if (!canEditResults(selectedExam.value)) return (localError.value = 'You cannot edit this exam results.')

  const endpoint = safeRouteAny(routeNames.resultsStore, selectedExam.value.id)
  if (!endpoint) return (localError.value = 'Result route not available.')

  const changed = marksheetRows.value.filter((r) => r.dirty || r.id === null)
  if (!changed.length) return (localSuccess.value = 'No changes to save.')

  isSavingAll.value = true
  let ok = 0
  let fail = 0

  for (const row of changed) {
    const body = new URLSearchParams({
      student_id: String(row.student_id),
      grading_system: String(row.grading_system || selectedExam.value.assessment_system || '844'),
      score: row.score === null || row.score === undefined ? '' : String(row.score),
      grade: row.grade || '',
      points: row.points === null || row.points === undefined ? '' : String(row.points),
      cbc_level: row.cbc_level || '',
      cbc_comment: row.cbc_comment || '',
      remarks: row.remarks || '',
    })

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          ...(csrfToken.value ? { 'X-CSRF-TOKEN': csrfToken.value } : {}),
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json, text/plain, */*',
        },
        credentials: 'same-origin',
        body: body.toString(),
      })

      if (res.ok) {
        ok++
        row.dirty = false
      } else {
        fail++
      }
    } catch {
      fail++
    }
  }

  isSavingAll.value = false
  if (ok > 0) localSuccess.value = `Saved ${ok} row(s).`
  if (fail > 0) localError.value = `${fail} row(s) failed.`
  if (ok > 0) router.reload({ only: ['exams'], preserveScroll: true })
}

if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', (e) => {
    if (!hasUnsavedChanges.value) return
    e.preventDefault()
    e.returnValue = ''
  })
}
</script>

<template>
  <Head title="Exams • Workflow v2" />

  <div class="space-y-6">
    <!-- Header -->
    <section class="rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Exam Management v2</h1>
          <p class="text-sm text-slate-600">Draft → Published → Closed, scoped by role rules.</p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="canCreateExams"
            type="button"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white"
            @click="showCreateModal = true"
          >
            Create Exam
          </button>
          <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="clearAllFilters">
            Clear Filters
          </button>
        </div>
      </div>

      <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap gap-2">
          <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs text-indigo-700">Role: {{ roleLabel }}</span>
          <span
            v-for="(b, i) in scopeBadges"
            :key="`scope-${i}`"
            class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs text-slate-700"
          >
            {{ b.t }}: {{ b.v }}
          </span>
        </div>
      </div>
    </section>

    <!-- KPI -->
    <section class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
      <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total</p><p class="text-xl font-semibold">{{ kpis.total }}</p></article>
      <article class="rounded-lg border border-amber-200 bg-amber-50 p-4"><p class="text-xs text-amber-700">Draft</p><p class="text-xl font-semibold">{{ kpis.draft }}</p></article>
      <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Published</p><p class="text-xl font-semibold">{{ kpis.published }}</p></article>
      <article class="rounded-lg border border-slate-300 bg-slate-100 p-4"><p class="text-xs text-slate-700">Closed</p><p class="text-xl font-semibold">{{ kpis.closed }}</p></article>
      <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Students</p><p class="text-xl font-semibold">{{ kpis.students }}</p></article>
      <article class="rounded-lg border border-sky-200 bg-sky-50 p-4"><p class="text-xs text-sky-700">With Marks</p><p class="text-xl font-semibold">{{ kpis.withMarks }}</p></article>
      <article class="rounded-lg border border-rose-200 bg-rose-50 p-4"><p class="text-xs text-rose-700">Missing</p><p class="text-xl font-semibold">{{ kpis.missing }}</p></article>
    </section>

    <!-- Filters -->
    <section class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="grid gap-3 md:grid-cols-4 xl:grid-cols-8">
        <input v-model="state.search" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Search title/subject/class" />
        <input v-model="state.year" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Year" />
        <input v-model="state.term" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Term" />
        <select v-model="state.status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">Status</option><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option>
        </select>
        <select v-model="state.assessment_system" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">Assessment</option><option value="844">844</option><option value="cbc">CBC</option>
        </select>
        <input v-model="state.class_level" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Class" />
        <input v-model="state.stream" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Stream" />
        <input v-model="state.subject" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Subject" />
      </div>
    </section>

    <!-- Exams + Grid -->
    <section class="grid gap-6 xl:grid-cols-[1.1fr_1fr]">
      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3">
          <h2 class="font-semibold">Exams in your scope</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
              <tr><th class="px-3 py-2">Title</th><th class="px-3 py-2">Scope</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Actions</th></tr>
            </thead>
            <tbody>
              <tr
                v-for="exam in filteredExams"
                :key="exam.id"
                class="border-t border-slate-100"
                :class="String(selectedExamId) === String(exam.id) ? 'bg-indigo-50' : ''"
              >
                <td class="px-3 py-2">
                  <button class="text-left font-medium" @click="selectedExamId = exam.id">{{ exam.title || 'Untitled' }}</button>
                  <p class="text-xs text-slate-500">{{ exam.term }} • {{ exam.year }}</p>
                </td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-1 text-xs">
                    <span class="rounded-full border px-2 py-0.5">{{ exam.class_level || '-' }}</span>
                    <span v-if="exam.stream" class="rounded-full border px-2 py-0.5">{{ exam.stream }}</span>
                    <span v-if="exam.subject" class="rounded-full border px-2 py-0.5">{{ exam.subject }}</span>
                  </div>
                </td>
                <td class="px-3 py-2">
                  <span class="rounded-full border px-2 py-0.5 text-xs" :class="statusClass(exam.status)">{{ statusLabel(exam.status) }}</span>
                </td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-1">
                    <button v-if="canManageLifecycle() && String(exam.status).toLowerCase() === 'draft'" class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs" @click="setLifecycle(exam, 'published')">Publish</button>
                    <button v-if="canManageLifecycle() && String(exam.status).toLowerCase() === 'published'" class="rounded border border-slate-300 bg-slate-50 px-2 py-1 text-xs" @click="setLifecycle(exam, 'closed')">Close</button>
                    <button v-if="canManageLifecycle() && String(exam.status).toLowerCase() === 'closed'" class="rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs" @click="setLifecycle(exam, 'draft')">Re-open</button>
                    <button v-if="canDeleteExams" class="rounded border border-rose-300 bg-rose-50 px-2 py-1 text-xs" @click="deleteExam(exam.id)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filteredExams.length"><td colspan="4" class="px-3 py-8 text-center text-slate-500">No exams match your scope/filters.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <button class="rounded border border-slate-300 px-3 py-1 text-xs" :class="showMissingOnly ? 'bg-rose-50 border-rose-300 text-rose-700' : ''" @click="showMissingOnly = !showMissingOnly">Fill Missing Only ({{ missingRows.length }})</button>
            <button class="rounded bg-slate-900 px-3 py-1 text-xs text-white disabled:opacity-50" :disabled="!selectedExam || isSavingAll || !canEditResults(selectedExam)" @click="saveAllRows">
              {{ isSavingAll ? 'Saving...' : 'Save All' }}
            </button>
            <span v-if="hasUnsavedChanges" class="rounded-full border border-amber-300 bg-amber-50 px-2 py-1 text-xs text-amber-700">Unsaved changes</span>
            <span v-if="selectedExam && String(selectedExam.status).toLowerCase()==='closed'" class="rounded-full border border-slate-400 bg-slate-200 px-2 py-1 text-xs">Closed exam: edits disabled</span>
          </div>

          <div v-if="!selectedExam" class="rounded border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Select an exam to open marksheet.</div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th class="px-3 py-2">Student</th><th class="px-3 py-2">Adm No</th><th class="px-3 py-2">Score</th>
                  <th class="px-3 py-2">Grade</th><th class="px-3 py-2">Points</th><th class="px-3 py-2">CBC Level</th>
                  <th class="px-3 py-2">CBC Comment</th><th class="px-3 py-2">Remarks</th><th class="px-3 py-2">Audit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in visibleRows" :key="`row-${row.student_id}`" class="border-t">
                  <td class="px-3 py-2 font-medium">{{ row.student_name }}</td>
                  <td class="px-3 py-2">{{ row.admission_no || '-' }}</td>
                  <td class="px-3 py-2"><input v-model="row.score" type="number" min="0" class="w-24 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam)" @input="markDirty(row)" /></td>
                  <td class="px-3 py-2"><input v-model="row.grade" class="w-20 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam)" @input="markDirty(row)" /></td>
                  <td class="px-3 py-2"><input v-model="row.points" type="number" step="0.01" class="w-24 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam)" @input="markDirty(row)" /></td>
                  <td class="px-3 py-2">
                    <select v-model="row.cbc_level" class="w-32 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam) || String(selectedExam.assessment_system).toLowerCase()==='844'" @change="markDirty(row)">
                      <option value="">-</option><option value="EE">EE</option><option value="ME">ME</option><option value="AE">AE</option><option value="BE">BE</option>
                    </select>
                  </td>
                  <td class="px-3 py-2"><input v-model="row.cbc_comment" class="w-44 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam) || String(selectedExam.assessment_system).toLowerCase()==='844'" @input="markDirty(row)" /></td>
                  <td class="px-3 py-2"><input v-model="row.remarks" class="w-44 rounded border px-2 py-1" :disabled="!canEditResults(selectedExam)" @input="markDirty(row)" /></td>
                  <td class="px-3 py-2 text-xs text-slate-500">
                    <div v-if="row.updated_at">{{ row.updated_at }}</div>
                    <div v-if="row.edited_by">by {{ row.edited_by }}</div>
                  </td>
                </tr>
                <tr v-if="!visibleRows.length"><td colspan="9" class="px-3 py-8 text-center text-slate-500">No students in this scope.</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </section>

    <section v-if="flashSuccess || localSuccess || flashError || localError" class="space-y-2">
      <p v-if="flashSuccess" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ flashSuccess }}</p>
      <p v-if="localSuccess" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ localSuccess }}</p>
      <p v-if="flashError" class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ flashError }}</p>
      <p v-if="localError" class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ localError }}</p>
    </section>

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
      <div class="w-full max-w-3xl rounded-xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b px-5 py-3">
          <h3 class="font-semibold">Create Exam</h3>
          <button class="text-sm text-slate-500" @click="showCreateModal=false">Close</button>
        </div>
        <form class="grid gap-3 p-5 md:grid-cols-2" @submit.prevent="submitCreateExam">
          <div><label class="mb-1 block text-xs">Title</label><input v-model="createExam.title" class="w-full rounded border px-3 py-2 text-sm" /><p v-if="createExam.errors.title" class="text-xs text-rose-600">{{ createExam.errors.title }}</p></div>
          <div><label class="mb-1 block text-xs">Date</label><input v-model="createExam.exam_date" type="date" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Year</label><input v-model="createExam.year" type="number" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Term</label><input v-model="createExam.term" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Assessment</label><select v-model="createExam.assessment_system" class="w-full rounded border px-3 py-2 text-sm"><option value="844">844</option><option value="cbc">CBC</option></select></div>
          <div><label class="mb-1 block text-xs">Status</label><select v-model="createExam.status" class="w-full rounded border px-3 py-2 text-sm"><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option></select></div>
          <div><label class="mb-1 block text-xs">Class</label><input v-model="createExam.class_level" class="w-full rounded border px-3 py-2 text-sm" /><p v-if="createExam.errors.class_level" class="text-xs text-rose-600">{{ createExam.errors.class_level }}</p></div>
          <div><label class="mb-1 block text-xs">Stream</label><input v-model="createExam.stream" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Subject</label><input v-model="createExam.subject" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Pathway</label><input v-model="createExam.pathway" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div><label class="mb-1 block text-xs">Max Score</label><input v-model="createExam.max_score" type="number" min="1" class="w-full rounded border px-3 py-2 text-sm" /></div>
          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <button type="button" class="rounded border px-4 py-2 text-sm" @click="showCreateModal=false">Cancel</button>
            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm text-white" :disabled="createExam.processing">{{ createExam.processing ? 'Saving...' : 'Create Exam' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>