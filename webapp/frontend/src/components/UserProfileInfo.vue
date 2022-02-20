<template>
  <v-card outlined tile>
    <v-progress-linear indeterminate v-if="loading"/>
    <v-card-title>
      <h1 class="text-h4">
        {{ userProfile.name }}&nbsp;{{ userProfile.surname }}
      </h1>
      <v-spacer/>
      <v-btn icon @click="showDetails=!showDetails">
        <v-icon>mdi-account-details</v-icon>
      </v-btn>
      <!--        <v-btn icon @click="onClickEditUser">-->
      <!--          <v-icon>mdi-account-edit</v-icon>-->
      <!--        </v-btn>-->
      <!--        <v-btn icon @click="onClickDeleteUser">-->
      <!--          <v-icon>mdi-account-minus</v-icon>-->
      <!--        </v-btn>-->
    </v-card-title>
    <v-card-subtitle class="font-weight-bold pb-0">{{ userRoleName }}</v-card-subtitle>
    <v-card-text>
      <v-expand-transition>
        <v-list dense v-show="showDetails">
          <v-list-item>
            <v-list-item-icon>
              <v-icon>mdi-email</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              {{ userProfile.email }}
            </v-list-item-content>
          </v-list-item>
          <v-list-item>
            <v-list-item-icon>
              <v-icon>mdi-calendar</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              {{ userProfile.dateOfBirth | formatDate }}
            </v-list-item-content>
          </v-list-item>
          <v-list-item>
            <v-list-item-icon>
              <v-icon>mdi-phone</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              {{ userProfile.phoneNumber }}
            </v-list-item-content>
          </v-list-item>
          <v-list-item>
            <v-list-item-icon>
              <v-icon>mdi-map-marker</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              {{ userProfile.address }}
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-expand-transition>
      <div class="text-body-1" v-if="software">
        <v-btn small color="primary" link :href="fileUrl">Download software
          <v-icon right dark small>mdi-download</v-icon>
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script lang="ts">
import {Component, Prop, Vue, Watch} from "vue-property-decorator";
import {Software, User, UserProfile} from "@/store/models";
import {fields} from "@/forms/UserProfileFormValidator";
import UserModule, {Role} from "@/store/modules/user";
import softwareService from "@/service/api/softwareService";
import {settings} from "@/settings";

@Component
export default class UserProfileInfo extends Vue {
  @Prop() private user!: User;
  @Prop() private loading!: boolean;

  private software: Software | null = null;
  private showDetails = false;

  get fields() {
    return fields;
  }

  get userProfile() {
    return this.user.profile;
  }

  get userRoleName() {
    return (this.user.role instanceof Role) ? this.user.role.toString() : Role.parse(this.user.role['name']).toString();
  }

  get fileUrl() {
    return settings.apiUrl + this.software?.fileUrl;
  }

  get canManageUsers() {
    return UserModule.canManageOwnUsers || UserModule.canManageUsers;
  }

  mounted() {
    if (!UserModule.canManageFirmware) {
      softwareService.getLatest()
          .then((software) => {
            this.software = software;
          })
    }
  }

  private onClickEditUser() {
    console.log("edit");
  }

  private onClickDeleteUser() {
    console.log("delete");
  }

}
</script>
