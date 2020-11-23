<template>
  <v-card outlined class="mt-8">
    <v-progress-linear indeterminate v-if="loading" />
    <v-layout column pa-4>
      <v-row>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.name }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.name }}</span>
        </v-col>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.address }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.address }}</span>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.surname }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.surname }}</span>
        </v-col>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.dateOfBirth }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.dateOfBirth | formatDate }}</span>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.phoneNumber }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.phoneNumber }}</span>
        </v-col>
        <v-col cols="6" md="3" class="font-weight-bold">{{ fields.email }}:</v-col>
        <v-col cols="6" md="3">
          <span v-if="userProfile">{{ userProfile.email }}</span>
        </v-col>
      </v-row>
    </v-layout>
  </v-card>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { UserProfile } from "../store/models";
import { fields } from "../forms/UserProfileFormValidator";
import { formatDate } from "../utils/dateUtils";

@Component
export default class UserProfileInfo extends Vue {
  @Prop() private userProfile!: UserProfile;
  @Prop() private loading!: boolean;

  get fields() {
    return fields;
  }
}
</script>
