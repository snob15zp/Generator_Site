<template>
  <v-app-bar app flat color="#1e1a1a" dark v-if="isAuthorized" class="v-bar--underline">
    <v-app-bar-nav-icon
        @click="onClickShowDrawer"
        v-if="$vuetify.breakpoint.mdAndDown && showNavigation && routers.length > 0"></v-app-bar-nav-icon>
    <site-title v-if="!showNavigation || routers.length === 0"/>
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
import SiteTitle from "@/components/SiteTitle.vue";
import {navigationRouters} from "@/router";

@Component({
  components: {SiteTitle}
})
export default class AppBar extends Vue {

  get isAuthorized() {
    return UserModule.isAuthorized;
  }

  get showNavigation() {
    return this.$route.meta?.requiresAuth;
  }

  get userRoleName() {
    return UserModule.userRoleName;
  }

  get userName() {
    return UserModule.userName;
  }

  get routers() {
    return navigationRouters(UserModule.user, this.$router.options.routes);
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
.site-title {
  font-family: termina, sans-serif;
  font-weight: 700;
  font-style: normal;
}

.v-bar--underline {
  border-bottom-style: solid !important;
  border-bottom-width: 1px !important;
  border-bottom-color: rgba(0, 0, 0, .12) !important;
}

</style>