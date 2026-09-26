<!-- resources/js/Pages/Admin/Store/Index.vue -->
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
    label: String(l.label).replace('&laquo;', '<<').replace('&raquo;', '>>'),
  }))
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
      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Store</h1>
        <p class="mt-1 text-sm text-slate-300">Manage inventory items, stock levels, and reorder points.</p>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Filters</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-4" @submit.prevent="applyFilters">
          <input
            v-model="filterForm.search"
            class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-emerald-400 md:col-span-2"
            placeholder="Search item"
          />
          <input
            v-model="filterForm.category"
            class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-emerald-400"
            placeholder="Category"
          />
          <div class="flex items-center gap-2">
            <button type="submit" class="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">Filter</button>
            <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="clearFilters">Clear</button>
          </div>
        </form>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="text-lg font-semibold text-white">Add Item</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-3" @submit.prevent="submitCreate">
          <input v-model="createForm.item_name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Item Name" />
          <input v-model="createForm.category" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Category" />
          <input v-model="createForm.quantity" type="number" step="0.01" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Quantity" />
          <input v-model="createForm.unit" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Unit (pcs, kg...)" />
          <input v-model="createForm.reorder_level" type="number" step="0.01" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Reorder Level" />
          <textarea v-model="createForm.notes" rows="2" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 md:col-span-2" placeholder="Notes" />
          <button class="rounded-md bg-cyan-500 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 md:col-span-3 md:w-fit" type="submit">Add Item</button>
        </form>
      </section>

      <section class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
        <h2 class="mb-4 text-lg font-semibold text-white">Inventory Items</h2>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead class="bg-slate-950/50 text-left text-slate-300">
              <tr>
                <th class="px-3 py-2">Item</th>
                <th class="px-3 py-2">Category</th>
                <th class="px-3 py-2">Qty</th>
                <th class="px-3 py-2">Unit</th>
                <th class="px-3 py-2">Reorder</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="item in items.data" :key="item.id">
                <td class="px-3 py-2 text-white">{{ item.item_name }}</td>
                <td class="px-3 py-2 text-slate-300">{{ item.category }}</td>
                <td class="px-3 py-2 text-slate-200">{{ item.quantity }}</td>
                <td class="px-3 py-2 text-slate-300">{{ item.unit || '-' }}</td>
                <td class="px-3 py-2 text-amber-300">{{ item.reorder_level ?? '-' }}</td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-2">
                    <button class="rounded border border-cyan-300/40 px-2 py-1 text-xs font-medium text-cyan-200 hover:bg-cyan-400/10" @click="openEdit(item)">Edit</button>
                    <button class="rounded border border-rose-300/40 px-2 py-1 text-xs font-medium text-rose-200 hover:bg-rose-400/10" @click="removeItem(item.id)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!items.data?.length">
                <td colspan="6" class="px-3 py-6 text-center text-slate-400">No items found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button
            v-for="l in links"
            :key="l.label + String(l.url)"
            class="rounded-md border border-white/20 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-white/5 disabled:opacity-40"
            :class="l.active ? 'border-cyan-300/40 text-cyan-200' : ''"
            :disabled="!l.url"
            @click="l.url && router.visit(l.url, { preserveState: true, preserveScroll: true })"
            v-html="l.label"
          />
        </div>
      </section>

      <div v-if="editOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-2xl rounded-xl border border-white/10 bg-slate-900 p-5">
          <h2 class="text-lg font-semibold text-white">Edit Item</h2>
          <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitEdit">
            <input v-model="editForm.item_name" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Item Name" />
            <input v-model="editForm.category" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Category" />
            <input v-model="editForm.quantity" type="number" step="0.01" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Quantity" />
            <input v-model="editForm.unit" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Unit" />
            <input v-model="editForm.reorder_level" type="number" step="0.01" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400" placeholder="Reorder Level" />
            <textarea v-model="editForm.notes" rows="2" class="rounded-md border border-white/15 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none focus:border-cyan-400 md:col-span-2" placeholder="Notes" />

            <div class="md:col-span-2 flex justify-end gap-2 pt-2">
              <button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-white/5" @click="closeEdit">Cancel</button>
              <button class="rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400" type="submit">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>