import Vue from "vue";
import App from "./App.vue";
import router from "./router";
import store from "./store";
import vuetify from "./plugins/vuetify";
import i18n from "./i18n";
import "./utils/filters";
import axios from "axios";
import {settings} from "./settings";

import Vuelidate from "vuelidate";
Vue.use(Vuelidate);

axios.defaults.baseURL = settings.apiUrl;
axios.defaults.headers.post['Content-Type'] = 'application/json;charset=utf-8';
axios.defaults.headers.post['Access-Control-Allow-Origin'] = '*';

Vue.config.productionTip = false;

new Vue({
  i18n,
  router,
  store,
  vuetify,
  render: (h) => h(App)
}).$mount("#app");
