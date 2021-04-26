<template>
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
            {{ folder.name }}
          </v-card-title>
          <v-card-subtitle>
            <v-btn v-if="canManagePrograms" class="mr-2"
                   @click="downloadFolder(folder)"
                   :loading="downloadFolderId===folder.id"
                   outlined
                   small
                   text>
              download
              <v-icon right dark small>mdi-archive-arrow-down-outline</v-icon>
            </v-btn>
            <v-btn
                class="mr-2"
                @click="importToDevice(folder)"
                :disabled="!canManagePrograms && isExpired(folder.expiredAt)"
                :loading="importFolderId===folder.id"
                outlined
                small
                text>
              Import to device
              <v-icon right dark small>mdi-import</v-icon>
            </v-btn>
            <v-btn
                v-if="canManagePrograms"
                class="mr-2"
                @click="deleteFolder(folder)"
                :loading="deletingFolderId===folder.id"
                outlined
                small
                text>
              Delete
              <v-icon right dark small>mdi-delete</v-icon>
            </v-btn>
            <v-btn-toggle v-if="canManagePrograms" v-model="toggle" class="mr-2">
              <v-btn
                  :disabled="uploadInProgress"
                  :color="isUploadDialogShow?'primary':''"
                  @click="showUploadForm()"
                  outlined
                  small>
                Upload programs
                <v-icon right dark small :color="isUploadDialogShow?'primary':''">mdi-upload</v-icon>
              </v-btn>
            </v-btn-toggle>
          </v-card-subtitle>
          <v-expand-transition>
            <v-layout row v-show="isUploadDialogShow" class="pl-4 pr-6">
              <v-file-input
                  v-if="canManagePrograms"
                  :disabled="uploadInProgress || isFoldersEmpty"
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
              <v-btn @click="uploadFile"
                     color="primary"
                     :loading="uploadInProgress"
                     :disabled="fileInput == null || uploadInProgress">
                Upload
                <v-icon>mdi-upload</v-icon>
              </v-btn>
            </v-layout>
          </v-expand-transition>
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

    <message-dialog ref="messageDialog"/>
  </v-card>
</template>

<script lang="ts">
import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, UploadFileRequest, UserProfile} from "@/store/models";
import {expiredAtInterval, isExpired} from "@/utils/dateUtils";
import {ResizeObserver} from "@juggle/resize-observer";
import UserModule from "@/store/modules/user";
import programService from "@/service/api/programService";
import saveDownloadFile from "@/utils/download-file";
import {EventBus} from "@/utils/event-bus";
import moment from "moment";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";

@Component({
  components: {MessageDialog},
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
  @Ref() readonly messageDialog: MessageDialog | undefined;

  private folders: Folder[] = [];
  private files: Program[] = [];

  private filesLoading = false;
  private foldersLoading = false;

  private selected: number | null = null;
  private fileInput: any | null = null;
  private folder: Folder | null = null;

  private downloadFolderId: string | null = null;
  private deletingFolderId: string | null = null;
  private importFolderId: string | null = null;
  private uploadInProgress = false;
  private progress = 0;
  private file: File | null = null;

  private listHeight = this.getHeight();

  private menu = false;
  private isCreateDialogShow = false;
  private expiredAt: string | null = null;

  private isUploadDialogShow = false;
  private toggle = -1;

  private selectedPrograms: string[] = [];

  private observer = new ResizeObserver(() => {
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
    if (this.selected == null) return;
    if (this.folders[this.selected].id == this.folder?.id) return;

    this.folder = this.folders[this.selected];
    this.files = [];
    this.onFolderChanged(this.folder);
  }

  private showUploadForm() {
    this.isUploadDialogShow = !this.isUploadDialogShow;
  }

  private isItemSelected(program: Program) {
    return this.selectedPrograms.indexOf(program.id) >= 0;
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
            return programService.deletePrograms(this.selectedPrograms)
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
    this.fileInput = null;
    this.selectedPrograms.length = 0;
    this.fetchPrograms(folder);
    return folder;
  }

  private getHeight() {
    return Math.max(window.innerHeight - 490, 450);
  }

  private uploadFile() {
    this.uploadInProgress = true;
    this.file = this.fileInput;
    programService.uploadFile({
      files: this.fileInput,
      folder: this.folder,
      onProgressCallback: (progress) => this.progress = progress
    } as UploadFileRequest)
        .then(() => this.onFolderChanged(this.folder!))
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.uploadInProgress = false;
          this.isUploadDialogShow = false;
          this.fileInput = null;
          this.toggle = -1;
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

  private createFolderClose() {
    this.expiredAt = null;
    this.isCreateDialogShow = false;
  }

  private createFolderConfirm() {
    this.isCreateDialogShow = false;
    this.foldersLoading = true;
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