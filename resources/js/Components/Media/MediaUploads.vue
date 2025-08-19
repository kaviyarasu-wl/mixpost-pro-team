<script setup>
import {inject, onMounted} from "vue";
import useMedia from "@/Composables/useMedia";
import useNotifications from "@/Composables/useNotifications";
import UploadMedia from "@/Components/Media/UploadMedia.vue"
import MediaSelectable from "@/Components/Media/MediaSelectable.vue";
import MediaFile from "@/Components/Media/MediaFile.vue";
import Masonry from "@/Components/Layout/Masonry.vue";
import SectionTitle from "@/Components/DataDisplay/SectionTitle.vue";
import Select from "@/Components/Form/Select.vue";

const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    columns: {
        type: Number,
        default: 3
    }
})

const {notify} = useNotifications();

const {
    page,
    items,
    endlessPagination,
    selected,
    toggleSelect,
    deselectAll,
    removeItems,
    isSelected,
    createObserver,
    type
} = useMedia('mixpost.media.fetchUploads', {workspace: workspaceCtx.id});

onMounted(() => {
    createObserver();
});

defineExpose({selected, deselectAll, removeItems})
</script>
<template>
    <UploadMedia :max-selection="4"
                 :combines-mime-types="''"
                 :selected="selected"
                 :toggleSelect="toggleSelect"
                 :isSelected="isSelected"
                 :columns="columns"
    />

    <div :class="{'mt-lg': items.length}">
        <template v-if="items.length">
            <div class="flex items-center justify-between mb-4">
                <SectionTitle>{{ $t('media.library') }}</SectionTitle>
                <div class="flex items-center">
                    <label class="mr-2 text-sm text-gray-700">{{ $t('media.filter_by_source') }}:</label>
                    <Select v-model="type" class="w-40">
                        <option value="all">{{ $t('general.all') }}</option>
                        <option value="uploaded">{{ $t('media.uploaded') }}</option>
                        <option value="stock">{{ $t('media.stock') }}</option>
                        <option value="gifs">{{ $t('media.gifs') }}</option>
                        <option value="gravity_write">GravityWrite</option>
                    </Select>
                </div>
            </div>

            <Masonry :items="items" :columns="columns">
                <template #default="{item}">
                    <MediaSelectable v-if="item" :active="isSelected(item)" @click="toggleSelect(item)">
                        <MediaFile :media="item"/>
                    </MediaSelectable>
                </template>
            </Masonry>
        </template>
        <div ref="endlessPagination" class="-z-10 w-full"/>
    </div>
</template>
