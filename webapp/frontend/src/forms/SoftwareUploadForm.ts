import { BaseUploadForm } from "./BaseUploadForm";

export class SoftwareUploadForm extends BaseUploadForm {
    version: string | null = null;
    file: File | null = null;

    fileRules = [
        (v: File | null) => !!v || "File is required"
    ];

    versionRules = [
        (v: string | null) => !!v || "Version is required",
        (v: string) => /^\d+\.\d+.\d+$/.test(v) || "Version is not valid"
    ];
}