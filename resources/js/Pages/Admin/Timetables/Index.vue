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
      <div>
        <h1 class="text-2xl font-bold text-slate-900">School Timetable</h1>
        <p class="text-sm text-slate-600">Create and maintain timetable slots.</p>
      </div>

      <form class="grid gap-3 border border-slate-200 bg-white p-4 md:grid-cols-3" @submit.prevent="submit">
        <select v-model="form.day_of_week" class="border border-slate-300 px-3 py-2">
          <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option>
        </select>
        <input v-model="form.period_label" placeholder="Period (e.g. P1)" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.subject" placeholder="Subject" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.teacher_name" placeholder="Teacher name" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.class_level" placeholder="Class level (Form 2 / Grade 10)" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.stream" placeholder="Stream" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.starts_at" type="time" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.ends_at" type="time" class="border border-slate-300 px-3 py-2" />
        <input v-model="form.notes" placeholder="Notes" class="border border-slate-300 px-3 py-2 md:col-span-2" />
        <button type="submit" class="bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">Save Slot</button>
      </form>

      <div class="overflow-x-auto border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left">
            <tr>
              <th class="px-3 py-2">Day</th>
              <th class="px-3 py-2">Period</th>
              <th class="px-3 py-2">Subject</th>
              <th class="px-3 py-2">Class</th>
              <th class="px-3 py-2">Time</th>
              <th class="px-3 py-2">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="border-t">
              <td class="px-3 py-2">{{ item.day_of_week }}</td>
              <td class="px-3 py-2">{{ item.period_label }}</td>
              <td class="px-3 py-2">{{ item.subject }}</td>
              <td class="px-3 py-2">{{ item.class_level }} {{ item.stream ? `(${item.stream})` : '' }}</td>
              <td class="px-3 py-2">{{ item.starts_at || '-' }} - {{ item.ends_at || '-' }}</td>
              <td class="px-3 py-2">
                <button class="text-red-600 hover:underline" @click="remove(item.id)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>