<template>
  <v-alert
      :value="visible"
      :type="alertType"
      dismissible
      transition="scale-transition"
      border="right"
      class="ml-2 mr-2 mt-2"><span v-html="message"></span>
  </v-alert>
</template>

<script lang="ts">

import {Component, Vue} from "vue-property-decorator";
import {EventBus} from "@/utils/event-bus";

@Component
export default class Toast extends Vue {
  private visible = false;
  private message: string | null = null;
  private alertType: string | null = null;

  mounted() {
    EventBus.$on("error", (error: Error) => {
      this.alertType = 'error';
      this.message = error.message;
      this.visible = true;
      setTimeout(() => this.visible = false, 5000);
    });
  }
}
</script>

<style scoped>

</style>