import i18n from "@/i18n";
import {ErrorResponseJson} from "@/store/models";
import {AxiosError} from "axios";

export const API_ERROR_CODES = {
  USER_NOT_FOUND: 100,
  USER_PROFILE_NOT_FOUND: 200,
  DUPLICATE_PROGRAMS: 800,
  UNAUTHORIZED: 401
};

export function apiErrorMapper(error: AxiosError<ErrorResponseJson>): string {
  switch (error.response?.data?.errors.status) {
    case API_ERROR_CODES.DUPLICATE_PROGRAMS:
      return i18n.t("server-error.duplicate_program") as string;

    case API_ERROR_CODES.UNAUTHORIZED:
      return i18n.t("server-error.unauthorized") as string;
  }

  return error.response?.data.errors.message || i18n.t("server-error.general_error") as string;
}
