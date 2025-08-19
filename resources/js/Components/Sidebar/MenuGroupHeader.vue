<script setup>
import {Link} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";

const {t: $t} = useI18n();

defineProps({
    createUrl: {
        type: String,
        default: null
    },
    tooltipText: {
        type: String,
        default: null
    }
});
</script>
<template>
    <div class="flex justify-between items-center">
        <div class="font-semibold text-black"><slot/></div>
        <template v-if="createUrl">
            <Link :href="createUrl" class="py-2 text-gray-400 hover:text-primary-500 transition-colors ease-in-out duration-200" v-tooltip="tooltipText || $t('post.create_post')">
                <slot name="icon" />
            </Link>
        </template>
    </div>
</template>
