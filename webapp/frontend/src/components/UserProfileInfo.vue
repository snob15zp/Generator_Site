<template>
  <v-card outlined>
    <v-progress-linear indeterminate v-if="loading"/>
    <v-card-title>
      <div class="text-h4">
        {{ userProfile.name }}&nbsp;{{ userProfile.surname }}
      </div>
      <v-spacer/>
      <div class="text-body-1" v-if="software">
        <v-btn small color="primary" link :href="fileUrl">Download software<v-icon right dark small>mdi-download</v-icon></v-btn>
      </div>
    </v-card-title>
    <v-card-text>
      <v-list dense>
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
    </v-card-text>
  </v-card>
</template>

<script lang="ts">
import {Component, Prop, Vue} from "vue-property-decorator";
import {Software, UserProfile} from "@/store/models";
import {fields} from "@/forms/UserProfileFormValidator";
import UserModule from "@/store/modules/user";
import softwareService from "@/service/api/softwareService";
import {settings} from "@/settings";

@Component
export default class UserProfileInfo extends Vue {
  @Prop() private userProfile!: UserProfile;
  @Prop() private loading!: boolean;

  private software: Software | null = null;

  get fields() {
    return fields;
  }

  get fileUrl() {
    return settings.apiUrl + this.software?.fileUrl;
  }

  mounted() {
    if (!UserModule.canManageFirmware) {
      softwareService.getLatest()
          .then((software) => {
            this.software = software;
          })
    }
  }

}
</script>
