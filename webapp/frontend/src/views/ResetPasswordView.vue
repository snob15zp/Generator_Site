<template>
  <v-layout justify-center align-center>
    <v-card elevation="4" width="25rem" class="mx-auto my-12" :loading="inProgress">
      <v-card-title>
        <v-row justify="center">Reset Password</v-row>
      </v-card-title>
      <v-form v-model="valid" @submit.prevent="submit" :disabled="inProgress">
        <v-card-text>
          <div v-if="errorMessage" class="error--text mb-4">
            {{ errorMessage }}
          </div>
          <v-text-field
              v-model="password"
              :rules="passwordRules"
              label="New password"
              :type="showPassword ? 'text' : 'password'"
              :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
              @click:append="showPassword = !showPassword"
          />
          <v-text-field
              v-model="passwordConfirm"
              :rules="passwordConfirmRules"
              label="Repeat password"
              :append-icon="showPasswordConfirm ? 'mdi-eye' : 'mdi-eye-off'"
              :type="showPasswordConfirm ? 'text' : 'password'"
              @click:append="showPasswordConfirm = !showPasswordConfirm"
          />
        </v-card-text>
        <v-card-actions>
          <v-container>
            <div>
              <v-btn
                  width="100%"
                  color="primary"
                  type="submit"
                  :dark="valid && !inProgress"
                  :disabled="!valid || inProgress">Reset
              </v-btn>
            </div>
            <div class="d-flex justify-center mt-3">
              <a class="ml-3 text-decoration-none text-sm caption" href="/login">Back to login</a>
            </div>
          </v-container>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-layout>
</template>

<script lang="ts">
import {Component, Vue} from "vue-property-decorator";
import authService from "@/service/api/authService";

@Component
export default class ResetPasswordView extends Vue {
  private password = "";
  private passwordConfirm = "";
  private inProgress = false;

  private valid = false;
  private showPassword = false;
  private showPasswordConfirm = false;

  private errorMessage: any | null = null;

  private passwordRules = [
    (v: string | null) => !!v || this.$t("login-form.password_required"),
    (v: string) => (v && v.length > 6) || "Password must be greater than 6 characters",
  ];

  private get passwordConfirmRules() {
    return [
      (v: string | null) => !!v || this.$t("login-form.password_required"),
      (v: string | null) => {
        return (this.password === this.passwordConfirm) || "Do not match with password"
      }
    ];
  }

  private submit() {
    this.inProgress = true;
    this.errorMessage = null;
    authService.resetPassword(this.$route.params.hash, this.password)
        .then(() => this.$router.push("/"))
        .catch((err) => {
          this.errorMessage = err.message;
        })
        .finally(() => (this.inProgress = false));
  }
}
</script>