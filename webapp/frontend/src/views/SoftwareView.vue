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
                accept=".exe"
                placeholder="Select your file"
                required
                :rules="uploadForm.fileRules"
                v-model="uploadForm.file"
                class="mt-6">
                <template v-slot:selection="{ text }">
                  <v-chip
                    color="accent"
                    dark
                    label
                    small>
                    {{ text }}
                  </v-chip>
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
        Software versions
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
        <template v-slot:[`item.file`]="{ item }">
          <div class="font-weight-light">
            <a :href="fileUrl(item)">{{ item.file }}</a>
          </div>
        </template>
        <template v-slot:[`item.createdAt`]="{ item }">{{ $d(item.createdAt) }}</template>
        <template v-slot:[`item.active`]="{ item }">
          <v-btn icon
                 :color="item.active ? 'primary':''"
                 :loading="loadingSoftwareId === item.id"
                 @click="onActiveStatusChange(item)">
            <v-icon v-if="item.active">mdi-check</v-icon>
            <v-icon v-else>mdi-close</v-icon>
          </v-btn>
          <v-btn-toggle/>
        </template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon small :disabled="loadingSoftwareId === item.id">
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
import softwareService from "@/service/api/softwareService";
import {Software} from "@/store/models";
import {EventBus} from "@/utils/event-bus";
import {FormRef} from "@/forms/types";
import {SoftwareUploadForm} from "@/forms/SoftwareUploadForm";
import {settings} from "@/settings";


@Component
export default class SoftwareView extends Vue {
  private loading = false;
  private options: DataOptions | null = null;

  private items: Software[] = [];
  private selected: Software[] = [];

  // Delete
  private deleteDialogShow = false;
  private software: Software | null = null;

  // Upload
  @Ref() readonly form: FormRef;
  private uploadForm = new SoftwareUploadForm();

  private loadingSoftwareId: string | null = null;

  private get headers() {
    return [
      {text: "Version", value: "version"},
      {text: "File", value: "file"},
      {text: "Date", value: "createdAt"},
      {text: "Active", value: "active"},
      {
        value: "actions",
        sortable: false,
        width: "90px",
      },
    ];
  }

  private fileUrl(software: Software): string {
    return settings.apiUrl + software.fileUrl;
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

    softwareService
        .getAll()
        .then((data: Software[]) => (this.items = data))
        .catch(e => EventBus.$emit("error", e))
        .finally(() => (this.loading = false));
  }

  private closeDelete() {
    this.deleteDialogShow = false;
  }

  private onActiveStatusChange(software: Software) {
    this.loadingSoftwareId = software.id;
    softwareService.updateStatus(software.id, !software.active)
        .then(() => software.active = !software.active)
        .catch(e => EventBus.$emit("error", e))
        .finally(() => {
          this.loadingSoftwareId = null;
        });
  }

  private submit() {
    this.uploadForm.uploadInProgress = true;
    softwareService.upload(
        this.uploadForm.version!,
        this.uploadForm.file!,
        (process) => this.uploadForm.progress = process)
        .then(() => {
          this.cancel();
          this.fetchData();
        })
        .catch(error => {
          console.log(error);
          this.uploadForm.errorMessage = error;
        })
        .finally(() => {
          this.uploadForm.progress = 0;
          this.uploadForm.uploadInProgress = false;
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

    if (!this.software) return;
    softwareService.delete(this.software.version)
        .then(() => this.fetchData())
        .catch(error => EventBus.$emit("error", error))
        .finally(() => {
          this.loading = false;
        });
  }

  private onDeleteItem(item: Software) {
    this.software = item;
    this.deleteDialogShow = true;
  }
}
</script>