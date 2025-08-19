<script setup>
import {nextTick, onMounted, ref} from 'vue';

defineProps({
    modelValue: {},
    error: {},
    readonly: {
        type: Boolean,
        default: false
    }
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        nextTick(() => {
            input.value.focus();
        })
    }
});
</script>

<template>
    <input :value="modelValue"
           @input="$emit('update:modelValue', $event.target.value)"
           ref="input"
           :readonly="readonly"
           :class="{'border-stone-600': !error, 'border-red-600': error, 'bg-gray-100': readonly}"
           class="w-full rounded-md focus:border-primary-200 focus:ring-primary-600 outline-none transition-colors ease-in-out duration-200">
</template>
