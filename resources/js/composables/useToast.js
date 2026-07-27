import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

function remove(id) {
  toasts.value = toasts.value.filter((t) => t.id !== id);
}

function push(type, title, message = '') {
  const item = { id: ++nextId, type, title, message };
  toasts.value.push(item);
  setTimeout(() => remove(item.id), 5500);
  return item.id;
}

export function useToast() {
  return {
    toasts,
    success: (title, message) => push('success', title, message),
    error: (title, message) => push('error', title, message),
    info: (title, message) => push('info', title, message),
    remove,
  };
}
