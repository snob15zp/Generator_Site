<template>
  <v-app>
    <v-app-bar app v-if="isAuthorized" dark color="primary" style="left: 0 !important;">
      <v-app-bar-nav-icon @click="drawer = !drawer" v-if="$vuetify.breakpoint.mdAndDown"></v-app-bar-nav-icon>
      <v-toolbar-title>Generator</v-toolbar-title>
      <v-spacer/>
      <v-btn text>
        <v-icon left>mdi-account</v-icon>
        {{ userName }}
      </v-btn>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer
        v-if="isAuthorized && canManageProfiles"
        v-model="drawer"
        absolute
        app
        class="pt-16"
    >
      <v-list nav dense>
        <v-list-item-group active-class="primary--text" v-model="selected">
          <v-list-item v-for="route in routers" :key="route.path" :to="route.path" link>
            <v-list-item-icon @click="route.push(route.path)">
              <v-icon>{{ route.meta.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-title>{{ route.meta.title }}</v-list-item-title>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <v-alert
          :value="alert"
          :type="alertType"
          dismissible
          transition="scale-transition"
          border="right"
          class="ml-2 mr-2 mt-2">{{ alertMessage }}
      </v-alert>
      <v-container fill-height class="justify-center align-start pa-0">
        <router-view/>
      </v-container>
    </v-main>
  </v-app>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import LocaleSwitcher from "@/components/LocaleSwitcher.vue";
import UserModule from "./store/modules/user";
import {EventBus} from "@/utils/event-bus";

@Component({
  components: {LocaleSwitcher},
})
export default class App extends Vue {
  private drawer = null;
  private selected = 0;

  private alert = false
  private alertMessage: string | null = null;
  private alertType: string | null = null;

  get isAuthorized() {
    return UserModule.isAuthorized;
  }

  get canManageProfiles() {
    return UserModule.canManageProfiles;
  }

  get userName() {
    return UserModule.userName;
  }

  get routers() {
    return this.$router.options.routes?.filter(config => config.meta?.navigation)
  }

  mounted() {
    EventBus.$on("error", (error: Error) => {
      this.alertType = 'error';
      this.alertMessage = error.message;
      this.alert = true;
      setTimeout(() => this.alert = false, 5000);
    });
  }

  logout() {
    UserModule.logout().then(() => {
      this.$router.push("/login");
    });
  }
}
</script>

<style lang="scss">
@import "~vuetify/src/styles/main.sass";

#app {
  background: map-get($grey, lighten-4);
}
</style>
