<script setup>
import PrimaryButton from '@/Components/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Button/SecondaryButton.vue';
import ChevronLeftIcon from '@/Icons/ChevronLeft.vue';
import ChevronRightIcon from '@/Icons/ChevronRight.vue';

const props = defineProps({
    currentYear: {
        type: Number,
        required: true
    },
    selectedYear: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['previousYear', 'nextYear', 'today']);
</script>
<template>
    <div class="flex items-center space-x-xs">
        <SecondaryButton @click="emit('previousYear')" size="sm" square>
            <ChevronLeftIcon/>
        </SecondaryButton>

        <PrimaryButton @click="emit('today')" :class="{'!bg-gray-500': currentYear !== selectedYear}" size="sm">
            {{ $t('calendar.today') }}
        </PrimaryButton>

        <SecondaryButton @click="emit('nextYear')" size="sm" square>
            <ChevronRightIcon/>
        </SecondaryButton>
        
        <div class="text-lg font-semibold ml-sm">
            {{ selectedYear }}
        </div>
    </div>
</template>