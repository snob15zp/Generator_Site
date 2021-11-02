<template>
  <v-app>
    <v-app-bar app flat color="#1e1a1a" dark v-if="isAuthorized" class="v-bar--underline">
      <v-app-bar-nav-icon
          @click="drawer = !drawer"
          v-if="$vuetify.breakpoint.mdAndDown && showNavigation"></v-app-bar-nav-icon>
      <v-toolbar-title v-if="!showNavigation" class="site-title">
        <img src="@/assets/logo-small.png" alt="logo"/>
        <span>InHealion</span>
      </v-toolbar-title>
      <v-spacer/>
      <div class="pa-4 d-flex">
        <v-icon class="ma-2" x-large>mdi-account</v-icon>
        <div class="mt-1">{{ userName }}<small class="d-flex">{{ userRole }}</small></div>
      </div>
      <v-btn icon color="primary" @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer
        v-if="isAuthorized && showNavigation"
        v-model="drawer"
        floating
        app>
      <v-app-bar flat color="#1e1a1a" dark class="v-bar--underline" style="height: 64px">
        <v-toolbar-title class="site-title">
          <img src="@/assets/logo-small.png" alt="logo"/>
          <span>InHealion</span>
        </v-toolbar-title>
      </v-app-bar>
      <v-list nav dense class="mt-2">
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
      <v-container class="justify-center align-start pa-0">
        <v-layout fill-height fluid>
          <router-view/>
        </v-layout>
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

  get showNavigation() {
    return UserModule.canManageProfiles && this.$route.meta?.requiresAuth;
  }

  get userName() {
    return UserModule.userName;
  }

  get userRole() {
    return UserModule.userRole;
  }

  get windowHeight() {
    return window.innerHeight + 'px';
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

  .v-bar--underline {
    border-bottom-style: solid !important;
    border-bottom-width: 1px !important;
    border-bottom-color: rgba(0, 0, 0, .12) !important;
  }

  background: map-get($grey, lighten-4);

  .site-title {
    font-family: termina, sans-serif;
    font-weight: 700;
    font-style: normal;

    span {
      float: right;
      padding-top: 6px;
    }

    img {
      float: left;
      margin-right: 12px;
    }
  }
}
</style>
