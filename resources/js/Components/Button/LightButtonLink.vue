<script setup>
import {Link} from '@inertiajs/vue3';
import useButtonSize from "@/Composables/useButtonSize"

const props = defineProps({
    href: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'lg'
    },
    hiddenTextOnSmallScreen: {
        type: Boolean,
        default: false,
    }
});

const {sizeClass} = useButtonSize(props.size);
</script>

<template>
    <Link :href="href" :class="sizeClass"
          class="inline-flex items-center bg-blue-100 text-primary-800 font-semibold py-3 px-6 rounded-lg hover:bg-blue-200 focus:shadow-outline-indigo transition ease-in-out duration-200">
        <span v-if="$slots.icon" class="inline-flex"
              :class="{'sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': $slots.default, 'mr-0 sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': hiddenTextOnSmallScreen, 'mr-xs rtl:mr-xs rtl:ml-xs': !hiddenTextOnSmallScreen && $slots.default}">
            <slot name="icon"/>
        </span>

        <span v-if="$slots.default" class="inline-flex items-center" :class="{'hidden sm:inline': hiddenTextOnSmallScreen}">
            <slot/>
        </span>
    </Link>
</template>
