<!-- resources/js/Pages/Admin/Sports/Index.vue -->
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
  router.get(
    route('admin.sports.index'),
    {
      sport: props.filters.sport,
      status: props.filters.status,
    },
    { preserveState: true, replace: true }
  )
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
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Sports Department</h1>
        <p class="mt-1 text-sm text-slate-300">Capture player names and sports played (Basketball, Football, Volleyball, etc).</p>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <form class="rounded-xl border border-white/10 bg-slate-900/70 p-5 space-y-3" @submit.prevent="createRecord">
          <h2 class="text-lg font-semibold text-white">Add Sports Player</h2>

          <select v-model="createForm.student_id" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <select v-model="createForm.sport_name" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option value="">Select sport</option>
            <option v-for="sport in sportsOptions" :key="sport" :value="sport">{{ sport }}</option>
          </select>

          <select v-model="createForm.team_category" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option v-for="team in teamCategories" :key="team" :value="team">{{ team }}</option>
          </select>

          <input v-model="createForm.position" type="text" placeholder="Position (optional)" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />

          <select v-model="createForm.status" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>

          <textarea v-model="createForm.notes" rows="3" placeholder="Notes" class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />

          <button type="submit" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">
            Save Record
          </button>
        </form>

        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="text-lg font-semibold text-white">Sports Overview</h2>
          <div class="mt-4 space-y-3">
            <div v-for="(rows, sport) in grouped" :key="sport" class="rounded-lg border border-white/10 bg-slate-950/60 p-3">
              <p class="font-medium text-white">{{ sport }}</p>
              <p class="text-xs text-slate-400">{{ rows.length }} player(s)</p>
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="mb-4 flex flex-wrap items-center gap-2">
          <select v-model="props.filters.sport" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" @change="applyFilters">
            <option value="">All sports</option>
            <option v-for="sport in sportsOptions" :key="sport" :value="sport">{{ sport }}</option>
          </select>

          <select v-model="props.filters.status" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" @change="applyFilters">
            <option value="">All statuses</option>
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-4 py-3">Student</th>
                <th class="px-4 py-3">Sport</th>
                <th class="px-4 py-3">Team</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="record in records.data" :key="record.id">
                <td class="px-4 py-3">
                  <p class="font-medium text-white">{{ record.student?.first_name }} {{ record.student?.last_name }}</p>
                  <p class="text-xs text-slate-400">{{ record.student?.admission_no }}</p>
                </td>
                <td class="px-4 py-3 text-slate-200">{{ record.sport_name }}</td>
                <td class="px-4 py-3 text-slate-300 uppercase">{{ record.team_category }}</td>
                <td class="px-4 py-3 text-slate-300 capitalize">{{ record.status }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-3">
                    <button class="text-cyan-200 hover:underline" @click="startEdit(record)">Edit</button>
                    <button class="text-rose-200 hover:underline" @click="deleteRecord(record.id)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!records.data?.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-400">No sports records found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="editingId" class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Edit Sports Record</h2>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <select v-model="editForm.student_id" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option value="">Select student</option>
            <option v-for="student in students" :key="student.id" :value="student.id">
              {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
            </option>
          </select>

          <select v-model="editForm.sport_name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option value="">Select sport</option>
            <option v-for="sport in sportsOptions" :key="sport" :value="sport">{{ sport }}</option>
          </select>

          <select v-model="editForm.team_category" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option v-for="team in teamCategories" :key="team" :value="team">{{ team }}</option>
          </select>

          <select v-model="editForm.status" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
            <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
          </select>

          <input v-model="editForm.position" type="text" placeholder="Position" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
          <textarea v-model="editForm.notes" rows="2" placeholder="Notes" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />

          <div class="md:col-span-2 flex gap-3">
            <button class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400" @click="updateRecord">Update</button>
            <button class="rounded-md border border-white/20 px-4 py-2 text-sm text-slate-200 hover:bg-white/5" @click="cancelEdit">Cancel</button>
          </div>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>