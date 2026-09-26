<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  exams: {
    type: Object,
    default: () => ({ data: [], links: [] }),
  },
  students: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  role: {
    type: String,
    default: 'subject_teacher',
  },
  permissions: {
    type: Object,
    default: () => ({
      canCreateExams: false,
      canDeleteExams: false,
      canUpdateAnyResult: false,
      canUpdateClassResults: false,
      canUpdateSubjectResults: true,
    }),
  },
  subjectAssignments: {
    type: Array,
    default: () => [],
  },
})

const examRows = computed(() => props.exams?.data ?? [])
const paginationLinks = computed(() => props.exams?.links ?? [])
const roleLabel = computed(() => String(props.role || '').replaceAll('_', ' '))

const canUpdateResults = computed(
  () =>
    !!props.permissions?.canUpdateAnyResult ||
    !!props.permissions?.canUpdateClassResults ||
    !!props.permissions?.canUpdateSubjectResults,
)

const filterForm = useForm({
  year: props.filters?.year ?? '',
  term: props.filters?.term ?? '',
  status: props.filters?.status ?? '',
  assessment_system: props.filters?.assessment_system ?? '',
  class_level: props.filters?.class_level ?? '',
})

const resultModalOpen = ref(false)
const selectedExam = ref(null)

const resultForm = useForm({
  student_id: '',
  grading_system: '844',
  score: '',
  grade: '',
  points: '',
  cbc_level: '',
  cbc_comment: '',
  remarks: '',
})

const isCBC = computed(() => resultForm.grading_system === 'CBC')
const is844 = computed(() => resultForm.grading_system === '844')

watch(
  () => resultForm.grading_system,
  (value) => {
    if (value === 'CBC') {
      resultForm.grade = ''
      resultForm.points = ''
    } else {
      resultForm.cbc_level = ''
      resultForm.cbc_comment = ''
    }
  },
)

const applyFilters = () => {
  router.get(window.location.pathname, filterForm.data(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
}

const openResultModal = (exam) => {
  selectedExam.value = exam
  resultModalOpen.value = true
  resultForm.reset()
  resultForm.grading_system = exam?.assessment_system === 'CBC' ? 'CBC' : '844'
}

const closeResultModal = () => {
  resultModalOpen.value = false
  selectedExam.value = null
  resultForm.reset()
}

const submitResult = () => {
  if (!selectedExam.value || !canUpdateResults.value) return

  resultForm.post(route('admin.subject-room.exams.marks.store', selectedExam.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      resultForm.reset('student_id', 'score', 'grade', 'points', 'cbc_level', 'cbc_comment', 'remarks')
    },
  })
}

const classOptionList = computed(() => {
  const fromAssignments = props.subjectAssignments.map((item) => item.class_level)
  const fromExams = examRows.value.map((item) => item.class_level)
  return [...new Set([...fromAssignments, ...fromExams].filter(Boolean))]
})

const printPage = () => window.print()
</script>

<template>
  <Head title="Subject Room • Exams" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between gap-4 print:block">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Subject Teacher Examination Desk</h1>
          <p class="text-sm capitalize text-slate-600">Role: {{ roleLabel }}</p>
        </div>

        <div class="no-print flex gap-2">
          <Link
            :href="route('admin.subject-room.discipline')"
            class="border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
          >
            Discipline
          </Link>
          <button
            type="button"
            class="border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
            @click="printPage"
          >
            Print Page
          </button>
        </div>
      </div>

      <div class="border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Assignment Scope</h2>

        <div class="mt-3 flex flex-wrap gap-2" v-if="subjectAssignments.length">
          <span
            v-for="(item, idx) in subjectAssignments"
            :key="`scope-${idx}`"
            class="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700"
          >
            {{ item.class_level }}<span v-if="item.stream"> - {{ item.stream }}</span>
            <span v-if="item.subject_token"> ({{ item.subject_token }})</span>
          </span>
        </div>

        <p v-else class="mt-3 text-sm text-amber-700">
          No subject assignment scope found.
        </p>
      </div>

      <form class="no-print grid gap-3 border border-slate-200 bg-white p-4 md:grid-cols-6" @submit.prevent="applyFilters">
        <input v-model="filterForm.year" class="border border-slate-300 px-3 py-2 text-sm" placeholder="Year" type="number" />

        <select v-model="filterForm.term" class="border border-slate-300 px-3 py-2 text-sm">
          <option value="">All Terms</option>
          <option value="Term 1">Term 1</option>
          <option value="Term 2">Term 2</option>
          <option value="Term 3">Term 3</option>
        </select>

        <select v-model="filterForm.status" class="border border-slate-300 px-3 py-2 text-sm">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="closed">Closed</option>
        </select>

        <select v-model="filterForm.assessment_system" class="border border-slate-300 px-3 py-2 text-sm">
          <option value="">All Systems</option>
          <option value="844">844</option>
          <option value="CBC">CBC</option>
          <option value="HYBRID">HYBRID</option>
        </select>

        <select v-model="filterForm.class_level" class="border border-slate-300 px-3 py-2 text-sm">
          <option value="">All Classes</option>
          <option v-for="level in classOptionList" :key="level" :value="level">{{ level }}</option>
        </select>

        <div class="flex gap-2">
          <button type="submit" class="border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Apply</button>
          <button type="button" class="border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" @click="clearFilters">Clear</button>
        </div>
      </form>

      <div class="border border-slate-200 bg-white">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left">
              <tr>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Title</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Term</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Year</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Date</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Class</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">System</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Results</th>
                <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500 no-print">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exam in examRows" :key="exam.id" class="border-t border-slate-100">
                <td class="px-3 py-2 text-slate-900">{{ exam.title }}</td>
                <td class="px-3 py-2">{{ exam.term }}</td>
                <td class="px-3 py-2">{{ exam.year }}</td>
                <td class="px-3 py-2">{{ exam.exam_date }}</td>
                <td class="px-3 py-2">{{ exam.class_level || '-' }}</td>
                <td class="px-3 py-2">{{ exam.assessment_system || '-' }}</td>
                <td class="px-3 py-2">{{ exam.results?.length || 0 }}</td>
                <td class="px-3 py-2 no-print">
                  <button
                    class="border border-slate-300 bg-white px-2 py-1 text-xs hover:bg-slate-50"
                    type="button"
                    @click="openResultModal(exam)"
                  >
                    Open Results
                  </button>
                </td>
              </tr>

              <tr v-if="!examRows.length">
                <td class="px-3 py-6 text-center text-slate-500" colspan="8">No exams available for your subject scope.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="paginationLinks.length" class="no-print flex flex-wrap gap-2">
        <component
          :is="link.url ? Link : 'span'"
          v-for="(link, idx) in paginationLinks"
          :key="`page-${idx}`"
          :href="link.url || undefined"
          class="border px-3 py-1 text-sm"
          :class="[
            link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-800',
            !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50',
          ]"
          v-html="link.label"
        />
      </div>
    </div>

    <div v-if="resultModalOpen" class="no-print fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
      <div class="mx-auto w-full max-w-5xl border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ selectedExam?.title || 'Exam Results' }}</h3>
            <p class="text-xs text-slate-500">
              {{ selectedExam?.term }} {{ selectedExam?.year }} • {{ selectedExam?.class_level || '-' }}
            </p>
          </div>

          <button class="border border-slate-300 bg-white px-3 py-1 text-sm hover:bg-slate-50" @click="closeResultModal">
            Close
          </button>
        </div>

        <form class="mt-4 grid gap-3 md:grid-cols-3" @submit.prevent="submitResult">
          <select v-model="resultForm.student_id" class="border border-slate-300 px-3 py-2 text-sm" required>
            <option value="">Select Student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }} ({{ student.class_level }})
            </option>
          </select>

          <select v-model="resultForm.grading_system" class="border border-slate-300 px-3 py-2 text-sm" required>
            <option value="844">844</option>
            <option value="CBC">CBC</option>
          </select>

          <input
            v-model="resultForm.score"
            class="border border-slate-300 px-3 py-2 text-sm"
            type="number"
            step="0.01"
            :placeholder="is844 ? 'Score (for 844)' : 'Optional score'"
          />

          <input
            v-if="is844"
            v-model="resultForm.grade"
            class="border border-slate-300 px-3 py-2 text-sm"
            placeholder="Grade (optional auto)"
          />

          <input
            v-if="is844"
            v-model="resultForm.points"
            class="border border-slate-300 px-3 py-2 text-sm"
            type="number"
            step="0.01"
            placeholder="Points (optional auto)"
          />

          <select v-if="isCBC" v-model="resultForm.cbc_level" class="border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select CBC Level</option>
            <option value="BE">BE - Below Expectation</option>
            <option value="AE">AE - Approaching Expectation</option>
            <option value="ME">ME - Meeting Expectation</option>
            <option value="EE">EE - Exceeding Expectation</option>
          </select>

          <input
            v-if="isCBC"
            v-model="resultForm.cbc_comment"
            class="border border-slate-300 px-3 py-2 text-sm"
            placeholder="CBC comment"
          />

          <input
            v-model="resultForm.remarks"
            class="border border-slate-300 px-3 py-2 text-sm md:col-span-2"
            placeholder="General remarks"
          />

          <button
            class="border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-800"
            type="submit"
            :disabled="!canUpdateResults"
          >
            Save Result
          </button>
        </form>

        <div class="mt-4 border border-slate-200">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-left">
                <tr>
                  <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Student</th>
                  <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">System</th>
                  <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Score</th>
                  <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Grade/CBC</th>
                  <th class="px-3 py-2 text-xs uppercase tracking-wide text-slate-500">Remarks</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="result in selectedExam?.results || []"
                  :key="result.id"
                  class="border-t border-slate-100"
                >
                  <td class="px-3 py-2">{{ result.student?.first_name }} {{ result.student?.last_name }}</td>
                  <td class="px-3 py-2">{{ result.grading_system || '-' }}</td>
                  <td class="px-3 py-2">{{ result.score || '-' }}</td>
                  <td class="px-3 py-2">{{ result.grading_system === '844' ? (result.grade || '-') : (result.cbc_level || '-') }}</td>
                  <td class="px-3 py-2">{{ result.remarks || '-' }}</td>
                </tr>

                <tr v-if="!(selectedExam?.results || []).length">
                  <td class="px-3 py-4 text-center text-slate-500" colspan="5">No result records yet for this exam.</td>
                </tr>
              </tbody>
            </table>
          </div>
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