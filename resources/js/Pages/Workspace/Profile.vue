<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import PageHeader from "../../Components/DataDisplay/PageHeader.vue";
import Profile from "../../Components/User/Profile.vue";
import LightButton from "@/Components/Button/LightButton.vue"
import ArrowLeft from "../../Icons/ArrowLeft.vue";

const props = defineProps(['settings', 'locales', 'timezone_list', 'subscription'])

const form = useForm(props.settings);

const goBack = () => {
    window.history.back();
}
</script>
<template>
    <Head :title="$t('profile.edit_profile')"/>

    <div class="flex px-6 pt-lg justify-between">
        <LightButton size="md" class="!py-2 px-4" weight="normal" @click.prevent="goBack">
            <template #icon>
                <ArrowLeft />
            </template>
            Back
        </LightButton>
    </div>
    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('profile.edit_profile')">
            <!-- <BackToDashboardButton/> -->
        </PageHeader>

        <Profile :form="form" :timezone_list="timezone_list" :locales="locales" :subscription="subscription" />
    </div>
</template>
