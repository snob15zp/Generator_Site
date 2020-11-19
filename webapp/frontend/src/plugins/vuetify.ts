import Vue from "vue";
import Vuetify from "vuetify";
import "vuetify/dist/vuetify.min.css";
import "@mdi/font/css/materialdesignicons.css";
import i18n from "../i18n";

Vue.use(Vuetify);

export default new Vuetify({
  lang: {
    t: (key, ...params) => i18n.t(key, params).toString()
  },
  icons: {
    iconfont: "mdi"
  }
});
