<template>
  <v-container class="not-found">
      <v-card outlined class="mt-8 mb-8 pa-12">
        <v-card-text>
        <h1 class="v-heading text-h1 text-sm-h1 mb-6">404</h1>
        <h2
          class="v-heading text-h3 text-sm-h3 mb-8"
          v-html="$t('not-found.title')"
        ></h2>
        <p
          class="justify-start text--secondary"
          v-html="$t('not-found.message', ['/'])"
        ></p>
        </v-card-text>
      </v-card>
  </v-container>
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
