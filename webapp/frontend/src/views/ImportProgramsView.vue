<template>
  <v-container>
    <v-card outlined id="import-programs" class="mt-16">
      <v-card-text>
        <p class="text-center">
          Click
          <b>Open generator-manager.exe</b> on the dialog shown by your browser
          <br/>If you don’t see a dialog, click
          <b>Import</b> below or install the <b>client</b> and try to import again.
        </p>
        <div class="text-center">
          <v-btn class="mr-1" color="primary" @click="onStartImport()">Import</v-btn>
          <v-btn class="ml-1" color="primary" @click="onCopy()">Copy to clipboard</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="snackbar" light>
      Copied!
      <template v-slot:action="{ attrs }">
        <v-btn
            color="primary"
            text
            v-bind="attrs"
            @click="snackbar = false">
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";

@Component
export default class ImportProgramsView extends Vue {

  private snackbar = false;

  private get link() {
    const id = this.$route.params.id;
    const hash = this.$route.params.hash;
    return `generator://${location.host}/generator/folders/${id}/download/${hash}`;
  }

  mounted() {
    this.onStartImport();
  }

  private onCopy() {
    const tempInput: HTMLInputElement = document.createElement("input");
    Object.assign(tempInput.style, {position: "absolute", left: "-1000px", top: "-1000px"});
    tempInput.value = this.link;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    this.snackbar = true;
  }

  private onStartImport() {
    let result = null;
    try {
      result = window.open(this.link, "_self");
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