<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  items: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({ search: '', category: '' }) },
})

const filterForm = useForm({
  search: props.filters.search ?? '',
  category: props.filters.category ?? '',
})

const createForm = useForm({
  item_name: '',
  category: '',
  quantity: 0,
  unit: '',
  reorder_level: 0,
  notes: '',
})

const editOpen = ref(false)
const editId = ref(null)
const editForm = useForm({
  item_name: '',
  category: '',
  quantity: 0,
  unit: '',
  reorder_level: 0,
  notes: '',
})

const links = computed(() =>
  (props.items.links ?? []).map((l) => ({
    ...l,
    label: String(l.label).replace('&laquo;', '«').replace('&raquo;', '»'),
  })),
)

const applyFilters = () => {
  router.get(route('admin.store.index'), filterForm.data(), { preserveState: true, replace: true })
}

const clearFilters = () => {
  filterForm.reset()
  applyFilters()
}

const submitCreate = () => {
  createForm.post(route('admin.store.store'), {
    preserveScroll: true,
    onSuccess: () => createForm.reset(),
  })
}

const openEdit = (item) => {
  editId.value = item.id
  editForm.item_name = item.item_name ?? ''
  editForm.category = item.category ?? ''
  editForm.quantity = item.quantity ?? 0
  editForm.unit = item.unit ?? ''
  editForm.reorder_level = item.reorder_level ?? 0
  editForm.notes = item.notes ?? ''
  editOpen.value = true
}

const closeEdit = () => {
  editOpen.value = false
  editId.value = null
  editForm.clearErrors()
}

const submitEdit = () => {
  editForm.put(route('admin.store.update', editId.value), {
    preserveScroll: true,
    onSuccess: () => closeEdit(),
  })
}

const removeItem = (id) => {
  if (!confirm('Delete this inventory item?')) return
  router.delete(route('admin.store.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Store" />
  <AdminLayout>
    <div class="space-y-6">
      <form class="grid gap-3 border bg-white p-4 md:grid-cols-4" @submit.prevent="applyFilters">
        <input v-model="filterForm.search" class="border px-3 py-2" placeholder="Search item" />
        <input v-model="filterForm.category" class="border px-3 py-2" placeholder="Category" />
        <button class="bg-slate-900 px-4 py-2 text-white">Filter</button>
        <button type="button" class="border px-4 py-2" @click="clearFilters">Clear</button>
      </form>

      <form class="grid gap-3 border bg-white p-4 md:grid-cols-3" @submit.prevent="submitCreate">
        <input v-model="createForm.item_name" class="border px-3 py-2" placeholder="Item Name" />
        <input v-model="createForm.category" class="border px-3 py-2" placeholder="Category" />
        <input v-model="createForm.quantity" type="number" step="0.01" class="border px-3 py-2" placeholder="Quantity" />
        <input v-model="createForm.unit" class="border px-3 py-2" placeholder="Unit (pcs, kg...)" />
        <input v-model="createForm.reorder_level" type="number" step="0.01" class="border px-3 py-2" placeholder="Reorder Level" />
        <textarea v-model="createForm.notes" rows="2" class="border px-3 py-2 md:col-span-3" placeholder="Notes" />
        <button class="bg-slate-900 px-4 py-2 text-white md:col-span-3">Add Item</button>
      </form>

      <div class="overflow-x-auto border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50"><tr>
            <th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-left">Category</th><th class="px-3 py-2 text-left">Qty</th><th class="px-3 py-2 text-left">Unit</th><th class="px-3 py-2 text-left">Reorder</th><th class="px-3 py-2 text-left">Actions</th>
          </tr></thead>
          <tbody>
            <tr v-for="item in items.data" :key="item.id" class="border-t">
              <td class="px-3 py-2">{{ item.item_name }}</td>
              <td class="px-3 py-2">{{ item.category }}</td>
              <td class="px-3 py-2">{{ item.quantity }}</td>
              <td class="px-3 py-2">{{ item.unit || '-' }}</td>
              <td class="px-3 py-2">{{ item.reorder_level ?? '-' }}</td>
              <td class="px-3 py-2">
                <button class="mr-3 text-blue-700" @click="openEdit(item)">Edit</button>
                <button class="text-rose-700" @click="removeItem(item.id)">Delete</button>
              </td>
            </tr>
            <tr v-if="!items.data?.length"><td colspan="6" class="px-3 py-5 text-center text-slate-500">No items found.</td></tr>
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
        <h2 class="text-lg font-semibold">Edit Item</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
          <input v-model="editForm.item_name" class="border px-3 py-2" placeholder="Item Name" />
          <input v-model="editForm.category" class="border px-3 py-2" placeholder="Category" />
          <input v-model="editForm.quantity" type="number" step="0.01" class="border px-3 py-2" placeholder="Quantity" />
          <input v-model="editForm.unit" class="border px-3 py-2" placeholder="Unit" />
          <input v-model="editForm.reorder_level" type="number" step="0.01" class="border px-3 py-2" placeholder="Reorder Level" />
          <textarea v-model="editForm.notes" rows="2" class="border px-3 py-2 md:col-span-2" placeholder="Notes" />
          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" class="border px-4 py-2" @click="closeEdit">Cancel</button>
            <button class="bg-slate-900 px-4 py-2 text-white">Save</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>