<template>
  <v-app>
    <v-app-bar app v-if="isAuthorized" dark color="primary">
      <v-btn icon @click="$router.push('/')">
        <v-icon>mdi-home</v-icon>
      </v-btn>
      <v-toolbar-title>{{ $router.currentRoute.meta.title }}</v-toolbar-title>
      <v-spacer />
      <v-btn text><v-icon left>mdi-account</v-icon>{{ userName }}</v-btn>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>
    <v-main>
      <v-container fill-height class="justify-center align-start" pa-0>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import LocaleSwitcher from "@/components/LocaleSwitcher.vue";
import UserModule from "./store/modules/user";

@Component({
  components: { LocaleSwitcher }
})
export default class App extends Vue {
  get isAuthorized() {
    return UserModule.isAuthorized;
  }
  get userName() {
    return UserModule.userName;
  }

  logout() {
    UserModule.logout();
    this.$router.push("/login");
  }
}
</script>

<style lang="scss">
@import "~vuetify/src/styles/main.sass";

#app {
  background: map-get($grey, lighten-4);
}
</style>
