<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  maxWidth: {
    type: String,
    default: 'max-w-3xl',
  },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed(() => props.modelValue)

const close = () => {
  emit('update:modelValue', false)
}

const onEscape = (event) => {
  if (event.key === 'Escape' && isOpen.value) {
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onEscape)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
      @click.self="close"
    >
      <div :class="['w-full border border-slate-200 bg-white p-5 shadow-xl', maxWidth]">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
          <button
            type="button"
            class="px-2 py-1 text-sm font-medium text-slate-600 hover:text-slate-900"
            @click="close"
          >
            Close
          </button>
        </div>
        <slot />
      </div>
    </div>
  </Teleport>
</template>