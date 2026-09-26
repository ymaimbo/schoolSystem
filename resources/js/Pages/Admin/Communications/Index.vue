<!-- resources/js/Pages/Admin/Communications/Index.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  students: { type: Array, default: () => [] },
  exams: { type: Array, default: () => [] },
  latestMessages: { type: Array, default: () => [] },
})

const selected = ref([])

const noticeForm = useForm({
  student_ids: [],
  message: '',
})

const resultForm = useForm({
  student_ids: [],
  exam_id: '',
})

const syncSelections = () => {
  noticeForm.student_ids = [...selected.value]
  resultForm.student_ids = [...selected.value]
}

const sendNotice = () => {
  syncSelections()
  noticeForm.post(route('admin.communications.notice'), { preserveScroll: true })
}

const sendResults = () => {
  syncSelections()
  resultForm.post(route('admin.communications.results'), { preserveScroll: true })
}
</script>

<template>
  <Head title="Parent Communications" />
  <AdminLayout>
    <div class="space-y-6">
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Parent Communications</h1>
        <p class="mt-1 text-sm text-slate-300">Send notices and student results to parents/guardians.</p>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-3 text-lg font-semibold text-white">Select Students</h2>

        <div class="grid gap-2 md:grid-cols-2">
          <label
            v-for="s in students"
            :key="s.id"
            class="flex items-center gap-2 rounded-md border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-200"
          >
            <input
              type="checkbox"
              :value="s.id"
              v-model="selected"
              class="h-4 w-4 rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400"
            />
            <span>{{ s.admission_no }} - {{ s.first_name }} {{ s.last_name }}</span>
          </label>
        </div>
      </section>

      <section class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-3 text-lg font-semibold text-white">Send Notice</h2>
          <textarea
            v-model="noticeForm.message"
            rows="6"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-cyan-400"
            placeholder="Write notice message"
          ></textarea>
          <button
            class="mt-3 rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400"
            @click="sendNotice"
          >
            Send Notice
          </button>
        </div>

        <div class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <h2 class="mb-3 text-lg font-semibold text-white">Send Results</h2>
          <select
            v-model="resultForm.exam_id"
            class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400"
          >
            <option value="">Use latest result per student</option>
            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
              {{ exam.title }} - {{ exam.term }} {{ exam.year }}
            </option>
          </select>
          <button
            class="mt-3 rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
            @click="sendResults"
          >
            Send Results
          </button>
        </div>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-3 text-lg font-semibold text-white">Recent Messages</h2>

        <div class="space-y-3 text-sm">
          <div
            v-for="msg in latestMessages"
            :key="msg.id"
            class="rounded-lg border border-white/10 bg-slate-950/60 p-3"
          >
            <p class="text-slate-200">
              <strong class="text-white">{{ msg.message_type.toUpperCase() }}</strong>
              to {{ msg.recipient_name }} ({{ msg.recipient_phone }})
              - {{ msg.sent_at }}
            </p>
            <p class="mt-1 text-slate-300">{{ msg.message_body }}</p>
          </div>

          <p v-if="!latestMessages.length" class="text-slate-400">No recent messages found.</p>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>