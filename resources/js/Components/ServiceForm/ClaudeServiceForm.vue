<script setup>
import {useI18n} from "vue-i18n";
import {router} from "@inertiajs/vue3";
import {ref, computed} from "vue";
import useNotifications from "@/Composables/useNotifications";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Error from "@/Components/Form/Error.vue";
import InputHidden from "../Form/InputHidden.vue";
import Checkbox from "../Form/Checkbox.vue";
import Flex from "../Layout/Flex.vue";
import Label from "../Form/Label.vue";
import LabelSuffix from "../Form/LabelSuffix.vue";
import HorizontalGroup from "../Layout/HorizontalGroup.vue";
import Select from "../Form/Select.vue";
import Badge from "../DataDisplay/Badge.vue";
import Claude from "../../Icons/Claude.vue";

const {t: $t} = useI18n()

const props = defineProps({
    form: {
        required: true,
        type: Object
    }
})

const {notify} = useNotifications();
const errors = ref({});

// Define available Claude models
const claudeModels = computed(() => [
    {
        value: 'claude-opus-4-20250514',
        label: 'Claude 4 Opus',
        description: 'Most capable model with highest level of intelligence and capability',
        badge: 'Latest',
        badgeType: 'success',
        available: true,
    },
    {
        value: 'claude-sonnet-4-20250514',
        label: 'Claude 4 Sonnet',
        description: 'High-performance model with balanced intelligence and performance',
        badge: 'Latest',
        badgeType: 'success',
        available: true,
    },
    {
        value: 'claude-3-7-sonnet-20250219',
        label: 'Claude 3.7 Sonnet',
        description: 'High performance with extended thinking capabilities',
        badge: 'Extended Thinking',
        badgeType: 'info',
        available: true,
    },
    {
        value: 'claude-3-5-haiku-20241022',
        label: 'Claude 3.5 Haiku',
        description: 'Fastest model with enhanced capabilities',
        badge: 'Fast',
        badgeType: 'info',
        available: true,
    },
    {
        value: 'claude-3-5-sonnet-20241022',
        label: 'Claude 3.5 Sonnet',
        description: 'Great balance of performance and cost',
        badge: 'Recommended',
        badgeType: 'success',
        available: true,
    },
    {
        value: 'claude-3-opus-20240229',
        label: 'Claude 3 Opus',
        description: 'Previous generation - powerful model for complex tasks',
        badge: 'Legacy',
        badgeType: 'gray',
        available: true,
    },
    {
        value: 'claude-3-sonnet-20240229',
        label: 'Claude 3 Sonnet',
        description: 'Previous generation - balanced performance',
        badge: 'Legacy',
        badgeType: 'gray',
        available: true,
    },
    {
        value: 'claude-3-haiku-20240307',
        label: 'Claude 3 Haiku',
        description: 'Previous generation - fastest model',
        badge: 'Legacy',
        badgeType: 'gray',
        available: true,
    },
]);

// Set default model if not already set
if (!props.form.configuration.model) {
    props.form.configuration.model = 'claude-3-5-sonnet-20241022';
}

const save = () => {
    errors.value = {};

    router.put(route('mixpost.services.update', {service: 'claude'}), props.form, {
        preserveScroll: true,
        onSuccess() {
            notify('success', $t('service.service_saved', {service: 'Claude'}));
        },
        onError: (err) => {
            errors.value = err;
        },
    });
}
</script>
<template>
    <Panel class="mt-lg">
        <template #title>
            <div class="flex items-center">
                <span class="mr-xs"><Claude class="text-claude"/></span>
                <span>Claude</span>
            </div>
        </template>

        <template #description>
            <p>
                <a href="https://console.anthropic.com/settings/keys" class="link" target="_blank">You can generate an
                    API key here</a>.
            </p>
        </template>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="secret_key">API Key <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <InputHidden v-model="form.configuration.secret_key"
                         :error="errors['configuration.secret_key'] !== undefined"
                         id="secret"
                         placeholder="sk-..."
                         autocomplete="new-password"/>

            <template #footer>
                <Error :message="errors['configuration.secret_key']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                <label for="model">Model <LabelSuffix danger>*</LabelSuffix></label>
            </template>

            <div class="w-full">
                <Select v-model="form.configuration.model"
                        :error="errors['configuration.model'] !== undefined"
                        id="model">
                    <option v-for="model in claudeModels"
                            :key="model.value"
                            :value="model.value"
                            :disabled="!model.available">
                        {{ model.label }}
                    </option>
                </Select>

                <!-- Model descriptions with badges -->
                <div class="mt-sm space-y-xs text-sm text-gray-600">
                    <div v-for="model in claudeModels"
                         :key="model.value"
                         v-show="form.configuration.model === model.value"
                         class="flex items-center gap-xs">
                        <Badge :class="model.badgeType">{{ model.badge }}</Badge>
                        <span>{{ model.description }}</span>
                    </div>
                </div>
            </div>

            <template #footer>
                <Error :message="errors['configuration.model']"/>
            </template>
        </HorizontalGroup>

        <HorizontalGroup class="mt-lg">
            <template #title>
                {{ $t('general.status') }}
</template>

            <Flex :responsive="false" class="items-center">
                <Checkbox v-model:checked="form.active" id="active"/>
                <Label for="active" class="!mb-0">{{ $t('general.active') }}</Label>
            </Flex>

            <template #footer>
                <Error :message="errors.active"/>
            </template>
        </HorizontalGroup>

        <PrimaryButton @click="save" class="mt-lg">{{ $t('general.save') }}</PrimaryButton>
    </Panel>
</template>
