<template>
  <v-container v-resize="onResize" class="mt-1">
    <!-- Fake loading card -->
    <v-card loading="loading" :height="height + 300" v-if="loading" tile outlined>
      <v-overlay :value="loading" absolute opacity="0.5" color="#ffffff"></v-overlay>
    </v-card>

    <v-card outlined class="mt-8" v-if="!loading && !user">
      <v-card-title>No data</v-card-title>
      <v-card-text><p class="justify-start text--secondary" style="font-size:1.2em">There is no user with the given
        id</p></v-card-text>
    </v-card>

    <user-profile-info :user="user" v-if="user" :loading="loading"/>

    <programs :user="user" class="flex-column fill-height mt-2" v-if="user && isRoleUser"/>

    <v-card tile outlined class="mt-2" v-if="user && !isRoleUser">
      <v-tabs v-model="tab">
        <v-tab v-if="canManageOwnUsers || canManageUsers">Users</v-tab>
        <v-tab v-if="canUploadPrograms && canUserUploadPrograms">Programs</v-tab>
      </v-tabs>
      <v-tabs-items v-model="tab">
        <v-tab-item v-if="canManageOwnUsers || canManageUsers">
          <user-profile-table :filter="userFilter" :height="height"/>
        </v-tab-item>
        <v-tab-item v-if="canUploadPrograms && canUserUploadPrograms">
          <program-data-table :user="user" :height="height" v-if="canManagePrograms"/>
        </v-tab-item>
      </v-tabs-items>
    </v-card>
  </v-container>
</template>

<script lang="ts">

import {Component, Vue} from "vue-property-decorator";
import userService, {UserFilter} from "@/service/api/userService";
import UserModule, {Role} from "@/store/modules/user";
import {EventBus} from "@/utils/event-bus";
import Programs from "@/components/Programs.vue";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import UserProfileTable from "@/components/UserProfileTable.vue";
import ProgramDataTable from "@/components/ProgramDataTable.vue";
import ProgramHistoryDataTable from "@/components/ProgramHistoryDataTable.vue";
import BaseVueComponent from "@/components/BaseVueComponent";

@Component({
  components: {ProgramHistoryDataTable, UserProfileInfo, Programs, UserProfileTable, ProgramDataTable}
})
export default class UserView extends BaseVueComponent {
  private height: number | null = null;
  private tab = null;

  private userFilter: UserFilter | null = null;
  private loading = false;

  private get isRoleUser() {
    return this.user?.role == Role.User;
  }

  private get canUserUploadPrograms() {
    return this.user?.role == Role.Admin || this.user?.role == Role.SuperProfessional;
  }

  get user() {
    return this.userFilter?.owner;
  }

  mounted() {
    this.loading = true;
    userService.get(this.$route.params.id)
        .then((user) => {
          this.userFilter = {
            owner: user,
            roles: [Role.User]
          };
        })
        .catch((e) => {
          e && EventBus.$emit("error", e)
        })
        .finally(() => {
          this.loading = false;
        })

  }

  private onResize() {
    this.height = window.innerWidth < 600 ? null : window.innerHeight - 395;
  }
}

</script>

<style scoped>

</style>