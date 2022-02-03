<template>
  <v-dialog v-model="visible" max-width="500px">
    <v-card>
      <v-card-title class="headline">{{ dialogTitle }}</v-card-title>
      <v-divider></v-divider>
      <v-card-text class="mt-4" v-html="dialogMessage"/>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="onCancelClick" v-if="showCancelButton">{{ $t("form.cancel") }}</v-btn>
        <v-btn color="blue darken-1" text @click="onOkClick" v-if="showOkButton">{{ $t("form.ok") }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import {Component, Prop, Vue} from "vue-property-decorator";

@Component
export default class MessageDialog extends Vue {
  private visible = false;
  private resolve?: (value: (PromiseLike<boolean> | boolean)) => void;

  private dialogTitle: string | null = null;
  private dialogMessage: string | null = null;

  private showOkButton = true;
  private showCancelButton = true;

  async show(title: string, message: string, buttons: string[] = ['OK', 'CANCEL']): Promise<boolean> {
    this.dialogTitle = title;
    this.dialogMessage = message;
    this.showCancelButton = buttons.indexOf('CANCEL') >= 0;
    this.showOkButton = buttons.indexOf('OK') >= 0;
    return new Promise<boolean>((resolve) => {
      this.visible = true;
      this.resolve = resolve;
    });
  }

  private hide() {
    this.visible = false;
  }

  private onCancelClick() {
    this.hide();
    this.resolve && this.resolve(false);
  }

  private onOkClick() {
    this.hide();
    this.resolve && this.resolve(true);
  }
}
</script>