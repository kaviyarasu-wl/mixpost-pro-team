import {ref} from 'vue';

const usePreloader = (delay = 250) => {
    const isLoadingPreloader = ref(false);
    let timeoutId = null;

    const startPreloader = () => {
        timeoutId = setTimeout(() => {
            isLoadingPreloader.value = true;
        }, delay);
    };

    const stopPreloader = () => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        isLoadingPreloader.value = false;
    };

    return {
        isLoadingPreloader,
        startPreloader,
        stopPreloader
    };
}

export default usePreloader;
