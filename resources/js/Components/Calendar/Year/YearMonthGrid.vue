<script setup>
import {computed, inject} from "vue";
import {router} from "@inertiajs/vue3";
import {format, getMonth, getDaysInMonth, getDay, startOfMonth} from "date-fns";
import {isDatePast} from "@/helpers";
import useDateLocalize from "@/Composables/useDateLocalize";
const {translatedFormat} = useDateLocalize();
const props = defineProps({
    months: {
        required: true,
        type: Array
    },
    timeZone: {
        required: false,
        type: String,
        default: 'UTC'
    },
    weekStartsOn: {
        required: false,
        type: Number,
        default: 0
    },
    today: {
        required: true,
        type: String
    }
});

const emit = defineEmits(['selectMonth']);

const workspaceCtx = inject('workspaceCtx');

const monthNames = [
    'january', 'february', 'march', 'april', 'may', 'june',
    'july', 'august', 'september', 'october', 'november', 'december'
];

const weekdayNames = computed(() => {
    const names = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const orderedNames = [];

    for (let i = 0; i < 7; i++) {
        orderedNames.push(names[(props.weekStartsOn + i) % 7]);
    }

    return orderedNames;
});

const getMonthDays = (monthData) => {
    const firstDay = startOfMonth(monthData.date);
    const totalDays = getDaysInMonth(monthData.date);
    const startDayOfWeek = getDay(firstDay);
    const offsetDays = (startDayOfWeek - props.weekStartsOn + 7) % 7;

    const days = [];

    for (let i = 0; i < offsetDays; i++) {
        days.push(null);
    }

    for (let day = 1; day <= totalDays; day++) {
        const dateStr = format(new Date(monthData.year, monthData.month, day), 'yyyy-MM-dd');
        const dayPosts = monthData.posts.filter(post => post.scheduled_at.date === dateStr);

        days.push({
            day,
            date: dateStr,
            posts: dayPosts,
            isToday: dateStr === props.today,
            isPast: isDatePast(new Date(dateStr + 'T00:00:00'), props.timeZone)
        });
    }

    return days;
};

const getMonthLabel = (monthData) => {
    return translatedFormat(monthData.date, 'MMMM');
};

const selectMonth = (monthIndex) => {
    emit('selectMonth', monthIndex);
};

const selectDay = (dateStr) => {
    router.get(route('mixpost.calendar', {
        workspace: workspaceCtx.id,
        date: dateStr,
        type: 'month'
    }));
};

const getDayTooltip = (day) => {
    if (!day || day.posts.length === 0) return null;

    const postCount = day.posts.length;
    const dateFormatted = translatedFormat(new Date(day.date), 'MMM d, yyyy');

    if (postCount === 1) {
        return `1 post scheduled on ${dateFormatted}`;
    }
    return `${postCount} posts scheduled on ${dateFormatted}`;
};
</script>
<template>
    <div class="calendar-year-height overflow-y-auto mixpost-scroll-style">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-md p-lg">
        <div v-for="(monthData, index) in months" :key="index" class="border border-gray-200 rounded-lg">
            <div
                class="p-sm bg-gray-50 text-center font-semibold cursor-pointer hover:bg-gray-100 transition-colors"
                @click="selectMonth(monthData.month)"
            >
                    {{ getMonthLabel(monthData) }}
                <span v-if="monthData.postCount > 0" class="ml-xs text-sm text-gray-500">
                    ({{ monthData.postCount }})
                </span>
            </div>

            <div class="p-xs">
                <div class="grid grid-cols-7 mb-xs text-xs text-gray-500 text-center">
                    <div v-for="weekday in weekdayNames" :key="weekday" class="py-xs">
                            {{ $t(`calendar.weekdays.${weekday}.shortest`) }}
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-xs text-xs">
                    <div
                        v-for="(day, dayIndex) in getMonthDays(monthData)"
                        :key="dayIndex"
                        :class="[
                            'relative aspect-square flex items-center justify-center rounded cursor-pointer transition-colors',
                            {
                                'invisible': !day,
                                'bg-indigo-500 text-white': day?.isToday,
                                'text-gray-400': day?.isPast,
                                'hover:bg-gray-100': day && !day.isToday && !day.isPast,
                                'font-semibold': day?.posts.length > 0
                            }
                        ]"
                            @click="day && selectDay(day.date)"
                            v-tooltip="getDayTooltip(day)"
                    >
                        <span v-if="day">{{ day.day }}</span>
                        <span
                            v-if="day?.posts.length > 0"
                            class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-indigo-500 rounded-full"
                            :class="{'bg-white': day.isToday}"
                        ></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>
