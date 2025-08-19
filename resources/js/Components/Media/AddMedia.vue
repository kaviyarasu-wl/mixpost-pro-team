<script setup>
import {computed, inject, ref} from "vue";
import useMedia from "@/Composables/useMedia";
import DialogModal from "@/Components/Modal/DialogModal.vue"
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import MediaUploads from "@/Components/Media/MediaUploads.vue";
import MediaStock from "@/Components/Media/MediaStock.vue";
import MediaGifs from "@/Components/Media/MediaGifs.vue";
import Preloader from "@/Components/Util/Preloader.vue"
import XIcon from "@/Icons/X.vue"

const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    maxSelection: {
        type: Number,
        default: 1,
    },
    combinesMimeTypes: {
        type: String,
        default: '',
    }
})

const emit = defineEmits(['insert']);

const show = ref(false);

const {
    activeTab,
    tabs,
    isDownloading,
    downloadExternal,
    getMediaCrediting,
} = useMedia('mixpost.media.fetchStock', {workspace: workspaceCtx.id});

const sources = {
    'uploads': MediaUploads,
    'stock': MediaStock,
    'gifs': MediaGifs
};

const sourceProperties = ref();

const source = computed(() => {
    return sources[activeTab.value]
})

const selectedItems = computed(() => {
    return sourceProperties.value ? sourceProperties.value.selected : [];
})

const deselectAll = () => {
    sourceProperties.value.deselectAll()
}

const close = () => {
    deselectAll();
    show.value = false;
    activeTab.value = 'uploads'
};

const insert = () => {
    const toDownload = activeTab.value !== 'uploads';

    if (toDownload) {
        // Download external media files
        downloadExternal(selectedItems.value.map((item) => {
            const {id, url, source, author, download_data, data, name} = item;

            // Build the download item object
            const downloadItem = {
                id,
                url,
                source,
                author,
                download_data,
            };

            // Add Unsplash-specific data
            if (source === 'Unsplash') {
                downloadItem.alt_description = data?.alt_description;
                downloadItem.description = data?.description;
                downloadItem.unsplash_id = data?.unsplash_id || id;
            }

            // Add Tenor-specific data
            if (activeTab.value === 'gifs') {
                downloadItem.name = name;
                downloadItem.content_description = data?.content_description;
                downloadItem.search_term = data?.search_term;
                downloadItem.tenor_id = data?.tenor_id || id;
            }

            return downloadItem;
        }), (response) => {
            emit('insert', {
                items: response.data,
                crediting: getMediaCrediting(response.data)
            });

            close();
        })
    }

    if (!toDownload) {
        emit('insert', {
            items: selectedItems.value,
            crediting: getMediaCrediting(selectedItems.value)
        });

        close();
    }
}
</script>
<template>
    <div @click="show = !show">
        <slot/>
    </div>

    <DialogModal :show="show"
                 max-width="2xl"
                 :closeable="true"
                 :scrollable-body="true"
                 @close="close">
        <template #header>
            {{ $t('media.add_media') }}
        </template>

        <template #body>
            <Preloader v-if="isDownloading" :opacity="75">
                {{ $t('media.downloading') }}
            </Preloader>

            <Tabs>
                <template v-for="tab in tabs">
                    <Tab @click="activeTab = tab" :active="activeTab === tab">{{ $t(`media.${tab}`) }}</Tab>
                </template>
            </Tabs>

            <div class="mt-lg">
                <component :is="source" ref="sourceProperties"/>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close" class="mr-xs rtl:mr-0 rtl:ml-xs">{{ $t('general.cancel') }}</SecondaryButton>

            <template v-if="selectedItems.length">
                <SecondaryButton @click="deselectAll" v-tooltip.top="$t('general.dismiss')" class="mr-xs rtl:mr-0 rtl:ml-xs">
                    <template #icon>
                        <XIcon/>
                    </template>
                </SecondaryButton>

                <PrimaryButton @click="insert">{{ $t('general.insert') }} {{ selectedItems.length }}
                    {{ $t('general.items') }}
                </PrimaryButton>
            </template>
        </template>
    </DialogModal>
</template>
