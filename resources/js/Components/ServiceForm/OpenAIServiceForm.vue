<script setup>
import {useI18n} from "vue-i18n";
import {router} from "@inertiajs/vue3";
import {ref, computed} from "vue";
import useNotifications from "@/Composables/useNotifications";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Error from "@/Components/Form/Error.vue";
import ReadDocHelp from "@/Components/Util/ReadDocHelp.vue";
import InputHidden from "../Form/InputHidden.vue";
import OpenAI from "../../Icons/OpenAI.vue";
import Checkbox from "../Form/Checkbox.vue";
import Flex from "../Layout/Flex.vue";
import Label from "../Form/Label.vue";
import LabelSuffix from "../Form/LabelSuffix.vue";
import HorizontalGroup from "../Layout/HorizontalGroup.vue";
import Select from "../Form/Select.vue";
import Badge from "../DataDisplay/Badge.vue";

const {t: $t} = useI18n()

const props = defineProps({
    form: {
        required: true,
        type: Object
    }
})

const {notify} = useNotifications();
const errors = ref({});

// Define available OpenAI models
const openAIModels = computed(() => [
    {
        value: 'gpt-4-turbo',
        label: 'GPT-4 Turbo',
        description: 'Best for high-quality, long-form content, creative writing, technical documentation. Supports up to 128k tokens.',
        badge: 'Recommended',
        badgeType: 'success',
        available: true,
    },
    {
        value: 'gpt-4-turbo-mini',
        label: 'GPT-4 Turbo Mini',
        description: 'Fast, cost-efficient version of GPT-4 Turbo with excellent reasoning and creativity.',
        badge: 'Cost Efficient',
        badgeType: 'info',
        available: true,
    },
    {
        value: 'gpt-4',
        label: 'GPT-4 (Legacy)',
        description: 'Advanced reasoning, in-depth analysis, and content creation requiring high fidelity.',
        badge: 'Legacy',
        badgeType: 'gray',
        available: true,
    },
    {
        value: 'gpt-3.5-turbo',
        label: 'GPT-3.5 Turbo',
        description: 'Budget-friendly content generation with good performance for blogs, summaries, emails, product descriptions.',
        badge: 'Budget',
        badgeType: 'info',
        available: true,
    },
    {
        value: 'gpt-4-vision-preview',
        label: 'GPT-4 Vision',
        description: 'Generating and editing content based on images and text. Visual content analysis with textual captions.',
        badge: 'Vision',
        badgeType: 'warning',
        available: true,
    },
    {
        value: 'gpt-4-turbo-vision',
        label: 'GPT-4 Turbo Vision',
        description: 'Latest vision model with improved performance for visual content analysis and generation.',
        badge: 'Vision',
        badgeType: 'warning',
        available: true,
    },
    {
        value: 'gpt-4o-mini',
        label: 'GPT-4o Mini',
        description: 'Lightweight applications needing balanced performance. Fast content generation where speed and cost-efficiency are critical.',
        badge: 'Fast & Light',
        badgeType: 'success',
        available: true,
    },
]);

// Set default model if not already set
if (!props.form.configuration.model) {
    props.form.configuration.model = 'gpt-4o-mini';
}

const save = () => {
    errors.value = {};

    router.put(route('mixpost.services.update', {service: 'openai'}), props.form, {
        preserveScroll: true,
        onSuccess() {
            notify('success', $t('service.service_saved', {service: 'Open AI'}));
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
                <span class="mr-xs"><OpenAI class="text-openai"/></span>
                <span>OpenAI</span>
            </div>
        </template>

        <template #description>
            <p>
                <a href="https://platform.openai.com/account/api-keys" class="link" target="_blank">You can generate an
                    API key here</a>.
            </p>
            <ReadDocHelp :href="`${$page.props.mixpost.docs_link}/services/ai/open-ai`"
                         class="mt-xs"/>
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
                    <option v-for="model in openAIModels"
                            :key="model.value"
                            :value="model.value"
                            :disabled="!model.available">
                        {{ model.label }}
                    </option>
                </Select>

                <!-- Model descriptions with badges -->
                <div class="mt-sm space-y-xs text-sm text-gray-600">
                    <div v-for="model in openAIModels"
                         :key="model.value"
                         v-show="form.configuration.model === model.value"
                         class="flex items-center gap-xs">
                        <Badge :class="model.badgeType">{{ model.badge }}</Badge>
                        <span>{{ model.description }}</span>
                    </div>
                </div>

                <!-- Additional help text for model selection -->
                <p class="mt-sm text-xs text-gray-500">
                    <a href="https://platform.openai.com/docs/models" class="link" target="_blank">Learn more about OpenAI models</a>
                </p>
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
