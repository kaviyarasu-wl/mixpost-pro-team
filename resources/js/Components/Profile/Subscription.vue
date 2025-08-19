<script setup>
import {gwUrl} from "../../helpers.js";

const props = defineProps(['subscription'])

const date = new Date(props.subscription.end_at || new Date());
const day = date.getDate().toString().padStart(2, '0');
const month = date.toLocaleString('en-GB', { month: 'short' });
const year = date.getFullYear();
const formattedDate = `${day} ${month} ${year}`;

const status = {
    'active': {
        'bg-color': 'bg-emerald-500',
        'text-color': 'text-white',
        'text': 'Active',
    },
    'pending': {
        'bg-color': 'bg-orange-500',
        'text-color': 'text-white',
        'text': 'Pending',
    },
    'canceled': {
        'bg-color': 'bg-red-600',
        'text-color': 'text-white',
        'text': `Plan Active Till: ${formattedDate}`,
    },
    'overdue': {
        'bg-color': 'bg-pinterest',
        'text-color': 'text-white',
        'text': 'Overdue',
    },
    'expired': {
        'bg-color': 'bg-gray-500',
        'text-color': 'text-white',
        'text': 'Expired',
    },
}

</script>
<template>
    <div class="flex items-center justify-between p-6 bg-white rounded-lg shadow-sm">
        <div class="space-y-2">
            <div class="text-sm text-gray-600">Your Plan (Billed {{ props.subscription.payment_frequency || 'monthly' }}):</div>
            <div class="flex items-center space-x-4">
                <h3 class="text-md font-semibold text-gray-900">{{ props.subscription.plan.title }}</h3>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <span :class="{
                'px-3 py-1 text-sm font-medium full rounded-md': true,
                [status[props.subscription.status]['bg-color']]: true,
                [status[props.subscription.status]['text-color']]: true
                }">
                {{ status[props.subscription.status]['text'] }}
            </span>
        </div>
        <a :href="gwUrl('credit-based?target=subscription')"
            target="_blank"
            class="relative inline-flex items-center bg-primary-500 border border-transparent rounded-md font-medium text-primary-context hover:bg-primary-700 active:bg-primary-700 focus:border-primary-700 focus:shadow-outline-indigo disabled:bg-primary-200 disabled:text-gray-600 disabled:cursor-not-allowed transition ease-in-out duration-200 px-4 py-2">
            Manage Subscription
        </a>
    </div>
</template>
