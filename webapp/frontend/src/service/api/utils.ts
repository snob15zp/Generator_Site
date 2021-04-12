import i18n from "@/i18n";
import {ErrorResponseJson} from "@/store/models";
import {AxiosError} from "axios";

export const API_ERROR_CODES = {
  USER_NOT_FOUND: 100,
  USER_PROFILE_NOT_FOUND: 200
};

export function apiErrorMapper(error: AxiosError<ErrorResponseJson>): string {
  return error.response?.data.errors.message || i18n.t("server-error.general_error") as string;

  // switch (error.code) {
  //   case API_ERROR_CODES.USER_NOT_FOUND: {
  //     return i18n.t("server-error.user_not_found") as string;
  //   }
  //   case API_ERROR_CODES.USER_PROFILE_NOT_FOUND: {
  //     return i18n.t("server-error.user_profile_not_found") as string;
  //   }
  // }
  // return i18n.t("server-error.general_error") as string;
}
