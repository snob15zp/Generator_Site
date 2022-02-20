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
      <user-profile-form
          v-model="editUser"
          :title="editDialog.title"
          :roles="allRoles"
          @save="saveProfile"
          @cancel="closeEditDialog"/>
    </v-dialog>

    <v-dialog v-model="isUsersAddDialogShow" max-width="800px">
      <v-card>
        <v-card-title class="headline">Add users</v-card-title>
        <v-card-subtitle><small>Use SHIFT or CTRL with the mouse to select multiple items.</small></v-card-subtitle>
        <user-data-list
            :refresh="isUsersAddDialogShow"
            :user="filter.owner"
            height="600"
            :selected.sync="addedUsers"/>
        <v-card-actions>
          <span>Total selected: {{ addedUsers.length }}</span>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="onAddUsersCancel()">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="onAddUsersConfirm()" :disabled="addedUsers.length === 0">
            {{ $t("form.ok") }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-card outlined>
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
          :height="height"
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
            <v-tooltip bottom v-if="isAdmin && filter.owner">
              <template v-slot:activator="{on, attrs}">
                <v-btn color="primary" @click="onAddUsers" icon v-bind="attrs" v-on="on">
                  <v-icon right dark>mdi-account-multiple-plus</v-icon>
                </v-btn>
              </template>
              <span>Add users</span>
            </v-tooltip>
            <v-tooltip bottom>
              <template v-slot:activator="{on, attrs}">
                <v-btn color="primary" @click="onCreateItem" icon v-bind="attrs" v-on="on">
                  <v-icon right dark>mdi-account-plus</v-icon>
                </v-btn>
              </template>
              <span>New user</span>
            </v-tooltip>
            <v-tooltip bottom>
              <template v-slot:activator="{on, attrs}">
                <v-btn color="primary" icon
                       :disabled="selected.length === 0"
                       @click="onDeleteItems"
                       v-bind="attrs" v-on="on">
                  <v-icon right dark>mdi-delete</v-icon>
                </v-btn>
              </template>
              <span>Delete users</span>
            </v-tooltip>
            <v-tooltip bottom>
              <template v-slot:activator="{on, attrs}">
                <v-btn color="primary" icon
                       @click="fetchData()"
                       v-bind="attrs" v-on="on">
                  <v-icon dark>mdi-refresh</v-icon>
                </v-btn>
              </template>
              <span>Refresh</span>
            </v-tooltip>
          </v-toolbar>
        </template>
        <template v-slot:[`item.name`]="{ item }">
          <a :href="'user/' + item.id" v-if="!isAdmin(item)">{{ userName(item) }}</a>
          <div v-else>{{ userName(item) }}</div>
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
import {Component, Prop, Vue, Watch} from "vue-property-decorator";
import {PagingRequest, User, UserProfile} from "@/store/models";
import {DataOptions} from "vuetify";
import {TranslateResult} from "vue-i18n";
import UserProfileForm from "@/components/UserProfileForm.vue";
import {EventBus} from "@/utils/event-bus";
import userService, {UserFilter} from "@/service/api/userService";
import UserModule, {Role} from "@/store/modules/user";
import UserDataList from "@/components/UserDataList.vue";

const START_PAGE = 1;
const DEFAULT_ITEMS_PER_PAGE = 10;

declare interface DialogData {
  show: boolean;
  title?: string | TranslateResult;
  message?: string | TranslateResult;
  items?: Array<User>;
}

const EMPTY_PROFILE: User = {
  profile: {
    name: "",
    surname: "",
    phoneNumber: "",
    address: "",
    dateOfBirth: null,
    email: ""
  } as UserProfile
} as User;

@Component({
  components: {UserDataList, UserProfileForm}
})
export default class UserProfileTable extends Vue {
  @Prop() readonly filter!: UserFilter;
  @Prop() readonly height!: number;

  private deleteDialog: DialogData = {show: false};
  private editDialog: DialogData = {show: false};

  private loading = false;
  private request!: PagingRequest;

  private items: User[] = [];
  private total = 0;

  private searchTimeout?: number | null = null;

  private options: DataOptions | null = null;
  private selected: User[] = [];

  private search = "";

  private isUsersAddDialogShow = false;
  private addedUsers: User[] = [];

  private get headers() {
    return [
      {text: this.$t("user-profile.column-user"), value: "name"},
      {text: this.$t("user-profile.column-created-at"), value: "created_at", width: "20%"},
      {text: this.$t("user-profile.column-modified-at"), value: "updated_at", width: "20%"},
      {text: this.$t("user-profile.column-actions"), value: "actions", sortable: false, width: "90px"}
    ];
  }

  get editUser() {
    return this.editDialog.items ? this.editDialog.items[0] : null;
  }

  get allRoles() {
    if (UserModule.canManageUsers)
      return [Role.User, Role.Professional, Role.SuperProfessional, Role.Admin];
    else
      return [Role.User];
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchData();
  }

  private userName(user: User): string {
    return user.profile.name + (user.profile.surname ? " " + user.profile.surname : "");
  }

  private isAdmin(user: User) {
    return user.role == Role.Admin;
  }

  private closeDelete() {
    this.deleteDialog.show = false;
  }

  private fetchUsers(request: PagingRequest) {
    this.request = request;

    this.loading = true;
    userService.fetchAll(request)
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
    userService.delete(this.deleteDialog.items!.map(user => user.id))
        .then(() => this.fetchUsers(this.request))
        .catch((e) => {
          this.loading = false;
          EventBus.$emit("error", e)
        });
    this.closeDelete();
  }

  private closeEditDialog() {
    this.editDialog.show = false;
    this.resetEditUser();
  }

  private saveProfile(user: User) {
    this.closeEditDialog();
    this.loading = true;
    if (this.filter.owner) {
      user.owner = this.filter.owner;
    }
    userService.save(user)
        .then(() => this.fetchUsers(this.request))
        .catch((e) => {
          this.loading = false;
          EventBus.$emit("error", e);
        });
  }

  private onAddUsers() {
    this.addedUsers = [];
    this.isUsersAddDialogShow = true;
  }

  private onAddUsersCancel() {
    this.addedUsers = [];
    this.isUsersAddDialogShow = false;
  }

  private onAddUsersConfirm() {
    if (this.addedUsers.length > 0) {
      this.loading = true;
      userService.add(this.filter.owner!, this.addedUsers)
          .then(() => this.fetchData())
          .catch((e) => {
            this.loading = false;
            EventBus.$emit("error", e);
          });

    }
    this.addedUsers = [];
    this.isUsersAddDialogShow = false;
  }

  private onDeleteItems() {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_selected_profiles_title"),
      message: this.$t("user-profile.delete_selected_profiles_confirm"),
      items: this.selected
    };
  }

  private onDeleteItem(user: User) {
    this.deleteDialog = {
      show: true,
      title: this.$t("user-profile.delete_profile_title"),
      message: this.$t("user-profile.delete_profile_confirm", [user.profile.name]),
      items: [user]
    };
  }

  onSaveItem(user: User) {
    this.editDialog = {
      show: true,
      items: [Object.assign({}, user)],
      title: this.$t("user-profile.edit-profile")
    };
  }

  onCreateItem() {
    this.editDialog = {show: true, items: [EMPTY_PROFILE], title: this.$t("user-profile.new-profile")};
  }

  fetchData() {
    const {sortBy, sortDesc, page, itemsPerPage} = this.options!;
    this.fetchUsers({
      page: page || 1,
      itemsPerPage: itemsPerPage || DEFAULT_ITEMS_PER_PAGE,
      sortBy: sortBy || [],
      sortDesc: sortDesc || [],
      query: this.search || "",
      filter: this.filter
    });
  }

  private onSearchChanged() {
    const length = this.search.trim().length;
    if (length > 0 && length < 3) return;

    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchData(), 500);
  }

  private resetEditUser() {
    this.editDialog.items = [];
  }
}
</script>

<style scoped>
.theme--light.v-sheet--outlined {
  border: none !important;
}
</style>
