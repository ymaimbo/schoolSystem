<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  cases: { type: Object, default: () => ({ data: [] }) },
  students: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ status: '', subject_type: '' }) },
  statusOptions: { type: Array, default: () => [] },
  subjectTypeOptions: { type: Array, default: () => [] },
})

const createForm = useForm({
  subject_type: 'student',
  student_id: '',
  worker_name: '',
  worker_department: '',
  case_title: '',
  description: '',
  status: 'pending',
  reported_on: new Date().toISOString().slice(0, 10),
  action_taken: '',
  next_step: '',
})

const editingId = ref(null)
const editForm = useForm({
  subject_type: 'student',
  student_id: '',
  worker_name: '',
  worker_department: '',
  case_title: '',
  description: '',
  status: 'pending',
  reported_on: '',
  action_taken: '',
  next_step: '',
})

const startEdit = (row) => {
  editingId.value = row.id
  editForm.subject_type = row.subject_type
  editForm.student_id = row.student_id || ''
  editForm.worker_name = row.worker_name || ''
  editForm.worker_department = row.worker_department || ''
  editForm.case_title = row.case_title
  editForm.description = row.description
  editForm.status = row.status
  editForm.reported_on = row.reported_on
  editForm.action_taken = row.action_taken || ''
  editForm.next_step = row.next_step || ''
}

const cancelEdit = () => {
  editingId.value = null
}

const createCase = () => {
  createForm.post(route('admin.discipline.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset('student_id', 'worker_name', 'worker_department', 'case_title', 'description', 'action_taken', 'next_step'),
  })
}

const updateCase = () => {
  if (!editingId.value) return
  editForm.put(route('admin.discipline.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => cancelEdit(),
  })
}

const deleteCase = (id) => {
  if (!confirm('Delete this discipline case?')) return
  useForm({}).delete(route('admin.discipline.destroy', id), { preserveScroll: true })
}

const applyFilters = () => {
  router.get(route('admin.discipline.index'), {
    status: props.filters.status,
    subject_type: props.filters.subject_type,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Discipline Department" />
  <AdminLayout>
    <div class="space-y-6">
      <section>
        <h1 class="text-2xl font-semibold text-slate-900">Discipline Department</h1>
        <p class="text-sm text-slate-600">
          Managed by Deputy Principal. Keep records of ongoing and pending student and worker cases.
        </p>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 border border-slate-200 bg-white p-5" @submit.prevent="createCase">
          <h2 class="text-lg font-semibold">Record New Case</h2>

          <select v-model="createForm.subject_type" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="t in subjectTypeOptions" :key="t" :value="t">{{ t }}</option>
          </select>

          <select
            v-if="createForm.subject_type === 'student'"
            v-model="createForm.student_id"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <input
            v-if="createForm.subject_type === 'worker'"
            v-model="createForm.worker_name"
            type="text"
            placeholder="Worker name"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-if="createForm.subject_type === 'worker'"
            v-model="createForm.worker_department"
            type="text"
            placeholder="Worker department"
            class="w-full border border-slate-300 px-3 py-2 text-sm"
          />

          <input v-model="createForm.case_title" type="text" placeholder="Case title" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="createForm.description" rows="3" placeholder="Case description" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="createForm.status" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>
          <input v-model="createForm.reported_on" type="date" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="createForm.action_taken" rows="2" placeholder="Action taken (optional)" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="createForm.next_step" type="text" placeholder="Next step (optional)" class="w-full border border-slate-300 px-3 py-2 text-sm" />

          <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Save Case
          </button>
        </form>

        <div class="space-y-3 border border-slate-200 bg-white p-5">
          <h2 class="text-lg font-semibold">Filter Cases</h2>
          <select v-model="filters.subject_type" class="w-full border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
            <option value="">All subject types</option>
            <option v-for="t in subjectTypeOptions" :key="t" :value="t">{{ t }}</option>
          </select>
          <select v-model="filters.status" class="w-full border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
            <option value="">All statuses</option>
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>
          <p class="text-sm text-slate-600">
            Use filters to monitor ongoing and pending cases across students and workers.
          </p>
        </div>
      </section>

      <section class="overflow-x-auto border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left text-slate-700">
            <tr>
              <th class="px-4 py-3">Subject</th>
              <th class="px-4 py-3">Title</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Reported</th>
              <th class="px-4 py-3">Handled By</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in cases.data" :key="row.id" class="border-t border-slate-100">
              <td class="px-4 py-3">
                <p class="capitalize">{{ row.subject_type }}</p>
                <p v-if="row.subject_type === 'student'" class="text-xs text-slate-500">
                  {{ row.student?.admission_no }} - {{ row.student?.first_name }} {{ row.student?.last_name }}
                </p>
                <p v-else class="text-xs text-slate-500">
                  {{ row.worker_name }} <span v-if="row.worker_department">({{ row.worker_department }})</span>
                </p>
              </td>
              <td class="px-4 py-3">{{ row.case_title }}</td>
              <td class="px-4 py-3 capitalize">{{ row.status }}</td>
              <td class="px-4 py-3">{{ row.reported_on }}</td>
              <td class="px-4 py-3">{{ row.handler?.name || '-' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-3">
                  <button class="text-sky-700 hover:underline" @click="startEdit(row)">Edit</button>
                  <button class="text-rose-600 hover:underline" @click="deleteCase(row.id)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-if="editingId" class="space-y-3 border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Discipline Case</h2>

        <div class="grid gap-3 md:grid-cols-2">
          <select v-model="editForm.subject_type" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="t in subjectTypeOptions" :key="t" :value="t">{{ t }}</option>
          </select>

          <select
            v-if="editForm.subject_type === 'student'"
            v-model="editForm.student_id"
            class="border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <input
            v-if="editForm.subject_type === 'worker'"
            v-model="editForm.worker_name"
            type="text"
            placeholder="Worker name"
            class="border border-slate-300 px-3 py-2 text-sm"
          />

          <input
            v-if="editForm.subject_type === 'worker'"
            v-model="editForm.worker_department"
            type="text"
            placeholder="Worker department"
            class="border border-slate-300 px-3 py-2 text-sm"
          />

          <input v-model="editForm.case_title" type="text" placeholder="Case title" class="border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="editForm.status" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>
          <input v-model="editForm.reported_on" type="date" class="border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="editForm.next_step" type="text" placeholder="Next step" class="border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="editForm.description" rows="3" placeholder="Description" class="border border-slate-300 px-3 py-2 text-sm md:col-span-2" />
          <textarea v-model="editForm.action_taken" rows="2" placeholder="Action taken" class="border border-slate-300 px-3 py-2 text-sm md:col-span-2" />
        </div>

        <div class="flex gap-3">
          <button class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white" @click="updateCase">Update</button>
          <button class="border border-slate-300 px-4 py-2 text-sm" @click="cancelEdit">Cancel</button>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>