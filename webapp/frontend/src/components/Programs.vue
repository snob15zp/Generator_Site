<template>
  <v-card ref="container" outlined :loading="loading">
    <v-card-title class="headline">
      Programs
      <v-spacer/>
      <v-btn color="primary" @click="openCreateFolderDialog" v-if="canManagePrograms" class="ml-2">
        New folder
        <v-icon right>mdi-folder-plus</v-icon>
      </v-btn>
    </v-card-title>
    <v-divider horizontal/>
    <v-row dense justify="space-between">
      <v-col cols="4">
        <v-list class="overflow-y-auto" nav :height="listHeight">
          <v-list-item-group active-class="primary--text" v-model="selected">
            <v-list-item v-for="folder in folders" :key="folder.id">
              <v-list-item-avatar>
                <v-icon>
                  mdi-folder
                </v-icon>
              </v-list-item-avatar>
              <v-list-item-content>
                <v-list-item-title>{{ folder.name }}</v-list-item-title>
                <v-list-item-subtitle v-bind:class="{ 'error--text': isExpired(folder.expiredAt) }">
                  {{ folder.expiredAt | expiredAtInterval }}
                </v-list-item-subtitle>
              </v-list-item-content>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-col>
      <v-divider vertical></v-divider>
      <v-col class="d-flex">
        <v-card
          v-if="folder"
          :key="folder.id"
          :disabled="filesLoading"
          class="mx-auto"
          flat
          style="width:100%">
          <v-card-title>
            {{ folder.name }}
            <v-btn
                icon
                @click="downloadFolder(folder)"
                :disabled="isExpired(folder.expiredAt)"
                :loading="downloadFolderId===folder.id">
              <v-icon>mdi-download</v-icon>
            </v-btn>
          </v-card-title>
            <v-card-subtitle>Total programs: {{ files.length }}</v-card-subtitle>
          <v-card-text>
            <v-row v-if="canManagePrograms" class="mt-2">
              <v-file-input
                      :disabled="uploadInProgress || isFoldersEmpty"
                      outlined
                      dense
                      multiple
                      placeholder="Select your file"
                      v-model="fileInput"
                      class="mr-4"
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
              <v-btn icon @click="uploadFile"
                    :loading="uploadInProgress"
                    :disabled="fileInput == null || uploadInProgress">
                <v-icon>mdi-upload</v-icon>
              </v-btn>
            </v-row>
          </v-card-text>
          <v-divider />
          <v-row dense class="overflow-y-auto ma-2" :style="{'max-height': (listHeight - 150) + 'px'}">
            <v-col cols="3" v-for="file in files" :key="file.id" class="text-truncate">
              <small>{{ file.name }}</small>
            </v-col>
          </v-row>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="isCreateDialogShow" max-width="500px">
      <v-card>
        <v-card-title class="headline">Create Folder</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="mt-4">
          <v-menu
              ref="menuRef"
              v-model="menu"
              :close-on-content-click="false"
              transition="scale-transition"
              offset-y
              min-width="290px"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field v-model="expiredAt" label="Expired Date" v-bind="attrs" v-on="on" readonly/>
            </template>
            <v-date-picker
                ref="pickerRef"
                no-title
                v-model="expiredAt"
                :locale="$i18n.locale"
                :min="$options.filters.formatDate(new Date())"
                @change="save"
            />
          </v-menu>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="createFolderClose">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="createFolderConfirm">{{ $t("form.ok") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script lang="ts">
import {Component, Emit, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, UploadFileRequest, UserProfile} from "@/store/models";
import {expiredAtInterval, isExpired} from "@/utils/dateUtils";
import {ResizeObserver} from "@juggle/resize-observer";
import UserModule from "@/store/modules/user";
import programService from "@/service/api/programService";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";
import moment from "moment";

@Component({
  filters: {
    expiredAtInterval: function (value: Date) {
      return expiredAtInterval(value);
    }
  }
})
export default class Programs extends Vue {
  @Prop({default: null}) readonly userProfileId!: string;

  @Ref() readonly container: Vue | undefined;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;

  private folders: Folder[] = [];
  private files: Program[] = [];

  private filesLoading = false;
  private foldersLoading = false;

  private selected: number | null = null;
  private fileInput: any | null = null;
  private folder: Folder | null = null;

  private downloadFolderId: string | null = null;
  private uploadInProgress = false;
  private progress = 0;
  private file: File | null = null;

  private listHeight = this.getHeight();

  private menu = false;
  private isCreateDialogShow = false;
  private expiredAt: string | null = null;

  private observer = new ResizeObserver((entries) => {
    console.log("resize")
    this.listHeight = this.getHeight();
  });

  get loading() {
    return this.foldersLoading || this.filesLoading;
  }

  get isFoldersEmpty() {
    return this.folders?.length === 0;
  }

  get canManagePrograms() {
    return UserModule.canManagePrograms;
  }

  get isDownloadDisabled() {
    return this.folder ? isExpired(this.folder!!.expiredAt) && !this.canManagePrograms : true;
  }

  mounted() {
    this.observer.observe(this.container!.$el);
    this.fetchFolders();
  }

  @Watch("selected")
  private onSelectedChanged() {
    if(this.selected == null) return;
    if(this.folders[this.selected].id == this.folder?.id) return;

    this.folder = this.folders[this.selected];
    this.files = [];
    this.onFolderChanged(this.folder);
  }


  private onFolderChanged(folder: Folder) {
    this.folder = folder;
    this.fileInput = null;
    this.fetchPrograms(folder);
    return folder;
  }

  private getHeight() {
    return Math.max(window.innerHeight - 490, 450);
  }

  private uploadFile() {
    //this.snackbar = true;
    this.uploadInProgress = true;
    this.file = this.fileInput;
    programService.uploadFile({
      files: this.fileInput,
      folder: this.folder,
      onProgressCallback: (progress) => this.progress = progress
    } as UploadFileRequest)
        .finally(() => {
          this.uploadInProgress = false;
          this.fileInput = null;
          this.progress = 0;
          this.onFolderChanged(this.folder!);
        });
  }

  private fetchPrograms(folder: Folder) {
    this.filesLoading = true;
    programService.fetchPrograms(folder)
        .then(programs => this.files = programs)
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.filesLoading = false;
        });
  }

  private fetchFolders() {
    this.foldersLoading = true;
    programService.fetchFolders(this.userProfileId)
        .then((folders) => {
          this.folders = folders;
          if (this.folders && this.folders.length > 0) {
            this.selected = 0;
          }
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.foldersLoading = false;
        });
  }

  private downloadFolder(folder: Folder) {
    this.downloadFolderId = folder.id;
    programService.downloadFolder(folder.id!!, (progress) => this.progress = progress)
        .then(blob => saveDownloadFile(blob, folder.name))
        .finally(() => {
          this.progress = 0;
          this.downloadFolderId = null;
        });
  }

  private downloadFile(program: Program) {
    this.file = new File([], program.name);
    programService.downloadFile({
      program: program,
      onProgressCallback: (process) => this.progress = process
    })
        .then(blob => saveDownloadFile(blob, program.name))
        .finally(() => {
          this.progress = 0;
        });
  }

  private isExpired(date: Date) {
    return isExpired(date);
  }

  private openCreateFolderDialog() {
    this.isCreateDialogShow = true;
  }

  private createFolderClose() {
    this.expiredAt = null;
    this.isCreateDialogShow = false;
  }

  private createFolderConfirm() {
    const date = moment(this.expiredAt);
    const folder = {
      id: null,
      name: date.format("DD-MM-YY"),
      expiredAt: date.toDate()
    } as Folder;
    programService.saveFolder(this.userProfileId, folder)
        .then(() => this.fetchFolders())
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => this.createFolderClose());
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }
}
</script>
