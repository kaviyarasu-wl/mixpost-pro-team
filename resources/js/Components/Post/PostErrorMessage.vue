<script setup>
const props = defineProps({
    errors: {
        required: true,
        type: Array
    }
});
</script>
<template>
    <div class="overflow-x-auto">
        <template v-for="(error, index) in errors" :key="index">
            <div v-if="typeof error === 'string'">
                <div class="hyphens-none">{{ error }}</div>
            </div>
            <div v-else-if="error.message">
                <div class="hyphens-none">{{ error.message }}</div>
            </div>
            <div v-else-if="error.errors">
                <template v-for="(error, index) in error.errors" :key="index">
                    <div class="hyphens-none">{{ error.message }}</div>
                </template>
            </div>
            <div v-else-if="error.error_description">
                <div class="hyphens-none">{{ error.error_description }}</div>
            </div>
            <div v-else-if="error.error?.message">
                <div class="hyphens-none">{{ error.error.message }}</div>
            </div>
            <div v-else>
                <div class="hyphens-none">{{ JSON.stringify(error) }}</div>
            </div>
        </template>
    </div>
</template>
