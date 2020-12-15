<template>
  <v-container>
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
          <user-profile-form v-model="userProfile"/>
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
      <v-data-table
          v-model="selected"
          :loading="loading"
          :server-items-length="total"
          :options.sync="options"
          :headers="headers"
          :items="items"
          :single-select="false"
          item-key="id"
          show-select>
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
            <v-btn color="primary" @click="onCreateItem">
              New user
              <v-icon right dark>mdi-account-plus</v-icon>
            </v-btn>
            <v-btn color="primary" :disabled="selected.length === 0" @click="onDeleteItems" class="ml-2">
              Delete
              <v-icon right dark>mdi-delete</v-icon>
            </v-btn>
          </v-toolbar>
        </template>
        <template v-slot:[`item.name`]="{ item }">
          <a :href="'/profile/' + item.id">{{ item.name + " " + item.surname }}</a>
        </template>
        <template v-slot:[`item.created_at`]="{ item }">{{ $d(item.createdAt) }}</template>
        <template v-slot:[`item.updated_at`]="{ item }">{{ $d(item.updatedAt) }}</template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-icon small class="mr-2" @click="onSaveItem(item)">mdi-pencil</v-icon>
          <v-icon small @click="onDeleteItem(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>
  </v-container>
</template>

<script lang="ts">
import {Component, Vue, Watch} from "vue-property-decorator";
import {PagingRequest, UserProfile} from "@/store/models";
import {DataOptions} from "vuetify";
import {TranslateResult} from "vue-i18n";
import UserProfileForm from "@/components/UserProfileForm.vue";
import userProfileService from "@/service/api/userProfileService";
import {EventBus} from "@/utils/event-bus";

const START_PAGE = 1;
const DEFAULT_ITEMS_PER_PAGE = 10;

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
  components: {UserProfileForm}
})
export default class UserProfileTable extends Vue {
  private deleteDialog: DialogData = {show: false};
  private editDialog: DialogData = {show: false};

  private loading = false;
  private request!: PagingRequest;

  private items: UserProfile[] = [];
  private total = 0;

  private searchTimeout?: number | null = null;

  private options: DataOptions | null = null;
  private selected: UserProfile[] = [];

  private search = "";

  private get headers() {
    return [
      {text: this.$t("user-profile.column-user"), value: "name"},
      {text: this.$t("user-profile.column-created-at"), value: "created_at", width: "20%"},
      {text: this.$t("user-profile.column-modified-at"), value: "updated_at", width: "20%"},
      {text: this.$t("user-profile.column-actions"), value: "actions", sortable: false, width: "90px"}
    ];
  }

  get userProfile() {
    return this.editDialog.items ? this.editDialog.items[0] : null;
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchData();
  }

  private closeDelete() {
    this.deleteDialog.show = false;
  }

  private fetchUserProfiles(request: PagingRequest) {
    this.request = request;

    this.loading = true;
    userProfileService.fetchAll(request)
        .then(page => {
          this.items = page.data;
          this.total = page.total;
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.loading = false;
        });
  }

  private deleteConfirm() {
    this.loading = true;
    userProfileService.delete(this.deleteDialog.items!)
        .then(() => this.fetchUserProfiles(this.request))
        .catch((e) => EventBus.$emit("error", e))
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
    userProfileService.save(this.editDialog.items![0])
        .then(() => this.fetchUserProfiles(this.request))
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.loading = false;
        });
  }

  private onDeleteItems() {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_selected_profiles_title"),
      message: this.$t("user-profile.delete_selected_profiles_confirm"),
      items: this.selected
    };
  }

  private onDeleteItem(userProfile: UserProfile) {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_profile_title"),
      message: this.$t("user-profile.delete_profile_confirm", [userProfile.name]),
      items: [userProfile]
    };
  }

  onSaveItem(userProfile: UserProfile) {
    this.editDialog = {
      show: true,
      items: [Object.assign({}, userProfile)],
      title: this.$t("user-profile.edit-profile")
    };
  }

  onCreateItem() {
    this.editDialog = {show: true, items: [EMPTY_PROFILE], title: this.$t("user-profile.new-profile")};
  }

  fetchData() {
    const {sortBy, sortDesc, page, itemsPerPage} = this.options!;
    this.fetchUserProfiles({
      page: page || 1,
      itemsPerPage: itemsPerPage || DEFAULT_ITEMS_PER_PAGE,
      sortBy: sortBy || [],
      sortDesc: sortDesc || [],
      query: this.search || ""
    });
  }

  private onSearchChanged() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchData(), 500);
  }
}
</script>
