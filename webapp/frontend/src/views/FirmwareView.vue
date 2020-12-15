<template>
  <v-container>
    <v-dialog v-model="deleteDialogShow" max-width="500px">
      <v-card>
        <v-card-title class="headline">Delete</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="mt-4">
          Are you sure you want to delete the selected files?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeDelete">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="deleteConfirm">{{ $t("form.ok") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-card outlined class="mt-8 mb-8">
      <v-card-title>
        Releases
        <v-spacer/>
        <v-file-input
            :disabled="uploadInProgress"
            outlined
            dense
            placeholder="Select your file"
            v-model="fileInput"
            class="mr-4"
            style="height: 40px"
        ></v-file-input>
        <v-btn color="primary" @click="uploadFile"
               :disabled="fileInput == null || uploadInProgress">Upload
          <v-icon right>mdi-upload</v-icon>
        </v-btn>
      </v-card-title>

      <v-overlay :absolute="true" :value="loading"/>
      <v-data-table
          v-model="selected"
          item-key="hash"
          :headers="headers"
          :single-select="false"
          :items="items"
          show-select
          disable-pagination
          hide-default-footer
      >
        <template v-slot:[`item.createdAt`]="{ item }">{{ $d(item.createdAt) }}</template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon small :loading="downloadInProgress">
            <v-icon small @click="onDownloadItem(item)">mdi-download</v-icon>
          </v-btn>
          <v-btn icon small>
            <v-icon small @click="onDeleteItem(item)">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>
    <v-snackbar v-model="snackbar" :timeout="-1">
      <v-container>
        <v-row v-if="file">
          {{ file.name }}
        </v-row>
        <v-row>
          <v-progress-linear :value="progress"/>
        </v-row>
      </v-container>
    </v-snackbar>
  </v-container>
</template>


<script lang="ts">
import {Component, Vue, Watch} from "vue-property-decorator";
import {DataOptions} from "vuetify";
import firmwareService from "@/service/api/firmwareService";
import {Firmware} from "@/store/models";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";

@Component
export default class FirmwareView extends Vue {
  private loading = false;
  private options: DataOptions | null = null;

  private deleteDialogShow = false;
  private firmware: Firmware | null = null;

  private items: Firmware[] = [];
  private selected: Firmware[] = [];

  private fileInput: any | null = null;

  private progress = 0;
  private downloadInProgress = false;
  private uploadInProgress = false;
  private snackbar = false;
  private file: File | null = null;

  private get headers() {
    return [
      {text: "Version", value: "version"},
      {text: "Device", value: "device"},
      {text: "Date", value: "createdAt"},
      {
        value: "actions",
        sortable: false,
        width: "90px",
      },
    ];
  }

  mounted() {
    this.fetchData();
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchData();
  }

  fetchData() {
    this.loading = true;

    firmwareService
        .getAll()
        .then((data: Firmware[]) => (this.items = data))
        .catch((error: Error) => console.log(error))
        .finally(() => (this.loading = false));
  }

  private closeDelete() {
    this.deleteDialogShow = false;
  }

  private deleteConfirm() {
    this.loading = true;
    this.closeDelete();

    if (!this.firmware) return;
    firmwareService.delete(this.firmware.hash)
        .then(() => this.fetchData())
        .catch(error => EventBus.$emit("error", error))
        .finally(() => {
          this.loading = false;
        });
  }

  private onDeleteItem(item: Firmware) {
    this.firmware = item;
    this.deleteDialogShow = true;
  }

  private uploadFile() {
    this.snackbar = true;
    this.uploadInProgress = true;
    this.file = this.fileInput;
    firmwareService.uploadFile(this.fileInput, (progress) => this.progress = progress)
        .then(() => this.fetchData())
        .catch(error => EventBus.$emit("error", error))
        .finally(() => {
          this.snackbar = false;
          this.uploadInProgress = false;
          this.fileInput = null;
        });
  }

  private onDownloadItem(item: Firmware) {
    this.file = new File([], item.name);
    this.downloadInProgress = true;
    firmwareService.downloadFile(item.hash, (process) => this.progress = process)
        .then(blob => saveDownloadFile(blob, item.name))
        .catch(error => console.log(error))
        .finally(() => {
          this.downloadInProgress = false;
          this.file = null;
        });
  }
}
</script>