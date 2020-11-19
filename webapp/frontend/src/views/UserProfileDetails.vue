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
              :min="formattedDate(new Date())"
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

    <v-card outlined class="mt-8">
      <v-progress-linear indeterminate v-if="profileLoading" />
      <v-layout column pa-4>
        <v-row>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.name }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ userProfile.name }}</span>
          </v-col>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.address }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ userProfile.address }}</span>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.surname }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ userProfile.surname }}</span>
          </v-col>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.dateOfBirth }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ formattedDate(userProfile.dateOfBirth) }}</span>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.phoneNumber }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ userProfile.phoneNumber }}</span>
          </v-col>
          <v-col cols="6" md="3" class="font-weight-bold">{{ fields.email }}:</v-col>
          <v-col cols="6" md="3">
            <span v-if="userProfile">{{ userProfile.email }}</span>
          </v-col>
        </v-row>
      </v-layout>
    </v-card>
    <v-card
      ref="programsCardRef"
      outlined
      class="flex-column fill-height mt-8 mb-8"
      :loading="foldersLoding || filesLoading"
    >
      <v-card-title class="headline">
        Programms
        <v-spacer />
        <v-btn icon @click="openCreateFolderDialog">
          <v-icon>mdi-folder-plus</v-icon>
        </v-btn>
        <div>
          <v-file-input
            dense
            v-model="uploadFile"
            hide-input
            :disabled="selected === null"
            prepend-icon="mdi-file-upload"
          ></v-file-input>
        </div>
      </v-card-title>
      <v-divider />
      <v-layout ref="programsContainer" row class="row--dense">
        <v-col cols="12" md="6">
          <v-list v-if="userProfile" class="overflow-y-auto pa-0" v-bind:style="{ height: listHeight + 'px' }">
            <v-list-item-group active-class="primary--text" v-model="selected">
              <v-list-item v-for="folder in folders" :key="folder.hash">
                <v-list-item-avatar>
                  <v-icon>
                    mdi-folder
                  </v-icon>
                </v-list-item-avatar>
                <v-list-item-content>
                  <v-list-item-title>{{ folder.name }} </v-list-item-title>
                  <v-list-item-subtitle v-bind:class="{ 'error--text': isExpired(folder.expiredAt) }">
                    {{ expiredAtInterval(folder.expiredAt) }}
                  </v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-col>
        <v-col cols="12" md="6">
          <v-list v-if="userProfile" class="overflow-y-auto" v-bind:style="{ height: listHeight + 'px' }">
            <v-list-item v-for="file in files" :key="file.hash">
              <v-list-item-content>
                <a :href="'/program/' + file.hash">{{ file.name }}</a>
              </v-list-item-content>
            </v-list-item>
          </v-list>
        </v-col>
      </v-layout>
    </v-card>
  </v-layout>
</template>

<script lang="ts">
import Vue from "vue";
import Component from "vue-class-component";
import userProfiles from "@/store/modules/userProfiles";
import { fields } from "../forms/UserProfileFormValidator";
import programs from "@/store/modules/programs";
import moment from "moment";
import { Watch, Ref } from "vue-property-decorator";
import { Folder, UserProfile } from "../store/models";

@Component
export default class UserProfileDetails extends Vue {
  @Ref() readonly programsCardRef: Vue | undefined;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;

  private userProfile: UserProfile | null = null;
  private selected = 0;
  private profileLoading = false;
  private foldersLoding = false;
  private filesLoading = false;
  private height = 0;

  private menu = false;
  private folder: Folder | null = null;
  private isCreateDialogShow = false;
  private expiredAt: string | null = null;
  private uploadFile: any | null = null;

  get folders() {
    return programs.folders;
  }

  get files() {
    return programs.files;
  }

  get fields() {
    return fields;
  }

  get listHeight() {
    return this.height;
  }

  @Watch("selected")
  onFolderSelected() {
    this.fetchPrograms(this.selected);
  }

  @Watch("uploadFile")
  onFileSelected() {
    console.log("File selected " + this.uploadFile);
  }

  isExpired(date: Date) {
    const interval = date.getTime() - new Date().getTime();
    return interval < 0;
  }

  expiredAtInterval(date: Date) {
    const interval = date.getTime() - new Date().getTime();
    return interval < 0 ? "expired" : "expires " + moment.duration(interval).humanize(true);
  }

  formattedDate(date: Date) {
    return moment(date).format("YYYY-MM-DD");
  }

  mounted() {
    this.$nextTick(() => this.onSizeChanged());

    this.profileLoading = true;
    this.filesLoading = false;
    this.foldersLoding = false;

    userProfiles
      .findByHash(this.$route.params.hash)
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

  private fetchPrograms(index: number) {
    const folder: Folder = this.folders![index];
    this.filesLoading = true;
    programs
      .loadFilesByFolder(folder)
      .then()
      .finally(() => {
        this.filesLoading = false;
      });
  }

  private fetchFodlers(userProfile: UserProfile) {
    this.foldersLoding = true;
    programs
      .loadFoldersByUserProfile(userProfile)
      .then(() => {
        this.fetchPrograms(0);
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
    this.folder = {
      hash: null,
      name: date.format("DD-MM-YY"),
      path: "",
      expiredAt: date.toDate()
    };
    programs
      .saveFolder(this.folder)
      .then(() => this.fetchFodlers(this.userProfile!))
      .finally(() => this.createFolderClose());
    console.log("");
  }

  private onSizeChanged() {
    this.height = this.programsCardRef ? Math.max(this.programsCardRef!.$el.clientHeight - 100, 300) : 300;
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }
}
</script>

<style lang="scss">
.user-profile {
  .row--dense > .col {
    padding: 0;
    :first-child {
      padding-left: 4px;
      border-right: 1px solid #f0f0f0;
    }
  }
}
</style>
