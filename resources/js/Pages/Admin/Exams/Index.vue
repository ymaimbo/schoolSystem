<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  exams: { type: Object, default: () => ({ data: [], links: [] }) },
  students: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const filterForm = useForm({
  year: props.filters.year ?? '',
  term: props.filters.term ?? '',
  status: props.filters.status ?? '',
  assessment_system: props.filters.assessment_system ?? '',
  class_level: props.filters.class_level ?? '',
})

const createForm = useForm({
  title: '',
  term: 'Term 1',
  year: new Date().getFullYear(),
  exam_date: new Date().toISOString().slice(0, 10),
  max_score: 100,
  status: 'draft',
  assessment_system: 'HYBRID',
  class_level: '',
  pathway: '',
})

const resultOpen = ref(false)
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
  (system) => {
    if (system === 'CBC') {
      resultForm.grade = ''
      resultForm.points = ''
    } else {
      resultForm.cbc_level = ''
      resultForm.cbc_comment = ''
    }
  }
)

const links = computed(() =>
  (props.exams.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  })),
)

const applyFilters = () => {
  router.get(route('admin.exams.index'), filterForm.data(), { preserveState: true, replace: true })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
}

const submitCreate = () => {
  createForm.post(route('admin.exams.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset('title', 'class_level', 'pathway'),
  })
}

const removeExam = (id) => {
  if (!confirm('Delete this exam?')) return
  router.delete(route('admin.exams.destroy', id), { preserveScroll: true })
}

const openResultModal = (exam) => {
  selectedExam.value = exam
  resultForm.reset()
  resultForm.grading_system = exam.assessment_system === 'CBC' ? 'CBC' : '844'
  resultOpen.value = true
}

const closeResultModal = () => {
  resultOpen.value = false
  selectedExam.value = null
  resultForm.clearErrors()
}

const submitResult = () => {
  if (!selectedExam.value) return
  resultForm.post(route('admin.exams.results.store', selectedExam.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      const keepSystem = resultForm.grading_system
      resultForm.reset()
      resultForm.grading_system = keepSystem
    },
  })
}

const removeResult = (id) => {
  if (!confirm('Delete this result?')) return
  router.delete(route('admin.exam-results.destroy', id), { preserveScroll: true })
}

const printPage = () => {
  window.print()
}
</script>

<template>
  <Head title="Exams" />
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Exams Report</h1>
        <button type="button" class="no-print border px-4 py-2 text-sm font-medium hover:bg-slate-50" @click="printPage">
          Print Exam Results
        </button>
      </div>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-6" @submit.prevent="applyFilters">
        <input v-model="filterForm.year" type="number" class="border px-3 py-2" placeholder="Year" />
        <select v-model="filterForm.term" class="border px-3 py-2"><option value="">All Terms</option><option>Term 1</option><option>Term 2</option><option>Term 3</option></select>
        <select v-model="filterForm.status" class="border px-3 py-2"><option value="">All Status</option><option>draft</option><option>published</option><option>closed</option></select>
        <select v-model="filterForm.assessment_system" class="border px-3 py-2"><option value="">All Systems</option><option>844</option><option>CBC</option><option>HYBRID</option></select>
        <input v-model="filterForm.class_level" class="border px-3 py-2" placeholder="Form 4 / Grade 11" />
        <div class="flex gap-2">
          <button class="w-full bg-slate-900 px-4 py-2 text-white">Filter</button>
          <button type="button" class="w-full border px-4 py-2" @click="clearFilters">Clear</button>
        </div>
      </form>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-3" @submit.prevent="submitCreate">
        <input v-model="createForm.title" class="border px-3 py-2" placeholder="Exam Title" />
        <select v-model="createForm.term" class="border px-3 py-2"><option>Term 1</option><option>Term 2</option><option>Term 3</option></select>
        <input v-model="createForm.year" type="number" class="border px-3 py-2" placeholder="Year" />
        <input v-model="createForm.exam_date" type="date" class="border px-3 py-2" />
        <input v-model="createForm.max_score" type="number" step="0.01" class="border px-3 py-2" placeholder="Max Score" />
        <select v-model="createForm.status" class="border px-3 py-2"><option>draft</option><option>published</option><option>closed</option></select>
        <select v-model="createForm.assessment_system" class="border px-3 py-2"><option>HYBRID</option><option>844</option><option>CBC</option></select>
        <input v-model="createForm.class_level" class="border px-3 py-2" placeholder="Form 4 / Grade 11" />
        <input v-model="createForm.pathway" class="border px-3 py-2" placeholder="Pathway (optional)" />
        <button class="bg-slate-900 px-4 py-2 text-white md:col-span-3">Create Exam</button>
      </form>

      <div class="overflow-x-auto border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left">Title</th>
              <th class="px-3 py-2 text-left">System</th>
              <th class="px-3 py-2 text-left">Class</th>
              <th class="px-3 py-2 text-left">Term</th>
              <th class="px-3 py-2 text-left">Date</th>
              <th class="px-3 py-2 text-left">Status</th>
              <th class="px-3 py-2 text-left">Results</th>
              <th class="no-print px-3 py-2 text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exam in exams.data" :key="exam.id" class="border-t">
              <td class="px-3 py-2">{{ exam.title }}</td>
              <td class="px-3 py-2">{{ exam.assessment_system }}</td>
              <td class="px-3 py-2">{{ exam.class_level || '-' }}</td>
              <td class="px-3 py-2">{{ exam.term }} {{ exam.year }}</td>
              <td class="px-3 py-2">{{ exam.exam_date }}</td>
              <td class="px-3 py-2">{{ exam.status }}</td>
              <td class="px-3 py-2">{{ exam.results?.length || 0 }}</td>
              <td class="no-print px-3 py-2">
                <button class="mr-3 text-violet-700" @click="openResultModal(exam)">Results</button>
                <button class="text-rose-700" @click="removeExam(exam.id)">Delete</button>
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

    <div v-if="resultOpen" class="no-print fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
      <div class="mx-auto w-full max-w-3xl border bg-white p-5">
        <h2 class="text-lg font-semibold">Result Entry - {{ selectedExam?.title }}</h2>

        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitResult">
          <select v-model="resultForm.student_id" class="border px-3 py-2">
            <option value="">Select Student</option>
            <option v-for="s in students" :key="s.id" :value="s.id">
              {{ s.admission_no }} - {{ s.first_name }} {{ s.last_name }} ({{ s.education_system }} {{ s.class_level }})
            </option>
          </select>

          <select v-model="resultForm.grading_system" class="border px-3 py-2">
            <option value="844">844</option>
            <option value="CBC">CBC</option>
          </select>

          <template v-if="is844">
            <input v-model="resultForm.score" type="number" step="0.01" class="border px-3 py-2" placeholder="Score (required for 844)" />
            <input v-model="resultForm.grade" class="border px-3 py-2" placeholder="Grade (optional auto)" />
            <input v-model="resultForm.points" type="number" step="0.01" class="border px-3 py-2 md:col-span-2" placeholder="Points (optional auto)" />
          </template>

          <template v-if="isCBC">
            <select v-model="resultForm.cbc_level" class="border px-3 py-2">
              <option value="">Select CBC Level</option>
              <option value="BE">BE - Below Expectation</option>
              <option value="AE">AE - Approaching Expectation</option>
              <option value="ME">ME - Meeting Expectation</option>
              <option value="EE">EE - Exceeding Expectation</option>
            </select>
            <input v-model="resultForm.score" type="number" step="0.01" class="border px-3 py-2" placeholder="Optional score" />
            <textarea v-model="resultForm.cbc_comment" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="CBC competency comment" />
          </template>

          <textarea v-model="resultForm.remarks" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="General remarks" />
          <button class="bg-slate-900 px-4 py-2 text-white md:col-span-2">Save Result</button>
        </form>

        <div class="mt-6 overflow-x-auto border">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-3 py-2 text-left">Student</th>
                <th class="px-3 py-2 text-left">System</th>
                <th class="px-3 py-2 text-left">Score</th>
                <th class="px-3 py-2 text-left">Grade / CBC Level</th>
                <th class="px-3 py-2 text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in selectedExam?.results || []" :key="r.id" class="border-t">
                <td class="px-3 py-2">{{ r.student?.admission_no }} - {{ r.student?.first_name }} {{ r.student?.last_name }}</td>
                <td class="px-3 py-2">{{ r.grading_system }}</td>
                <td class="px-3 py-2">{{ r.score ?? '-' }}</td>
                <td class="px-3 py-2">{{ r.grading_system === '844' ? (r.grade || '-') : (r.cbc_level || '-') }}</td>
                <td class="px-3 py-2"><button class="text-rose-700" @click="removeResult(r.id)">Delete</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end">
          <button class="border px-4 py-2" @click="closeResultModal">Close</button>
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