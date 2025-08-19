import posthog from "posthog-js";

export default {
  install(app) {
    app.config.globalProperties.$posthog = posthog.init(
      'phc_Kuk4qWZvCm6fn7zgztHsy677eIFe94vDzZWzXc24fBP',
      {
        api_host: 'https://us.i.posthog.com',
      }
    );
  },
};