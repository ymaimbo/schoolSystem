<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  records: { type: Object, default: () => ({ data: [] }) },
  students: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ sport: '', status: '' }) },
  sportsOptions: { type: Array, default: () => [] },
  teamCategories: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
})

const createForm = useForm({
  student_id: '',
  sport_name: '',
  team_category: 'mixed',
  position: '',
  status: 'active',
  notes: '',
})

const editingId = ref(null)
const editForm = useForm({
  student_id: '',
  sport_name: '',
  team_category: 'mixed',
  position: '',
  status: 'active',
  notes: '',
})

const startEdit = (record) => {
  editingId.value = record.id
  editForm.student_id = record.student_id
  editForm.sport_name = record.sport_name
  editForm.team_category = record.team_category
  editForm.position = record.position || ''
  editForm.status = record.status
  editForm.notes = record.notes || ''
}

const cancelEdit = () => {
  editingId.value = null
}

const createRecord = () => {
  createForm.post(route('admin.sports.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset('student_id', 'sport_name', 'team_category', 'position', 'status', 'notes'),
  })
}

const updateRecord = () => {
  if (!editingId.value) return
  editForm.put(route('admin.sports.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => cancelEdit(),
  })
}

const deleteRecord = (id) => {
  if (!confirm('Delete this sports record?')) return
  useForm({}).delete(route('admin.sports.destroy', id), { preserveScroll: true })
}

const applyFilters = () => {
  router.get(route('admin.sports.index'), {
    sport: props.filters.sport,
    status: props.filters.status,
  }, { preserveState: true, replace: true })
}

const grouped = computed(() => {
  const map = {}
  for (const row of props.records.data) {
    if (!map[row.sport_name]) map[row.sport_name] = []
    map[row.sport_name].push(row)
  }
  return map
})
</script>

<template>
  <Head title="Sports Department" />
  <AdminLayout>
    <div class="space-y-6">
      <section>
        <h1 class="text-2xl font-semibold text-slate-900">Sports Department</h1>
        <p class="text-sm text-slate-600">Capture player names and sports played (Basketball, Football, Volleyball, etc).</p>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="space-y-3 border border-slate-200 bg-white p-5" @submit.prevent="createRecord">
          <h2 class="text-lg font-semibold">Add Sports Player</h2>

          <select v-model="createForm.student_id" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <select v-model="createForm.sport_name" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select sport</option>
            <option v-for="sport in sportsOptions" :key="sport" :value="sport">{{ sport }}</option>
          </select>

          <select v-model="createForm.team_category" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="team in teamCategories" :key="team" :value="team">{{ team }}</option>
          </select>

          <input v-model="createForm.position" type="text" placeholder="Position (optional)" class="w-full border border-slate-300 px-3 py-2 text-sm" />

          <select v-model="createForm.status" class="w-full border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>

          <textarea v-model="createForm.notes" rows="3" placeholder="Notes" class="w-full border border-slate-300 px-3 py-2 text-sm" />

          <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Save Record
          </button>
        </form>

        <div class="space-y-3 border border-slate-200 bg-white p-5">
          <h2 class="text-lg font-semibold">Sports Overview</h2>
          <div v-for="(rows, sport) in grouped" :key="sport" class="border border-slate-100 p-3">
            <p class="font-medium text-slate-900">{{ sport }}</p>
            <p class="text-xs text-slate-600">{{ rows.length }} player(s)</p>
          </div>
        </div>
      </section>

      <section class="overflow-x-auto border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left text-slate-700">
            <tr>
              <th class="px-4 py-3">Student</th>
              <th class="px-4 py-3">Sport</th>
              <th class="px-4 py-3">Team</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in records.data" :key="record.id" class="border-t border-slate-100">
              <td class="px-4 py-3">
                <p class="font-medium">{{ record.student?.first_name }} {{ record.student?.last_name }}</p>
                <p class="text-xs text-slate-500">{{ record.student?.admission_no }}</p>
              </td>
              <td class="px-4 py-3">{{ record.sport_name }}</td>
              <td class="px-4 py-3 uppercase">{{ record.team_category }}</td>
              <td class="px-4 py-3 capitalize">{{ record.status }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-3">
                  <button class="text-sky-700 hover:underline" @click="startEdit(record)">Edit</button>
                  <button class="text-rose-600 hover:underline" @click="deleteRecord(record.id)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-if="editingId" class="space-y-3 border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Sports Record</h2>

        <div class="grid gap-3 md:grid-cols-2">
          <select v-model="editForm.student_id" class="border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <select v-model="editForm.sport_name" class="border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select sport</option>
            <option v-for="sport in sportsOptions" :key="sport" :value="sport">{{ sport }}</option>
          </select>

          <select v-model="editForm.team_category" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="team in teamCategories" :key="team" :value="team">{{ team }}</option>
          </select>

          <select v-model="editForm.status" class="border border-slate-300 px-3 py-2 text-sm">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>

          <input v-model="editForm.position" type="text" placeholder="Position" class="border border-slate-300 px-3 py-2 text-sm" />
          <textarea v-model="editForm.notes" rows="2" placeholder="Notes" class="border border-slate-300 px-3 py-2 text-sm" />
        </div>

        <div class="flex gap-3">
          <button class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm text-white" @click="updateRecord">Update</button>
          <button class="border border-slate-300 px-4 py-2 text-sm" @click="cancelEdit">Cancel</button>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>