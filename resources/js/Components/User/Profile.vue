<script setup>
import {inject} from "vue";
import Preferences from "../../Components/Profile/Preferences.vue";
import UserAccount from "../../Components/Profile/UserAccount.vue";
import ActionSection from "../../Components/Surface/ActionSection.vue";
import SectionBorder from "../../Components/Surface/SectionBorder.vue";
import SubscriptionSection from "../../Components/Surface/SubscriptionSection.vue";
import Subscription from "../../Components/Profile/Subscription.vue";

const workspaceCtx = inject('workspaceCtx');

const props = defineProps(['locales', 'timezone_list', 'form', 'subscription'])
</script>
<template>
    <div class="row-py w-full mx-auto">
        <div class="w-full row-px">
            <div class="mt-2xl sm:mt-0">
                <ActionSection>
                    <template #title>{{ $t('profile.profile_information') }}</template>
                    <template #description>{{ $t('profile.update_your_account') }}</template>

                    <UserAccount/>
                </ActionSection>
            </div>
            <SectionBorder :contrasted="true"/>

            <template v-if="workspaceCtx != undefined">
                <div class="mt-2xl sm:mt-0">
                    <SubscriptionSection>
                        <template #title>My Subscription</template>
                        <template #description>Update your general preferences.</template>

                        <Subscription :subscription="props.subscription" />
                    </SubscriptionSection>
                </div>
                <SectionBorder :contrasted="true"/>
            </template>

            <div class="mt-2xl sm:mt-0">
                <ActionSection>
                    <template #title>{{ $t('profile.preferences') }}</template>
                    <template #description>{{ $t('profile.update_preferences') }}</template>

                    <Preferences :form="form"
                                :timezone_list="timezone_list"
                                :locales="locales"
                    />
                </ActionSection>
            </div>
        </div>
    </div>
</template>
