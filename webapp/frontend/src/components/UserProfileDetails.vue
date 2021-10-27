<template>
  <v-layout column align-top justify-center>
    <user-profile-info :user-profile="userProfile" :loading="profileLoading" v-if="userProfile" class="mt-4"/>
    <programs :user-profile-id="userProfile.id" class="flex-column fill-height mt-8 mb-8" v-if="userProfile"/>
    <v-card outlined class="mt-8" v-else-if="!profileLoading">
      <v-card-title>No data</v-card-title>
      <v-card-text><p class="justify-start text--secondary" style="font-size:1.2em">There is no user with the given
        id</p></v-card-text>
    </v-card>
  </v-layout>
</template>

<script lang="ts">
import {Component, Prop, Vue, Watch} from "vue-property-decorator";
import {UserProfile} from "@/store/models";
import {fields} from "@/forms/UserProfileFormValidator";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import Programs from "@/components/Programs.vue";
import userProfileService from "@/service/api/userProfileService";
import {EventBus} from "@/utils/event-bus";

@Component({
  components: {UserProfileInfo, Programs}
})
export default class UserProfileDetails extends Vue {
  @Prop({default: null}) readonly userProfileId?: string;
  @Prop({default: null}) readonly userId?: string;

  private userProfile: UserProfile | null = null;

  private profileLoading = false;

  get fields() {
    return fields;
  }

  @Watch("userId")
  onUserIdChanged() {
    console.log(this.userId)
    if (this.userId) {
      const promise = userProfileService.fetchByUserId(this.userId)
      this.fetchUserProfile(promise);
    }
  }

  @Watch("userProfileId")
  onUserProfileIdChanged() {
    if (this.userProfileId) {
      const promise = userProfileService.fetchById(this.userProfileId!);
      this.fetchUserProfile(promise);
    }
  }

  mounted() {
    if (this.userId) {
      this.fetchUserProfile(userProfileService.fetchByUserId(this.userId));
    } else if (this.userProfileId) {
      this.fetchUserProfile(userProfileService.fetchById(this.userProfileId));
    }
  }

  private fetchUserProfile(promise: Promise<UserProfile>) {
    this.profileLoading = true;
    promise
        .then((profile) => {
          this.userProfile = profile;
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.profileLoading = false;
        });
  }
}
</script>