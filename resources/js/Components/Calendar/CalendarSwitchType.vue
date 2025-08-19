<script setup>
import {inject, computed} from "vue";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import ChevronDownIcon from "@/Icons/ChevronDown.vue"
import CalendarIcon from "@/Icons/Calendar.vue"

const calendarType = inject('calendarType');

const select = (type) => {
    calendarType.value = type;
}

const typeLabel = computed(() => {
    switch(calendarType.value) {
        case 'month':
            return 'calendar.month';
        case 'week':
            return 'calendar.week';
        case 'year':
            return 'calendar.year';
        default:
            return 'calendar.month';
    }
})
</script>
<template>
    <Dropdown placement="bottom">
        <template #trigger>
            <SecondaryButton size="sm">
                <span class="inline-block mr-xs">{{ $t(typeLabel) }}</span>
                <ChevronDownIcon/>
            </SecondaryButton>
        </template>

        <template #content>
            <DropdownItem as="button" @click="select('year')">
                <template #icon>
                    <CalendarIcon/>
                </template>

                {{ $t("calendar.year") }}
            </DropdownItem>

            <DropdownItem as="button" @click="select('month')">
                <template #icon>
                    <CalendarIcon/>
                </template>

                {{ $t("calendar.month") }}
            </DropdownItem>

            <DropdownItem as="button" @click="select('week')">
                <template #icon>
                    <CalendarIcon/>
                </template>

                {{ $t("calendar.week") }}
            </DropdownItem>
        </template>
    </Dropdown>
</template>
