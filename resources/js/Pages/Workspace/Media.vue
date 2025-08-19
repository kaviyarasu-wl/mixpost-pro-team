<script setup>
import {computed, inject, ref} from "vue";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import usePostVersions from "@/Composables/usePostVersions";
import useMedia from "@/Composables/useMedia";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import MediaUploads from "@/Components/Media/MediaUploads.vue";
import MediaStock from "@/Components/Media/MediaStock.vue";
import MediaGifs from "@/Components/Media/MediaGifs.vue";
import SelectableBar from "@/Components/DataDisplay/SelectableBar.vue";
import PureDangerButton from "@/Components/Button/PureDangerButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Panel from "@/Components/Surface/Panel.vue";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import TrashIcon from "@/Icons/Trash.vue";
import PlusIcon from "@/Icons/Plus.vue";

const workspaceCtx = inject('workspaceCtx');

const {
    activeTab,
    tabs,
    isDownloading,
    isDeleting,
    downloadExternal,
    deletePermanently,
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

const use = () => {
    const toDownload = activeTab.value !== 'uploads';

    if (toDownload) {
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
            createPost(response.data);
        });
    }

    if (!toDownload) {
        createPost(selectedItems.value);
    }
}

const {versionObject} = usePostVersions();

const createPost = (mediaCollection) => {
    router.post(route('mixpost.posts.store', {workspace: workspaceCtx.id}), {
        versions: [
            versionObject(0, true, getMediaCrediting(mediaCollection), mediaCollection.map((item) => item.id)),
        ]
    });
}

const confirmationDeletion = ref(false);

const deleteSelectedItems = () => {
    const items = selectedItems.value.map((item) => item.id);

    deletePermanently(items, () => {
        deselectAll();
        sourceProperties.value.removeItems(items);
        confirmationDeletion.value = false;
    })
}
</script>
<template>
    <Head :title="$t('media.media_library')"/>

    <div class="w-full mx-auto row-py mb-2xl">
        <PageHeader :title="$t('media.media_library')"/>

        <div class="w-full row-px">
            <Tabs>
                <template v-for="tab in tabs" :key="tab">
                    <Tab @click="activeTab = tab" :active="activeTab === tab">{{ $t(`media.${tab}`) }}</Tab>
                </template>
            </Tabs>
        </div>

        <div class="w-full row-px mt-lg">
            <Panel>
                <component :is="source" ref="sourceProperties" :columns="4"/>

                <SelectableBar :count="selectedItems.length" @close="deselectAll()">
                    <SecondaryButton @click="use"
                                     :isLoading="isDownloading"
                                     :disabled="isDownloading"
                                     class="mr-sm rtl:mr-0 rtl:ml-sm"
                                     size="xs">
                        <template #icon>
                            <PlusIcon/>
                        </template>

                        {{ $t("media.create_post") }}
                    </SecondaryButton>

                    <template v-if="activeTab === 'uploads'">
                        <PureDangerButton @click="confirmationDeletion = true" v-tooltip="$t('general.delete')">
                            <TrashIcon/>
                        </PureDangerButton>
                    </template>
                </SelectableBar>
            </Panel>
        </div>
    </div>

    <ConfirmationModal :show="confirmationDeletion" variant="danger" @close="confirmationDeletion = false">
        <template #header>
            {{ $t("media.delete_media") }}
        </template>
        <template #body>
            {{ $t("media.do_you_want_delete") }}
        </template>
        <template #footer>
            <SecondaryButton @click="confirmationDeletion = false" class="mr-xs rtl:mr-0 rtl:ml-xs"> {{ $t("general.cancel") }}
            </SecondaryButton>
            <DangerButton :isLoading="isDeleting" :disabled="isDeleting" @click="deleteSelectedItems">
                {{ $t("general.delete") }}
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>
