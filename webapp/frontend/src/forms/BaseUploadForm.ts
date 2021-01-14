export class BaseUploadForm {
    uploadDialogShow = false;

    uploadInProgress = false;
    valid = false;
    errorMessage: string | null = null;
    progress = 0;

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