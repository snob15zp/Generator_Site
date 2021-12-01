<template>
  <v-container class="pa-0">
    <message-dialog ref="messageDialog"/>
    <v-dialog v-model="showEditDialog" max-width="500px">
      <user-profile-form
          v-model="userProfile"
          :title="editDialogTitle"
          :roles="filter.roles"
          @save="saveProfile"
          @cancel="closeEditDialog"/>
    </v-dialog>
    <data-list
        :height="height"
        :headers="headers"
        :items="items"
        :loading="loading"
        :title="title"
        :selected.sync="selected">
      <template v-slot:action>
        <v-btn icon @click="onClickNewUser">
          <v-icon>mdi-account-plus</v-icon>
        </v-btn>
        <v-btn icon class="ml-2" @click="onClickDeleteSelected" :disabled="selected.length === 0">
          <v-icon>mdi-account-multiple-minus</v-icon>
        </v-btn>
      </template>
      <template v-slot:item-action="{item}">
        <v-btn @click="onClickDeleteUser(item)" icon x-small>
          <v-icon>mdi-delete</v-icon>
        </v-btn>
      </template>
    </data-list>
  </v-container>
</template>

<script lang="ts">

import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import DataList, {DataListHeader} from "@/components/DataList.vue";
import {Program, User, UserProfile} from "@/store/models";
import userService, {UserFilter} from "@/service/api/userService";
import {EventBus} from "@/utils/event-bus";
import {Role} from "@/store/modules/user";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import UserProfileForm from "@/components/UserProfileForm.vue";
import userProfileService from "@/service/api/userProfileService";
import programService from "@/service/api/programService";

@Component({
  components: {UserProfileForm, MessageDialog, DataList}
})
export default class UserDataList extends Vue {
  @Prop({default: null}) readonly height?: number;
  @Prop({default: null}) readonly title?: string;
  @Prop({default: () => ['name', 'date']}) readonly sortBy?: string[];
  @Prop() readonly filter!: UserFilter;
  @Ref() readonly messageDialog: MessageDialog | undefined;

  private readonly emptyUserProfile = {
    role: this.filter.roles ? this.filter.roles[0] : null
  } as UserProfile;

  private selected: User[] = [];
  private showEditDialog = false;
  private userProfile: UserProfile | null = null;
  private items: User[] = [];

  private loading = false;
  private headers: DataListHeader[] = [
    {
      text: "Name",
      value: (user: User) => user.profile.name + (user.profile.surname ? " " + user.profile.surname : ""),
      sortable: (this.sortBy?.indexOf('name') ?? -1) >= 0,
      title: true,
      filtered: true,
      href: (user: User) => `user/${user.id}`
    },
    {
      text: "Role",
      value: (user: User) => user.role.toString(),
      sortable: (this.sortBy?.indexOf('role') ?? -1) >= 0,
      subtitle: true
    },
    {
      text: "Date created",
      value: (user: User) => user.profile.createdAt?.getTime() ?? 0,
      sortable: (this.sortBy?.indexOf('date') ?? -1) >= 0
    },
  ];

  private get editDialogTitle() {
    return (this.filter.roles!.indexOf(Role.User) >= 0) ? "Create user" : "Create professional";
  }

  mounted() {
    this.fetchData()
  }

  @Watch("filter", {deep: true})
  private onFilterChanged() {
    this.fetchData();
  }


  private onClickDeleteSelected() {
    this.deleteUsers("Are you sure you want to delete the selected users?", this.selected.map((p) => p.id))
  }

  private onClickDeleteUser(user: User) {
    this.deleteUsers("Are you sure you want to delete the user?", [user.id]);
  }

  private deleteUsers(message: string, userIds: string[]) {
    this.messageDialog?.show("Delete", message)
        .then((result) => {
          if (result) {
            this.loading = true;
            return userService.delete(userIds)
          } else {
            return Promise.reject();
          }
        })
        .then(() => this.fetchData())
        .catch((e) => {
              this.loading = false;
              e && EventBus.$emit("error", e)
            }
        );
  }

  private closeEditDialog() {
    this.showEditDialog = false;
    this.resetUserProfile();
  }

  private saveProfile() {
    this.showEditDialog = false;
    this.loading = true;
    userProfileService.save(this.userProfile!)
        .then(() => this.fetchData())
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.resetUserProfile();
          this.loading = false;
        });
  }

  private fetchData() {
    this.loading = true;
    userService.getUsers(this.filter)
        .then(users => this.items = users)
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.selected = [];
          this.loading = false;
        });
  }

  private onClickNewUser() {
    this.resetUserProfile();
    this.showEditDialog = true;
  }

  private resetUserProfile() {
    this.userProfile = this.emptyUserProfile;
  }
}

</script>

<style scoped>

</style>