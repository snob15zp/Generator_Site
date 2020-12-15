<template>
  <v-layout fill-height column class="user-profile">
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
    <user-profile-info :user-profile="userProfile" :loading="profileLoading"/>
    <programs
        class="flex-column fill-height mt-8 mb-8"
        :folders="folders"
        :files="programs"
        :loading="foldersLoading || filesLoading"
        @on-folder-changed="fetchPrograms"
        @create-folder="openCreateFolderDialog"
    />
  </v-layout>
</template>

<script lang="ts">
import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, SaveFolderRequest, UploadFileRequest, UserProfile} from "@/store/models";
import {fields} from "@/forms/UserProfileFormValidator";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import Programs from "@/components/Programs.vue";
import moment from "moment";
import programService from "@/service/api/programService";
import userProfileService from "@/service/api/userProfileService";
import saveDownloadFile from "../utils/download-file";
import {EventBus} from "@/utils/event-bus";

@Component({
  components: {UserProfileInfo, Programs}
})
export default class UserProfileDetails extends Vue {
  @Prop({default: null}) readonly userProfileId?: string;
  @Prop({default: null}) readonly userId?: string;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;

  private userProfile: UserProfile | null = null;

  private folders: Folder[] = [];
  private programs: Program[] = [];

  private profileLoading = false;

  private foldersLoading = false;
  private filesLoading = false;
  private menu = false;
  private isCreateDialogShow = false;
  private expiredAt: string | null = null;

  get fields() {
    return fields;
  }

  @Watch("userId")
  onUserIdChanged() {
    console.log(this.userId)
    if (this.userId) {
      const promise = userProfileService.fetchByUserId(this.userId)
      this.fetchUserProfile(promise);
    }
  }

  @Watch("userProfileId")
  onUserProfileIdChanged() {
    if (this.userProfileId) {
      const promise = userProfileService.fetchById(this.userProfileId!);
      this.fetchUserProfile(promise);
    }
  }

  mounted(){
    if(this.userId) {
      this.fetchUserProfile(userProfileService.fetchByUserId(this.userId));
    } else if(this.userProfileId){
      this.fetchUserProfile(userProfileService.fetchById(this.userProfileId));
    }
  }

  private fetchUserProfile(promise: Promise<UserProfile>) {
    this.profileLoading = true;
    this.filesLoading = false;
    this.foldersLoading = false;

    promise
        .then((profile) => {
          this.userProfile = profile;
          this.fetchFolders(profile);
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.profileLoading = false;
        });
  }

  private fetchPrograms(folder: Folder) {
    this.filesLoading = true;
    programService.fetchPrograms(folder)
        .then(programs => this.programs = programs)
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.filesLoading = false;
        });
  }

  private fetchFolders(userProfile: UserProfile) {
    this.foldersLoading = true;
    programService.fetchFolders(userProfile.id!)
        .then((folders) => {
          this.folders = folders;
          if (this.folders && this.folders.length > 0) {
            this.fetchPrograms(this.folders[0]);
          }
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.foldersLoading = false;
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
    programService.saveFolder(this.userProfile!, folder)
        .then(() => this.fetchFolders(this.userProfile!))
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => this.createFolderClose());
  }

  private save(date: string) {
    this.menuRef!.save(date);
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
