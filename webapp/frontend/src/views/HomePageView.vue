<template>
  <v-container v-resize="onResize" class="mt-4">
    <v-row>
      <v-col md="4" cols="12">
        <user-data-list
            :height="height"
            :filter="adminFilter"
            :sort-by="['name', 'role', 'date']"
            title="Professionals">
        </user-data-list>
      </v-col>
      <v-col md="4" cols="12">
        <user-data-list
            :height="height"
            :filter="userFilter"
            title="Users">
        </user-data-list>
      </v-col>
      <v-col md="4" cols="12">
        <program-data-list
            :height="height"
            title="Programs">
        </program-data-list>
      </v-col>
    </v-row>
    <!--        <UserProfileTable v-if="canManageProfiles"/>-->
    <!--        <UserProfileDetails v-else :user-id="userId"/>-->
  </v-container>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import UserProfileTable from "@/components/UserProfileTable.vue";
import UserModule, {Privileges} from "@/store/modules/user"
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import UserProfileDetails from "@/components/UserProfileDetails.vue";
import DataList, {DataListHeader} from "@/components/DataList.vue";
import UserDataList from "@/components/UserDataList.vue";
import {Role} from '@/store/modules/user';
import ProgramDataList from "@/components/ProgramDataList.vue";
import {User} from "@/store/models";
import {UserFilter} from "@/service/api/userService";

@Component({
  components: {ProgramDataList, UserDataList, DataList, UserProfileDetails, UserProfileInfo, UserProfileTable}
})
export default class HomePageView extends Vue {
  private height: number | null = null;

  private adminFilter: UserFilter = {
    roles: [Role.Professional, Role.SuperProfessional]
  };
  private userFilter: UserFilter = {
    roles: [Role.User]
  };

  private onResize() {
    this.height = window.innerWidth < 600 ? null : window.innerHeight - 260;
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