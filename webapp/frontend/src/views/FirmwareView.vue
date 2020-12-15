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

    <v-dialog v-model="uploadDialogShow" max-width="500px">
      <v-form v-model="valid" @submit.prevent="submit" :disabled="uploadInProgress" ref="form">
        <v-card>
          <v-card-title>Upload new version</v-card-title>
          <v-progress-linear :value="progress" v-if="uploadInProgress"/>
          <v-divider/>
          <v-card-text>
             <div v-if="errorMessage" class="error--text mb-4">
              {{ errorMessage }}
            </div>
            <v-text-field
              class="ml-2 mr-1"
              hint="For example 2.1.0"
              persistent-hint
              v-model="version"
              :rules="versionRules"
              label="Version" />
              <v-file-input
                dense
                required
                :rules="fileRules"
                v-model="cpuFileInput"
                label="CPU"
                class="mt-6" />
            <v-file-input
                dense
                required
                :rules="fileRules"
                v-model="fpgaFileInput"
                label="FPGA"
                class="mt-4" />
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" text @click="cancel()">{{ $t("form.cancel") }}</v-btn>
            <v-btn 
                color="primary" 
                text
                type="submit"
                :disabled="!valid || uploadInProgress">{{ $t("form.save") }}</v-btn>
          </v-card-actions>
        </v-card>
      </v-form>
    </v-dialog>

    <v-card outlined class="mt-8 mb-8">
      <v-card-title>
        Releases
        <v-spacer/>
        <v-btn color="primary" @click="uploadDialogShow = true">Upload
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
          hide-default-footer >
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
  </v-container>
</template>


<script lang="ts">
import {Component, Vue, Watch, Ref} from "vue-property-decorator";
import {DataOptions} from "vuetify";
import firmwareService from "@/service/api/firmwareService";
import {Firmware} from "@/store/models";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";

@Component
export default class FirmwareView extends Vue {
  private loading = false;
  private options: DataOptions | null = null;

  private items: Firmware[] = [];
  private selected: Firmware[] = [];
  
  // Delete
  private deleteDialogShow = false;
  private firmware: Firmware | null = null;

  // Upload
   @Ref() readonly form: (Vue & { reset: () => void }) | undefined;
  private uploadDialogShow = false;
  private version: string | null = null;
  private cpuFileInput: any | null = null;
  private fpgaFileInput: any | null = null;
  private uploadInProgress = false;
  private valid = false;
  private errorMessage: string | null = null;
  private progress = 0;

  // Download 
  private downloadInProgress = false;
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

  private fileRules = [(v: string | null) => !!v || "File is required"];
  private versionRules = [
    (v: string | null) => !!v || "Version is required",
    (v: string) => /^\d+\.\d+.\d+$/.test(v) || "Version is not valid"
  ];

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

  private submit() {
    this.uploadInProgress = true;
    firmwareService.upload(this.version, this.cpuFileInput, this.fpgaFileInput, (process) => this.progress = process)
      .then(() => {
        this.cancel();
        this.fetchData();
      })
      .catch(error => this.errorMessage = error)
      .finally(() =>  this.uploadInProgress = false);
  }

  private cancel() {
    this.uploadDialogShow = false;
    this.form.reset();
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

  private onDownloadItem(item: Firmware) {
    this.file = new File([], item.name);
    this.downloadInProgress = true;
    firmwareService.downloadFile(item.hash, (process) => this.progress = process)
        .then(blob => saveDownloadFile(blob, item.name))
        .catch(error => EventBus.$emit("error", error))
        .finally(() => {
          this.downloadInProgress = false;
          this.file = null;
        });
  }
}
</script>