<!-- resources/js/Pages/Admin/Students/Index.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  students: {
    type: Object,
    default: () => ({ data: [], links: [] }),
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      education_system: '',
      class_level: '',
      status: '',
    }),
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      inactive: 0,
      transferred: 0,
      male: 0,
      female: 0,
    }),
  },
})

const formOptions = {
  curriculum: ['8-4-4', 'CBC'],
  classes844: ['Form 1', 'Form 2', 'Form 3', 'Form 4'],
  classesCBC: ['Grade 10', 'Grade 11', 'Grade 12'],
  pathways: ['STEM', 'Arts & Sports Science', 'Social Sciences'],
  statuses: ['active', 'alumni', 'transferred'],
  genders: ['Male', 'Female'],
}

const filters = useForm({
  search: props.filters.search ?? '',
  education_system: props.filters.education_system ?? '',
  class_level: props.filters.class_level ?? '',
  status: props.filters.status ?? '',
})

const createForm = useForm({
  admission_no: '',
  first_name: '',
  last_name: '',
  gender: 'Male',
  education_system: '8-4-4',
  class_level: 'Form 1',
  pathway: '',
  stream: '',
  status: 'active',
})

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  admission_no: '',
  first_name: '',
  last_name: '',
  gender: 'Male',
  education_system: '8-4-4',
  class_level: 'Form 1',
  pathway: '',
  stream: '',
  status: 'active',
})

const classOptionsCreate = computed(() =>
  createForm.education_system === 'CBC' ? formOptions.classesCBC : formOptions.classes844
)

const classOptionsEdit = computed(() =>
  editForm.education_system === 'CBC' ? formOptions.classesCBC : formOptions.classes844
)

watch(
  () => createForm.education_system,
  (val) => {
    if (!classOptionsCreate.value.includes(createForm.class_level)) {
      createForm.class_level = val === 'CBC' ? 'Grade 10' : 'Form 1'
    }
  }
)

watch(
  () => editForm.education_system,
  (val) => {
    if (!classOptionsEdit.value.includes(editForm.class_level)) {
      editForm.class_level = val === 'CBC' ? 'Grade 10' : 'Form 1'
    }
  }
)

const links = computed(() =>
  (props.students.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '<<').replace('&raquo;', '>>'),
  }))
)

const studentsRows = computed(() => props.students?.data ?? [])

const printableClassLevels = computed(() => {
  const values = studentsRows.value.map((s) => s.class_level).filter(Boolean)
  return [...new Set(values)].sort((a, b) => String(a).localeCompare(String(b)))
})

const applyFilters = () => {
  router.get(route('admin.students.index'), filters.data(), {
    preserveState: true,
    replace: true,
  })
}

const resetFilters = () => {
  filters.reset()
  applyFilters()
}

const reloadStudentsData = () => {
  router.reload({
    preserveScroll: true,
    only: ['students', 'stats'],
  })
}

const submitCreate = () => {
  createForm.post(route('admin.students.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset()
      createForm.education_system = '8-4-4'
      createForm.class_level = 'Form 1'
      createForm.gender = 'Male'
      createForm.status = 'active'
      reloadStudentsData()
    },
  })
}

const openEdit = (student) => {
  editId.value = student.id
  editForm.admission_no = student.admission_no ?? ''
  editForm.first_name = student.first_name ?? ''
  editForm.last_name = student.last_name ?? ''
  editForm.gender = student.gender ?? 'Male'
  editForm.education_system = student.education_system ?? '8-4-4'
  editForm.class_level = student.class_level ?? 'Form 1'
  editForm.pathway = student.pathway ?? ''
  editForm.stream = student.stream ?? ''
  editForm.status = student.status ?? 'active'
  editOpen.value = true
}

const closeEdit = () => {
  editOpen.value = false
  editId.value = null
  editForm.clearErrors()
}

const submitEdit = () => {
  if (!editId.value) return
  editForm.put(route('admin.students.update', editId.value), {
    preserveScroll: true,
    onSuccess: () => {
      closeEdit()
      reloadStudentsData()
    },
  })
}

const removeStudent = (id) => {
  if (!confirm('Delete this student record?')) return
  router.delete(route('admin.students.destroy', id), {
    preserveScroll: true,
    onSuccess: () => reloadStudentsData(),
  })
}

/** Print support */
const printMode = ref('') // all | class
const printClassLevel = ref('')
const printPreparedAt = ref('')

const printRows = computed(() => {
  if (printMode.value === 'class') {
    return studentsRows.value.filter((s) => String(s.class_level ?? '') === String(printClassLevel.value))
  }
  if (printMode.value === 'all') return studentsRows.value
  return []
})

const printStats = computed(() => {
  const rows = printRows.value
  return {
    total: rows.length,
    male: rows.filter((s) => s.gender === 'Male').length,
    female: rows.filter((s) => s.gender === 'Female').length,
    active: rows.filter((s) => s.status === 'active').length,
    transferred: rows.filter((s) => s.status === 'transferred').length,
  }
})

const printTitle = computed(() =>
  printMode.value === 'class'
    ? `Student List - ${printClassLevel.value}`
    : 'Student List - All Classes'
)

const resetPrintMode = () => {
  printMode.value = ''
}

const handleAfterPrint = () => {
  resetPrintMode()
}

onMounted(() => {
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

const printAllStudents = () => {
  runPrint('all')
}

const printStudentsByClass = () => {
  if (!printClassLevel.value) {
    window.alert('Please select a class first.')
    return
  }
  runPrint('class')
}
</script>

<template>
  <Head title="Students" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="no-print">
        <section class="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Students</h1>
            <p class="mt-1 text-sm text-slate-300">Student records, filters, and enrollment updates.</p>
          </div>
        </section>

        <section class="no-print mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Print Student List</h2>
          <p class="mt-1 text-sm text-slate-300">Print all students or only students from a selected class.</p>

          <div class="mt-4 grid gap-3 md:grid-cols-3">
            <button
              type="button"
              class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
              @click="printAllStudents"
            >
              Print All (Current Page)
            </button>

            <select
              v-model="printClassLevel"
              class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">Select class...</option>
              <option v-for="level in printableClassLevels" :key="`print-level-${level}`" :value="level">{{ level }}</option>
            </select>

            <button
              type="button"
              class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
              @click="printStudentsByClass"
            >
              Print Selected Class
            </button>
          </div>
        </section>

        <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Total</p>
            <p class="mt-2 text-xl font-bold text-white">{{ stats.total }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Active</p>
            <p class="mt-2 text-xl font-bold text-emerald-300">{{ stats.active }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Inactive</p>
            <p class="mt-2 text-xl font-bold text-slate-200">{{ stats.inactive }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Transferred</p>
            <p class="mt-2 text-xl font-bold text-amber-300">{{ stats.transferred }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Male</p>
            <p class="mt-2 text-xl font-bold text-cyan-300">{{ stats.male }}</p>
          </article>
          <article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
            <p class="text-xs uppercase tracking-wider text-slate-400">Female</p>
            <p class="mt-2 text-xl font-bold text-fuchsia-300">{{ stats.female }}</p>
          </article>
        </section>

        <section class="no-print mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Filters</h2>

          <form class="mt-4 grid gap-3 md:grid-cols-5" @submit.prevent="applyFilters">
            <input
              v-model="filters.search"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-emerald-400"
              placeholder="Search admission/name"
            />

            <select
              v-model="filters.education_system"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
            >
              <option value="">All systems</option>
              <option value="8-4-4">8-4-4</option>
              <option value="CBC">CBC</option>
            </select>

            <input
              v-model="filters.class_level"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-emerald-400"
              placeholder="Form / Grade"
            />

            <select
              v-model="filters.status"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-emerald-400"
            >
              <option value="">All statuses</option>
              <option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option>
            </select>

            <div class="flex items-center gap-2">
              <button
                class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400"
                type="submit"
              >
                Apply
              </button>
              <button
                class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
                type="button"
                @click="resetFilters"
              >
                Clear
              </button>
            </div>
          </form>
        </section>

        <section class="no-print mb-8 rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Add Student</h2>

          <form class="mt-4 grid gap-3 md:grid-cols-3" @submit.prevent="submitCreate">
            <input
              v-model="createForm.admission_no"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Admission No"
            />
            <input
              v-model="createForm.first_name"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="First Name"
            />
            <input
              v-model="createForm.last_name"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Last Name"
            />

            <select
              v-model="createForm.gender"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="g in formOptions.genders" :key="g" :value="g">{{ g }}</option>
            </select>

            <select
              v-model="createForm.education_system"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="sys in formOptions.curriculum" :key="sys" :value="sys">{{ sys }}</option>
            </select>

            <select
              v-model="createForm.class_level"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="cl in classOptionsCreate" :key="cl" :value="cl">{{ cl }}</option>
            </select>

            <select
              v-if="createForm.education_system === 'CBC'"
              v-model="createForm.pathway"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">Select pathway</option>
              <option v-for="p in formOptions.pathways" :key="p" :value="p">{{ p }}</option>
            </select>

            <input
              v-model="createForm.stream"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Stream"
            />

            <select
              v-model="createForm.status"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option>
            </select>

            <button
              class="md:col-span-3 inline-flex w-fit items-center rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
              type="submit"
              :disabled="createForm.processing"
            >
              {{ createForm.processing ? 'Saving...' : 'Add Student' }}
            </button>
          </form>
        </section>

        <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-4 text-lg font-semibold text-white">Student List</h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
              <thead class="bg-slate-950/50 text-left text-slate-300">
                <tr>
                  <th class="px-3 py-2">Admission</th>
                  <th class="px-3 py-2">Name</th>
                  <th class="px-3 py-2">System</th>
                  <th class="px-3 py-2">Class</th>
                  <th class="px-3 py-2">Pathway</th>
                  <th class="px-3 py-2">Stream</th>
                  <th class="px-3 py-2">Status</th>
                  <th class="no-print px-3 py-2">Actions</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-white/5">
                <tr v-for="s in students.data" :key="s.id" class="hover:bg-white/[0.03]">
                  <td class="px-3 py-2 text-slate-200">{{ s.admission_no }}</td>
                  <td class="px-3 py-2 text-white">{{ s.first_name }} {{ s.last_name }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ s.education_system }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ s.class_level }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ s.pathway || '-' }}</td>
                  <td class="px-3 py-2 text-slate-300">{{ s.stream || '-' }}</td>
                  <td class="px-3 py-2 text-slate-200">{{ s.status }}</td>
                  <td class="no-print px-3 py-2">
                    <div class="flex flex-wrap gap-2">
                      <button
                        class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10"
                        @click="openEdit(s)"
                      >
                        Edit
                      </button>
                      <button
                        class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10"
                        @click="removeStudent(s.id)"
                      >
                        Del
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="!(students.data ?? []).length">
                  <td colspan="8" class="px-3 py-6 text-center text-slate-400">No students found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <div class="no-print mt-5 flex flex-wrap gap-2">
          <button
            v-for="l in links"
            :key="l.label + String(l.url)"
            class="rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-40"
            :disabled="!l.url"
            @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
            v-html="l.label"
          />
        </div>
      </div>

      <!-- PRINT VIEW -->
      <section v-if="printMode" class="hidden print:block bg-white text-black">
        <div class="mb-4">
          <h1 class="text-2xl font-bold">{{ printTitle }}</h1>
          <p class="text-sm">Generated: {{ printPreparedAt }}</p>
        </div>

        <div class="mb-4 grid grid-cols-5 gap-3 text-sm">
          <div><strong>Total:</strong> {{ printStats.total }}</div>
          <div><strong>Active:</strong> {{ printStats.active }}</div>
          <div><strong>Transferred:</strong> {{ printStats.transferred }}</div>
          <div><strong>Male:</strong> {{ printStats.male }}</div>
          <div><strong>Female:</strong> {{ printStats.female }}</div>
        </div>

        <table class="print-table w-full text-sm">
          <thead>
            <tr>
              <th>Admission No</th>
              <th>Name</th>
              <th>System</th>
              <th>Class</th>
              <th>Pathway</th>
              <th>Stream</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in printRows" :key="`print-${s.id}`">
              <td>{{ s.admission_no }}</td>
              <td>{{ s.first_name }} {{ s.last_name }}</td>
              <td>{{ s.education_system }}</td>
              <td>{{ s.class_level }}</td>
              <td>{{ s.pathway || '-' }}</td>
              <td>{{ s.stream || '-' }}</td>
              <td>{{ s.status }}</td>
            </tr>
            <tr v-if="!printRows.length">
              <td colspan="7" style="text-align: center;">No students found for this print selection.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <div v-if="editOpen" class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-3xl rounded-xl border border-white/10 bg-slate-900 p-5">
          <h3 class="mb-4 text-lg font-semibold text-white">Edit Student</h3>

          <form class="grid gap-3 md:grid-cols-3" @submit.prevent="submitEdit">
            <input
              v-model="editForm.admission_no"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Admission No"
            />
            <input
              v-model="editForm.first_name"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="First Name"
            />
            <input
              v-model="editForm.last_name"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Last Name"
            />

            <select
              v-model="editForm.gender"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="g in formOptions.genders" :key="g" :value="g">{{ g }}</option>
            </select>

            <select
              v-model="editForm.education_system"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="sys in formOptions.curriculum" :key="sys" :value="sys">{{ sys }}</option>
            </select>

            <select
              v-model="editForm.class_level"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="cl in classOptionsEdit" :key="cl" :value="cl">{{ cl }}</option>
            </select>

            <select
              v-if="editForm.education_system === 'CBC'"
              v-model="editForm.pathway"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option value="">Select pathway</option>
              <option v-for="p in formOptions.pathways" :key="p" :value="p">{{ p }}</option>
            </select>

            <input
              v-model="editForm.stream"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
              placeholder="Stream"
            />

            <select
              v-model="editForm.status"
              class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
            >
              <option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option>
            </select>

            <div class="md:col-span-3 flex justify-end gap-2 pt-2">
              <button
                type="button"
                class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5"
                @click="closeEdit"
              >
                Cancel
              </button>
              <button
                class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
                type="submit"
              >
                Save
              </button>
            </div>
          </form>
        </div>
      </div>
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