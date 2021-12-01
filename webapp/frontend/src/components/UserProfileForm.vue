<template>
  <v-card>
    <v-form ref="form" @submit.prevent="submit">
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-row>
          <v-col>
            <v-select
                v-model="userProfile.role"
                :label="fields.role"
                :error-messages="validateField('role')"
                :items="roles"
                @input="$v.userProfile.role.$touch()"
                @blur="$v.userProfile.role.$touch()"
            >
              <template v-slot:item="{item}">
                {{ item.toString() }}
              </template>
            </v-select>
          </v-col>
        </v-row>

        <v-row>
          <v-col>
            <v-text-field
                v-model="userProfile.email"
                :label="fields.email"
                @input="$v.userProfile.email.$touch()"
                @blur="$v.userProfile.email.$touch()"
                :error-messages="validateField('email')"
            />
          </v-col>
        </v-row>
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
          >
            <v-text-field
                v-model="userProfile.surname"
                :label="fields.surname"
                @input="$v.userProfile.surname.$touch()"
                @blur="$v.userProfile.surname.$touch()"
                :error-messages="validateField('surname')"
                counter="40"
            />
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-text-field
                v-model="userProfile.password"
                @input="$v.userProfile.password.$touch()"
                @blur="$v.userProfile.password.$touch()"
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
        <v-row>
          <v-col
          >
            <v-text-field
                v-model="userProfile.address"
                :label="fields.address"
                @input="$v.userProfile.address.$touch()"
                @blur="$v.userProfile.address.$touch()"
                :error-messages="validateField('address')"
                counter="200"
            />
          </v-col>
        </v-row>
        <v-row>
          <v-col
          >
            <v-text-field
                v-model="userProfile.phoneNumber"
                :label="fields.phoneNumber"
                @input="$v.userProfile.phoneNumber.$touch()"
                @blur="$v.userProfile.phoneNumber.$touch()"
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
                    @input="$v.userProfile.dateOfBirth.$touch()"
                    @blur="$v.userProfile.dateOfBirth.$touch()"
                    :error-messages="validateField('dateOfBirth')"
                />
              </template>
              <v-date-picker
                  no-title
                  v-model="dateOfBirth"
                  :active-picker.sync="activePicker"
                  :max="new Date().toISOString().substr(0, 10)"
                  :locale="$i18n.locale"
                  min="1950-01-01"
                  @change="save"
              />
            </v-menu>
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue darken-1" text @click="onCancel">{{
            $t("form.cancel")
          }}
        </v-btn>
        <v-btn color="blue darken-1" text
               :disabled="$v.$invalid"
               @click="onSave">{{
            $t("form.save")
          }}
        </v-btn>
      </v-card-actions>
    </v-form>
  </v-card>
</template>

<script lang="ts">
import {Component, Emit, Mixins, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {UserProfile} from "@/store/models";
import UserProfileFormValidator from "../forms/UserProfileFormValidator";
import moment from "moment";
import {Role} from "@/store/modules/user";

@Component
export default class UserProfileForm extends Mixins(UserProfileFormValidator) {
  @Prop({required: true}) value!: UserProfile;
  @Prop({required: true}) title?: string;
  @Prop({default: () => [Role.User]}) roles?: Role[]

  @Ref() readonly pickerRef: (Vue & { activePicker: string }) | undefined;
  @Ref() readonly menuRef: (Vue & { save: (date: string) => void }) | undefined;
  @Ref() readonly form: (Vue & { reset: () => void }) | undefined;

  private menu = false
  private confirmPassword = "";
  private activePicker: string | null = null;

  get userProfile() {
    return this.value;
  }

  get dateOfBirth() {
    return this.value.dateOfBirth
        ? moment(this.value!.dateOfBirth).format("YYYY-MM-DD")
        : "";
  }

  set dateOfBirth(value: string) {
    this.value.dateOfBirth = moment(value!).toDate();
  }

  @Emit("save")
  onSave() {
    return this.userProfile;
  }

  @Emit("cancel")
  onCancel() {
    return null;
  }

  @Watch("menu")
  private onMenuChanged(val: Date) {
    val && setTimeout(() => (this.activePicker = 'YEAR'));
  }

  private save(date: string) {
    this.menuRef!.save(date);
  }
}
</script>
