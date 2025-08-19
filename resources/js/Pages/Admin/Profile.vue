<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/Admin.vue';
import PageHeader from "../../Components/DataDisplay/PageHeader.vue";
import Profile from "../../Components/User/Profile.vue";
import BackToDashboardButton from "../../Components/Helper/BackToDashboardButton.vue";
import LightButton from "@/Components/Button/LightButton.vue"
import ArrowLeft from "../../Icons/ArrowLeft.vue";

defineOptions({layout: AdminLayout})

const props = defineProps(['settings', 'locales', 'timezone_list'])

const form = useForm(props.settings);

const goBack = () => {
    window.history.back();
}
</script>
<template>
    <Head :title="$t('profile.edit_profile')"/>

    <div class="flex px-6 pt-lg">
        <LightButton @click.prevent="goBack">
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

        <Profile :form="form" :timezone_list="timezone_list" :locales="locales" />
    </div>
</template>
