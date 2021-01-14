import { BaseUploadForm } from "./BaseUploadForm";

export class FirmwareUploadForm extends BaseUploadForm {
    version: string | null = null;
    files: File[] | null = null;
 
    fileRules = [
        (v: File[] | null) => !!v || "File is required",
        //(v: File[]) => v.length > 0 || "File is required",
    ];

    versionRules = [
        (v: string | null) => !!v || "Version is required",
        (v: string) => /^\d+\.\d+.\d+$/.test(v) || "Version is not valid"
    ];
}