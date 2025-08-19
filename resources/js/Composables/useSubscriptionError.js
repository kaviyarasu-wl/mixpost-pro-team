import { ref } from 'vue';

// Create a singleton instance to share state across all components
const globalSubscriptionState = {
    showSubscriptionModal: ref(false)
};

const useSubscriptionError = () => {
    /**
     * Check if the error response contains a subscription error
     * @param {Object} error - Axios error object
     * @returns {boolean} - True if subscription error is found
     */
    const hasSubscriptionError = (error) => {
        if (!error.response || error.response.status !== 422) {
            return false;
        }

        const validationErrors = error.response.data.errors;
        return validationErrors && validationErrors.hasOwnProperty('subscription');
    };

    /**
     * Check if a flash message contains account limit error
     * @param {string} message - Flash error message
     * @returns {boolean} - True if account limit error is found
     */
    const hasAccountLimitError = (message) => {
        return message && (
            message.includes('maximum number of accounts') ||
            message.includes('account limit') ||
            message.includes('reached the limit')
        );
    };

    /**
     * Handle subscription error by showing the upgrade modal
     * @param {Object} error - Axios error object
     * @returns {boolean} - True if subscription error was handled
     */
    const handleSubscriptionError = (error) => {
        if (hasSubscriptionError(error)) {
            globalSubscriptionState.showSubscriptionModal.value = true;
            return true;
        }
        return false;
    };

    /**
     * Handle account limit error from flash messages
     * @param {string} message - Flash error message
     * @returns {boolean} - True if account limit error was handled
     */
    const handleAccountLimitError = (message) => {
        if (hasAccountLimitError(message)) {
            globalSubscriptionState.showSubscriptionModal.value = true;
            return true;
        }
        return false;
    };

    /**
     * Close the subscription upgrade modal
     */
    const closeSubscriptionModal = () => {
        globalSubscriptionState.showSubscriptionModal.value = false;
    };

    /**
     * Reset modal state (useful for testing or manual state management)
     */
    const resetSubscriptionModal = () => {
        globalSubscriptionState.showSubscriptionModal.value = false;
    };

    return {
        showSubscriptionModal: globalSubscriptionState.showSubscriptionModal,
        hasSubscriptionError,
        hasAccountLimitError,
        handleSubscriptionError,
        handleAccountLimitError,
        closeSubscriptionModal,
        resetSubscriptionModal
    };
};

export default useSubscriptionError;
