<template>
  <v-layout fill-height column class="user-profile col-md-12">
    <UserProfileTable v-if="canManageProfiles"/>
    <UserProfileDetails v-else :user-id="userId"/>
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
export default class Home extends Vue {

  get canManageProfiles() {
    return UserModule.canManageProfiles;
  }

  get userId() {
    console.log(UserModule.user?.id);
    return UserModule.user?.id || null;
  }
}
</script>
