<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import {computed} from "vue";
import MinimalLayout from "@/Layouts/Minimal.vue";
import Error from "@/Components/Form/Error.vue";
import Panel from "@/Components/Surface/Panel.vue";

defineOptions({layout: MinimalLayout});

const props = defineProps({
    locales: {
        type: Array,
        required: true,
    },
    is_forgot_password_enabled: {
        type: Boolean,
        required: true,
    }
});

// Get the errors from the Inertia shared data and add a computed property to safely check for errors
const page = usePage();
const hasError = computed(() => page.props.errors && page.props.errors.error);
const errorMessage = computed(() => hasError.value ? page.props.errors.error : '');
</script>

<template>
    <Head :title="$t('auth.sign_in')"/>

    <div class="w-full sm:max-w-lg mx-auto">
        <form>
            <Panel>
                <template #title>
                    {{ $t('auth.login_account') }}
                </template>

                <!-- Show error message if it exists -->
                <div class="mb-md">
                    <Error v-if="hasError" :message="errorMessage" class="mb-xs"/>
                </div>

                <!-- <template #description>
                    {{ $t('auth.enter_details') }}
                </template> -->

                <div>
                    <a :href="route('mixpost.login.oauth', 'gravity')" class="link-primary">
                        Sign in with Gravity Write
                    </a>
                </div>

            </Panel>
        </form>
    </div>
</template>