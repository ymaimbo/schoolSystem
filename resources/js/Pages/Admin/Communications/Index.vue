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
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Parent Communications</h1>
        <p class="text-sm text-slate-600">Send notices and student results to parents/guardians.</p>
      </div>

      <div class="border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-semibold">Select Students</h2>
        <div class="grid gap-2 md:grid-cols-2">
          <label v-for="s in students" :key="s.id" class="flex items-center gap-2 text-sm">
            <input type="checkbox" :value="s.id" v-model="selected" />
            <span>{{ s.admission_no }} - {{ s.first_name }} {{ s.last_name }}</span>
          </label>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <div class="border border-slate-200 bg-white p-4">
          <h2 class="mb-3 text-lg font-semibold">Send Notice</h2>
          <textarea v-model="noticeForm.message" rows="6" class="w-full border border-slate-300 px-3 py-2" placeholder="Write notice message"></textarea>
          <button class="mt-3 bg-slate-900 px-4 py-2 text-white hover:bg-slate-700" @click="sendNotice">
            Send Notice
          </button>
        </div>

        <div class="border border-slate-200 bg-white p-4">
          <h2 class="mb-3 text-lg font-semibold">Send Results</h2>
          <select v-model="resultForm.exam_id" class="w-full border border-slate-300 px-3 py-2">
            <option value="">Use latest result per student</option>
            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
              {{ exam.title }} - {{ exam.term }} {{ exam.year }}
            </option>
          </select>
          <button class="mt-3 bg-slate-900 px-4 py-2 text-white hover:bg-slate-700" @click="sendResults">
            Send Results
          </button>
        </div>
      </div>

      <div class="border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-semibold">Recent Messages</h2>
        <div class="space-y-2 text-sm">
          <div v-for="msg in latestMessages" :key="msg.id" class="border-b border-slate-100 pb-2">
            <p>
              <strong>{{ msg.message_type.toUpperCase() }}</strong>
              to {{ msg.recipient_name }} ({{ msg.recipient_phone }})
              - {{ msg.sent_at }}
            </p>
            <p class="text-slate-600">{{ msg.message_body }}</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>