<template>
  <v-layout fill-height column class="user-profile col-md-12 col-lg-9">
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
              <v-text-field v-model="expiredAt" label="Expired Date" v-bind="attrs" v-on="on" readonly />
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

    <v-snackbar v-model="snackbar" :timeout="-1">
      <v-container>
        <v-row v-if="file">
          {{file.name}}
        </v-row>
        <v-row>
          <v-progress-linear :value="progress"/>
        </v-row>
      </v-container>
    </v-snackbar>

    <user-profile-info :user-profile="userProfile" :loading="profileLoading" />
    <programs
      class="flex-column fill-height mt-8 mb-8"
      :folders="folders"
      :files="files"
      :loading="foldersLoding || filesLoading"
      @on-folder-changed="fetchPrograms"
      @create-folder="openCreateFolderDialog"
      @upload-file="uploadFile"
    />
  </v-layout>
</template>

<script lang="ts">
import { Component, Vue, Watch, Ref } from "vue-property-decorator";
import { Folder, UploadFileRequest, UserProfile } from "../store/models";
import { fields } from "../forms/UserProfileFormValidator";
import UserProfilesModule from "../store/modules/userProfiles";
import ProgramsModule from "../store/modules/programs";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import Programs from "@/components/Programs.vue";
import moment from "moment";

@Component({
  components: { UserProfileInfo, Programs }
})
export default class UserProfileDetails extends Vue {
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;

  private userProfile: UserProfile | null = null;
  private selected = 0;
  private profileLoading = false;
  private foldersLoding = false;
  private filesLoading = false;

  private menu = false;
  private folder: Folder | null = null;
  private isCreateDialogShow = false;
  private expiredAt: string | null = null;
  private progress = 0;
  private snackbar = false;
  private file: File | null = null;

  get folders() {
    return ProgramsModule.folders;
  }

  get files() {
    return ProgramsModule.files;
  }

  get fields() {
    return fields;
  }

  mounted() {
    this.profileLoading = true;
    this.filesLoading = false;
    this.foldersLoding = false;

    UserProfilesModule.findById(this.$route.params.id)
      .then((v) => {
        this.userProfile = v.profile;
        this.fetchFodlers(v.profile);
      })
      .catch((e) => {
        console.error(e);
      })
      .finally(() => {
        this.profileLoading = false;
      });
  }

  private fetchPrograms(folder: Folder) {
    this.folder = folder;
    this.filesLoading = true;
    ProgramsModule.loadFilesByFolder(folder)
      .then()
      .finally(() => {
        this.filesLoading = false;
      });
  }

  private fetchFodlers(userProfile: UserProfile) {
    this.foldersLoding = true;
    ProgramsModule.loadFoldersByUserProfile(userProfile)
      .then(() => {
        this.fetchPrograms(this.folders![0]!);
      })
      .finally(() => {
        this.foldersLoding = false;
      });
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
    ProgramsModule.saveFolder([this.userProfile, folder])
      .then(() => this.fetchFodlers(this.userProfile!))
      .finally(() => this.createFolderClose());
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }

  private uploadFile(file: File) {
    this.snackbar = true;
    this.file = file;
    ProgramsModule.uploadFile({
      file: file,
      folder: this.folder,
      onProgressCallback: (progress) => this.progress = progress
    } as UploadFileRequest)
    .then(() => ProgramsModule.loadFilesByFolder(this.folder))
    .finally(() => {
      this.snackbar = false;
    });

  }
}
</script>

<style lang="scss">
.user-profile {
  .row--dense {
    > .col-12 {
      // padding: 16px;

      &:first-child {
        border-right: 1px solid #f0f0f0;
      }
    }
  }
}
</style>
