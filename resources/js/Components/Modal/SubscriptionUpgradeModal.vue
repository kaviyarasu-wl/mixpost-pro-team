<script setup>
import {computed} from "vue";
import DialogModal from "@/Components/Modal/DialogModal.vue"
import ExclamationIcon from "@/Icons/Exclamation.vue"
import PrimaryButton from "@/Components/Button/PrimaryButton.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import {gwUrl} from "@/helpers.js"

const emit = defineEmits(['close']);

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    variant: {
        type: String,
        default: 'warning'
    },
    zIndexClass: {
        type: String,
        default: 'z-30'
    }
});

const exclamationBgClasses = computed(() => {
    return {
        'warning': 'bg-orange-100',
        'danger': 'bg-red-100',
    }[props.variant];
});

const exclamationIconClasses = computed(() => {
    return {
        'warning': 'text-orange-600',
        'danger': 'text-red-600',
    }[props.variant];
});

const close = () => {
    emit('close');
};

const handleUpgrade = () => {
    // Redirect to subscription page using gwUrl helper
    // window.location.href = gwUrl('credit-based?target=subscription');
    window.open(gwUrl('credit-based?target=subscription'), '_blank');
};
</script>

<template>
    <DialogModal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        :z-index-class="zIndexClass"
        @close="close"
    >
        <template #body>
            <div class="sm:flex sm:items-start">
                <div :class="exclamationBgClasses" class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                   <ExclamationIcon :class="exclamationIconClasses"/>
                </div>

                <div class="flex flex-col text-center sm:ml-md mt-md sm:mt-0 sm:text-left">
                    <div class="text-lg font-medium text-gray-900">
                        {{ $t('subscription.upgrade_required') }}
                    </div>
                    <div class="mt-xs text-sm text-gray-500">
                        {{ $t('subscription.plan_limit_reached_message') }}
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end space-x-3">
                <SecondaryButton @click="close">
                    {{ $t('general.cancel') }}
                </SecondaryButton>
                <PrimaryButton @click="handleUpgrade">
                    {{ $t('subscription.upgrade_plan') }}
                </PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>
