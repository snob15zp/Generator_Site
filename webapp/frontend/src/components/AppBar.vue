<template>
  <v-app-bar app flat color="#1e1a1a" dark v-if="isAuthorized" class="v-bar--underline">
    <v-app-bar-nav-icon
        @click="onClickShowDrawer"
        v-if="$vuetify.breakpoint.mdAndDown && showNavigation"></v-app-bar-nav-icon>
    <v-toolbar-title v-if="!showNavigation" class="site-title">
      <img src="@/assets/logo-small.png" alt="logo"/>
      <span>InHealion</span>
    </v-toolbar-title>
    <v-spacer/>
    <div class="pa-4 d-flex">
      <v-icon class="ma-2" x-large>mdi-account</v-icon>
      <div class="mt-1">{{ userName }}<small class="d-flex">{{ userRoleName }}</small></div>
    </div>
    <v-btn icon color="primary" @click="logout">
      <v-icon>mdi-logout</v-icon>
    </v-btn>
  </v-app-bar>
</template>


<script lang="ts">

import {Component, Emit, Vue} from "vue-property-decorator";
import UserModule from "@/store/modules/user";

@Component
export default class AppBar extends Vue {
  get isAuthorized() {
    return UserModule.isAuthorized;
  }

  get showNavigation() {
    return UserModule.canManageProfiles && this.$route.meta?.requiresAuth;
  }

  get userRoleName() {
    return UserModule.userRoleName;
  }

  get userName() {
    return UserModule.userName;
  }

  @Emit()
  onClickShowDrawer() {
    return;
  }


  logout() {
    UserModule.logout().then(() => {
      this.$router.push("/login");
    });
  }
}
</script>

<style scoped lang="scss">
.v-bar--underline {
  border-bottom-style: solid !important;
  border-bottom-width: 1px !important;
  border-bottom-color: rgba(0, 0, 0, .12) !important;
}

</style>