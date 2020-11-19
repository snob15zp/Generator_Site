import i18n from "@/i18n";
import { ValidataionRules } from "./types";

type RuleError = { [key: string]: string };

export class FormValidationHelper {
  private errors: RuleError;

  constructor(errors: RuleError = {}) {
    this.errors = errors;
  }

  private validationErrors(...args: any[]): { [key: string]: string } {
    return {
      ...this.errors,
      required: i18n.t("form.error-field-required", args).toString(),
      minLength: i18n.t("form.error-field-too-short", args).toString(),
      maxLength: i18n.t("form.error-field-too-long", args).toString(),
      email: i18n.t("form.error-email-invalid", args).toString(),
      date: i18n.t("form.error-date-invalid", args).toString()
    };
  }

  validate(validator: ValidataionRules, label: string): string | string[] {
    if (!validator.$dirty) return [];

    const errors = Object.keys(validator)
      .filter((key) => !key.startsWith("$"))
      .map((rule) => {
        const params = Object.keys(validator.$params[rule] || {})
          .filter((key) => key != "type")
          .map((key) => validator.$params[rule][key]);
        return !validator[rule] ? this.validationErrors(...[label].concat(params))[rule] : "";
      })
      .filter((val) => val != "");

    return errors;
  }
}
