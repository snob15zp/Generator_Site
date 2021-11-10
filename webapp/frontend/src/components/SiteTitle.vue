<template>
  <v-toolbar-title class="site-title">
    <img src="@/assets/logo-small.png" alt="logo"/>
    <span>InHealion</span>
  </v-toolbar-title>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";

@Component
export default class SiteTitle extends Vue {}
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