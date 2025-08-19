import {computed, inject, reactive} from "vue";
import {isEmpty} from "lodash";
import useEditor from "./useEditor";

const state = reactive({
    isClickedPostNow: false,
    hasValidationRun: false
});

const usePost = () => {
    const postCtx = inject('postCtx');

    const errors = computed(() => {
        return postCtx.errors;
    })

    const isClickedPostNow = computed({
        get: () => state.isClickedPostNow,
        set: (value) => {
            state.isClickedPostNow = value;
            // When isClickedPostNow becomes true, also set hasValidationRun to true
            if (value) {
                state.hasValidationRun = true;
            }
        }
    });

    const hasValidationRun = computed({
        get: () => state.hasValidationRun,
        set: (value) => {
            state.hasValidationRun = value;
        }
    });

    const setIsClickedPostNow = (value) => {
        state.isClickedPostNow = value;
        // When isClickedPostNow becomes true, also set hasValidationRun to true
        if (value) {
            state.hasValidationRun = true;
        }
    };

    const setHasValidationRun = (value) => {
        state.hasValidationRun = value;
    };

    const isPostEmpty = (versions) => {
        // Import the getTextFromHtmlString function
        const { getTextFromHtmlString } = useEditor();

        if (versions.length === 0 || versions[0].content.length === 0) {
            return true;
        }

        // Return true if any version has empty content
        return versions.some(version => {
            return version.content.some(content => {
                // Extract text from HTML and check if it's empty after trimming
                const extractedText = getTextFromHtmlString(content.body || '').trim();
                return (extractedText === '') &&
                       (!content.media || content.media.length === 0);
            });
        });
    };

    const validationPassed = computed(() => {
        return isEmpty(errors.value);
    });

    const addError = ({group, key, message}) => {
        if (!postCtx.errors[group]) {
            postCtx.errors[group] = {};
        }

        // Check if this exact error message already exists in any key in this group
        // This prevents duplicate error messages regardless of the key
        const existingErrorValues = Object.values(postCtx.errors[group]);
        if (existingErrorValues.includes(message)) {
            return; // Skip adding this error as it's a duplicate
        }

        postCtx.errors[group][key] = message;
    }

    const addAccountError = ({group, key, message, accountId, accountName, providerName}) => {
        let msg = providerName ? `${providerName}` : '';
        msg += accountName ? ` (${accountName})` : '';
        msg += message ? ` → ${message}` : '';

        // Check if this exact error message already exists in any key in this group
        // This prevents duplicate error messages regardless of the key
        if (postCtx.errors[group]) {
            const existingErrorValues = Object.values(postCtx.errors[group]);
            if (existingErrorValues.includes(msg)) {
                return; // Skip adding this error as it's a duplicate
            }
        }

        addError({
            group,
            key: `${accountId}_${key}`,
            message: msg
        });
    }

    const removeError = ({group, key = null}) => {
        if (!postCtx.errors || !postCtx.errors[group]) {
            return;
        }

        if (!key) {
            delete postCtx.errors[group];
            return;
        }

        if (postCtx.errors[group][key]) {
            delete postCtx.errors[group][key];
        }

        if (isEmpty(postCtx.errors[group])) {
            delete postCtx.errors[group];
        }
    }

    const removeAccountError = ({group, key, accountId}) => {
        removeError({
            group,
            key: `${accountId}_${key}`
        });
    }

    // Clear provider-specific validation errors
    const clearProviderErrors = () => {
        if (!postCtx.errors) return;

        // Look through all error groups and remove provider-specific errors
        Object.keys(postCtx.errors).forEach(group => {
            // Skip the empty_post group as it's handled separately
            if (group === 'empty_post') return;

            // For char_limit and media groups, clear all errors
            if (group === 'char_limit' || group === 'media') {
                delete postCtx.errors[group];
                return;
            }

            // For other groups, check each error message
            Object.keys(postCtx.errors[group] || {}).forEach(key => {
                const errorMessage = postCtx.errors[group][key];
                // Check if the error message contains any provider name
                if (errorMessage && (
                    errorMessage.includes('LinkedIn') ||
                    errorMessage.includes('Twitter') ||
                    errorMessage.includes('Facebook') ||
                    errorMessage.includes('Instagram')
                )) {
                    delete postCtx.errors[group][key];
                }
            });

            // Remove empty groups
            if (isEmpty(postCtx.errors[group])) {
                delete postCtx.errors[group];
            }
        });
    }

    const checkEmptyPost = (post) => {
        const isEmpty = isPostEmpty(post.versions);
        const hasAccounts = post.accounts.length > 0;

        if (isEmpty && hasAccounts) {
            addError({
                group: 'empty_post',
                key: 'empty',
                message: 'The post is empty. Please enter a message or media to share.'
            });
            return false;
        } else {
            // Always remove the empty_post error when content is present
            removeError({
                group: 'empty_post'
            });

            // Also clear provider errors when content is added
            if (!isEmpty) {
                clearProviderErrors();
            }

            return true;
        }
    };

    const clearErrors = () => {
        postCtx.errors = {};
    }

    // Reset validation state when form changes
    const resetValidationState = () => {
        state.isClickedPostNow = false;
        state.hasValidationRun = false;
        clearErrors();
    }

    return {
        errors,
        validationPassed,
        addError,
        addAccountError,
        removeError,
        removeAccountError,
        clearErrors,
        clearProviderErrors,
        isPostEmpty,
        checkEmptyPost,
        isClickedPostNow,
        setIsClickedPostNow,
        hasValidationRun,
        setHasValidationRun,
        resetValidationState,
    }
}

export default usePost;
