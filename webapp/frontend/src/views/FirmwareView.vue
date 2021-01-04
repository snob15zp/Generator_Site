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

    <v-dialog v-model="uploadForm.uploadDialogShow" max-width="500px">
      <v-form v-model="uploadForm.valid" @submit.prevent="submit" :disabled="uploadForm.uploadInProgress" ref="form">
        <v-card>
          <v-card-title>Upload new version</v-card-title>
          <v-progress-linear :value="uploadForm.progress" v-if="uploadForm.uploadInProgress"/>
          <v-divider/>
          <v-card-text>
            <div v-if="uploadForm.errorMessage" class="error--text mb-4">
              {{ uploadForm.errorMessage }}
            </div>
            <v-text-field
                class="ml-2 mr-1"
                hint="For example 2.1.0"
                persistent-hint
                v-model="uploadForm.version"
                :rules="uploadForm.versionRules"
                label="Version"/>
            <v-file-input
                dense
                multiple
                accept=".bf,.rbf"
                placeholder="Select your files"
                required
                :rules="uploadForm.fileRules"
                v-model="uploadForm.files"
                class="mt-6">
                <template v-slot:selection="{ index, text }">
                  <v-chip
                    v-if="index < 4"
                    color="accent"
                    dark
                    label
                    small
                  >
                    {{ text }}
                  </v-chip>
                  <span
                    v-else-if="index === 4"
                    class="overline grey--text text--darken-3 mx-2"
                  >
                    +{{ files.length - 4 }} File(s)
                  </span>
                </template>
            </v-file-input>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" text @click="cancel()">{{ $t("form.cancel") }}</v-btn>
            <v-btn
                color="primary"
                text
                type="submit"
                :disabled="!uploadForm.valid || uploadForm.uploadInProgress">{{ $t("form.save") }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-form>
    </v-dialog>

    <v-card outlined class="mt-8 mb-8">
      <v-card-title>
        Releases
        <v-spacer/>
        <v-btn color="primary" @click="uploadForm.show()">Upload
          <v-icon right>mdi-upload</v-icon>
        </v-btn>
      </v-card-title>

      <v-overlay :absolute="true" :value="loading"/>
      <v-data-table
          v-model="selected"
          item-key="version"
          :headers="headers"
          :loading="loading"
          :items="items"
          disable-pagination
          hide-default-footer>
        <template v-slot:[`item.version`]="{ item }"><span class="text-subtitle-2">{{ item.version }}</span></template>
        <template v-slot:[`item.files`]="{ item }">
          <div class="text-caption font-weight-light">
            <div v-for="file in item.files" :key="file.fileName">{{ file.fileName }}</div>
          </div>
        </template>
        <template v-slot:[`item.createdAt`]="{ item }">{{ $d(item.createdAt) }}</template>
        <template v-slot:[`item.active`]="{ item }">
          <v-btn icon
                 :color="item.active ? 'primary':''"
                 :loading="loadingFirmwareId === item.id"
                 @click="onActiveStatusChange(item)">
            <v-icon v-if="item.active">mdi-check</v-icon>
            <v-icon v-else>mdi-close</v-icon>
          </v-btn>
          <v-btn-toggle/>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon small :loading="downloadInProgress" :disabled="loadingFirmwareId === item.id">
            <v-icon small @click="onDownloadItem(item)">mdi-download</v-icon>
          </v-btn>
          <v-btn icon small :disabled="loadingFirmwareId === item.id">
            <v-icon small @click="onDeleteItem(item)">mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>
  </v-container>
</template>


<script lang="ts">
import {Component, Ref, Vue, Watch} from "vue-property-decorator";
import {DataOptions} from "vuetify";
import firmwareService from "@/service/api/firmwareService";
import {Firmware} from "@/store/models";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";
import {FormRef} from "@/forms/types";
import {FirmwareUploadForm} from "@/forms/FirmwareUploadForm";


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
  @Ref() readonly form: FormRef;
  private uploadForm = new FirmwareUploadForm();

  // Download 
  private downloadInProgress = false;
  private file: File | null = null;

  private loadingFirmwareId: string | null = null;

  private get headers() {
    return [
      {text: "Version", value: "version"},
      {text: "Files", value: "files"},
      {text: "Date", value: "createdAt"},
      {text: "Active", value: "active"},
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
        .catch(e => EventBus.$emit("error", e))
        .finally(() => (this.loading = false));
  }

  private closeDelete() {
    this.deleteDialogShow = false;
  }

  private onActiveStatusChange(firmware: Firmware) {
    this.loadingFirmwareId = firmware.id;
    firmwareService.updateStatus(firmware.id, !firmware.active)
        .then(() => firmware.active = !firmware.active)
        .catch(e => EventBus.$emit("error", e))
        .finally(() => {
          this.loadingFirmwareId = null;
        });
  }

  private submit() {
    this.uploadForm.uploadInProgress = true;
    firmwareService.upload(
        this.uploadForm.version!,
        this.uploadForm.files!,
        (process) => this.uploadForm.progress = process)
        .then(() => {
          this.cancel();
          this.fetchData();
        })
        .catch(error => this.uploadForm.errorMessage = error)
        .finally(() => {
          this.uploadForm.reset();
        });
  }

  private cancel() {
    this.form?.reset();
    this.uploadForm.reset();
    this.uploadForm.hide();
  }

  private deleteConfirm() {
    this.loading = true;
    this.closeDelete();

    if (!this.firmware) return;
    firmwareService.delete(this.firmware.version)
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
    this.file = new File([], item.version);
    this.downloadInProgress = true;
    firmwareService.downloadFile(item.version)
        .then(blob => saveDownloadFile(blob, "firmware_v" + item.version.replaceAll('.', '-') + ".zip"))
        .catch(error => EventBus.$emit("error", error))
        .finally(() => {
          this.downloadInProgress = false;
          this.file = null;
        });
  }
}
</script>