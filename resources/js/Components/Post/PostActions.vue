<script setup>
import {computed, inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {parseISO} from "date-fns";
import useDateLocalize from "../../Composables/useDateLocalize";
import {router} from "@inertiajs/vue3";
import {usePage} from "@inertiajs/vue3";
import usePost from "@/Composables/usePost";
import usePostValidator from "@/Composables/usePostValidator";
import useNotifications from "@/Composables/useNotifications";
import useSettings from "@/Composables/useSettings";
import useSubscriptionError from "@/Composables/useSubscriptionError";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import SubscriptionUpgradeModal from "@/Components/Modal/SubscriptionUpgradeModal.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import PickTime from "@/Components/Package/PickTime.vue"
import PostTags from "@/Components/Post/PostTags.vue"
import Badge from "@/Components/DataDisplay/Badge.vue";
import ProviderIcon from "@/Components/Account/ProviderIcon.vue";
import PostCalendar from "../../Icons/PostCalendar.vue"
import PaperAirplaneIcon from "@/Icons/PaperAirplane.vue"
import XIcon from "@/Icons/X.vue"
import LightButton from "../Button/LightButton.vue";
import Queue from "../../Icons/Queue.vue";
import CheckBadgeSolid from "../../Icons/CheckBadgeSolid.vue";
import SuccessButton from "../Button/SuccessButton.vue";
import CheckBadge from "../../Icons/CheckBadge.vue";

const {t: $t} = useI18n()

const props = defineProps({
    form: {
        required: true,
        type: Object
    },
    hasAvailableTimes: {
        type: Boolean,
        default: false,
    }
});

const confirmation = inject('confirmation');

const {postId, editAllowed, needsApproval, userCanApprove} = usePost();
const { validationPassed, isPostEmpty, addError, removeError, isClickedPostNow, setIsClickedPostNow, hasValidationRun, setHasValidationRun, resetValidationState } = usePostValidator();
const {translatedFormat} = useDateLocalize();
const { showSubscriptionModal, handleSubscriptionError, closeSubscriptionModal } = useSubscriptionError();

const emit = defineEmits(['submit'])

const workspaceCtx = inject('workspaceCtx');

const timePicker = ref(false);

const {timeFormat, weekStartsOn} = useSettings();

const scheduleTime = computed(() => {
    if (props.form.date && props.form.time) {
        return translatedFormat(new Date(parseISO(props.form.date + ' ' + props.form.time)), `MMM do, ${timeFormat === 24 ? 'kk:mm' : 'h:mmaaa'}`, {
            weekStartsOn: weekStartsOn
        });
    }

    return null;
})

const clearScheduleTime = () => {
    props.form.date = '';
    props.form.time = '';
}

const {notify} = useNotifications();
const isLoading = ref(false);

const canSchedule = computed(() => {
    // For initial page load, we only check if accounts are selected
    // We don't check for content until after the first validation
    const hasAccounts = props.form.accounts.length > 0;

    // Only check for content if validation has already run
    const contentCheck = hasValidationRun.value ? !isPostEmpty(props.form.versions) : true;

    // Only consider validation errors if validation has already run
    const validationCheck = hasValidationRun.value ? validationPassed.value : true;

    return postId.value &&
        hasAccounts &&
        editAllowed.value &&
        contentCheck &&
        validationCheck;
});

const schedule = (postNow = false) => {
    isLoading.value = true;

    axios.post(route('mixpost.posts.schedule', {workspace: workspaceCtx.id, post: postId.value}), {
        postNow
    }).then((response) => {
        const message = `${$t('post.post_scheduled')}\n${response.data.scheduled_at}
        ${response.data.needs_approval ? `<div class="text-sm max-w-xs mt-xs">${$t('post.approval_required')}</div>` : ''}`;

        notify('success', message, {
            name: $t("post.view_in_calendar"),
            href: route('mixpost.calendar', {workspace: workspaceCtx.id, date: props.form.date})
        });

        router.visit(route('mixpost.posts.index', {workspace: workspaceCtx.id}));
    }).catch((error) => {
        handleValidationError(error);
    }).finally(() => {
        isLoading.value = false;
    })
}

const addToQueue = () => {
    isLoading.value = true;

    axios.post(route('mixpost.posts.addToQueue', {
        workspace: workspaceCtx.id,
        post: postId.value
    }), {}).then((response) => {
        const message = `${$t('post.post_scheduled')}\n${response.data.scheduled_at}
        ${response.data.needs_approval ? `<div class="text-sm max-w-xs mt-xs">${$t('post.approval_required')}</div>` : ''}`;

        notify('success', message, {
            name: $t("post.view_in_calendar"),
            href: route('mixpost.calendar', {workspace: workspaceCtx.id, date: response.data.date})
        });

        router.visit(route('mixpost.posts.index', {workspace: workspaceCtx.id}));
    }).catch((error) => {
        handleValidationError(error);
    }).finally(() => {
        isLoading.value = false;
    })
}

const approve = () => {
    isLoading.value = true;

    axios.post(route('mixpost.posts.approve', {workspace: workspaceCtx.id, post: postId.value})).then((response) => {
        notify('success', `${$t('post.post_scheduled')}\n${response.data.scheduled_at}`, {
            name: $t("post.view_in_calendar"),
            href: route('mixpost.calendar', {workspace: workspaceCtx.id, date: props.form.date})
        });

        router.visit(route('mixpost.posts.index', {workspace: workspaceCtx.id}));
    }).catch((error) => {
        handleValidationError(error);
    }).finally(() => {
        isLoading.value = false;
    })
}

const handleValidationError = (error) => {
    if (error.response.status !== 422) {
        notify('error', error);
        return;
    }

    const validationErrors = error.response.data.errors;

    // Check for subscription error first
    if (handleSubscriptionError(error)) {
        return; // Subscription modal will be shown, don't show other errors
    }

    const mustRefreshPage = validationErrors.hasOwnProperty('in_history') || validationErrors.hasOwnProperty('publishing');

    if (!mustRefreshPage) {
        notify('error', error);
    }

    if (mustRefreshPage) {
        router.visit(route('mixpost.posts.edit', {workspace: workspaceCtx.id, post: postId.value}));
    }
}

const confirmationPostNow = ref(false);

const accounts = computed(() => {
    return usePage().props.accounts.filter(account => props.form.accounts.includes(account.id));
})

const publishPost = () => {
    // Set isValidating to true to prevent resetting validation state when form changes
    isValidating.value = true;

    // First, clear any existing validation state
    resetValidationState();

    // Then set isClickedPostNow to true to trigger validation
    setIsClickedPostNow(true);

    // Also set hasValidationRun to true to ensure real-time validation
    setHasValidationRun(true);

    // Force a nextTick to ensure reactivity has propagated
    setTimeout(() => {
        // Check if the post is empty
        if (isPostEmpty(props.form.versions)) {
            // Add the empty post error
            addError({
                group: 'empty_post',
                key: 'empty',
                message: 'The post is empty. Please enter a message or media to share.'
            });
            isValidating.value = false;
            return;
        } else {
            // Remove the empty post error if content is present
            removeError({
                group: 'empty_post'
            });
        }

        // If scheduling is selected, proceed with scheduling
        if (scheduleTime.value) {
            isValidating.value = false;
            return schedule();
        }

        // Only show confirmation dialog if validation passed
        if (!validationPassed.value) {
            isValidating.value = false;
            return;
        }

        // Show confirmation dialog for posting now
        confirmationPostNow.value = true;
        isValidating.value = false;
    }, 0);
}

watch(isClickedPostNow, (newVal) => {
    // Watch for changes in isClickedPostNow
    // When isClickedPostNow becomes true, also set hasValidationRun to true
    if (newVal) {
        setHasValidationRun(true);
    } else {
        setHasValidationRun(false);
    }
}, { immediate: true });


// Track if we're currently in the validation process
const isValidating = ref(false);

watch(() => props.form, () => {
    // Only reset validation state when form changes if we're not in the validation process
    // This prevents resetting the validation state when the form changes due to validation
    if (!isValidating.value && !hasValidationRun.value) {
        resetValidationState();
    }
}, { deep: true });
</script>
<template>
    <div class="w-full flex items-center justify-end bg-stone-500 border-t border-gray-200 z-10">
        <div class="py-4 flex items-center space-x-xs row-px">
            <PostTags :items="form.tags" @update="form.tags = $event"/>

            <div class="flex items-center" role="group">
                <SecondaryButton size="lg"
                                 :hiddenTextOnSmallScreen="true"
                                 :class="{
                                    '!normal-case border-r-primary-800 ltr:rounded-r-none rtl:rounded-l-none': scheduleTime,
                                    'ltr:!rounded-r-lg rtl:!rounded-l-lg': !canSchedule,
                                    'border-primary-800 text-primary-500 py-3': !scheduleTime
                                }"
                                 @click="timePicker = true">
                    <template #icon>
                        <PostCalendar/>
                    </template>

                    {{ scheduleTime ? scheduleTime : $t("post.pick_time") }}
                </SecondaryButton>

                <template v-if="scheduleTime && canSchedule">
                    <SecondaryButton size="md" @click="clearScheduleTime" v-tooltip="$t('post.clear_time')"
                                     class="ltr:rounded-l-none ltr:border-l-0 rtl:rounded-r-none rtl:border-r-0 hover:text-red-500 !px-2">
                        <XIcon/>
                    </SecondaryButton>
                </template>

                <PickTime :show="timePicker" :date="form.date" :time="form.time" :isSubmitActive="editAllowed"
                          @close="timePicker = false" @update="form.date = $event.date; form.time = $event.time;"/>
            </div>

            <!-- Display only for users with approval rights-->
            <template v-if="userCanApprove && needsApproval">
                <SuccessButton
                    @click="approve"
                    :hiddenTextOnSmallScreen="true"
                    size="md">
                    <template #icon>
                        <CheckBadge/>
                    </template>

                    {{ $t('post.approve') }}
                </SuccessButton>
            </template>

            <!-- Display only for users with non approval rights-->
            <template v-if="editAllowed && !userCanApprove && !needsApproval && canSchedule && scheduleTime">
                <PrimaryButton @click="schedule()"
                               :hiddenTextOnSmallScreen="true"
                               :disabled="isLoading"
                               :isLoading="isLoading"
                               size="md"
                               class="px-4 py-3">
                    <template #icon>
                        <PaperAirplaneIcon/>
                    </template>

                    {{ $t("post.schedule") }}
                </PrimaryButton>
            </template>

            <template v-if="editAllowed && userCanApprove && !needsApproval">
                <PrimaryButton @click="publishPost"
                               :hiddenTextOnSmallScreen="true"
                               :disabled="!canSchedule || isLoading" :isLoading="isLoading" size="md"
                               class="px-4 py-3">
                    <template #icon>
                        <PaperAirplaneIcon/>
                    </template>

                    {{ scheduleTime ? $t("post.schedule") : $t("post.post_now") }}
                </PrimaryButton>
            </template>

            <template v-if="editAllowed && !needsApproval && hasAvailableTimes">
                <LightButton @click="addToQueue"
                               :hiddenTextOnSmallScreen="true"
                               :disabled="!canSchedule || isLoading" size="md"
                               class="!py-2 px-4 border border-transparent" weight="normal">
                    <template #icon>
                        <Queue/>
                    </template>

                    {{ $t("post.add_to_queue") }}
                </LightButton>
            </template>

            <template v-if="editAllowed && !userCanApprove">
                <div class="cursor-help" v-tooltip="$t('post.approval_required')">
                    <CheckBadgeSolid class="text-primary-500"/>
                </div>
            </template>
        </div>

        <ConfirmationModal :show="confirmationPostNow" @close="confirmationPostNow = false">
            <template #header>
                {{ $t("post.confirm_publication") }}
            </template>
            <template #body>
                {{ $t("post.now_confirm_publication") }}

                <div class="mt-sm flex flex-wrap items-center gap-xs">
                    <Badge v-for="account in accounts" :key="account.id">
                        <ProviderIcon :provider="account.provider" class="mr-xs"/>
                        {{ account.name }}
                    </Badge>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="confirmationPostNow = false" class="mr-xs">{{ $t("general.cancel") }}
                </SecondaryButton>
                <PrimaryButton :disabled="isLoading" :isLoading="isLoading" @click="schedule(true)"> {{
                        $t("post.post_now")
                    }}
                </PrimaryButton>
            </template>
        </ConfirmationModal>

        <SubscriptionUpgradeModal :show="showSubscriptionModal" @close="closeSubscriptionModal" />
    </div>
</template>
