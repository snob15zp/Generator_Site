import Vue from "vue/types/umd";
import { Validation } from "vuelidate";
import i18n from "../i18n";

declare type ValidataionRules = Validation & { [key: string]: boolean };

declare interface FormValidator<T> {
  readonly fields: { [key in keyof T]: string };
  validateField(field: keyof T): string | string[];
}
