<script setup>
import {onBeforeUnmount, onMounted, watch, inject} from "vue";
import {useI18n} from "vue-i18n";
import emitter from "@/Services/emitter";
import {debounce, filter, isEmpty} from "lodash";
import usePostValidator from "../../Composables/usePostValidator";
import useEditor from "@/Composables/useEditor";
import usePostCharacterLimit from "../../Composables/usePostCharacterLimit";
import usePostMediaLimit from "../../Composables/usePostMediaLimit";
import usePostRequirementOperator from "../../Composables/usePostRequirementOperator";

const {t: $t} = useI18n();

const props = defineProps({
    selectedAccounts: {
        type: Array,
        required: true,
    },
    versions: {
        type: Array,
        required: true,
    },
    activeVersion: {
        type: Number,
        required: true,
    },
    activeContent: {
        type: Number,
        required: true,
    }
});

const mediaErrorGroup = 'media';
const charErrorGroup = 'char_limit';
const pinterestErrorGroup = 'pinterest';

const {addAccountError, removeAccountError, removeError, addError, isClickedPostNow, hasValidationRun, isPostEmpty } = usePostValidator();
const postCtx = inject('postCtx');
const {getTextFromHtmlString} = useEditor();

const getAccount = (accountId) => {
    return props.selectedAccounts.find(account => account.id === accountId) || null;
};

const isAccountSelected = (accountId) =>
    props.selectedAccounts.some((account) => account.id === accountId);

const getEnabledVersions = () =>
    filter(props.versions, (version) =>
        version.account_id === 0 || isAccountSelected(version.account_id)
    );

const clearErrors = () => {
    removeError({group: mediaErrorGroup});
    removeError({group: charErrorGroup});
    removeError({group: pinterestErrorGroup});
};

// Track which validation errors have been added to prevent duplicates
const addedValidationErrors = new Set();

// Centralized function to handle media validation errors
const handleMediaValidationError = ({message, contentIndex, isThread, accountId, accountName, providerName}) => {
    // Create a unique key for this error to prevent duplicates
    const errorKey = `${providerName}_${message}_${contentIndex}_${accountId}_media_validation`;

    // If this error has already been added, don't add it again
    if (addedValidationErrors.has(errorKey)) {
        return;
    }

    // Remove potential duplicate errors across different groups
    removeAccountError({
        group: mediaErrorGroup,
        key: `c_m_min_${contentIndex}`,
        accountId,
    });

    removeAccountError({
        group: charErrorGroup,
        key: `c_min_o_media_${contentIndex}`,
        accountId,
    });

    removeAccountError({
        group: charErrorGroup,
        key: `c_min_o_${contentIndex}`,
        accountId,
    });

    // Add the error
    addAccountError({
        group: mediaErrorGroup,
        key: `c_m_min_${contentIndex}`,
        message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${message}` : message,
        accountId,
        accountName,
        providerName,
    });

    // Mark this error as added
    addedValidationErrors.add(errorKey);
};

const {
    getRequirementOperator,
} = usePostRequirementOperator(props);

const {
    currentCharMaxLimit,
    currentCharMinLimit,
    currentCharUsed,
    currentCharLeft,
    getCharMaxLimit,
    getCharMinLimit,
    getTextLength,
    calculateCharLeft
} = usePostCharacterLimit(props);

const handleCharMaxLimitError = ({
                                     charLimit,
                                     charLeft,
                                     contentIndex,
                                     isThread,
                                     accountId,
                                     accountName,
                                     providerName
                                 }) => {
    if (charLeft < 0) {
        const maxCharMessage = $t('post.rules.max_char', {'count': charLimit});

        addAccountError({
            group: charErrorGroup,
            key: `c_${contentIndex}`,
            message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${maxCharMessage}` : maxCharMessage,
            accountId: accountId,
            accountName: accountName,
            providerName: providerName,
        });
    } else {
        removeAccountError({
            group: charErrorGroup,
            key: `c_${contentIndex}`,
            accountId: accountId,
        });
    }
}

// TODO: Refactor this function
const handleCharMinLimitError = ({
                                     charLimit,
                                     charUsed,
                                     contentIndex,
                                     isThread,
                                     accountId,
                                     accountName,
                                     providerName
                                 }) => {
    if (charLimit > charUsed) {
        const minCharMessage = $t('post.rules.min_char', {'count': charLimit});

        addAccountError({
            group: charErrorGroup,
            key: `c_min_${contentIndex}`,
            message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${minCharMessage}` : minCharMessage,
            accountId: accountId,
            accountName: accountName,
            providerName: providerName,
        });
    } else {
        removeAccountError({
            group: charErrorGroup,
            key: `c_min_${contentIndex}`,
            accountId: accountId,
        });
    }
}

const handleRequirementOperationOr = ({
                                          usedChar,
                                          usedMedia,
                                          charMinLimit,
                                          mediaMinLimit,
                                          contentIndex,
                                          isThread,
                                          accountId,
                                          accountName,
                                          providerName
                                      }) => {
    // Create a unique key for this validation operation
    const validationKey = `requirement_or_${accountId}_${contentIndex}`;

    // Check if we've already processed this validation
    if (addedValidationErrors.has(validationKey)) {
        return;
    }

    // Mark this validation as processed
    addedValidationErrors.add(validationKey);

    // First, remove any existing errors for this content and account to prevent duplicates
    removeAccountError({
        group: charErrorGroup,
        key: `c_min_o_${contentIndex}`,
        accountId: accountId,
    });

    removeAccountError({
        group: charErrorGroup,
        key: `c_min_o_char_${contentIndex}`,
        accountId: accountId,
    });

    removeAccountError({
        group: charErrorGroup,
        key: `c_min_o_media_${contentIndex}`,
        accountId: accountId,
    });

    // Also remove any media errors that might have been added by handleMediaMinLimitError
    removeAccountError({
        group: mediaErrorGroup,
        key: `c_m_min_${contentIndex}`,
        accountId: accountId,
    });

    const charMinCondition = {
        passes: !(usedChar < charMinLimit),
        message: $t('post.rules.min_char', {'count': charMinLimit}),
        providerName,
    };

    const mediaMinCondition = getMediaMinCondition({
        used: usedMedia,
        limits: mediaMinLimit,
        contentIndex: contentIndex,
        accountId: accountId
    });

    const messageArray = [];

    if (!charMinCondition.passes) {
        messageArray.push(charMinCondition.message);
    }

    if (!mediaMinCondition.passes) {
        messageArray.push(mediaMinCondition.message);
    }

    const message = messageArray.join(' or ');

    if (charMinCondition.providerName === mediaMinCondition.provider && messageArray.length === (charMinLimit === 0 ? 1 : 2)) {
        // If we have a media condition, use the centralized function
        if (!mediaMinCondition.passes) {
            // Use the centralized function to handle the error
            handleMediaValidationError({
                message: mediaMinCondition.message,
                contentIndex: contentIndex,
                isThread: isThread,
                accountId: accountId,
                accountName: accountName,
                providerName: mediaMinCondition.provider,
            });
        } else {
            // If it's a character limit issue, add it to the charErrorGroup
            addAccountError({
                group: charErrorGroup,
                key: `c_min_o_${contentIndex}`,
                message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${message}` : `${message}`,
                accountId: accountId,
                accountName: accountName,
                providerName: providerName,
            });
        }

        return;
    }

    if (charMinCondition.providerName !== mediaMinCondition.provider) {
        if (!charMinCondition.passes) {
            addAccountError({
                group: charErrorGroup,
                key: `c_min_o_char_${contentIndex}`,
                message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${charMinCondition.message}` : `${charMinCondition.message}`,
                accountId: accountId,
                accountName: accountName,
                providerName: charMinCondition.providerName,
            });
        }

        if (!mediaMinCondition.passes) {
            // Use the centralized function to handle the error
            handleMediaValidationError({
                message: mediaMinCondition.message,
                contentIndex: contentIndex,
                isThread: isThread,
                accountId: accountId,
                accountName: accountName,
                providerName: mediaMinCondition.provider,
            });

            // Remove any potential duplicate in the charErrorGroup
            removeAccountError({
                group: charErrorGroup,
                key: `c_min_o_media_${contentIndex}`,
                accountId: accountId,
            });
        }

        return;
    }
}

const {
    mediaTypesBasic,
    currentMediaMaxLimits,
    currentMediaMinLimits,
    currentMediaUsed,
    getMediaMinLimits,
    getMediaMaxLimits,
    getMediaLength
} = usePostMediaLimit(props);
const handleMediaMaxLimitError = ({used, limits, contentIndex, isThread, accountId, accountName}) => {
    mediaTypesBasic.forEach((type) => {
        if (used[type] > limits[type].limit) {
            addAccountError({
                group: mediaErrorGroup,
                key: `c_${contentIndex}_${type}`,
                message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${$t(`post.rules.max_${type}`, limits[type].limit)}` : $t(`post.rules.max_${type}`, limits[type].limit),
                accountId,
                accountName,
                providerName: limits[type].provider,
            });
        } else {
            removeAccountError({group: mediaErrorGroup, key: `c_${contentIndex}_${type}`, accountId});
        }
    });

    if (used.mixing && !limits.allow_mixing.limit) {
        addAccountError({
            group: mediaErrorGroup,
            key: `c_${contentIndex}_mixing`,
            message: isThread ? `${$t('post.post')} #${contentIndex + 1} - ${$t('post.rules.no_mixed_media')}` : $t('post.rules.no_mixed_media'),
            accountId,
            accountName,
            providerName: limits.allow_mixing.provider,
        });
    } else {
        removeAccountError({group: mediaErrorGroup, key: `c_${contentIndex}_mixing`, accountId});
    }
};

const getMediaMinCondition = ({used, limits, contentIndex, accountId}) => {
    const requiredMedia = Object.keys(limits).filter(format => limits[format] !== null);

    if (!requiredMedia.length) return {
        passes: true,
        message: '',
        provider: ''
    };

    // TODO: rename to providerName, because this is not an object.
    const provider = limits[requiredMedia[0]].provider; // All media types have the same provider
    let message = '';

    // Check if any media type with a limit is already used
    const unmetLimits = requiredMedia.filter(mediaType => limits[mediaType].limit > 0 && used[mediaType] < limits[mediaType].limit);

    if (!unmetLimits.length) {
        // Just return the result, don't remove errors here
        return {
            passes: true,
            message,
            provider
        };
    }

    // If any type of media has met its limit, return null
    for (const mediaType of Object.keys(used)) {
        if (!limits.hasOwnProperty(mediaType)) {
            continue;
        }

        if (limits[mediaType].limit !== 0 && used[mediaType] >= limits[mediaType].limit) {
            // Just return the result, don't remove errors here
            return {
                passes: true,
                message,
                provider
            };
        }
    }

    // TODO: Translate message
    // Construct message based on unmet limits
    if (unmetLimits.length === 1) {
        message = `A minimum of 1 ${unmetLimits[0].slice(0, -1)} is required.`;
    }

    if (unmetLimits.length > 1) {
        const mediaTypes = unmetLimits.map(mediaType => mediaType.slice(0, -1));
        message = `A minimum of 1 ${mediaTypes.join(' or ')} is required.`;
    }

    return {
        passes: message === '',
        message,
        provider
    }
}

// Refactored to use centralized error handling with improved duplicate prevention
const handleMediaMinLimitError = ({used, limits, contentIndex, isThread, accountId, accountName}) => {
    // Create a unique key for this validation operation
    const validationKey = `media_min_${accountId}_${contentIndex}`;

    // Check if we've already processed this validation
    if (addedValidationErrors.has(validationKey)) {
        return;
    }

    // Mark this validation as processed
    addedValidationErrors.add(validationKey);

    const {passes, message, provider} = getMediaMinCondition({used, limits, contentIndex, accountId});

    if (!passes) {
        // Use the centralized function to handle the error
        handleMediaValidationError({
            message: message,
            contentIndex: contentIndex,
            isThread: isThread,
            accountId: accountId,
            accountName: accountName,
            providerName: provider,
        });
    }
};

// Function to validate URL format
const isValidUrl = (url) => {
    try {
        // Check if URL has http:// or https:// prefix
        if (!url.match(/^https?:\/\//i)) {
            return false;
        }

        // Use URL constructor to validate the URL format
        const urlObj = new URL(url);

        // Check if the URL has a valid domain (at least one dot in hostname)
        return urlObj.hostname.includes('.');
    } catch (e) {
        // If URL constructor throws an error, the URL is invalid
        return false;
    }
};

// Validate Pinterest-specific fields (title, link, board)
const validatePinterestFields = ({ options, contentIndex, isThread, accountId, accountName }) => {
    // Check if title is empty
    if (!options.title || options.title?.trim() === '') {
        addAccountError({
            group: pinterestErrorGroup,
            key: `title_${contentIndex}`,
            message: isThread ?
                `${$t('post.post')} #${contentIndex + 1} - ${$t('general.title')} ${$t('general.is_required')}` :
                `${$t('general.title')} ${$t('general.is_required')}`,
            accountId,
            accountName,
            providerName: 'Pinterest',
        });
    } else {
        removeAccountError({
            group: pinterestErrorGroup,
            key: `title_${contentIndex}`,
            accountId,
        });
    }

    // Check if link is empty or invalid
    const link = options.link?.trim() || '';

    if (link === '') {
        // Link is empty
        addAccountError({
            group: pinterestErrorGroup,
            key: `link_${contentIndex}`,
            message: isThread ?
                `${$t('post.post')} #${contentIndex + 1} - ${$t('general.link')} ${$t('general.is_required')}` :
                `${$t('general.link')} ${$t('general.is_required')}`,
            accountId,
            accountName,
            providerName: 'Pinterest',
        });

        // Remove any URL format error since we're showing the required error
        removeAccountError({
            group: pinterestErrorGroup,
            key: `link_format_${contentIndex}`,
            accountId,
        });
    } else if (!isValidUrl(link)) {
        // Link is present but not a valid URL
        addAccountError({
            group: pinterestErrorGroup,
            key: `link_format_${contentIndex}`,
            message: isThread ?
                `${$t('post.post')} #${contentIndex + 1} - Please enter a valid URL including http:// or https://` :
                `Please enter a valid URL including http:// or https://`,
            accountId,
            accountName,
            providerName: 'Pinterest',
        });

        // Remove the required error since we're showing the format error
        removeAccountError({
            group: pinterestErrorGroup,
            key: `link_${contentIndex}`,
            accountId,
        });
    } else {
        // Link is valid, remove both error types
        removeAccountError({
            group: pinterestErrorGroup,
            key: `link_${contentIndex}`,
            accountId,
        });

        removeAccountError({
            group: pinterestErrorGroup,
            key: `link_format_${contentIndex}`,
            accountId,
        });
    }

    // Check if board is selected for each Pinterest account
    const boards = Object.fromEntries(Object.entries(options.boards).filter(([_, value]) => value !== null));
    if (!boards || Object.keys(boards).length == 0) {
        addAccountError({
            group: pinterestErrorGroup,
            key: `board_${contentIndex}`,
            message: isThread ?
                `${$t('post.post')} #${contentIndex + 1} - ${$t('service.pinterest.select_board')} ${$t('general.is_required')}` :
                `${$t('service.pinterest.select_board')} ${$t('general.is_required')}`,
            accountId,
            accountName,
            providerName: 'Pinterest',
        });
    } else {
        removeAccountError({
            group: pinterestErrorGroup,
            key: `board_${contentIndex}`,
            accountId,
        });
    }
};

watch(isClickedPostNow, (newVal) => {
    if (newVal) {
        // Force validation when isClickedPostNow becomes true
        clearErrors();
        addedValidationErrors.clear();
        init();
    }
}, { immediate: false });

// Watch for changes in hasValidationRun
watch(hasValidationRun, (newVal) => {
    // If hasValidationRun becomes true, ensure validation runs
    if (newVal) {
        handleUpdate();
    }
}, { immediate: true });

// Watch for changes in Pinterest options
watch(() => {
    return getEnabledVersions().find(v => v.account_id === props.activeVersion)?.options?.pinterest;
}, () => {
    // Only validate if initial validation has run (after POST NOW was clicked)
    if (!hasValidationRun.value) {
        return;
    }
    const pinterestAccounts = props.selectedAccounts.filter((account) => account.provider === 'pinterest');
    if (pinterestAccounts.length == 0) return;

    const isThread = getEnabledVersions().some(
        (version) => version.account_id === props.activeVersion && version.content.length > 1
    );
    const version = getEnabledVersions().find(v => v.account_id === props.activeVersion);

    if (version) {
        for (const account of pinterestAccounts) {
            validatePinterestFields({
                options: version.options.pinterest || {},
                contentIndex: props.activeContent,
                isThread,
                accountId: props.activeVersion,
                accountName: account?.name || "",
            });
        }
    }
}, { deep: true });

watch(
    [
        currentCharLeft,
        currentCharMinLimit,
        currentCharMaxLimit,
        currentMediaMaxLimits,
        currentMediaMinLimits,
        currentMediaUsed,
    ],
    debounce(() => {
        // Only validate if initial validation has run (after POST NOW was clicked)
        // This allows real-time validation after the initial validation
        if (!hasValidationRun.value) {
            return;
        }
        const account = getAccount(props.activeVersion);
        const isThread = getEnabledVersions().some(
            (version) =>
                version.account_id === props.activeVersion && version.content.length > 1
        );

        const requirementOperator = getRequirementOperator(props.activeVersion);

        handleCharMaxLimitError({
            charLimit: currentCharMaxLimit.value.limit,
            charLeft: currentCharLeft.value,
            contentIndex: props.activeContent,
            isThread,
            accountId: props.activeVersion,
            accountName: account?.name || "",
            providerName: currentCharMaxLimit.value?.provider.name,
        });

        if (currentMediaMaxLimits.value) {
            handleMediaMaxLimitError({
                used: currentMediaUsed.value,
                limits: currentMediaMaxLimits.value,
                contentIndex: props.activeContent,
                isThread,
                accountId: props.activeVersion,
                accountName: account?.name || "",
            });
        }
        if (requirementOperator && requirementOperator.operator === "or") {
            handleRequirementOperationOr({
                usedChar: currentCharUsed.value,
                usedMedia: currentMediaUsed.value,
                charMinLimit: currentCharMinLimit.value?.limit || 0,
                mediaMinLimit: currentMediaMinLimits?.value,
                contentIndex: props.activeContent,
                isThread,
                accountId: props.activeVersion,
                accountName: account?.name || "",
                providerName: currentCharMinLimit.value?.provider.name,
            });
        } else {
            handleCharMinLimitError({
                charLimit: currentCharMinLimit.value?.limit || 0,
                charUsed: currentCharUsed.value,
                contentIndex: props.activeContent,
                isThread,
                accountId: props.activeVersion,
                accountName: account?.name || "",
                providerName: currentCharMinLimit.value?.provider.name,
            });

            // Only run this if we're not using the OR operator
            if (currentMediaMinLimits.value) {
                handleMediaMinLimitError({
                    used: currentMediaUsed.value,
                    limits: currentMediaMinLimits.value,
                    contentIndex: props.activeContent,
                    isThread,
                    accountId: props.activeVersion,
                    accountName: account?.name || "",
                });
            }
        }
    }, 100));

// Function to remove duplicate error messages across all error groups
const removeDuplicateErrors = () => {
    const postCtx = inject('postCtx');
    if (!postCtx.errors) return;

    // Track all error messages we've seen
    const seenMessages = new Set();

    // Check each error group
    Object.keys(postCtx.errors).forEach(group => {
        // Track keys to remove
        const keysToRemove = [];

        // Check each error in this group
        Object.entries(postCtx.errors[group]).forEach(([key, message]) => {
            // If we've seen this message before, mark it for removal
            if (seenMessages.has(message)) {
                keysToRemove.push(key);
            } else {
                // Otherwise, add it to our set of seen messages
                seenMessages.add(message);
            }
        });

        // Remove the duplicate keys
        keysToRemove.forEach(key => {
            delete postCtx.errors[group][key];
        });

        // Remove empty groups
        if (isEmpty(postCtx.errors[group])) {
            delete postCtx.errors[group];
        }
    });
};

const init = () => {
    // Clear all errors before running validation to prevent duplicates
    clearErrors();

    // Clear the validation error tracking set
    addedValidationErrors.clear();

    getEnabledVersions().forEach((version) => {
        const account = getAccount(version.account_id);
        const accountName = account?.name;
        const isThread = version.content.length > 1;

        const charMaxLimit = getCharMaxLimit(version.account_id);
        const charMinLimit = getCharMinLimit(version.account_id);

        const mediaMaxLimits = getMediaMaxLimits(version.account_id);
        const mediaMinLimits = getMediaMinLimits(version.account_id);

        const requirementOperator = getRequirementOperator(version.account_id);

        version.content.forEach((item, index) => {
            const text = getTextFromHtmlString(item.body);
            const usedMedia = getMediaLength(item.media);

            if (charMaxLimit?.limit) {
                handleCharMaxLimitError({
                    charLimit: charMaxLimit.limit,
                    charLeft: calculateCharLeft(charMaxLimit.limit, getTextLength(charMaxLimit?.provider.id, text)),
                    contentIndex: index,
                    isThread,
                    accountId: version.account_id,
                    accountName: accountName || '',
                    providerName: charMaxLimit?.provider.name,
                });
            }

            if (mediaMaxLimits) {
                handleMediaMaxLimitError({
                    used: usedMedia,
                    limits: mediaMaxLimits,
                    contentIndex: index,
                    isThread,
                    accountId: version.account_id,
                    accountName: accountName || '',
                });
            }

            const pinterestAccounts = props.selectedAccounts.filter((account) => account.provider === 'pinterest');
            if (pinterestAccounts.length > 0) {
                const version = getEnabledVersions().find(v => v.account_id === props.activeVersion);
                if (version) {
                    for (const account of pinterestAccounts) {
                        validatePinterestFields({
                            options: version.options.pinterest || {},
                            contentIndex: props.activeContent,
                            isThread,
                            accountId: props.activeVersion,
                            accountName: account?.name || "",
                        });
                    }
                }
            }

            if (requirementOperator && requirementOperator.operator === 'or') {
                const textLength = getTextLength(charMinLimit?.provider.id, text);

                handleRequirementOperationOr({
                    usedChar: textLength,
                    usedMedia: usedMedia,
                    charMinLimit: charMinLimit?.limit || 0,
                    mediaMinLimit: mediaMinLimits,
                    contentIndex: index,
                    isThread,
                    accountId: version.account_id,
                    accountName: accountName || '',
                    providerName: charMinLimit?.provider.name,
                });

                return;
            }

            if (requirementOperator && requirementOperator.operator === 'and') {
                handleCharMinLimitError({
                    charLimit: charMinLimit?.limit || 0,
                    charUsed: getTextLength(charMinLimit?.provider.id, text),
                    contentIndex: index,
                    isThread,
                    accountId: version.account_id,
                    accountName: accountName || '',
                    providerName: charMinLimit?.provider.name,
                });

                if (mediaMinLimits) {
                    handleMediaMinLimitError({
                        used: usedMedia,
                        limits: mediaMinLimits,
                        contentIndex: index,
                        isThread,
                        accountId: version.account_id,
                        accountName: accountName || '',
                    });
                }
            }
        });
    });

    // After all validation is complete, remove any duplicate error messages
    removeDuplicateErrors();
};

const handleUpdate = () => {
    // Always clear errors first
    clearErrors();

    // Only run full validation if initial validation has run (after POST NOW was clicked)
    // or if isClickedPostNow is true (for the initial validation)
    if (hasValidationRun.value || isClickedPostNow.value) {
        init();
    } else {
        // When validation hasn't run yet, we only want to check if the post is empty
        // to enable/disable the POST NOW button, but we don't want to show any validation errors
        // This is handled in the PostActions component
    }
};

// These watches ensure validation runs when props change
watch(() => props.activeVersion, handleUpdate);
watch(() => props.versions.length, handleUpdate);
watch(() => props.selectedAccounts, handleUpdate);

// Watch for changes in post content (deep watch)
watch(() => props.versions, () => {
    // Only run validation if initial validation has run (after POST NOW was clicked)
    if (hasValidationRun.value) {
        handleUpdate();
    }
    // When validation hasn't run yet, we don't want to show any validation errors
    // This ensures errors only appear after the user clicks POST NOW
}, { deep: true });

onMounted(() => {
    emitter.on('postVersionContentDeleted', () => {
        // Use handleUpdate which will check hasValidationRun internally
        handleUpdate();
    });

    // Just clear errors on mount without running validation
    clearErrors();
});

onBeforeUnmount(() => {
    emitter.off('postVersionContentDeleted');
});
</script>
<template></template>
