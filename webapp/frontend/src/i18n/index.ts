import Vue from "vue";
import VueI18n from "vue-i18n";

import en from "./locales/en.json";

const defaultLocale = localStorage.getItem("locale") || "en";

const languages = {
  en: en
};

const messages = Object.assign(languages);

Vue.use(VueI18n);

const i18n = new VueI18n({
  locale: defaultLocale,
  fallbackLocale: "en",
  messages
});

export default i18n;
