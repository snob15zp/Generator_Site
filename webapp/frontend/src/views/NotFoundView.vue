<template>
  <v-app class="not-found">
    <v-container fill-height fluid class="justify-center">
      <v-sheet>
        <h1 class="v-heading text-h1 text-sm-h1 mb-6">404</h1>
        <h2
          class="v-heading text-h3 text-sm-h3 mb-8"
          v-html="$t('not-found.title')"
        ></h2>
        <p
          class="justify-start text--secondary"
          v-html="$t('not-found.message', ['/'])"
        ></p>
      </v-sheet>
    </v-container>
  </v-app>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class NotFoundView extends Vue {}
</script>

<style lang="scss">
.not-found {
  p {
    font-size: 1.3em;
  }
}
</style>
