import i18n from "@/i18n";
import { ErrorReponse } from "@/store/models";

export const API_ERROR_CODES = {
  USER_NOT_FOUND: 100,
  USER_PROFILE_NOT_FOUND: 200
};

export function apiErrorMapper(error: ErrorReponse): string {
  switch (error.code) {
    case API_ERROR_CODES.USER_NOT_FOUND: {
      return i18n.t("server-error.user_not_found") as string;
    }
    case API_ERROR_CODES.USER_PROFILE_NOT_FOUND: {
      return i18n.t("server-error.user_profile_not_found") as string;
    }
  }
  return i18n.t("server-error.general_error") as string;
}
