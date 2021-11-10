<template>
  <v-app>
    <app-bar @on-click-show-drawer="showNavDrawer"/>
    <navigation v-if="$vuetify.breakpoint.smAndDown" :visible.sync="navDrawerVisible"/>
    <v-main>
      <toast/>
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
import AppBar from "@/components/AppBar.vue";
import Navigation from "@/components/Navigation.vue";
import Toast from "@/components/Toast.vue";

@Component({
  components: {Toast, Navigation, AppBar},
})
export default class App extends Vue {
  private navDrawerVisible: boolean | null = null;

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
