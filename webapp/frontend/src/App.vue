<template>
  <v-app>
    <app-bar @on-click-show-drawer="showNavDrawer"/>
    <navigation :visible.sync="navDrawerVisible"/>

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
import {EventBus} from "@/utils/event-bus";
import AppBar from "@/components/AppBar.vue";
import Navigation from "@/components/Navigation.vue";

@Component({
  components: {Navigation, AppBar},
})
export default class App extends Vue {

  private alert = false
  private alertMessage: string | null = null;
  private alertType: string | null = null;
  private navDrawerVisible: boolean | null = null;

  mounted() {
    EventBus.$on("error", (error: Error) => {
      this.alertType = 'error';
      this.alertMessage = error.message;
      this.alert = true;
      setTimeout(() => this.alert = false, 5000);
    });
  }

  private showNavDrawer() {
    this.navDrawerVisible = !this.navDrawerVisible;
  }
}
</script>

<style scoped lang="scss">
@import "~vuetify/src/styles/main.sass";

#app {
  background: map-get($grey, lighten-4);
}
</style>
