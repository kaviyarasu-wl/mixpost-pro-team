<script setup>
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
    weight: {
        type: String,
        default: 'semibold'
    },
    hiddenTextOnSmallScreen: {
        type: Boolean,
        default: false,
    }
});

const {sizeClass} = useButtonSize(props.size);
const fontWeightClass = `font-${props.weight}`;
</script>

<template>
    <button class="inline-flex items-center bg-blue-100 text-primary py-3 px-6 rounded-lg hover:bg-blue-200 focus:shadow-outline-indigo transition ease-in-out duration-200"
        :class="sizeClass, fontWeightClass">
        <span v-if="$slots.icon" class="inline-flex"
              :class="{'sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': $slots.default, 'mr-0 sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': hiddenTextOnSmallScreen, 'mr-xs rtl:mr-xs rtl:ml-xs': !hiddenTextOnSmallScreen && $slots.default}">
            <slot name="icon"/>
        </span>

        <span v-if="$slots.default" class="inline-flex items-center" :class="{'hidden sm:inline': hiddenTextOnSmallScreen}">
            <slot/>
        </span>
    </button>
</template>
