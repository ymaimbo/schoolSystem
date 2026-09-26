<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  activeView: {
    type: String,
    default: 'all',
  },
  summary: {
    type: Object,
    default: () => ({
      assigned_class_teachers: 0,
      unassigned_class_teachers: 0,
      classes_without_assignment: 0,
    }),
  },
  assignments: {
    type: Array,
    default: () => [],
  },
  teachers: {
    type: Array,
    default: () => [],
  },
  unassignedTeachers: {
    type: Array,
    default: () => [],
  },
  unassignedClasses: {
    type: Array,
    default: () => [],
  },
})

const editingId = ref(null)

const form = useForm({
  user_id: '',
  class_level: '',
  stream: '',
  is_active: true,
})

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.clearErrors()
  form.is_active = true
}

const editAssignment = (assignment) => {
  editingId.value = assignment.id
  form.user_id = assignment.user_id
  form.class_level = assignment.class_level || ''
  form.stream = assignment.stream || ''
  form.is_active = !!assignment.is_active
  form.clearErrors()
}

const prefillFromUnassignedTeacher = (teacher) => {
  editingId.value = null
  form.user_id = teacher.id
}

const submit = () => {
  if (editingId.value) {
    form.put(route('admin.class-teacher-assignments.update', editingId.value), {
      preserveScroll: true,
      onSuccess: () => resetForm(),
    })
    return
  }

  form.post(route('admin.class-teacher-assignments.store'), {
    preserveScroll: true,
    onSuccess: () => resetForm(),
  })
}

const removeAssignment = (id) => {
  if (!confirm('Remove this class teacher assignment?')) return

  form.delete(route('admin.class-teacher-assignments.destroy', id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Class Teacher Assignments" />

  <div class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-6xl px-6 py-10">
      <header>
        <p class="text-xs uppercase tracking-[0.28em] text-emerald-300">Principal Tools</p>
        <h1 class="mt-2 text-3xl font-semibold">Class Teacher Assignments</h1>
        <p class="mt-2 text-sm text-slate-300">
          Assign each class teacher to one class/stream so class-scoped access works for students, discipline, and marks.
        </p>
      </header>

      <section class="mt-6 grid gap-3 sm:grid-cols-3">
        <Link
          :href="route('admin.class-teacher-assignments.index', { view: 'assigned' })"
          class="border border-white/10 bg-white/[0.02] px-4 py-4 hover:bg-white/[0.05]"
          :class="activeView === 'assigned' ? 'ring-1 ring-emerald-300/50' : ''"
        >
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Assigned Class Teachers</p>
          <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ summary.assigned_class_teachers }}</p>
        </Link>

        <Link
          :href="route('admin.class-teacher-assignments.index', { view: 'unassigned_teachers' })"
          class="border border-white/10 bg-white/[0.02] px-4 py-4 hover:bg-white/[0.05]"
          :class="activeView === 'unassigned_teachers' ? 'ring-1 ring-amber-300/50' : ''"
        >
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Unassigned Class Teachers</p>
          <p class="mt-2 text-2xl font-semibold text-amber-300">{{ summary.unassigned_class_teachers }}</p>
        </Link>

        <Link
          :href="route('admin.class-teacher-assignments.index', { view: 'unassigned_classes' })"
          class="border border-white/10 bg-white/[0.02] px-4 py-4 hover:bg-white/[0.05]"
          :class="activeView === 'unassigned_classes' ? 'ring-1 ring-rose-300/50' : ''"
        >
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Classes Without Assignment</p>
          <p class="mt-2 text-2xl font-semibold text-rose-300">{{ summary.classes_without_assignment }}</p>
        </Link>
      </section>

      <div class="mt-3 flex gap-2 text-sm">
        <Link
          :href="route('admin.class-teacher-assignments.index', { view: 'all' })"
          class="border border-white/20 px-3 py-1 hover:bg-white/10"
          :class="activeView === 'all' ? 'bg-white/10' : ''"
        >
          View All Assignments
        </Link>
      </div>

      <section class="mt-8 border border-white/10 p-5">
        <h2 class="text-lg font-semibold">{{ editingId ? 'Edit Assignment' : 'Create Assignment' }}</h2>

        <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
          <select v-model="form.user_id" class="border border-white/20 bg-slate-900 px-3 py-2">
            <option value="">Select Class Teacher</option>
            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }} ({{ teacher.email }})
            </option>
          </select>

          <input v-model="form.class_level" type="text" placeholder="Class Level (e.g. Grade 10 / Form 3)" class="border border-white/20 bg-slate-900 px-3 py-2" />
          <input v-model="form.stream" type="text" placeholder="Stream (e.g. Blue)" class="border border-white/20 bg-slate-900 px-3 py-2" />

          <label class="flex items-center gap-2 border border-white/20 bg-slate-900 px-3 py-2">
            <input v-model="form.is_active" type="checkbox" />
            <span>Active Assignment</span>
          </label>

          <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="border border-emerald-300 bg-emerald-300 px-4 py-2 font-semibold text-slate-950" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : (editingId ? 'Update Assignment' : 'Create Assignment') }}
            </button>
            <button type="button" class="border border-white/20 px-4 py-2 hover:bg-white/10" @click="resetForm">
              Reset
            </button>
          </div>

          <p v-for="(msg, key) in form.errors" :key="key" class="sm:col-span-2 text-sm text-rose-300">{{ msg }}</p>
        </form>
      </section>

      <section v-if="activeView === 'unassigned_teachers'" class="mt-8 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Teacher</th>
              <th class="px-4 py-3 font-medium">Email</th>
              <th class="px-4 py-3 font-medium">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="teacher in unassignedTeachers" :key="teacher.id" class="border-b border-white/5">
              <td class="px-4 py-3">{{ teacher.name }}</td>
              <td class="px-4 py-3 text-slate-300">{{ teacher.email }}</td>
              <td class="px-4 py-3">
                <button class="border border-white/20 px-2 py-1 hover:bg-white/10" @click="prefillFromUnassignedTeacher(teacher)">
                  Assign Now
                </button>
              </td>
            </tr>
            <tr v-if="!unassignedTeachers.length">
              <td colspan="3" class="px-4 py-8 text-center text-slate-400">No unassigned class teachers.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-else-if="activeView === 'unassigned_classes'" class="mt-8 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Class Level</th>
              <th class="px-4 py-3 font-medium">Stream</th>
              <th class="px-4 py-3 font-medium">Student Count</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in unassignedClasses" :key="`${row.class_level}-${row.stream}`" class="border-b border-white/5">
              <td class="px-4 py-3">{{ row.class_level }}</td>
              <td class="px-4 py-3">{{ row.stream || '-' }}</td>
              <td class="px-4 py-3">{{ row.students_count }}</td>
            </tr>
            <tr v-if="!unassignedClasses.length">
              <td colspan="3" class="px-4 py-8 text-center text-slate-400">All student classes have active assignment coverage.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section v-else class="mt-8 overflow-x-auto border border-white/10">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-white/10 text-slate-300">
            <tr>
              <th class="px-4 py-3 font-medium">Teacher</th>
              <th class="px-4 py-3 font-medium">Email</th>
              <th class="px-4 py-3 font-medium">Class Level</th>
              <th class="px-4 py-3 font-medium">Stream</th>
              <th class="px-4 py-3 font-medium">Status</th>
              <th class="px-4 py-3 font-medium">Updated</th>
              <th class="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="assignment in assignments" :key="assignment.id" class="border-b border-white/5">
              <td class="px-4 py-3">{{ assignment.teacher_name || '-' }}</td>
              <td class="px-4 py-3 text-slate-300">{{ assignment.teacher_email || '-' }}</td>
              <td class="px-4 py-3">{{ assignment.class_level }}</td>
              <td class="px-4 py-3">{{ assignment.stream || '-' }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center border px-2 py-1 text-xs"
                  :class="assignment.is_active ? 'border-emerald-400/40 text-emerald-300 bg-emerald-500/10' : 'border-slate-400/40 text-slate-300 bg-slate-500/10'"
                >
                  {{ assignment.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-400">{{ assignment.updated_at }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button class="border border-white/20 px-2 py-1 hover:bg-white/10" @click="editAssignment(assignment)">Edit</button>
                  <button class="border border-rose-400/60 px-2 py-1 text-rose-300 hover:bg-rose-500/10" @click="removeAssignment(assignment.id)">Delete</button>
                </div>
              </td>
            </tr>

            <tr v-if="!assignments.length">
              <td colspan="7" class="px-4 py-8 text-center text-slate-400">No class teacher assignments yet.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</template>