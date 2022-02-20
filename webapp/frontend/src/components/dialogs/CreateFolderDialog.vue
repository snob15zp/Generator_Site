<template>
  <v-dialog v-model="visibleSync" max-width="800px" @click:outside="onCancel">
    <v-card>
      <v-card-title class="headline">Create Folder</v-card-title>
      <v-divider></v-divider>
      <v-card-text class="mt-4">
        <h4>Folder</h4>
        <div>
          <v-menu
              ref="menuRef"
              v-model="menu"
              :close-on-content-click="false"
              :disabled="isInProgress"
              transition="scale-transition"
              offset-y
              min-width="290px">
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
        </div>
        <div v-if="!renewFolder">
          <h4>Programs</h4>
          <v-radio-group v-model="programRadioGroup">
            <v-radio label="Upload programs" :value="programUploadValue" v-if="canUploadPrograms"/>
            <v-file-input
                v-if="canUploadPrograms"
                :disabled="isInProgress || !useUploadMethod"
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
            <v-radio label="Select programs" :value="programSelectValue" v-if="canUploadPrograms"/>
            <program-data-list
                v-if="canManagePrograms"
                height="400"
                :class="canUploadPrograms ? 'mr-4 ml-6' : ''"
                :disabled="useUploadMethod"
                :user="currentUser"
                :selected.sync="addProgramItems"/>
          </v-radio-group>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="onCancel">{{ $t("form.cancel") }}</v-btn>
        <v-btn color="blue darken-1" text @click="onCreate"
               :loading="isCreatingInProgress"
               :disabled="expiredAt == null || expiredAt.length === 0">
          {{ $t("form.ok") }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">

import {Emit, Model, Prop, Ref, Vue} from "vue-property-decorator";
import {Folder, Program, UploadFileRequest, User} from "@/store/models";
import UserModule from "@/store/modules/user";
import moment from "moment";
import programService from "@/service/api/programService";
import {EventBus} from "@/utils/event-bus";
import Component from "vue-class-component";
import ProgramDataList from "@/components/ProgramDataList.vue";
import axios from "axios";
import BaseVueComponent from "@/components/BaseVueComponent";

enum ProgramOptions {
  UPLOAD,
  SELECT
}

@Component({
  components: {ProgramDataList}
})
export default class CreateFolderDialog extends BaseVueComponent {
  @Model("visible") readonly visible!: boolean;
  @Prop() readonly user!: User;
  @Prop({default: () => null}) readonly renewFolder?: Folder | null;

  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;
  private menu = false;

  private readonly cancelSource = axios.CancelToken.source();

  private programRadioGroup: ProgramOptions = this.defaultProgramAddMethod;
  private expiredAt: string | null = null;
  private isInProgress = false;
  private isCreatingInProgress = false;
  private progress = 0;

  private fileInput: any = null;
  private addProgramItems: Program[] = [];

  get defaultProgramAddMethod() {
    return this.canUploadPrograms ? ProgramOptions.UPLOAD : ProgramOptions.SELECT;
  }

  get programUploadValue() {
    return ProgramOptions.UPLOAD
  }

  get programSelectValue() {
    return ProgramOptions.SELECT
  }

  get useUploadMethod() {
    return this.programRadioGroup == ProgramOptions.UPLOAD;
  }

  private get visibleSync() {
    return this.visible;
  }

  private set visibleSync(visible) {
    this.$emit('visible', visible);
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }

  private cancel() {
    if (this.isInProgress) {
      this.cancelSource.cancel();
    }
    this.reset();
  }

  private reset() {
    this.expiredAt = null;
    this.addProgramItems = [];
    this.fileInput = null;
    this.isCreatingInProgress = false;
    this.isInProgress = false;
    this.programRadioGroup = this.defaultProgramAddMethod;
  }

  private onCancel() {
    this.cancel();
    this.dismiss()
  }

  private onCreate() {
    this.isInProgress = true;
    this.isCreatingInProgress = true;
    const date = moment(this.expiredAt);
    const folder = {
      id: null,
      name: date.format("DD-MM-YY"),
      expiredAt: date.toDate()
    } as Folder;

    let promise: Promise<Folder>;
    if (this.renewFolder != null) {
      promise = programService.copyFolder(this.user, folder, this.renewFolder)
    } else {
      promise = programService.saveFolder(this.user, folder)
          .then((folder) => {
            if (this.useUploadMethod && this.fileInput?.length > 0) {
              return this.uploadFile(folder);
            } else if (!this.useUploadMethod && this.addProgramItems?.length > 0) {
              return this.addPrograms(folder);
            } else {
              return Promise.resolve(folder);
            }
          });
    }

    promise
        .then((folder) => this.createFinished(folder))
        .catch((e) => this.createFailed(e))
        .finally(() => {
          this.reset();
          this.dismiss();
        })
  };

  private addPrograms(folder: Folder): Promise<Folder> {
    return programService.addProgramsToFolder(folder, this.addProgramItems)
        .then(() => Promise.resolve(folder));
  }

  private uploadFile(folder: Folder): Promise<Folder> {
    return programService.uploadFile({
      folder: folder,
      files: this.fileInput,
      owner: this.user,
      onProgressCallback: (progress) => this.progress = progress,
      cancelSource: this.cancelSource
    } as UploadFileRequest)
        .then(() => Promise.resolve(folder));
  }

  @Emit("dismiss")
  private dismiss() {
    this.visibleSync = false;
    return false;
  }

  @Emit('failed')
  createFailed(e: Error) {
    return e;
  }

  @Emit('success')
  createFinished(folder: Folder) {
    return folder;
  }
}

</script>

<style scoped>

</style>