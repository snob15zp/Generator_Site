<template>
  <v-container>
    <v-card outlined id="import-programs" class="mt-16">
      <v-card-text>
        <p class="text-center">
          Click
          <b>Open generator-manager.exe</b> on the dialog shown by your browser
          <br />If you don’t see a dialog, click
          <b>Import</b> below or install the <b>client</b> and try to import again.
        </p>
        <div class="text-center">
          <v-btn color="primary" @click="onStartImport()">Import</v-btn>
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class ImportProgramsView extends Vue {
  mounted() {
    this.onStartImport();
  }

  private onStartImport() {
    const id = this.$route.params.id;
	const hash = this.$route.params.hash;
	let result = null;
    try {
      result = window.open(
        `generator://${location.host}/generator/folders/${id}/${hash}`,
        "_self"
      );
    } catch (e) {
      console.log("Error");
    } finally {
		console.log(result);
	}
  }
}
</script>

<style lang="scss">
#import-programs {
  p {
    font-size: 1.5em;
    line-height: 1.5;
  }
}
</style>