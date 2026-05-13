<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  programs: { type: Object, default: () => ({ data: [], links: [] }) },
})

const createForm = useForm({
  title: '',
  summary: '',
  details: '',
  image_path: '',
  sort_order: 0,
  is_active: true,
})

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  title: '',
  summary: '',
  details: '',
  image_path: '',
  sort_order: 0,
  is_active: true,
})

const links = computed(() =>
  (props.programs.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  })),
)

const submitCreate = () => {
  createForm.post(route('admin.programs.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset(),
  })
}

const openEdit = (p) => {
  editId.value = p.id
  editForm.title = p.title ?? ''
  editForm.summary = p.summary ?? ''
  editForm.details = p.details ?? ''
  editForm.image_path = p.image_path ?? ''
  editForm.sort_order = p.sort_order ?? 0
  editForm.is_active = Boolean(p.is_active)
  editOpen.value = true
}

const closeEdit = () => {
  editOpen.value = false
  editId.value = null
  editForm.clearErrors()
}

const submitEdit = () => {
  editForm.put(route('admin.programs.update', editId.value), {
    preserveScroll: true,
    onSuccess: () => closeEdit(),
  })
}

const removeProgram = (id) => {
  if (!confirm('Delete this program?')) return
  router.delete(route('admin.programs.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Programs" />
  <AdminLayout>
    <div class="space-y-6">
      <form class="grid gap-3 border bg-white p-4 md:grid-cols-2" @submit.prevent="submitCreate">
        <input v-model="createForm.title" class="border px-3 py-2" placeholder="Program Title" />
        <input v-model="createForm.image_path" class="border px-3 py-2" placeholder="Image Path" />
        <textarea v-model="createForm.summary" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="Summary" />
        <textarea v-model="createForm.details" rows="3" class="border px-3 py-2 md:col-span-2" placeholder="Details" />
        <input v-model="createForm.sort_order" type="number" class="border px-3 py-2" placeholder="Sort Order" />
        <label class="flex items-center gap-2 border px-3 py-2">
          <input v-model="createForm.is_active" type="checkbox" />
          Active
        </label>
        <button class="bg-slate-900 px-4 py-2 text-white md:col-span-2">Add Program</button>
      </form>

      <div class="overflow-x-auto border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left">Title</th>
              <th class="px-3 py-2 text-left">Summary</th>
              <th class="px-3 py-2 text-left">Sort</th>
              <th class="px-3 py-2 text-left">Active</th>
              <th class="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in programs.data" :key="p.id" class="border-t">
              <td class="px-3 py-2">{{ p.title }}</td>
              <td class="px-3 py-2">{{ p.summary }}</td>
              <td class="px-3 py-2">{{ p.sort_order }}</td>
              <td class="px-3 py-2">{{ p.is_active ? 'Yes' : 'No' }}</td>
              <td class="px-3 py-2">
                <button class="mr-3 text-blue-700" @click="openEdit(p)">Edit</button>
                <button class="text-rose-700" @click="removeProgram(p.id)">Delete</button>
              </td>
            </tr>
            <tr v-if="!programs.data?.length"><td colspan="5" class="px-3 py-5 text-center text-slate-500">No programs found.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          v-for="l in links"
          :key="l.label + String(l.url)"
          class="border px-3 py-1 text-sm"
          :class="l.active ? 'bg-slate-900 text-white' : 'bg-white'"
          :disabled="!l.url"
          @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
          v-html="l.label"
        />
      </div>
    </div>

    <div v-if="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-2xl border bg-white p-5">
        <h2 class="text-lg font-semibold">Edit Program</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
          <input v-model="editForm.title" class="border px-3 py-2" placeholder="Program Title" />
          <input v-model="editForm.image_path" class="border px-3 py-2" placeholder="Image Path" />
          <textarea v-model="editForm.summary" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="Summary" />
          <textarea v-model="editForm.details" rows="3" class="border px-3 py-2 md:col-span-2" placeholder="Details" />
          <input v-model="editForm.sort_order" type="number" class="border px-3 py-2" placeholder="Sort Order" />
          <label class="flex items-center gap-2 border px-3 py-2"><input v-model="editForm.is_active" type="checkbox" /> Active</label>
          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" class="border px-4 py-2" @click="closeEdit">Cancel</button>
            <button class="bg-slate-900 px-4 py-2 text-white">Save</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>