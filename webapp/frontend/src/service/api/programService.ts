import {
    DownloadFileRequest,
    Folder,
    FolderJson,
    Program,
    ProgramJson,
    UploadFileRequest,
    UserProfile
} from "@/store/models";
import api from ".";
import transformers from "./transformers";

class ProgramService {
    async fetchFolders(userProfileId: string): Promise<Folder[]> {
        return new Promise<Folder[]>((resolve, reject) => {
            api.get(`/profiles/${userProfileId}/folders`)
                .then((response) => resolve(response.data.map((json: FolderJson) => transformers.folderFromJson(json))))
                .catch((error) => reject(new Error(error)));
        });
    }

    async fetchPrograms(folder: Folder): Promise<Program[]> {
        return new Promise<Program[]>((resolve, reject) => {
            api.get(`/folders/${folder.id}/programs`)
                .then(response => resolve(response.data.map((json: ProgramJson) => transformers.programFromJson(json))))
                .catch(error => reject(new Error(error)))
        });
    }

    async saveFolder(userProfile: UserProfile, folder: Folder): Promise<Folder> {
        return new Promise<Folder>((resolve, reject) => {
            api.post(`/profiles/${userProfile.id}/folders`, transformers.folderToJson(folder))
                .then((response) => resolve(transformers.folderFromJson(response.data)))
                .catch((error) => reject(new Error(error)));
        });
    }

    async uploadFile(fileRequest: UploadFileRequest): Promise<Program> {
        return new Promise((resolve, reject) => {
            const formaData = new FormData();
            formaData.append("program", fileRequest.file);
            api.post(`/folders/${fileRequest.folder.id}/programs`, formaData, {
                onUploadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    fileRequest.onProgressCallback(percentCompleted);
                }
            })
                .then((response) => resolve(transformers.programFromJson(response.data)))
                .catch((error) => reject(new Error(error)));
        });
    }

    async downloadFile(request: DownloadFileRequest): Promise<Blob> {
        return new Promise<Blob>((resolve, reject) => {
            api.get(`/programs/${request.program.id}/download`, {
                onDownloadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    request.onProgressCallback(percentCompleted);
                }
            })
                .then(response => resolve(new Blob([response.data], {type: 'application/octet-stream'})))
                .catch(error => reject(new Error(error)))
        })
    }
}

const programService = new ProgramService();
export default programService;
