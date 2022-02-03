<template>
  <v-container v-resize="onResize" class="mt-1 ml-0 mr-0">
    <div v-if="(canManageOwnUsers || canManageUsers) && canManagePrograms">
      <v-tabs v-model="tab" v-if="isTabsShow">
        <v-tab v-if="canManageUsers">Administrators</v-tab>
        <v-tab v-if="canManageUsers">Professional</v-tab>
        <v-tab v-if="canManageUsers">S.Professional</v-tab>
        <v-tab v-if="canManageOwnUsers || canManageUsers">Users</v-tab>
        <v-tab v-if="canManagePrograms">Programs</v-tab>
      </v-tabs>
      <v-tabs-items v-model="tab">
        <v-tab-item v-if="canManageUsers">
          <user-profile-table :filter="adminFilter" :height="height"/>
        </v-tab-item>
        <v-tab-item v-if="canManageUsers">
          <user-profile-table :filter="professionalFilter" :height="height"/>
        </v-tab-item>
        <v-tab-item v-if="canManageUsers">
          <user-profile-table :filter="sProfessionalFilter" :height="height"/>
        </v-tab-item>
        <v-tab-item v-if="canManageOwnUsers || canManageUsers">
          <user-profile-table :filter="userFilter" :height="height"/>
        </v-tab-item>
        <v-tab-item v-if="canUploadPrograms">
          <program-data-table :user="currentUser" :height="height" v-if="canManagePrograms"/>
        </v-tab-item>
      </v-tabs-items>
    </div>
    <user-profile-details v-else :user="currentUser"/>
  </v-container>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import UserProfileTable from "@/components/UserProfileTable.vue";
import UserModule, {Role} from "@/store/modules/user"
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import UserProfileDetails from "@/components/UserProfileDetails.vue";
import {UserFilter} from "@/service/api/userService";
import ProgramDataTable from "@/components/ProgramDataTable.vue";
import ProgramHistoryDataTable from "@/components/ProgramHistoryDataTable.vue";
import BaseVueComponent from "@/components/BaseVueComponent";

@Component({
  components: {
    ProgramHistoryDataTable,
    ProgramDataTable, UserProfileDetails, UserProfileInfo, UserProfileTable
  }
})
export default class HomePageView extends BaseVueComponent {
  private height: number | null = null;
  private tab = null;

  private adminFilter: UserFilter = {roles: [Role.Admin]};
  private professionalFilter: UserFilter = {roles: [Role.Professional]};
  private sProfessionalFilter: UserFilter = {roles: [Role.SuperProfessional]};
  private userFilter: UserFilter = {roles: [Role.User]};

  private get isTabsShow() {
    return (this.canManageOwnUsers || this.canManageUsers);
  }

  private onResize() {
    const padding = this.isTabsShow ? 290 : 250;
    this.height = window.innerWidth < 600 ? null : window.innerHeight - 290;
  }
}
</script>

<style lang="scss" scoped>

.data {
  padding: 0 !important;

  [class*="col"] {
    border-left: rgba(0, 0, 0, 0.12) solid 1px;
    padding: 0 !important;
  }
}

</style>