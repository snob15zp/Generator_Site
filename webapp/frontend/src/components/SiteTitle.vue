<template>
  <v-toolbar-title class="site-title">
    <a href="/">
      <img src="@/assets/logo-small.png" alt="logo"/>
      InHealion
    </a>
  </v-toolbar-title>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";

@Component
export default class SiteTitle extends Vue {
}
</script>

<style scoped>
.v-bar--underline {
  background-color: rgb(30, 26, 26) !important;
}

.site-title {
  font-family: termina, sans-serif;
  font-weight: 700;
  font-style: normal;
}

a {
  text-decoration: none;
  color: white !important;
  padding-top: 6px;
}

img {
  float: left;
  margin-right: 12px;
}
</style>