<template>
  <v-navigation-drawer
      v-if="isAuthorized && showNavigation"
      v-model="computedVisible"
      floating
      app>
    <v-app-bar flat dark class="v-bar--underline">
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
</template>

<script lang="ts">

import {Component, Prop, Vue} from "vue-property-decorator";
import UserModule from "@/store/modules/user";

@Component
export default class Navigation extends Vue {
  @Prop() readonly visible!: boolean;

  private selected = 0;

  get computedVisible() {
    return this.visible;
  }

  set computedVisible(value: boolean) {
    this.$emit('update:visible', value);
  }

  get isAuthorized() {
    return UserModule.isAuthorized;
  }

  get showNavigation() {
    return UserModule.canManageProfiles && this.$route.meta?.requiresAuth;
  }

  get routers() {
    return this.$router.options.routes?.filter(config => config.meta?.navigation)
  }
}

</script>

<style scoped>

.v-bar--underline {
  background-color: rgb(30, 26, 26)!important;
}

.site-title {
  font-family: termina, sans-serif;
  font-weight: 700;
  font-style: normal;
}

span {
  float: right;
  padding-top: 6px;
}

img {
  float: left;
  margin-right: 12px;
}

</style>