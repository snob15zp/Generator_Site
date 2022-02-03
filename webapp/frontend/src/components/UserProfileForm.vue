<template>
  <v-card>
    <v-form ref="form" @submit.prevent="submit">
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-row dense>
          <v-col>
            <v-select v-if="roles.length > 1"
                      v-model="userProfileFormData.role"
                      :label="fields.role"
                      :error-messages="validateField('role')"
                      :items="roles"
                      @input="$v.userProfileFormData.role.$touch()"
                      @blur="$v.userProfileFormData.role.$touch()">
              <template v-slot:item="{item}">
                {{ item.toString() }}
              </template>
            </v-select>
            <!--            <v-combobox-->
            <!--                v-if="isUserRoleSelected"-->
            <!--                v-model="userProfileFormData.owner"-->
            <!--                label="Professional owner"-->
            <!--                :loading="professionalsLoading"-->
            <!--                :items="professionals">-->
            <!--              <template v-slot:item="{item}">-->
            <!--                {{ item.profile.name }} {{ item.profile.surname }}-->
            <!--              </template>-->
            <!--              <template v-slot:selection="{ item }">-->
            <!--                {{ item.profile.name }} {{ item.profile.surname }}-->
            <!--              </template>-->
            <!--            </v-combobox>-->
          </v-col>
        </v-row>

        <v-row dense>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.email"
                :label="fields.email"
                @input="onEmailTouch"
                @blur="onEmailTouch"
                :error-messages="emailFieldError"
            />
          </v-col>
        </v-row>
        <v-row dense>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.name"
                :label="fields.name"
                @input="$v.userProfileFormData.name.$touch()"
                @blur="$v.userProfileFormData.name.$touch()"
                :error-messages="validateField('name')"
                counter="40"
            />
          </v-col>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.surname"
                :label="fields.surname"
                @input="$v.userProfileFormData.surname.$touch()"
                @blur="$v.userProfileFormData.surname.$touch()"
                :error-messages="validateField('surname')"
                counter="40"
            />
          </v-col>
        </v-row>
        <v-row dense>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.password"
                @input="$v.userProfileFormData.password.$touch()"
                @blur="$v.userProfileFormData.password.$touch()"
                type="password"
                :error-messages="validateField('password')"
                :label="fields.password"
            />
          </v-col>
          <v-col>
            <v-text-field
                v-model="confirmPassword"
                type="password"
                @input="$v.confirmPassword.$touch()"
                @blur="$v.confirmPassword.$touch()"
                :error-messages="validateConfirmPassword()"
                :label="fields.confirmPassword"
            />
          </v-col>
        </v-row>
        <v-row dense>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.address"
                :label="fields.address"
                @input="$v.userProfileFormData.address.$touch()"
                @blur="$v.userProfileFormData.address.$touch()"
                :error-messages="validateField('address')"
                counter="200"
            />
          </v-col>
        </v-row>
        <v-row dense>
          <v-col>
            <v-text-field
                v-model="userProfileFormData.phoneNumber"
                :label="fields.phoneNumber"
                @input="$v.userProfileFormData.phoneNumber.$touch()"
                @blur="$v.userProfileFormData.phoneNumber.$touch()"
                :error-messages="validateField('phoneNumber')"
            />
          </v-col>
          <v-col>
            <v-menu
                ref="menuRef"
                v-model="menu"
                :close-on-content-click="false"
                transition="scale-transition"
                offset-y
                min-width="auto"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                    v-model="dateOfBirth"
                    :label="fields.dateOfBirth"
                    v-bind="attrs"
                    v-on="on"
                    readonly
                    @input="$v.userProfileFormData.dateOfBirth.$touch()"
                    @blur="$v.userProfileFormData.dateOfBirth.$touch()"
                    :error-messages="validateField('dateOfBirth')"
                />
              </template>
              <v-date-picker
                  no-title
                  v-model="dateOfBirth"
                  :active-picker.sync="activePicker"
                  :max="new Date().toISOString().substr(0, 10)"
                  :locale="$i18n.locale"
                  :min="new Date(Date.now() - 100*365*24*3600*1000).toISOString().substr(0, 10)"
                  @change="save"
              />
            </v-menu>
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="onCancel">
          {{ $t("form.cancel") }}
        </v-btn>
        <v-btn color="blue darken-1"
               text
               :loading="emailVerificationInProgress"
               :disabled="$v.$invalid || professionalsLoading"
               @click="onSaveClicked">
          {{ $t("form.save") }}
        </v-btn>
      </v-card-actions>
    </v-form>
  </v-card>
</template>

<script lang="ts">
import {Component, Emit, Mixins, Prop, PropSync, Ref, Vue, Watch} from "vue-property-decorator";
import UserProfileFormValidator from "../forms/UserProfileFormValidator";
import moment from "moment";
import {Role} from "@/store/modules/user";
import {User, UserProfile} from "@/store/models";
import {email} from "vuelidate/lib/validators";
import userService, {UserFilter} from "@/service/api/userService";
import {EventBus} from "@/utils/event-bus";

export interface UserProfileFormData {
  name?: string;
  surname?: string;
  phoneNumber?: string;
  address?: string;
  dateOfBirth?: Date | null;
  email?: string;
  password?: string;
  role?: Role | null;
  owner?: User
}

@Component
export default class UserProfileForm extends Mixins(UserProfileFormValidator) {
  @PropSync('value', {required: true}) readonly valueSync?: User;
  @Prop({required: true}) readonly title?: string;
  @Prop({default: () => [Role.User]}) readonly roles!: Role[]

  @Ref() readonly pickerRef: (Vue & { activePicker: string }) | undefined;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;
  @Ref() readonly form: (Vue & { reset: () => void }) | undefined;

  private menu = false
  private confirmPassword = "";
  private activePicker: string | null = null;
  private userProfileFormSync: UserProfileFormData | null = null;

  private emailVerificationInProgress = false;
  private emailVerificationStatus: boolean | null = null;

  private professionals: User[] = [];
  private professionalsLoading = false;

  get userProfileFormData() {
    const isEmpty = !this.userProfileFormSync || Object.values(this.userProfileFormSync).every(x => (x === null || x === ''));
    const user = this.valueSync;
    if (isEmpty && user) {
      this.userProfileFormSync = {
        email: user.profile.email,
        dateOfBirth: user.profile.dateOfBirth,
        address: user.profile.address,
        surname: user.profile.surname,
        name: user.profile.name,
        role: user.role || this.roles[0],
        phoneNumber: user.profile.phoneNumber
      }
    }
    return this.userProfileFormSync;
  }

  get isUserRoleSelected() {
    return this.userProfileFormData?.role == Role.User;
  }

  get emailFieldError() {
    if (this.emailVerificationStatus === false) {
      return this.$i18n.t("form.error-email-is-used");
    } else {
      return this.validateField('email');
    }
  }

  get dateOfBirth() {
    return this.userProfileFormData?.dateOfBirth
        ? moment(this.userProfileFormData.dateOfBirth).format("YYYY-MM-DD")
        : "";
  }

  set dateOfBirth(value: string) {
    this.userProfileFormData!.dateOfBirth = moment(value!).toDate();
  }

  // private fetchProfessionals() {
  //   this.professionalsLoading = true;
  //   userService.fetchAll({roles: [Role.Professional, Role.SuperProfessional]} as UserFilter)
  //       .then(users => this.professionals = users.data)
  //       .catch((e) => EventBus.$emit("error", e))
  //       .finally(() => {
  //         this.professionalsLoading = false;
  //       });
  // }

  private onEmailTouch() {
    this.emailVerificationStatus = null;
    this.$v.userProfileFormData.email?.$touch();
  }

  private onSaveClicked() {
    this.emailVerificationInProgress = true;
    userService.checkEmail(this.userProfileFormData!.email!, this.valueSync?.id)
        .then(() => {
          this.emailVerificationStatus = true;
          this.onSave()
        })
        .catch((error) => this.emailVerificationStatus = false)
        .finally(() => {
          this.emailVerificationInProgress = false;
        });
  }

  @Emit("save")
  onSave() {
    const data = this.userProfileFormData!;
    const user = {
      id: this.valueSync?.id,
      owner: data.owner,
      role: data.role,
      password: data.password,
      profile: {
        name: data.name,
        surname: data.surname,
        email: data.email,
        address: data.address,
        phoneNumber: data.phoneNumber,
        dateOfBirth: data.dateOfBirth
      } as UserProfile
    } as User;
    this.$v.$reset();
    this.resetFormData();
    return user;
  }

  @Emit("cancel")
  onCancel() {
    this.$v.$reset();
    this.resetFormData();
    return null;
  }

  @Watch("menu")
  private onMenuChanged(val: Date) {
    val && setTimeout(() => (this.activePicker = 'YEAR'));
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }

  private resetFormData() {
    this.emailVerificationStatus = null;
    this.userProfileFormSync = {};
  }
}
</script>
