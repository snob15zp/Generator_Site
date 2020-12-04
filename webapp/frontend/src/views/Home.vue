<template>
  <v-layout fill-height column class="user-profile col-md-12 col-lg-9">
    <v-dialog v-model="deleteDialog.show" max-width="500px">
      <v-card>
        <v-card-title class="headline">{{ deleteDialog.title }}</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="mt-4">
          {{ deleteDialog.message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeDelete">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="deleteConfirm">{{ $t("form.ok") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="editDialog.show" max-width="500px">
      <v-card>
        <v-card-title>{{ editDialog.title }}</v-card-title>
        <v-card-text>
          <user-profile-form v-model="userProfile" />
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeEditDialog">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="saveProfile">{{ $t("form.save") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-card outlined class="mt-8 mb-8">
      <v-overlay
        :absolute="true"
        :value="loading"
      />
      <UserProfileTable
        :loading="loading"
        :items="items"
        :total="total"
        @on-delete-item="deleteProfile"
        @on-delete-items="deleteProfiles"
        @on-save-item="editUserProfile"
        @on-create-item="createUserProfile"
        @fetch-data="fetchUserProfiles"
      />
    </v-card>
  </v-layout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import UserProfileTable from "@/components/UserProfileTable.vue";
import UserProfileForm from "@/components/UserProfileForm.vue";
import { TranslateResult } from "vue-i18n";
import { PagingRequest, UserProfile } from "../store/models";
import UserProfilesModule from "../store/modules/userProfiles";

declare interface DialogData {
  show: boolean;
  title?: string | TranslateResult;
  message?: string | TranslateResult;
  items?: Array<UserProfile>;
}

const EMPTY_PROFILE: UserProfile = {
  name: "",
  surname: "",
  phoneNumber: "",
  address: "",
  dateOfBirth: null,
  email: ""
};

@Component({
  components: { UserProfileTable, UserProfileForm }
})
export default class Home extends Vue {
  private deleteDialog: DialogData = { show: false };
  private editDialog: DialogData = { show: false };

  private loading = false;
  private request!: PagingRequest;

  get userProfile() {
    return this.editDialog.items ? this.editDialog.items[0] : null;
  }

  get items() {
    return UserProfilesModule.profiles?.data || [];
  }

  get total() {
    return UserProfilesModule.profiles?.total || 0;
  }

  private editUserProfile(profile: UserProfile) {
    this.editDialog = { show: true, items: [Object.assign({}, profile)], title: this.$t("user-profile.edit-profile") };
  }

  private createUserProfile() {
    this.editDialog = { show: true, items: [EMPTY_PROFILE], title: this.$t("user-profile.new-profile") };
  }

  private deleteProfiles(profiles: UserProfile[]) {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_selected_profiles_title"),
      message: this.$t("user-profile.delete_selected_profiles_confirm"),
      items: profiles
    };
  }

  private deleteProfile(profile: UserProfile) {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_profile_title"),
      message: this.$t("user-profile.delete_profile_confirm", [profile.name]),
      items: [profile]
    };
  }

  private closeDelete() {
    this.deleteDialog.show = false;
  }

  private fetchUserProfiles(request: PagingRequest) {
    this.request = request;

    this.loading = true;
    UserProfilesModule.load(request)
      .catch((e) => console.log("Error fetching user profiles " + e))
      .finally(() => {
        this.loading = false;
      });
  }

  private deleteConfirm() {
    this.loading = true;
    UserProfilesModule.remove(this.deleteDialog.items!)
      .then(() => this.fetchUserProfiles(this.request))
      .catch((e) => console.error(e))
      .finally(() => {
        this.loading = false;
      });

    this.closeDelete();
  }

  private closeEditDialog() {
    this.editDialog.show = false;
  }

  private saveProfile() {
    this.closeEditDialog();
    this.loading = true;
    UserProfilesModule.save(this.editDialog.items![0])
      .then(() => this.fetchUserProfiles(this.request))
      .catch((e) => console.error(e))
      .finally(() => {
        this.loading = false;
      });
  }
}
</script>
