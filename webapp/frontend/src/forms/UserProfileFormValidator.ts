import i18n from "@/i18n";
import {UserProfile} from "@/store/models";
import Component from "vue-class-component";
import {Vue} from "vue-property-decorator";
import {maxLength, minLength, required, email} from "vuelidate/lib/validators";
import {FormValidationHelper} from "./FormValidationHelper";
import {FormValidator} from "./types";

const validations = {
    userProfile: {
        name: {required, minLength: minLength(4), maxLength: maxLength(40)},
        password: {minLength: minLength(8), maxLength: maxLength(128)},
        surname: {required, minLength: minLength(4), maxLength: maxLength(40)},
        email: {required, email},
        address: {required, minLength: minLength(4), maxLength: maxLength(200)},
        phoneNumber: {required},
        dateOfBirth: {
            required,
            date(dateOfBirthday: string | Date): boolean {
                if (dateOfBirthday instanceof Date) {
                    return true;
                } else {
                    return /\d\d\d\d-\d\d-\d\d/.test(dateOfBirthday);
                }
            }
        },
        role: {required}
    },
    confirmPassword: {
        sameAsPassword(repeatPassword: string, v: any): boolean {
            if (!v.userProfile.password) {
                return true;
            } else {
                return repeatPassword == v.userProfile.password;
            }
        }
    }
};

export const fields = {
    name: i18n.t("user-profile.field-name").toString(),
    surname: i18n.t("user-profile.field-surname").toString(),
    email: i18n.t("user-profile.field-email").toString(),
    dateOfBirth: i18n.t("user-profile.field-date-of-birthday").toString(),
    address: i18n.t("user-profile.field-address").toString(),
    phoneNumber: i18n.t("user-profile.field-phone-number").toString(),
    password: i18n.t("user-profile.field-password").toString(),
    confirmPassword: i18n.t("user-profile.field-password-confirm").toString(),
    role: i18n.t("user-profile.field-role").toString(),
};

@Component({
    validations: validations
})
export default class UserProfileFormValidator extends Vue implements FormValidator<UserProfile> {
    private validationHelper = new FormValidationHelper();

    get fields(): { [key in keyof UserProfile]: string } {
        return fields;
    }

    validateField(field: keyof UserProfile): string | string[] {
        return this.validationHelper.validate(this.$v.userProfile[field]!, this.fields[field]!);
    }

    validateConfirmPassword(): string | string[] {
        const validator = this.$v.confirmPassword;
        if (!validator.$dirty) return [];
        return !validator.sameAsPassword ? this.validationHelper.validationErrors().sameAs : "";
    }
}
