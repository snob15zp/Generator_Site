<template>
  <v-dialog v-model="visibleSync" max-width="500px" @click:outside="onClosed">
    <v-card>
      <v-card-title class="headline">Upload programs</v-card-title>
      <v-divider></v-divider>
      <v-card-text>
        <v-row class="mt-4 mb-4">
          <v-file-input
              :disabled="uploadInProgress"
              accept=".txt"
              outlined
              dense
              multiple
              placeholder="Select your file"
              v-model="fileInput"
              class="mr-4 ml-4"
              style="height: 40px">
            <template v-slot:selection="{ index, text }">
              <v-chip
                  v-if="index < 2"
                  dark
                  label
                  small>
                {{ text }}
              </v-chip>
              <span
                  v-else-if="index === 2"
                  class="overline grey--text text--darken-3 mx-2">
                    +{{ fileInput.length - 2 }} File(s)
                  </span>
            </template>
          </v-file-input>
        </v-row>
        <v-row class="mt-4">
          <v-checkbox label="Encrypted" class="pl-4"/>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
            @click="uploadFile"
            text
            color="primary"
            :loading="uploadInProgress"
            :disabled="fileInput == null || uploadInProgress">
          Upload
        </v-btn>
        <v-btn color="primary"
               :disabled="uploadInProgress"
               text @click="onCancelClick"> {{ $t("form.cancel") }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">

import {Component, Emit, Model, Prop, Vue} from "vue-property-decorator";
import programService from "@/service/api/programService";
import {Folder, UploadFileRequest, User} from "@/store/models";
import {EventBus} from "@/utils/event-bus";
import axios from "axios";

@Component
export default class UploadDialog extends Vue {
  @Model("visible") readonly visible!: boolean;
  @Prop() readonly user?: User;
  @Prop() readonly folder?: Folder;

  private uploadInProgress = false;
  private fileInput: any | null = null;
  private progress = 0;

  private readonly cancelSource = axios.CancelToken.source();

  private get visibleSync() {
    return this.visible;
  }

  private set visibleSync(visible) {
    this.$emit('visible', visible);
  }

  private uploadFile() {
    this.uploadInProgress = true;
    programService.uploadFile({
      files: this.fileInput,
      owner: this.user,
      folder: this.folder,
      onProgressCallback: (progress) => this.progress = progress,
      cancelSource: this.cancelSource
    } as UploadFileRequest)
        .then(() => this.uploadFinished())
        .catch((e) => this.uploadFailed(e))
        .finally(() => {
          this.reset();
          this.dismiss();
        });
  }

  private onCancelClick() {
    this.cancelUpload();
    this.dismiss();
  }

  private onClosed() {
    this.cancelUpload()
  }

  private reset() {
    this.uploadInProgress = false;
    this.fileInput = null;
    this.progress = 0;
  }

  private cancelUpload() {
    if (this.uploadInProgress) {
      this.cancelSource.cancel();
    }
    this.reset();
  }

  private dismiss() {
    this.visibleSync = false;
  }

  @Emit('failed')
  uploadFailed(e: Error) {
    return e;
  }

  @Emit('success')
  uploadFinished() {
    return true;
  }
}

</script>

<style scoped>

</style>