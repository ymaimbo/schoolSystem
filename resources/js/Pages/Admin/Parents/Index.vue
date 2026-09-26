<!-- resources/js/Pages/Admin/Parents/Index.vue -->
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
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Parent and Guardian Details</h1>
        <p class="mt-1 text-sm text-slate-300">Managed by School Secretary.</p>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <input
          :value="filters.search"
          @input="doSearch($event.target.value)"
          placeholder="Search by name or admission number"
          class="w-full rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-emerald-400 md:w-96"
        />
      </section>

      <section class="space-y-4">
        <div v-for="student in students.data" :key="student.id" class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
          <div class="mb-3">
            <p class="font-semibold text-white">{{ student.first_name }} {{ student.last_name }}</p>
            <p class="text-xs text-slate-400">{{ student.admission_no }}</p>
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <input v-model="student.parent_name" placeholder="Parent name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
            <input v-model="student.parent_phone" placeholder="Parent phone" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
            <input v-model="student.guardian_name" placeholder="Guardian name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
            <input v-model="student.guardian_phone" placeholder="Guardian phone" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
            <input v-model="student.guardian_relationship" placeholder="Guardian relationship" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
            <select v-model="student.contact_preference" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100">
              <option value="parent">Parent</option>
              <option value="guardian">Guardian</option>
            </select>
          </div>

          <button class="mt-3 rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400" @click="updateContact(student)">
            Save Contact
          </button>
        </div>
      </section>
    </div>
  </AdminLayout>
</template>