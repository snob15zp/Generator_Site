<template>
  <v-layout column align-top>
    <v-card ref="container" outlined :loading="loading">
      <v-card-title class="headline">
        Programs
        <v-spacer/>
        <v-btn
            color="primary"
            @click="openCreateFolderDialog"
            :disabled="foldersLoading"
            v-if="canManagePrograms" class="ml-2">
          New folder
          <v-icon right>mdi-folder-plus</v-icon>
        </v-btn>
      </v-card-title>
      <v-divider horizontal/>
      <v-row dense justify="space-between">
        <!-- Folders -->
        <v-col cols="4">
          <v-list class="overflow-y-auto" nav :height="listHeight">
            <v-list-item-group active-class="primary--text" v-model="selected">
              <v-list-item v-for="folder in folders" :key="folder.id">
                <v-list-item-avatar>
                  <v-icon>{{ folder.isEncrypted && canManagePrograms ? 'mdi-folder-key' : 'mdi-folder' }}</v-icon>
                </v-list-item-avatar>
                <v-list-item-content>
                  <v-list-item-title>{{ folderName(folder) }}</v-list-item-title>
                  <v-list-item-subtitle v-bind:class="{ 'error--text': isExpired(folder.expiredAt) }">
                    expired at {{ folderExpireDate(folder) }}
                  </v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-col>
        <v-divider vertical></v-divider>
        <!-- Programs -->
        <v-col class="d-flex">
          <v-card
              v-if="folder"
              :key="folder.id"
              :disabled="filesLoading"
              class="mx-auto"
              flat
              style="width:100%">
            <v-card-title>
              {{ folderName(folder) }}
              <v-spacer/>
              <v-tooltip bottom v-if="canManagePrograms" class="mr-2">
                <template v-slot:activator="{on, attrs}">
                  <v-btn icon
                         @click="deleteFolder(folder)"
                         :loading="deletingFolderId===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-folder-remove</v-icon>
                  </v-btn>
                </template>
                <span>Delete folder</span>
              </v-tooltip>
              <v-tooltip bottom v-if="canDownloadPrograms" class="mr-2">
                <template v-slot:activator="{on, attrs}">
                  <v-btn icon
                         @click="downloadFolder(folder)"
                         :disabled="files.length === 0"
                         :loading="downloadFolderId===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-folder-download</v-icon>
                  </v-btn>
                </template>
                <span>Download</span>
              </v-tooltip>
              <v-tooltip bottom v-if="canManagePrograms" class="mr-2">
                <template v-slot:activator="{on, attrs}">
                  <v-btn icon
                         @click="onRenewFolder(folder)"
                         :disabled="!isExpired(folder.expiredAt)"
                         :loading="renewFolder && renewFolder.id===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-autorenew</v-icon>
                  </v-btn>
                </template>
                <span>Renew</span>
              </v-tooltip>
              <v-divider vertical v-if="canManagePrograms"/>
              <v-tooltip bottom v-if="canUploadPrograms" class="mr-2">
                <template v-slot:activator="{on, attrs}">
                  <v-btn icon
                         @click="onShowUploadDialog()"
                         :disabled="!canManagePrograms && isExpired(folder.expiredAt)"
                         :loading="importFolderId===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-upload-multiple</v-icon>
                  </v-btn>
                </template>
                <span>Upload programs</span>
              </v-tooltip>
              <v-tooltip bottom v-if="canManagePrograms" class="mr-2">
                <template v-slot:activator="{on, attrs}">
                  <v-btn icon
                         @click="onAddPrograms(folder)"
                         :disabled="!canManagePrograms && isExpired(folder.expiredAt)"
                         :loading="importFolderId===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-plus-box-multiple</v-icon>
                  </v-btn>
                </template>
                <span>Add programs</span>
              </v-tooltip>
              <v-tooltip bottom class="mr-2">
                <template v-slot:activator="{on, attrs}" v-if="canImportPrograms">
                  <v-btn icon
                         @click="importToDevice(folder)"
                         :disabled="(!canManagePrograms && isExpired(folder.expiredAt)) || files.length === 0"
                         :loading="importFolderId===folder.id"
                         v-bind="attrs" v-on="on">
                    <v-icon>mdi-import</v-icon>
                  </v-btn>
                </template>
                <span>Import to device</span>
              </v-tooltip>
            </v-card-title>
            <v-card-subtitle>
              Expired at {{ folderExpireDate(folder) }}
            </v-card-subtitle>
            <v-card-text id="programs">
              <v-toolbar flat dense>
                <div>
                  <h4>Total programs: {{ files.length }}</h4>
                  <small v-if="canManagePrograms">Use SHIFT or CTRL with the mouse to select multiple items.</small>
                </div>
                <v-spacer/>
                <v-btn
                    v-if="canManagePrograms"
                    icon x-small
                    class="mr-2"
                    :disabled="selectedPrograms.length === 0"
                    @click="onDeletePrograms">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </v-toolbar>
              <v-divider/>
              <v-row dense class="overflow-y-auto ma-2" :style="{'max-height': (listHeight - 150) + 'px'}">
                <v-col cols="3" class="text-truncate"
                       v-for="file in files" :key="file.id"
                       v-bind:class="{selected: isItemSelected(file)}"
                       @mousedown="onItemSelected(file, $event)">
                  <small>{{ file.name }}</small>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card>

    <create-folder-dialog
        v-model="isCreateDialogShow"
        :user="user"
        :renew-folder="renewFolder"
        @success="onCreateFolderSuccess"
        @failed="onCreateFolderFailed"
    />

    <upload-dialog v-model="isUploadDialogShow" @success="onUploadSuccess" @failed="onUploadFailed" :folder="folder"/>

    <v-dialog v-model="isProgramAddDialogShow" max-width="800px">
      <v-card>
        <v-card-title class="headline">Add programs</v-card-title>
        <v-card-subtitle><small>Use SHIFT or CTRL with the mouse to select multiple items.</small></v-card-subtitle>
        <program-data-list
            height="600"
            :user="user"
            :exists-programs="files"
            :selected.sync="addProgramItems"
            :key="programAddDialogKey"
        />
        <v-card-actions>
          <span>Total selected: {{ addProgramItems.length }}</span>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="onAddProgramsCancel()">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="onAddProgramsConfirm()" :disabled="addProgramItems.length === 0">
            {{ $t("form.ok") }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <message-dialog ref="messageDialog"/>
  </v-layout>
</template>

<script lang="ts">
import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, User} from "@/store/models";
import {expiredAtInterval, formatDate, isExpired} from "@/utils/dateUtils";
import {ResizeObserver} from "@juggle/resize-observer";
import programService from "@/service/api/programService";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import ProgramDataList from "@/components/ProgramDataList.vue";
import CreateFolderDialog from "@/components/dialogs/CreateFolderDialog.vue";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import BaseVueComponent from "@/components/BaseVueComponent";


@Component({
  components: {UploadDialog, CreateFolderDialog, ProgramDataList, MessageDialog},
  filters: {
    expiredAtInterval: function (value: Date) {
      return expiredAtInterval(value);
    }
  }
})
export default class Programs extends BaseVueComponent {
  @Prop() readonly user!: User;

  @Ref() readonly container: Vue | undefined;
  @Ref() readonly messageDialog: MessageDialog | undefined;

  private folders: Folder[] = [];
  private files: Program[] = [];

  private filesLoading = false;
  private foldersLoading = false;

  private selected: number | null = null;
  private folder: Folder | null = null;

  private downloadFolderId: string | null = null;
  private deletingFolderId: string | null = null;
  private importFolderId: string | null = null;
  private progress = 0;

  private isProgramAddDialogShow = false;
  private addProgramItems: Program[] = [];
  private programAddDialogKey = 0;

  private listHeight = this.getHeight();

  private isCreateDialogShow = false;
  private isUploadDialogShow = false;

  private selectedPrograms: string[] = [];

  private renewFolder: Folder | null = null;

  private observer = new ResizeObserver(() => {
    this.listHeight = this.getHeight();
  });

  get loading() {
    return this.foldersLoading || this.filesLoading;
  }

  get isFoldersEmpty() {
    return this.folders?.length === 0;
  }

  get isDownloadDisabled() {
    return this.folder ? isExpired(this.folder!!.expiredAt) && !this.canManagePrograms : true;
  }

  mounted() {
    this.observer.observe(this.container!.$el);
    this.fetchFolders();
  }

  private folderName(folder: Folder) {
    return folder.createdAt ? formatDate(folder.createdAt) : folder.name;
  }

  @Watch("selected")
  private onSelectedChanged() {
    if (this.selected == null) return;
    if (this.folders[this.selected].id == this.folder?.id) return;

    this.folder = this.folders[this.selected];
    this.files = [];
    this.onFolderChanged(this.folder);
  }

  private folderExpireDate(folder: Folder) {
    return folder.expiredAt ? formatDate(folder.expiredAt) : "";
  }

  private isItemSelected(program: Program) {
    return this.selectedPrograms.indexOf(program.id) >= 0;
  }

  private onRenewFolder(folder: Folder) {
    this.renewFolder = folder;
    this.openCreateFolderDialog()
  }

  private onAddPrograms(folder: Folder) {
    this.programAddDialogKey += 1;
    this.isProgramAddDialogShow = true;
  }

  private onAddProgramsCancel() {
    this.isProgramAddDialogShow = false;
  }

  private onAddProgramsConfirm() {
    this.isProgramAddDialogShow = false;
    this.filesLoading = true;
    this.addPrograms(this.folder!);
  }

  private addPrograms(folder: Folder) {
    programService.addProgramsToFolder(folder, this.addProgramItems)
        .then(() => this.fetchPrograms(folder))
        .catch(e => {
          this.filesLoading = false;
          EventBus.$emit("error", new Error("Unable to add files"));
          this.messageDialog?.show("Error", e.message, ['OK'])
              .then(() => this.folder && this.fetchPrograms(this.folder));
        })
        .finally(() => {
          this.addProgramItems = [];
        })
  }

  private onItemSelected(program: Program, event: MouseEvent) {
    if (!this.canManagePrograms) return;

    if (event.shiftKey) {
      const indexes = this.selectedPrograms.map(id => this.files.findIndex(f => f.id == id));
      const currentIdx = this.files.indexOf(program);

      const minIdx = Math.min.apply(null, indexes);
      const maxIdx = Math.max.apply(null, indexes);
      console.log("Item selected " + currentIdx + ", " + minIdx + ", " + maxIdx);
      let _i: number;
      for (_i = currentIdx; _i < minIdx; _i++) {
        this.selectProgram(this.files[_i]);
      }

      for (_i = maxIdx + 1; _i <= currentIdx; _i++) {
        this.selectProgram(this.files[_i]);
      }
    } else if (event.ctrlKey || event.metaKey) {
      this.selectProgram(program);
    } else {
      this.selectedPrograms.length = 0;
      this.selectedPrograms.push(program.id);
    }
  }

  private selectProgram(program: Program) {
    if (this.selectedPrograms.find(id => program.id == id) === undefined) {
      this.selectedPrograms.push(program.id);
    }
  }

  private onDeletePrograms() {
    this.messageDialog!.show("Delete", "Are you sure you want to delete the selected programs?")
        .then((result) => {
          if (result) {
            this.filesLoading = true;
            return programService.unlinkPrograms(this.folder!, this.selectedPrograms)
          } else {
            return Promise.reject();
          }
        })
        .then(() => this.fetchPrograms(this.folder!))
        .catch((e) => {
          this.filesLoading = false;
          e && EventBus.$emit("error", e);
        })
        .finally(() => {
          this.selectedPrograms.length = 0
        });
  }

  private onFolderChanged(folder: Folder) {
    this.folder = folder;
    this.selectedPrograms.length = 0;
    this.fetchPrograms(folder);
    return folder;
  }

  private getHeight() {
    return Math.max(window.innerHeight - 275, 450);
  }

  private onShowUploadDialog() {
    this.isUploadDialogShow = true;
  }

  private onUploadSuccess() {
    this.folder && this.fetchPrograms(this.folder);
  }

  private onUploadFailed(e: Error) {
    EventBus.$emit("error", new Error("Unable to upload files"));
    this.messageDialog?.show("Error", e.message, ['OK'])
        .then(() => this.folder && this.fetchPrograms(this.folder));
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
    programService.fetchFolders(this.user)
        .then((folders) => {
          this.folders = folders.sort((a, b) => a.expiredAt.getTime() - b.expiredAt.getTime());
          if (this.folders && this.folders.length > 0) {
            this.selected = 0;
          } else {
            this.selected = null;
            this.files = [];
            this.folder = null;
            this.filesLoading = false;
          }
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.foldersLoading = false;
        });
  }

  private importToDevice(folder: Folder) {
    this.importFolderId = folder.id;
    programService.importPrograms(folder.id!)
        .then(hash => {
          const route = this.$router.resolve({path: `/import-programs/${folder.id}/${hash}`});
          window.open(route.href, '_blank');
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.importFolderId = null;
        })
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

  private isExpired(date: Date) {
    return isExpired(date);
  }

  private openCreateFolderDialog() {
    this.isCreateDialogShow = true;
  }

  private onCreateFolderSuccess(folder: Folder) {
    this.folders.push(folder);
    this.folders = this.folders.sort((a, b) => a.expiredAt.getTime() - b.expiredAt.getTime());
    this.selected = this.folders.indexOf(folder);
  }

  private onCreateFolderFailed(e: Error) {
    EventBus.$emit("error", new Error("Unable to upload files"));
    this.messageDialog?.show("Error", e.message, ['OK'])
        .then(() => this.fetchFolders());
  }

  private deleteFolder(folder: Folder) {
    this.messageDialog!.show("Delete folder", `Are you sure you want to delete the folder ${folder.name}?`)
        .then((result) => {
          if (result) {
            this.deletingFolderId = folder.id;
            this.filesLoading = true;
            this.selected = null;
            return programService.deleteFolder(folder.id!)
          } else {
            return Promise.reject();
          }
        })
        .then(() => this.fetchFolders())
        .catch((e) => e && EventBus.$emit("error", e))
        .finally(() => {
          this.deletingFolderId = null;
        });
  }
}
</script>
<style lang="scss">
@import "~vuetify/src/styles/main.sass";

#programs {
  -moz-user-select: -moz-none;
  -khtml-user-select: none;
  -webkit-user-select: none;

  /*
    Introduced in IE 10.
    See http://ie.microsoft.com/testdrive/HTML5/msUserSelect/
  */
  -ms-user-select: none;
  user-select: none;

  .v-toolbar__content {
    padding: 0 !important;
  }

  .selected {
    color: #197bac;
    position: relative;

    &::before {
      background: currentColor;
      bottom: 2px;
      content: "";
      left: 0;
      opacity: 0.12;
      pointer-events: none;
      position: absolute;
      right: 4px;
      box-sizing: border-box;
      border-radius: 4px;
      top: 2px;
      transition: .3s cubic-bezier(.25, .8, .5, 1);
    }
  }
}
</style>