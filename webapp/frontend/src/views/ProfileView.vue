<template>
  <v-layout fill-height column class="user-profile col-md-12 col-lg-9">
    <UserProfileDetails :user-profile-id="$route.params.id"/>
  </v-layout>
</template>
<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import UserProfileDetails from "@/components/UserProfileDetails.vue";

@Component({
  components: {UserProfileDetails}
})
export default class ProfileView extends Vue {
}
</script>