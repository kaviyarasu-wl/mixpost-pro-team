<script setup>
import Radio from "@/Components/Form/Radio.vue";
import ProviderOptionWrap from "@/Components/ProviderVersionOptions/ProviderOptionWrap.vue";
import {onMounted, watch} from "vue";
import usePostMetaOptionsValidate from "../../Composables/usePostMetaOptionsValidate";

const props = defineProps(['options', 'activeVersion', 'versions'])

const {postTypeDisabled, postReelDisabled, validatePostType} = usePostMetaOptionsValidate();

const handleValidate = () => {
    validatePostType({options: props.options, activeVersion: props.activeVersion, versions: props.versions});
};

watch(props.versions, handleValidate)

onMounted(handleValidate)
</script>
<template>
    <ProviderOptionWrap :title="$t('service.provider_options', {provider: 'Instagram'})" provider="instagram">
        <div>
            <div class="flex items-center space-x-sm">
                <label>
                    <Radio v-model:checked="options.type" :disabled="postTypeDisabled" value="post"/>
                    {{ $t('service.meta.post') }}
                </label>
                <label>
                    <Radio v-model:checked="options.type" :disabled="postReelDisabled" value="reel"/>
                    {{ $t('service.meta.reel') }}
                </label>
                <label>
                    <Radio v-model:checked="options.type" value="story"/>
                    {{ $t('service.meta.story') }}
                </label>
            </div>
        </div>
    </ProviderOptionWrap>
</template>
