<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  records: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({ search: '', staff_type: '', role_category: '' }) },
  staffTypes: { type: Array, default: () => [] },
  roleCategories: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
})

const createForm = useForm({
  full_name: '',
  staff_type: 'teacher',
  role_category: 'teacher',
  department: '',
  phone: '',
  email: '',
  employment_status: 'active',
  is_on_duty: false,
  duty_date: '',
  notes: '',
})

const editingId = ref(null)
const editForm = useForm({
  full_name: '',
  staff_type: 'teacher',
  role_category: 'teacher',
  department: '',
  phone: '',
  email: '',
  employment_status: 'active',
  is_on_duty: false,
  duty_date: '',
  notes: '',
})

const applyFilters = () => {
  router.get(
    route('admin.staff.index'),
    {
      search: props.filters.search,
      staff_type: props.filters.staff_type,
      role_category: props.filters.role_category,
    },
    { preserveState: true, replace: true }
  )
}

const createRecord = () => {
  createForm.post(route('admin.staff.store'), {
    preserveScroll: true,
    onSuccess: () =>
      createForm.reset('full_name', 'department', 'phone', 'email', 'duty_date', 'notes'),
  })
}

const startEdit = (row) => {
  editingId.value = row.id
  editForm.full_name = row.full_name
  editForm.staff_type = row.staff_type
  editForm.role_category = row.role_category
  editForm.department = row.department || ''
  editForm.phone = row.phone || ''
  editForm.email = row.email || ''
  editForm.employment_status = row.employment_status
  editForm.is_on_duty = !!row.is_on_duty
  editForm.duty_date = row.duty_date || ''
  editForm.notes = row.notes || ''
}

const updateRecord = () => {
  if (!editingId.value) return
  editForm.put(route('admin.staff.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => (editingId.value = null),
  })
}

const deleteRecord = (id) => {
  if (!confirm('Delete this staff record?')) return
  useForm({}).delete(route('admin.staff.destroy', id), { preserveScroll: true })
}

const hodList = computed(() => props.records.data.filter((r) => r.role_category === 'hod'))
const classTeacherList = computed(() => props.records.data.filter((r) => r.role_category === 'class_teacher'))
const dutyList = computed(() => props.records.data.filter((r) => r.is_on_duty))
</script>

<template>
  <Head title="Staff Directory" />
  <AdminLayout>
    <div class="space-y-6">
      <section>
        <h1 class="text-2xl font-semibold text-slate-900">Staff Directory</h1>
        <p class="text-sm text-slate-600">
          Deputy Principal and Principal can manage teachers, workers, HOD list, class teachers, and duty roster.
        </p>
      </section>

      <section class="grid gap-4 border border-slate-200 bg-white p-4 md:grid-cols-3">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search name / department / phone"
          class="border border-slate-300 px-3 py-2 text-sm"
          @input="applyFilters"
        />
        <select v-model="filters.staff_type" class="border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
          <option value="">All staff types</option>
          <option v-for="type in staffTypes" :key="type" :value="type">{{ type }}</option>
        </select>
        <select v-model="filters.role_category" class="border border-slate-300 px-3 py-2 text-sm" @change="applyFilters">
          <option value="">All role categories</option>
          <option v-for="type in roleCategories" :key="type" :value="type">{{ type }}</option>
        </select>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 border border-slate-200 bg-white p-5" @submit.prevent="createRecord">
          <h2 class="text-lg font-semibold">Add Staff/Worker</h2>

          <input v-model="createForm.full_name" type="text" placeholder="Full name" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="createForm.staff_type" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="type in staffTypes" :key="type" :value="type">{{ type }}</option>
          </select>
          <select v-model="createForm.role_category" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="type in roleCategories" :key="type" :value="type">{{ type }}</option>
          </select>
          <input v-model="createForm.department" type="text" placeholder="Department" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="createForm.phone" type="text" placeholder="Phone" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="createForm.email" type="email" placeholder="Email" class="w-full border border-slate-300 px-3 py-2 text-sm" />

          <select v-model="createForm.employment_status" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>

          <label class="flex items-center gap-2 text-sm">
            <input v-model="createForm.is_on_duty" type="checkbox" />
            On duty
          </label>

          <input v-model="createForm.duty_date" type="date" class="w-full border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="createForm.notes" rows="2" placeholder="Notes" class="w-full border border-slate-300 px-3 py-2 text-sm" />

          <button class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Save Record
          </button>
        </form>

        <div class="space-y-4">
          <div class="border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-900">Heads of Department</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
              <li v-for="item in hodList" :key="`hod-${item.id}`">{{ item.full_name }} <span class="text-slate-500">({{ item.department || 'N/A' }})</span></li>
              <li v-if="!hodList.length" class="text-slate-500">No HOD records yet.</li>
            </ul>
          </div>

          <div class="border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-900">Class Teachers</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
              <li v-for="item in classTeacherList" :key="`ct-${item.id}`">{{ item.full_name }} <span class="text-slate-500">({{ item.department || 'N/A' }})</span></li>
              <li v-if="!classTeacherList.length" class="text-slate-500">No class teacher records yet.</li>
            </ul>
          </div>

          <div class="border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-900">Teachers On Duty</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
              <li v-for="item in dutyList" :key="`duty-${item.id}`">
                {{ item.full_name }} <span class="text-slate-500">{{ item.duty_date || '' }}</span>
              </li>
              <li v-if="!dutyList.length" class="text-slate-500">No duty records yet.</li>
            </ul>
          </div>
        </div>
      </section>

      <section class="overflow-x-auto border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left text-slate-700">
            <tr>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Category</th>
              <th class="px-4 py-3">Department</th>
              <th class="px-4 py-3">Duty</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in records.data" :key="row.id" class="border-t border-slate-100">
              <td class="px-4 py-3">{{ row.full_name }}</td>
              <td class="px-4 py-3 capitalize">{{ row.staff_type }}</td>
              <td class="px-4 py-3">{{ row.role_category }}</td>
              <td class="px-4 py-3">{{ row.department || '-' }}</td>
              <td class="px-4 py-3">{{ row.is_on_duty ? `Yes ${row.duty_date || ''}` : 'No' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-3">
                  <button class="text-sky-700 hover:underline" @click="startEdit(row)">Edit</button>
                  <button class="text-rose-600 hover:underline" @click="deleteRecord(row.id)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-if="editingId" class="space-y-3 border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Staff Record</h2>

        <div class="grid gap-3 md:grid-cols-2">
          <input v-model="editForm.full_name" type="text" placeholder="Full name" class="border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="editForm.staff_type" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="type in staffTypes" :key="type" :value="type">{{ type }}</option>
          </select>
          <select v-model="editForm.role_category" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="type in roleCategories" :key="type" :value="type">{{ type }}</option>
          </select>
          <input v-model="editForm.department" type="text" placeholder="Department" class="border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="editForm.phone" type="text" placeholder="Phone" class="border border-slate-300 px-3 py-2 text-sm" />
          <input v-model="editForm.email" type="email" placeholder="Email" class="border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="editForm.employment_status" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="editForm.is_on_duty" type="checkbox" />
            On duty
          </label>
          <input v-model="editForm.duty_date" type="date" class="border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="editForm.notes" rows="2" placeholder="Notes" class="border border-slate-300 px-3 py-2 text-sm md:col-span-2" />
        </div>

        <div class="flex gap-3">
          <button class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white" @click="updateRecord">Update</button>
          <button class="border border-slate-300 px-4 py-2 text-sm" @click="editingId = null">Cancel</button>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>