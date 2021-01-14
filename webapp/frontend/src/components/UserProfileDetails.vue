<template>
  <v-layout column align-top justify-center>
    <user-profile-info :user-profile="userProfile" :loading="profileLoading"/>
    <programs :user-profile-id="userProfile.id" class="flex-column fill-height mt-8 mb-8" v-if="userProfile"/>
  </v-layout>
</template>

<script lang="ts">
import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, Program, SaveFolderRequest, UploadFileRequest, UserProfile} from "@/store/models";
import {fields} from "@/forms/UserProfileFormValidator";
import UserProfileInfo from "@/components/UserProfileInfo.vue";
import Programs from "@/components/Programs.vue";
import moment from "moment";
import programService from "@/service/api/programService";
import userProfileService from "@/service/api/userProfileService";
import saveDownloadFile from "../utils/download-file";
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

  mounted(){
    if(this.userId) {
      this.fetchUserProfile(userProfileService.fetchByUserId(this.userId));
    } else if(this.userProfileId){
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