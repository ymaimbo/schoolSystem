<!-- resources/js/Pages/Admin/Timetable/Index.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const form = useForm({
  day_of_week: 'Monday',
  period_label: '',
  subject: '',
  teacher_name: '',
  class_level: '',
  stream: '',
  starts_at: '',
  ends_at: '',
  notes: '',
})

const submit = () => {
  form.post(route('admin.timetable.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('period_label', 'subject', 'teacher_name', 'class_level', 'stream', 'starts_at', 'ends_at', 'notes'),
  })
}

const remove = (id) => {
  if (!confirm('Delete this timetable slot?')) return
  useForm({}).delete(route('admin.timetable.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Timetable" />
  <AdminLayout>
    <div class="space-y-6">
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">School Timetable</h1>
        <p class="mt-1 text-sm text-slate-300">Create and maintain timetable slots.</p>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <form class="grid gap-3 md:grid-cols-3" @submit.prevent="submit">
          <select v-model="form.day_of_week" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400">
            <option>Monday</option>
            <option>Tuesday</option>
            <option>Wednesday</option>
            <option>Thursday</option>
            <option>Friday</option>
          </select>

          <input v-model="form.period_label" placeholder="Period (e.g. P1)" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.subject" placeholder="Subject" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.teacher_name" placeholder="Teacher name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.class_level" placeholder="Class level (Form 2 / Grade 10)" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.stream" placeholder="Stream" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.starts_at" type="time" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.ends_at" type="time" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" />
          <input v-model="form.notes" placeholder="Notes" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 md:col-span-2" />

          <button type="submit" class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 md:w-fit">
            Save Slot
          </button>
        </form>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Day</th>
                <th class="px-3 py-2">Period</th>
                <th class="px-3 py-2">Subject</th>
                <th class="px-3 py-2">Class</th>
                <th class="px-3 py-2">Time</th>
                <th class="px-3 py-2">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="item in items" :key="item.id">
                <td class="px-3 py-2 text-slate-200">{{ item.day_of_week }}</td>
                <td class="px-3 py-2 text-slate-200">{{ item.period_label }}</td>
                <td class="px-3 py-2 text-white">{{ item.subject }}</td>
                <td class="px-3 py-2 text-slate-300">{{ item.class_level }} {{ item.stream ? `(${item.stream})` : '' }}</td>
                <td class="px-3 py-2 text-slate-300">{{ item.starts_at || '' }} - {{ item.ends_at || '' }}</td>
                <td class="px-3 py-2">
                  <button class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10" @click="remove(item.id)">
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="!items.length">
                <td colspan="6" class="px-3 py-6 text-center text-slate-400">No timetable slots found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>