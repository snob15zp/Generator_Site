<template>
  <v-form v-model="valid" ref="form">
    <v-row>
      <v-col>
        <v-text-field
          v-model="userProfile.name"
          :label="fields.name"
          @input="$v.userProfile.name.$touch()"
          @blur="$v.userProfile.name.$touch()"
          :error-messages="validateField('name')"
          counter="40"
        />
      </v-col>
      <v-col
        ><v-text-field
          v-model="userProfile.surname"
          :label="fields.surname"
          @input="$v.userProfile.surname.$touch()"
          @blur="$v.userProfile.surname.$touch()"
          :error-messages="validateField('surname')"
          counter="40"
      /></v-col>
    </v-row>
    <v-row>
      <v-col
        ><v-text-field
          v-model="userProfile.email"
          :label="fields.email"
          @input="$v.userProfile.email.$touch()"
          @blur="$v.userProfile.email.$touch()"
          :error-messages="validateField('email')"
      /></v-col>
    </v-row>
    <v-row>
      <v-col
        ><v-text-field
          v-model="userProfile.address"
          :label="fields.address"
          @input="$v.userProfile.address.$touch()"
          @blur="$v.userProfile.address.$touch()"
          :error-messages="validateField('address')"
          counter="200"
      /></v-col>
    </v-row>
    <v-row>
      <v-col
        ><v-text-field
          v-model="userProfile.phoneNumber"
          :label="fields.phoneNumber"
          @input="$v.userProfile.phoneNumber.$touch()"
          @blur="$v.userProfile.phoneNumber.$touch()"
          :error-messages="validateField('phoneNumber')"
      /></v-col>
      <v-col>
        <v-menu
          ref="menuRef"
          v-model="menu"
          :close-on-content-click="false"
          transition="scale-transition"
          offset-y
          min-width="290px"
        >
          <template v-slot:activator="{ on, attrs }">
            <v-text-field
              v-model="dateOfBirth"
              :label="fields.dateOfBirth"
              v-bind="attrs"
              v-on="on"
              readonly
              @input="$v.userProfile.dateOfBirth.$touch()"
              @blur="$v.userProfile.dateOfBirth.$touch()"
              :error-messages="validateField('dateOfBirth')"
            />
          </template>
          <v-date-picker
            ref="pickerRef"
            no-title
            v-model="dateOfBirth"
            :max="new Date().toISOString().substr(0, 10)"
            :locale="$i18n.locale"
            min="1950-01-01"
            @change="save"
          />
        </v-menu>
      </v-col>
    </v-row>
  </v-form>
</template>

<script lang="ts">
import { Component, Prop, Ref, Vue, Watch, Mixins } from "vue-property-decorator";
import { UserProfile } from "../store/models";
import UserProfileFormValidator from "../forms/UserProfileFormValidator";
import moment from "moment";

@Component
export default class UserProfileForm extends Mixins(UserProfileFormValidator) {
  @Prop({ required: true }) value?: UserProfile;

  @Ref() readonly pickerRef: (Vue & { activePicker: string }) | undefined;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;
  @Ref() readonly form: (Vue & { reset: () => void }) | undefined;

  private menu = false;
  private valid = false;

  get userProfile() {
    return this.value;
  }

  get dateOfBirth() {
    return this.value!.dateOfBirth ? moment(this.value!.dateOfBirth).format("YYYY-MM-DD") : "";
  }
  set dateOfBirth(value: string) {
    this.value!.dateOfBirth = moment(value!).toDate();
  }

  @Watch("menu")
  private onMenuChanged(val: Date) {
    val && setTimeout(() => (this.pickerRef!.activePicker = "YEAR"));
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }
}
</script>
