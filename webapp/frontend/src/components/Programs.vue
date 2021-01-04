<template>
  <v-card ref="container" outlined :loading="loading">
    <v-card-title class="headline">
      Programs
      <v-spacer/>
      <v-file-input v-if="canManagePrograms"
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
        +{{ files.length - 2 }} File(s)
          </span>
        </template>
      </v-file-input>
      <v-btn color="primary" v-if="canManagePrograms" @click="uploadFile"
             :loading="uploadInProgress"
             :disabled="fileInput == null || uploadInProgress">Upload
        <v-icon right>mdi-upload</v-icon>
      </v-btn>
      <v-btn color="primary" @click="createFolder" v-if="canManagePrograms" class="ml-2">
        New folder
        <v-icon right>mdi-folder-plus</v-icon>
      </v-btn>
    </v-card-title>
    <v-divider/>
    <v-layout row class="row--dense">
      <v-col cols="12" md="6">
        <v-list class="overflow-y-auto p-list" nav>
          <v-list-item-group active-class="primary--text" v-model="selected">
            <v-list-item v-for="folder in folders" :key="folder.hash">
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
              <v-list-item-action>
                <v-btn
                    icon
                    @click="downloadFolder(folder)"
                    :disabled="isExpired(folder.expiredAt)"
                    :loading="downloadFolderId===folder.id">
                  <v-icon>mdi-download</v-icon>
                </v-btn>
              </v-list-item-action>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-col>
      <v-col cols="12" md="6">
        <v-list class="overflow-y-auto p-list" nav dense>
          <v-list-item v-for="file in files" :key="file.hash">
            <v-list-item-content>
              <span v-if="isDownloadDisabled" style="color: rgba(0,0,0,.6);">{{ file.name }}</span>
              <a @click="downloadFile(file)" href="#" v-else>{{ file.name }}</a>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-col>
    </v-layout>

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
  </v-card>
</template>

<script lang="ts">
import {Component, Emit, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, UploadFileRequest} from "@/store/models";
import {expiredAtInterval, isExpired} from "@/utils/dateUtils";
import {ResizeObserver} from "@juggle/resize-observer";
import UserModule from "@/store/modules/user";
import programService from "@/service/api/programService";
import saveDownloadFile from "@/utils/download-file";

@Component({
  filters: {
    expiredAtInterval: function (value: Date) {
      return expiredAtInterval(value);
    }
  }
})
export default class Programs extends Vue {
  @Prop({default: false}) readonly loading!: boolean;
  @Prop() readonly folders!: Folder[];
  @Prop({default: () => []}) readonly files!: Program[];

  @Ref() readonly container: Vue | undefined;

  private selected = 0;
  private fileInput: any | null = null;
  private folder: Folder | null = null;

  private downloadFolderId: string | null = null;
  private uploadInProgress = false;
  private progress = 0;
  private snackbar = false;
  private file: File | null = null;

  private observer = new ResizeObserver((entries) => {
    console.log("resize")
    const height = Math.max(entries[0].contentBoxSize[0].blockSize - 90, 300);
    Array.from(document.getElementsByClassName("p-list") as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      el.style.height = height + "px";
    });
  });

  get isFoldersEmpty() {
    return this.folders.length === 0;
  }

  get canManagePrograms() {
    return UserModule.canManagePrograms;
  }

  get isDownloadDisabled() {
    return this.folder ? isExpired(this.folder!!.expiredAt) && !this.canManagePrograms : true;
  }

  mounted() {
    this.observer.observe(this.container!.$el);
  }

  @Watch("selected")
  private onSelectedChanged() {
    console.log("Selected = " + this.selected);
    this.folder = this.folders[this.selected];
    this.onFolderChanged(this.folder);
  }

  @Watch("folders")
  private onFoldersChanged() {
    if (!this.isFoldersEmpty) {
      this.selected = 0;
    }
  }


  @Emit()
  onFolderChanged(folder: Folder) {
    this.folder = folder;
    return folder;
  }

  @Emit()
  createFolder() {
    return;
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
          this.snackbar = false;
          this.uploadInProgress = false;
          this.fileInput = null;
          this.progress = 0;
          this.onFolderChanged(this.folder!);
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
    this.snackbar = true;
    this.file = new File([], program.name);
    programService.downloadFile({
      program: program,
      onProgressCallback: (process) => this.progress = process
    })
        .then(blob => saveDownloadFile(blob, program.name))
        .finally(() => {
          this.snackbar = false;
          this.progress = 0;
        });
  }

  private isExpired(date: Date) {
    return isExpired(date);
  }
}
</script>
