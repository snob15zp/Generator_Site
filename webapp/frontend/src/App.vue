<template>
  <v-app>
    <v-app-bar app v-if="isAuthorized" elevation="2">
      <v-btn icon @click="$router.push('/')" v-if="userName">
        <v-icon>mdi-home</v-icon>
      </v-btn>
      <v-toolbar-title>{{ $router.currentRoute.meta.title }}</v-toolbar-title>
      <v-spacer />
      <a href="#" v-if="userName">{{ userName }}</a>
      <v-btn icon @click="logout" v-if="userName">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>
    <v-main>
      <v-container fill-height class="justify-center align-start" pa-0>
        <router-view />
      </v-container>
    </v-main>

    <v-footer>
      <v-row>
        <v-col></v-col>
        <v-col cols="auto">
          <LocaleSwitcher />
        </v-col>
      </v-row>
    </v-footer>
  </v-app>
</template>

<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import LocaleSwitcher from "@/components/LocaleSwitcher.vue";
import users from "@/store/modules/users";

@Component({
  components: { LocaleSwitcher }
})
export default class App extends Vue {
  public isAuthorized = true;
  get userName() {
    return users.user ? users.user.name : null;
  }

  logout() {
    users.logout();
    this.$router.push("/login");
  }
}
</script>
