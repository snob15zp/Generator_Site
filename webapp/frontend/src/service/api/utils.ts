import i18n from "@/i18n";
import {ErrorResponseJson, ProgramJson} from "@/store/models";
import {AxiosError} from "axios";

export const API_ERROR_CODES = {
    USER_NOT_FOUND: 100,
    UPLOAD_FILES_FAILED: 160,
    USER_PROFILE_NOT_FOUND: 200,
    UNAUTHORIZED: 401,
    UNPROCESSABLE_ENTITY: 422
};

export function apiErrorMapper(error: AxiosError<ErrorResponseJson>): string {
    const errors = error.response?.data?.errors;
    switch (errors?.code) {
        case API_ERROR_CODES.UPLOAD_FILES_FAILED: {
            const existsFiles = errors.data['exists_files'];
            const failedFiles = errors.data['failed_files'];
            let message = "";
            if (existsFiles && existsFiles.length > 0) {
                message += "Already exists: <ul>" +
                    existsFiles.map((p: ProgramJson) => `<li>${p.name}</li>`).join('') +
                    "</ul><br>";
            }
            if (failedFiles && failedFiles.length > 0) {
                message += "Unable to upload: <ul>" +
                    failedFiles.map((p: ProgramJson) => `<li>${p.name}</li>`).join('') +
                    "</ul>";
            }
            return message;
        }
        case API_ERROR_CODES.UNAUTHORIZED:
            return i18n.t("server-error.unauthorized") as string;

        case API_ERROR_CODES.USER_NOT_FOUND:
            return i18n.t("server-error.invalid_credentials") as string;
    }

    return error.response?.data.errors.message || i18n.t("server-error.general_error") as string;
}
