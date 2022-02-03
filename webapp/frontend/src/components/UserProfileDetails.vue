<template>
  <v-layout column align-top justify-center>
    <user-profile-info :user="user" class="mt-4"/>
    <programs :user="user" class="flex-column fill-height mt-2"/>
  </v-layout>
</template>

<script lang="ts">
import {Component, Prop, Vue} from "vue-property-decorator";
import {User} from "@/store/models";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import Programs from "@/components/Programs.vue";

@Component({
  components: {UserProfileInfo, Programs}
})
export default class UserProfileDetails extends Vue {
  @Prop({default: null}) readonly user!: User;
}
</script>