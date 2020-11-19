<template>
  <div class="locale-switcher" align="baseline">
    <div class="d-inline-flex mr-2">{{ $t("locale-switcher.title") }}</div>
    <div class="d-inline-flex">
      <v-select
        dense
        solo
        v-model="$i18n.locale"
        :items="items"
        item-text="name"
        item-value="locale"
        change="changeLocale"
      ></v-select>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import i18n from "@/i18n";

@Component
export default class LocaleSwither extends Vue {
  public items = i18n.availableLocales.map(locale => ({
    locale: locale,
    name: this.$t(locale)
  }));
}
</script>

<style lang="scss">
.locale-switcher {
  .v-input {
    width: 150px;
    height: 46px;
  }
}
</style>
