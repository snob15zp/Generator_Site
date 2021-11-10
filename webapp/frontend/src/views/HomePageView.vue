<template>
  <v-layout v-resize="onResize" class="pa-4">
    <v-row dense>
      <v-col cols="12" sm="6" xs="12" md="4">
        <v-card fluid>
          <v-card-title><h5 class="font-weight-light">PROFESSIONALS</h5></v-card-title>
          <v-data-table
              dense
              :height="tableHeight"
              :headers="professionalHeaders"
              :items="items">
          </v-data-table>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" xs="12" md="4">
        <v-card dense>
          <v-card-title><h5 class="font-weight-light">USERS</h5></v-card-title>
          <v-data-table
              dense
              :height="tableHeight"
              :headers="userHeaders"
              :items="users">
          </v-data-table>
        </v-card>
      </v-col>
      <v-col cols="12" sm="12" md="4">
        <v-card dense>
          <v-card-title><h5 class="font-weight-light">PROGRAMS</h5></v-card-title>
          <v-list dense :height="tableHeight + 37">
          </v-list>
        </v-card>
      </v-col>
    </v-row>
    <!--    <UserProfileTable v-if="canManageProfiles"/>-->
    <!--    <UserProfileDetails v-else :user-id="userId"/>-->
  </v-layout>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import UserProfileTable from "@/components/UserProfileTable.vue";
import UserModule from "@/store/modules/user"
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import UserProfileDetails from "@/components/UserProfileDetails.vue";

@Component({
  components: {UserProfileDetails, UserProfileInfo, UserProfileTable}
})
export default class HomePageView extends Vue {
  private tableHeight: number | null = null;
  private page = 0;

  private professionalHeaders = [
    {text: "Name", value: "name"},
    {text: "Role", value: "role"}
  ];

  private userHeaders = [
    {text: "Name", value: "name"}
  ];

  private items = [
    {name: "Alvis Yost", role: "Professional"},
    {name: "Angelita Pacocha", role: "Professional"},
    {name: "Caden Gulgowski", role: "S.Professional"},
    {name: "Cary Murazik", role: "Professional"},
    {name: "Dario Nienow", role: "S.Professional"},
    {name: "Demetrius Walsh", role: "S.Professional"},
    {name: "Elmore Cartwright", role: "Professional"},
    {name: "Emiliano Gaylord", role: "Professional"}
  ];

  private users = [
    {name: "Alvis Yost"},
    {name: "Angelita Pacocha"},
    {name: "Caden Gulgowski"},
    {name: "Cary Murazik"},
    {name: "Catharine Emmerich"},
    {name: "Clarabelle Cronin"},
    {name: "Dario Nienow"},
    {name: "Demetrius Walsh"},
    {name: "Elmore Cartwright"},
    {name: "Emiliano Gaylord"}
  ];

  get canManageProfiles() {
    return UserModule.canManageProfiles;
  }

  get userId() {
    console.log(UserModule.user?.id);
    return UserModule.user?.id || null;
  }

  private onResize() {
    this.tableHeight = window.innerWidth < 600 ? null : window.innerHeight - 210;
  }
}
</script>

<style lang="scss" scoped>

::v-deep {
  .v-data-footer__select {
    display: none !important;
  }
}

</style>