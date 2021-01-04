export class FirmwareUploadForm {
    uploadDialogShow = false;

    version: string | null = null;
    files: File[] | null = null;
    uploadInProgress = false;
    valid = false;
    errorMessage: string | null = null;
    progress = 0;

    fileRules = [
        (v: File[] | null) => !!v || "File is required",
        //(v: File[]) => v.length > 0 || "File is required",
    ];

    versionRules = [
        (v: string | null) => !!v || "Version is required",
        (v: string) => /^\d+\.\d+.\d+$/.test(v) || "Version is not valid"
    ];

    show() {
        this.uploadDialogShow = true;
    }

    hide() {
        this.uploadDialogShow = false;
    }

    reset() {
        this.errorMessage = null;
        this.uploadInProgress = false;
        this.progress = 0;
    }
}