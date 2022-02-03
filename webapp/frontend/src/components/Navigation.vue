<template>
  <v-navigation-drawer
      v-if="isAuthorized && showNavigation && isRoutesNotEmpty"
      v-model="visibleSync"
      floating
      app>
    <v-app-bar flat dark class="v-bar--underline">
      <site-title/>
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
</template>

<script lang="ts">

import {Component, Prop, PropSync, Vue} from "vue-property-decorator";
import UserModule from "@/store/modules/user";
import SiteTitle from "@/components/SiteTitle.vue";
import {RouteConfig, RouterOptions} from "vue-router/types/router";
import {navigationRouters} from "@/router";

@Component({
  components: {SiteTitle}
})
export default class Navigation extends Vue {
  @PropSync('visible') readonly visibleSync!: boolean;

  private selected = 0;

  get isAuthorized() {
    return UserModule.isAuthorized;
  }

  get isRoutesNotEmpty() {
    return (this.routers?.length || 0) > 0;
  }

  get showNavigation() {
    return this.$route.meta?.requiresAuth;
  }

  get routers() {
    return navigationRouters(UserModule.user, this.$router.options.routes);
  }

  private isRouterAllow(config: RouteConfig) {
    if (config.meta?.privileges) {
      return UserModule.user?.privileges?.some(p => config.meta?.privileges?.indexOf(p) >= 0);
    } else {
      return true;
    }
  }
}

</script>

<style scoped>

.v-bar--underline {
  background-color: rgb(30, 26, 26) !important;
}
</style>