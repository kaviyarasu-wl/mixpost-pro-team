<script setup>
import {computed, inject, ref} from "vue";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import {router} from "@inertiajs/vue3";
import emitter from "@/Services/emitter";
import useNotifications from "@/Composables/useNotifications";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import EllipsisVerticalIcon from "@/Icons/EllipsisVertical.vue"
import Dropdown from "@/Components/Dropdown/Dropdown.vue"
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue"
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import DangerButton from "@/Components/Button/DangerButton.vue"
import PencilSquareIcon from "@/Icons/PencilSquare.vue";
import DuplicateIcon from "@/Icons/Duplicate.vue";
import TrashIcon from "@/Icons/Trash.vue";
import ArrowUturnLeft from "../../Icons/ArrowUturnLeft.vue";
import useWorkspace from "../../Composables/useWorkspace.js";
import Eye from "../../Icons/Eye.vue";

const {t: $t} = useI18n()

const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    },
    trashed: {
        type: Boolean,
        default: false
    },
    hasDeletedAccounts: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['onDelete'])

const confirmationDeletion = ref(false);

const filterStatus = computed(() => {
    const pageProps = usePage().props;

    return pageProps.hasOwnProperty('filter') ? pageProps.filter.status : null;
});

const {notify} = useNotifications();
const {isWorkspaceEditorRole} = useWorkspace();

const deletePost = () => {
    router.delete(route('mixpost.posts.delete', {
        workspace: workspaceCtx.id,
        post: props.itemId,
        status: filterStatus.value
    }), {
        onSuccess() {
            confirmationDeletion.value = false;
            notify('success', filterStatus.value === 'trash' ? $t("post.post_deleted_permanently") : $t("post.post_moved_to_trash"))
            emit('onDelete')
            emitter.emit('postDelete', props.itemId);
        }
    })
}

const duplicate = () => {
    router.post(route('mixpost.posts.duplicate', {workspace: workspaceCtx.id, post: props.itemId}), {}, {
        onSuccess() {
            notify('success', $t('post.post_duplicated'))
        }
    })
}

const restore = () => {
    router.post(route('mixpost.posts.restore', {workspace: workspaceCtx.id, post: props.itemId}), {}, {
        onSuccess() {
            notify('success', $t('post.post_restored'))
        }
    })
}
</script>
<template>
    <div>
        <!-- Show nothing for posts with deleted accounts -->
        <template v-if="hasDeletedAccounts">
            <!-- No actions for deleted accounts -->
        </template>

        <!-- Show normal actions for posts with active accounts -->
        <template v-else>
            <div class="flex flex-row items-center gap-xs">
                <PureButtonLink :href="route('mixpost.posts.edit', { workspace: workspaceCtx.id, post: itemId })"
                                v-tooltip="$t(!isWorkspaceEditorRole ? 'general.view' : 'general.edit')">
                    <template v-if="!isWorkspaceEditorRole">
                        <Eye/>
                    </template>
                    <template v-else>
                        <PencilSquareIcon/>
                    </template>
                </PureButtonLink>

                <template v-if="isWorkspaceEditorRole">
                    <Dropdown placement="bottom-end">
                        <template #trigger>
                            <PureButton class="mt-1">
                                <EllipsisVerticalIcon/>
                            </PureButton>
                        </template>

                        <template #content>
                            <template v-if="trashed">
                                <DropdownItem @click="restore" as="button">
                                    <template #icon>
                                        <ArrowUturnLeft/>
                                    </template>
                                    {{ $t("general.restore") }}
                                </DropdownItem>
                            </template>

                            <DropdownItem @click="duplicate" as="button">
                                <template #icon>
                                    <DuplicateIcon/>
                                </template>

                                {{ $t("general.duplicate") }}
                            </DropdownItem>

                            <DropdownItem @click="confirmationDeletion = true" as="button">
                                <template #icon>
                                    <TrashIcon class="text-red-500"/>
                                </template>

                                {{ $t("general.delete") }}
                            </DropdownItem>
                        </template>
                    </Dropdown>
                </template>
            </div>
        </template>

        <ConfirmationModal :show="confirmationDeletion" variant="danger" @close="confirmationDeletion = false">
            <template #header>
                {{ $t("post.delete_post") }}
            </template>
            <template #body>
                {{ $t("post.confirm_delete_post") }}
            </template>
            <template #footer>
                <SecondaryButton @click="confirmationDeletion = false" class="mr-xs"> {{ $t("general.cancel") }}
                </SecondaryButton>
                <DangerButton @click="deletePost">{{
                        trashed ? $t("general.delete_permanently") : $t("general.delete")
                    }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
