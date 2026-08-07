<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'

const props = defineProps({
  students: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({ search: '' }) },
})

const updateContact = (student) => {
  const form = useForm({
    parent_name: student.parent_name || '',
    parent_phone: student.parent_phone || '',
    guardian_name: student.guardian_name || '',
    guardian_phone: student.guardian_phone || '',
    guardian_relationship: student.guardian_relationship || '',
    contact_preference: student.contact_preference || 'parent',
  })

  form.put(route('admin.parents.update', student.id), { preserveScroll: true })
}

const doSearch = (value) => {
  router.get(route('admin.parents.index'), { search: value }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Parent / Guardian Details" />
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Parent and Guardian Details</h1>
        <p class="text-sm text-slate-600">Managed by School Secretary.</p>
      </div>

      <input
        :value="filters.search"
        @input="doSearch($event.target.value)"
        placeholder="Search by name or admission number"
        class="w-full border border-slate-300 px-3 py-2 md:w-96"
      />

      <div class="space-y-4">
        <div v-for="student in students.data" :key="student.id" class="border border-slate-200 bg-white p-4">
          <div class="mb-3">
            <p class="font-semibold text-slate-900">{{ student.first_name }} {{ student.last_name }}</p>
            <p class="text-xs text-slate-500">{{ student.admission_no }}</p>
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <input v-model="student.parent_name" placeholder="Parent name" class="border border-slate-300 px-3 py-2" />
            <input v-model="student.parent_phone" placeholder="Parent phone" class="border border-slate-300 px-3 py-2" />
            <input v-model="student.guardian_name" placeholder="Guardian name" class="border border-slate-300 px-3 py-2" />
            <input v-model="student.guardian_phone" placeholder="Guardian phone" class="border border-slate-300 px-3 py-2" />
            <input v-model="student.guardian_relationship" placeholder="Guardian relationship" class="border border-slate-300 px-3 py-2" />
            <select v-model="student.contact_preference" class="border border-slate-300 px-3 py-2">
              <option value="parent">Parent</option>
              <option value="guardian">Guardian</option>
            </select>
          </div>

          <button class="mt-3 bg-slate-900 px-4 py-2 text-white hover:bg-slate-700" @click="updateContact(student)">
            Save Contact
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>