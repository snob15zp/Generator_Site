<template>
  <v-layout justify-center align-center>
    <v-card elevation="4" width="25rem" class="mx-auto my-12" :loading="inProgress">
      <v-card-title>
        <v-row justify="center">Forget Password</v-row>
      </v-card-title>
      <v-form v-model="valid" @submit.prevent="submit" :disabled="inProgress">
        <v-card-text>
          <div v-if="errorMessage" class="error--text mb-4">
            {{ errorMessage }}
          </div>
          <v-text-field
              v-model="login"
              :rules="emailRules"
              v-bind:label="$t('login-form.email')"
              append-icon="mdi-email"
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
                  :disabled="!valid || inProgress">Send
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
export default class ForgetPasswordView extends Vue {
  private login = "";
  private inProgress = false;
  private valid = false;
  private errorMessage: any | null = null;

  private emailRules = [(v: string | null) => !!v || this.$t("login-form.email_required")];

  private submit() {
    this.inProgress = true;
    this.errorMessage = null;
    authService.forgetPassword(this.login)
        .then(() => this.$router.push("/login"))
        .catch((err) => {
          this.errorMessage = err.message;
        })
        .finally(() => (this.inProgress = false));
  }
}
</script>