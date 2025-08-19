<script setup>
import {computed, inject, onMounted, ref, watch} from "vue";
import {Head, Link, usePage} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import NProgress from 'nprogress'
import {find} from "lodash";
import PlusIcon from "@/Icons/Plus.vue"
import useNotifications from "@/Composables/useNotifications";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Account from "@/Components/Account/Account.vue"
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import Alert from '@/Components/Util/Alert.vue'
import TwitterReports from "@/Components/Report/TwitterReports.vue"
import FacebookPageReports from "@/Components/Report/FacebookPageReports.vue"
import FacebookGroupReports from "@/Components/Report/FacebookGroupReports.vue"
import InstagramReports from "@/Components/Report/InstagramReports.vue"
import ThreadsReports from "@/Components/Report/ThreadsReports.vue"
import MastodonReports from "@/Components/Report/MastodonReports.vue"
import PinterestReports from "@/Components/Report/PinterestReports.vue"
import LinkedinReports from "@/Components/Report/LinkedinReports.vue"
import LinkedinPageReports from "@/Components/Report/LinkedinPageReports.vue"
import TikTokReports from "@/Components/Report/TikTokReports.vue"
import YoutubeReports from "@/Components/Report/YoutubeReports.vue"
import useWorkspace from "../../Composables/useWorkspace.js";
import posthog from 'posthog-js';

const {t: $t} = useI18n()

const props = defineProps({
    accounts: {
        required: true,
        type: Array,
    },
    user: {
        required: true,
        type: Object,
    },
})

if (posthog.get_property("distinct_id") != props.user.email) {
    posthog.reset();

    posthog.identify(props.user.email, {
        email: props.user.email,
        name: props.user.email,
        user_id: props.user.id,
        $set: {
            email: props.user.email,
            name: props.user.email,
        }
    });

    posthog.init(
        'phc_Kuk4qWZvCm6fn7zgztHsy677eIFe94vDzZWzXc24fBP',
        {
            api_host: 'https://us.i.posthog.com',
        }
    );
}

const workspaceCtx = inject('workspaceCtx');
const page = usePage();
const {notify} = useNotifications();
const {isWorkspaceEditorRole} = useWorkspace();

const isLoading = ref(false);
const data = ref({
    metrics: {},
    audience: {}
});

const showTimezoneAlert = ref(false);

const checkTimezoneAlert = () => {
    const alertDismissed = localStorage.getItem('mixpost_timezone_alert_dismissed');
    const timezone = page.props.mixpost?.settings?.timezone;

    const isDefaultTimezone = !timezone || timezone === 'UTC';

    if (!alertDismissed && isDefaultTimezone) {
        showTimezoneAlert.value = true;
    }
};

const closeTimezoneAlert = () => {
    showTimezoneAlert.value = false;
    localStorage.setItem('mixpost_timezone_alert_dismissed', 'true');
};

const selectAccount = (account) => {
    workspaceCtx.dashboard_filter.account_id = account.id;
}

const isAccountSelected = (account) => {
    return workspaceCtx.dashboard_filter.account_id === account.id;
}

const selectPeriod = (value) => {
    workspaceCtx.dashboard_filter.period = value;
}

const isPeriodSelected = (value) => {
    return workspaceCtx.dashboard_filter.period === value;
}

const fetch = () => {
    isLoading.value = true;
    NProgress.start();

    axios.get(route('mixpost.reports', {workspace: workspaceCtx.id}), {
        params: workspaceCtx.dashboard_filter
    }).then(function (response) {
        data.value = response.data;
    }).catch(() => {
        notify('error', $t('dashboard.error_retrieving_analytics'));
    }).finally(() => {
        isLoading.value = false;
        NProgress.done();
    });
}

const providers = {
    'twitter': TwitterReports,
    'facebook_page': FacebookPageReports,
    'facebook_group': FacebookGroupReports,
    'instagram': InstagramReports,
    'threads': ThreadsReports,
    'mastodon': MastodonReports,
    'pinterest': PinterestReports,
    'linkedin': LinkedinReports,
    'linkedin_page': LinkedinPageReports,
    'tiktok': TikTokReports,
    'youtube': YoutubeReports
};

const component = computed(() => {
    const account = find(props.accounts, {id: workspaceCtx.dashboard_filter.account_id});

    if (account === undefined) {
        return;
    }

    return providers[account.provider];
});

onMounted(() => {
    checkTimezoneAlert();

    if (!props.accounts.length) {
        return null;
    }

    if (!workspaceCtx.dashboard_filter.account_id) {
        selectAccount(props.accounts[0]);
        return null;
    }

    fetch();
})

watch(workspaceCtx.dashboard_filter, () => {
    fetch()
});
</script>
<template>
    <Head :title="$t('dashboard.dashboard')"/>

    <div class="row-py">
        <PageHeader :title="$t('dashboard.dashboard')">
            <div>
                <Tabs v-if="accounts.length">
                    <Tab @click="selectPeriod('7_days')" :active="isPeriodSelected('7_days')">7 {{
                            $t("dashboard.days")
                        }}
                    </Tab>
                    <Tab @click="selectPeriod('30_days')" :active="isPeriodSelected('30_days')">30
                        {{ $t("dashboard.days") }}
                    </Tab>
                    <Tab @click="selectPeriod('90_days')" :active="isPeriodSelected('90_days')">90
                        {{ $t("dashboard.days") }}
                    </Tab>
                </Tabs>
            </div>
        </PageHeader>

        <div v-if="showTimezoneAlert" class="row-px mb-lg">
            <Alert variant="warning" @close="closeTimezoneAlert">
                <div>
                    <span>Please update your Time Zone in </span>
                    <Link :href="route('mixpost.profile.index', {workspace: workspaceCtx.id})" class="underline">
                        Edit Profile
                    </Link>
                    <span> to ensure accurate post scheduling.</span>
                </div>
            </Alert>
        </div>

        <div class="row-px flex items-center">
            <div class="w-full">
                <div v-if="accounts.length" class="flex flex-wrap items-center gap-sm">
                    <template v-for="account in accounts" :key="account.id">
                        <button @click="selectAccount(account)" type="button">
                            <Account
                                :provider="account.provider"
                                :name="account.name"
                                :active="isAccountSelected(account)"
                                :img-url="account.image"
                                v-tooltip="account.name"
                            />
                        </button>
                    </template>
                </div>
                <div v-else>
                    <template v-if="isWorkspaceEditorRole">
                        <p class="mb-xs">{{ $t("account.add_social_account") }}</p>
                        <Link :href="route('mixpost.accounts.index', {workspace: workspaceCtx.id})">
                            <PrimaryButton>{{ $t("account.add_account", 2) }}</PrimaryButton>
                        </Link>

                        <div class="flex items-center justify-center flex-col min-h-93vh mt-2xl">
                            <img src="@img/dashboard-bg.png" class="mb-2xl" />

                            <Link :href="route('mixpost.accounts.index', {workspace: workspaceCtx.id})">
                                <PrimaryButton>
                                    <template #icon>
                                        <PlusIcon/>
                                    </template>
                                    {{ $t("account.add_account", 2) }}
                                </PrimaryButton>
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <component :is="component" :data="data" :isLoading="isLoading"/>
    </div>
</template>
