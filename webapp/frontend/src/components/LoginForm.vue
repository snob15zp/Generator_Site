<template>
  <div class="login-form justify-center">
    <v-card elevation="4" width="25rem" class="mx-auto my-12" :loading="inProgress">
      <v-row justify="center"><img src="@/assets/logo.png"/></v-row>
      <v-card-title>
        <v-row justify="center">{{ $t("login-form.title") }}<span class="title">InHealion</span></v-row>
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
          <v-text-field
            v-model="password"
            :rules="passwordRules"
            v-bind:label="$t('login-form.password')"
            :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
            :type="showPassword ? 'text' : 'password'"
            @click:append="showPassword = !showPassword"
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
                :disabled="!valid || inProgress"
                >{{ $t("login-form.login-action") }}</v-btn
              >
            </div>
            <div class="d-flex justify-center mt-3">
              <a class="ml-3 text-decoration-none text-sm caption" href="/forget-password">{{ $t("login-form.forget-password") }}</a>
            </div>
          </v-container>
        </v-card-actions>
      </v-form>
    </v-card>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import UserModule from "../store/modules/user";

@Component
export default class LoginForm extends Vue {
  private login = "";
  private password = "";
  private inProgress = false;
  private valid = false;
  private showPassword = false;

  private errorMessage: any | null = null;

  private emailRules = [(v: string | null) => !!v || this.$t("login-form.email_required")];

  private passwordRules = [(v: string | null) => !!v || this.$t("login-form.password_required")];

  private submit() {
    this.inProgress = true;
    this.errorMessage = null;
    UserModule.login({ login: this.login, password: this.password })
      .then(() => this.$router.push("/"))
      .catch((err) => {
        this.errorMessage = err.message;
      })
      .finally(() => (this.inProgress = false));
  }
}
</script>

<style lang="scss">
  .login-form {
    .v-card__title {
      color: #115293;
    }
    .title {
      margin-left: 4px;
      font-family: termina, sans-serif;
      font-weight: 700;
      font-style: normal;
    }
  }
</style>
