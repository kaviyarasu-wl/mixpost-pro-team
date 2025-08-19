<script setup>
import {computed, ref, inject} from "vue";
import {router} from "@inertiajs/vue3";
import {
    format,
    getYear,
    getDaysInMonth,
    startOfYear,
    endOfYear,
    eachMonthOfInterval,
    getMonth,
    setMonth,
    addYears,
    subYears
} from "date-fns"
import {utcToZonedTime} from "date-fns-tz";
import YearSelector from "@/Components/Calendar/Year/YearSelector.vue";
import YearMonthGrid from "@/Components/Calendar/Year/YearMonthGrid.vue";

const props = defineProps({
    timeZone: {
        required: false,
        type: String,
        default: 'UTC'
    },
    initialDate: {
        required: false,
        type: [String, Date],
        default: (props) => {
            return format(utcToZonedTime(new Date().toISOString(), props.timeZone), 'yyyy-MM-dd')
        }
    },
    weekStartsOn: {
        required: false,
        type: Number,
        default: 0
    },
    posts: {
        required: false,
        type: Array,
        default: []
    }
});

const emit = defineEmits(['dateSelected'])

const workspaceCtx = inject('workspaceCtx');
const calendarType = inject('calendarType');

const selectedDate = ref(new Date(`${props.initialDate}T00:00:00`));

const today = computed(() => {
    return format(utcToZonedTime(new Date().toISOString(), props.timeZone), 'yyyy-MM-dd')
});

const year = computed(() => {
    return getYear(selectedDate.value)
})

const months = computed(() => {
    const yearStart = startOfYear(selectedDate.value);
    const yearEnd = endOfYear(selectedDate.value);

    return eachMonthOfInterval({
        start: yearStart,
        end: yearEnd
    }).map(monthDate => {
        const monthPosts = getMonthPosts(monthDate);
        return {
            date: monthDate,
            month: getMonth(monthDate),
            year: getYear(monthDate),
            posts: monthPosts,
            postCount: monthPosts.length
        };
    });
});

const getMonthPosts = (monthDate) => {
    const monthStart = format(monthDate, 'yyyy-MM-01');
    const monthEnd = format(new Date(getYear(monthDate), getMonth(monthDate) + 1, 0), 'yyyy-MM-dd');

    return props.posts.filter((post) => {
        const postDate = post.scheduled_at.date;
        return postDate >= monthStart && postDate <= monthEnd;
    });
}

const selectDate = (value) => {
    selectedDate.value = value;
    emit('dateSelected', value);
}

const selectMonth = (monthIndex) => {
    const newDate = setMonth(selectedDate.value, monthIndex);
    const formattedDate = format(newDate, 'yyyy-MM-dd');

    router.get(route('mixpost.calendar', {
        workspace: workspaceCtx.id,
        date: formattedDate,
        type: 'month'
    }));
}

const goToPreviousYear = () => {
    selectDate(subYears(selectedDate.value, 1));
}

const goToNextYear = () => {
    selectDate(addYears(selectedDate.value, 1));
}

const goToToday = () => {
    selectDate(new Date());
}
</script>
<template>
    <div class="bg-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between py-lg row-px">
            <div class="flex items-center space-x-xs mb-xs md:mb-0">
                <YearSelector
                    :current-year="parseInt(format(new Date(), 'yyyy'))"
                    :selected-year="year"
                    @previousYear="goToPreviousYear"
                    @nextYear="goToNextYear"
                    @today="goToToday"
                />
            </div>

            <slot name="header"/>
        </div>

        <YearMonthGrid
            :months="months"
            :timeZone="timeZone"
            :weekStartsOn="weekStartsOn"
            :today="today"
            @selectMonth="selectMonth"
        />
    </div>
</template>
