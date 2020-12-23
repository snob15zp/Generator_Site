export class FirmwareUploadForm {
    uploadDialogShow = false;

    version: string | null = null;
    cpuFileInput: any | null = null;
    fpgaFileInput: any | null = null;
    uploadInProgress = false;
    valid = false;
    errorMessage: string | null = null;
    progress = 0;
    fileRules = [(v: string | null) => !!v || "File is required"];

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