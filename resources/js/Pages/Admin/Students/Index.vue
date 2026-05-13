<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  students: { type: Object, default: () => ({ data: [], links: [] }) },
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
      alumni: 0,
      transferred: 0,
      cbc: 0,
      legacy_844: 0,
    }),
  },
})

const formOptions = {
  systems: ['8-4-4', 'CBC'],
  classes844: ['Form 1', 'Form 2', 'Form 3', 'Form 4'],
  classesCBC: ['Grade 10', 'Grade 11', 'Grade 12'],
  pathways: ['STEM', 'Arts & Sports Science', 'Social Sciences'],
  statuses: ['active', 'alumni', 'transferred'],
  genders: ['Male', 'Female'],
}

const filterForm = useForm({
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
  createForm.education_system === 'CBC' ? formOptions.classesCBC : formOptions.classes844,
)

const classOptionsEdit = computed(() =>
  editForm.education_system === 'CBC' ? formOptions.classesCBC : formOptions.classes844,
)

watch(
  () => createForm.education_system,
  (val) => {
    createForm.class_level = val === 'CBC' ? 'Grade 10' : 'Form 1'
    if (val !== 'CBC') createForm.pathway = ''
  },
)

watch(
  () => editForm.education_system,
  (val) => {
    if (val !== 'CBC') editForm.pathway = ''
    if (!classOptionsEdit.value.includes(editForm.class_level)) {
      editForm.class_level = val === 'CBC' ? 'Grade 10' : 'Form 1'
    }
  },
)

const links = computed(() =>
  (props.students.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  })),
)

const applyFilters = () => {
  router.get(route('admin.students.index'), filterForm.data(), {
    preserveState: true,
    replace: true,
  })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
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
    onSuccess: () => closeEdit(),
  })
}

const removeStudent = (id) => {
  if (!confirm('Delete this student record?')) return
  router.delete(route('admin.students.destroy', id), { preserveScroll: true })
}

const printPage = () => {
  window.print()
}
</script>

<template>
  <Head title="Students" />
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Students</h1>
        <button type="button" class="no-print border px-4 py-2 text-sm font-medium hover:bg-slate-50" @click="printPage">
          Print Student List
        </button>
      </div>

      <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-semibold">{{ stats.total }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Active</p><p class="text-2xl font-semibold">{{ stats.active }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Alumni</p><p class="text-2xl font-semibold">{{ stats.alumni }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">Transferred</p><p class="text-2xl font-semibold">{{ stats.transferred }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">8-4-4</p><p class="text-2xl font-semibold">{{ stats.legacy_844 }}</p></div>
        <div class="border bg-white p-4"><p class="text-xs text-slate-500">CBC</p><p class="text-2xl font-semibold">{{ stats.cbc }}</p></div>
      </div>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-5" @submit.prevent="applyFilters">
        <input v-model="filterForm.search" class="border px-3 py-2" placeholder="Search admission/name" />
        <select v-model="filterForm.education_system" class="border px-3 py-2">
          <option value="">All Systems</option>
          <option value="8-4-4">8-4-4</option>
          <option value="CBC">CBC</option>
        </select>
        <input v-model="filterForm.class_level" class="border px-3 py-2" placeholder="Form 3 / Grade 11" />
        <select v-model="filterForm.status" class="border px-3 py-2">
          <option value="">All Status</option>
          <option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <div class="flex gap-2">
          <button class="w-full bg-slate-900 px-4 py-2 text-white">Filter</button>
          <button type="button" class="w-full border px-4 py-2" @click="clearFilters">Clear</button>
        </div>
      </form>

      <form class="no-print grid gap-3 border bg-white p-4 md:grid-cols-3" @submit.prevent="submitCreate">
        <input v-model="createForm.admission_no" class="border px-3 py-2" placeholder="Admission No" />
        <input v-model="createForm.first_name" class="border px-3 py-2" placeholder="First Name" />
        <input v-model="createForm.last_name" class="border px-3 py-2" placeholder="Last Name" />
        <select v-model="createForm.gender" class="border px-3 py-2">
          <option v-for="g in formOptions.genders" :key="g" :value="g">{{ g }}</option>
        </select>
        <select v-model="createForm.education_system" class="border px-3 py-2">
          <option v-for="sys in formOptions.systems" :key="sys" :value="sys">{{ sys }}</option>
        </select>
        <select v-model="createForm.class_level" class="border px-3 py-2">
          <option v-for="c in classOptionsCreate" :key="c" :value="c">{{ c }}</option>
        </select>
        <select v-if="createForm.education_system === 'CBC'" v-model="createForm.pathway" class="border px-3 py-2">
          <option value="">Select Pathway</option>
          <option v-for="p in formOptions.pathways" :key="p" :value="p">{{ p }}</option>
        </select>
        <input v-model="createForm.stream" class="border px-3 py-2" placeholder="Stream" />
        <select v-model="createForm.status" class="border px-3 py-2">
          <option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <button class="bg-slate-900 px-4 py-2 text-white md:col-span-3">Add Student</button>
      </form>

      <div class="overflow-x-auto border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left">Admission</th>
              <th class="px-3 py-2 text-left">Name</th>
              <th class="px-3 py-2 text-left">System</th>
              <th class="px-3 py-2 text-left">Class</th>
              <th class="px-3 py-2 text-left">Pathway</th>
              <th class="px-3 py-2 text-left">Stream</th>
              <th class="px-3 py-2 text-left">Status</th>
              <th class="no-print px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in students.data" :key="s.id" class="border-t">
              <td class="px-3 py-2">{{ s.admission_no }}</td>
              <td class="px-3 py-2">{{ s.first_name }} {{ s.last_name }}</td>
              <td class="px-3 py-2">{{ s.education_system }}</td>
              <td class="px-3 py-2">{{ s.class_level }}</td>
              <td class="px-3 py-2">{{ s.pathway || '-' }}</td>
              <td class="px-3 py-2">{{ s.stream || '-' }}</td>
              <td class="px-3 py-2">{{ s.status }}</td>
              <td class="no-print px-3 py-2">
                <button class="mr-3 text-blue-700" @click="openEdit(s)">Edit</button>
                <button class="text-rose-700" @click="removeStudent(s.id)">Delete</button>
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

    <div v-if="editOpen" class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-2xl border bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Student</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
          <input v-model="editForm.admission_no" class="border px-3 py-2" placeholder="Admission No" />
          <input v-model="editForm.first_name" class="border px-3 py-2" placeholder="First Name" />
          <input v-model="editForm.last_name" class="border px-3 py-2" placeholder="Last Name" />
          <select v-model="editForm.gender" class="border px-3 py-2"><option v-for="g in formOptions.genders" :key="g" :value="g">{{ g }}</option></select>
          <select v-model="editForm.education_system" class="border px-3 py-2"><option v-for="sys in formOptions.systems" :key="sys" :value="sys">{{ sys }}</option></select>
          <select v-model="editForm.class_level" class="border px-3 py-2"><option v-for="c in classOptionsEdit" :key="c" :value="c">{{ c }}</option></select>
          <select v-if="editForm.education_system === 'CBC'" v-model="editForm.pathway" class="border px-3 py-2">
            <option value="">Select Pathway</option><option v-for="p in formOptions.pathways" :key="p" :value="p">{{ p }}</option>
          </select>
          <input v-model="editForm.stream" class="border px-3 py-2" placeholder="Stream" />
          <select v-model="editForm.status" class="border px-3 py-2"><option v-for="s in formOptions.statuses" :key="s" :value="s">{{ s }}</option></select>
          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" class="border px-4 py-2" @click="closeEdit">Cancel</button>
            <button class="bg-slate-900 px-4 py-2 text-white">Save</button>
          </div>
        </form>
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