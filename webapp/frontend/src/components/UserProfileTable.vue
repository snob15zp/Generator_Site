<template>
  <v-container>
    <v-dialog v-model="dialogDelete.show" max-width="500px">
      <v-card>
        <v-card-title class="headline">{{ dialogDelete.title }}</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="mt-4">
          {{ dialogDelete.message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeDelete">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="deleteConfirm">{{ $t("form.ok") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogEdit.show" max-width="500px">
      <v-card>
        <v-card-title>{{ dialogEdit.title }}</v-card-title>
        <v-card-text>
          <user-profile-form v-model="dialogEdit.profile" />
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="closeEditDialog">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="saveProfile">{{ $t("form.save") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-data-table
      v-model="selected"
      :loading="loading"
      :server-items-length="total"
      :options.sync="options"
      :headers="headers"
      :items="items"
      item-key="hash"
      class="elevation-1"
      show-select
    >
      <template v-slot:top>
        <v-toolbar flat>
          <!-- Search bar -->
          <v-layout>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              @input="onSearchChanged"
              :label="$t('user-profile.search')"
              single-line
              hide-details
            ></v-text-field>
          </v-layout>
          <v-spacer></v-spacer>
          <v-btn icon @click="createUserProfile">
            <v-icon>mdi-account-plus</v-icon>
          </v-btn>
          <v-btn icon :disabled="selected.length == 0" @click="deleteSelectedItems">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </v-toolbar>
      </template>
      <template v-slot:[`item.user`]="{ item }">
        <a :href="'/profile/' + item.hash">{{ item.name + " " + item.surname }}</a>
      </template>
      <template v-slot:[`item.createdAt`]="{ item }">{{ $d(item.createdAt) }}</template>
      <template v-slot:[`item.modifiedAt`]="{ item }">{{ $d(item.modifiedAt) }}</template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-icon small class="mr-2" @click="editUserProfile(item)">mdi-pencil</v-icon>
        <v-icon small @click="deleteUserProfile(item)">mdi-delete</v-icon>
      </template>
    </v-data-table>
  </v-container>
</template>

<script lang="ts">
import { TranslateResult } from "vue-i18n";
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import { PagingResponse, UserProfile } from "../store/models";
import { DataOptions } from "vuetify";
import UserProfileForm from "@/components/UserProfileForm.vue";
import userProfiles from "@/store/modules/userProfiles";

declare interface DialogDelete {
  show: boolean;
  title?: string | TranslateResult;
  message?: string | TranslateResult;
  items?: Array<UserProfile>;
}

declare interface DialogEdit {
  show: boolean;
  title?: string | TranslateResult;
  profile?: UserProfile;
}

const START_PAGE = 1;
const DEFAULT_ITEMS_PER_PAGE = 10;
const EMPTY_PROFILE: UserProfile = {
  name: "",
  surname: "",
  phoneNumber: "",
  address: "",
  dateOfBirth: null,
  email: ""
};

@Component({
  components: { UserProfileForm }
})
export default class UserProfileTable extends Vue {
  private dialogDelete: DialogDelete = { show: false };
  private dialogEdit: DialogEdit = { show: false };

  private searchTimeout?: number | null = null;

  private options: DataOptions | null = null;
  private selected = [];

  private search = "";
  private loading = false;

  private get headers() {
    return [
      { text: this.$t("user-profile.column-user"), value: "user" },
      { text: this.$t("user-profile.column-created-at"), value: "createdAt", width: "20%" },
      { text: this.$t("user-profile.column-modified-at"), value: "modifiedAt", width: "20%" },
      { text: this.$t("user-profile.column-actions"), value: "actions", sortable: false, width: "90px" }
    ];
  }

  get total(): number {
    return userProfiles.profiles?.total || 0;
  }

  get items(): Array<UserProfile> {
    return userProfiles.profiles?.data || Array<UserProfile>();
  }

  mounted() {
    this.fetchUserProfiles();
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchUserProfiles();
  }

  private onSearchChanged() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchUserProfiles(), 500);
  }

  private fetchUserProfiles() {
    this.loading = true;
    const { sortBy, sortDesc, page, itemsPerPage } = this.options!;
    userProfiles
      .load({
        page: page || 1,
        itemsPerPage: itemsPerPage || DEFAULT_ITEMS_PER_PAGE,
        sortBy: sortBy || [],
        sortDesc: sortDesc || [],
        query: this.search
      })
      .catch((e) => console.log("Error fetching user profiles " + e))
      .finally(() => {
        this.loading = false;
      });
  }

  private editUserProfile(profile: UserProfile) {
    this.dialogEdit = { show: true, profile: Object.assign({}, profile), title: this.$t("user-profile.edit-profile") };
  }

  private createUserProfile() {
    this.dialogEdit = { show: true, profile: EMPTY_PROFILE, title: this.$t("user-profile.new-profile") };
  }

  private deleteSelectedItems() {
    this.dialogDelete = {
      show: true,
      title: this.$t("user-profile.delete_selected_profiles_title"),
      message: this.$t("user-profile.delete_selected_profiles_confirm"),
      items: this.selected
    };
  }

  private deleteUserProfile(profile: UserProfile) {
    this.dialogDelete = {
      show: true,
      title: this.$t("user-profile.delete_profile_title"),
      message: this.$t("user-profile.delete_profile_confirm", [profile.name]),
      items: [profile]
    };
  }

  private closeDelete() {
    this.dialogDelete.show = false;
  }

  private deleteConfirm() {
    userProfiles
      .deleteProfiles(this.dialogDelete.items!)
      .then(() => this.fetchUserProfiles())
      .catch((e) => console.error(e));

    this.closeDelete();
  }

  private closeEditDialog() {
    this.dialogEdit.show = false;
  }

  private saveProfile() {
    this.closeEditDialog();
    userProfiles
      .save(this.dialogEdit.profile!)
      .then(() => this.fetchUserProfiles())
      .catch((e) => console.error(e));
  }
}
</script>
