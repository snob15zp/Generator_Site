import {
    DownloadFileRequest,
    Folder,
    FolderJson,
    PagingRequest,
    PagingResponse,
    PagingResponseJson,
    Program,
    ProgramJson,
    UploadFileRequest,
    User
} from "@/store/models";
import api from ".";
import transformers from "./transformers";
import builder from "./builder";
import {apiErrorMapper} from "@/service/api/utils";
import axios from "axios";

class ProgramService {
    async fetchFolders(user: User): Promise<Folder[]> {
        return new Promise<Folder[]>((resolve, reject) => {
            api.get(`/users/${user.id}/folders`)
                .then((response) => resolve(response.data.map((json: FolderJson) => transformers.folderFromJson(json))))
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async unlinkPrograms(folder: Folder, programIds: string[]): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const query = programIds.map(id => `ids[]=${id}`).join('&');
            api.delete(`/folders/${folder.id}/programs?${query}`)
                .then(() => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async fetchPrograms(folder: Folder): Promise<Program[]> {
        return new Promise<Program[]>((resolve, reject) => {
            api.get(`/folders/${folder.id}/programs`)
                .then(response => resolve(response.data.map((json: ProgramJson) => transformers.programFromJson(json))))
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async addProgramsToFolder(folder: Folder, programs: Program[]): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            api.post(`/folders/${folder.id}/attach`, {programs: programs.map(p => p.id)})
                .then(response => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async addProgramsForUser(user: User, programs: Program[]): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            api.post(`/users/${user.id}/attach`, {programs: programs.map(p => p.id)})
                .then(response => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async deleteProgramsForUser(user: User, programIds: Array<string>): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const query = programIds.map(id => `ids[]=${id}`).join('&');
            api.delete(`/users/${user.id}/programs?${query}`)
                .then(() => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }


    async saveFolder(user: User, folder: Folder): Promise<Folder> {
        return new Promise<Folder>((resolve, reject) => {
            api.post(`/users/${user.id}/folders`, transformers.folderToJson(folder))
                .then((response) => resolve(transformers.folderFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async copyFolder(user: User, copyTo: Folder, copyFrom: Folder): Promise<Folder> {
        return new Promise<Folder>((resolve, reject) => {
            api.post(`/users/${user.id}/folders/${copyFrom.id}/renew`, transformers.folderToJson(copyTo))
                .then((response) => resolve(transformers.folderFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async uploadFile(fileRequest: UploadFileRequest): Promise<void> {
        const length = fileRequest.files.length;
        const promises: Array<Promise<void>> = [];
        const url = fileRequest.folder ? `/folders/${fileRequest.folder.id}/programs` : `/users/${fileRequest.owner.id}/programs`;
        for (let i = 0; i < length; i += 20) {
            const formData = new FormData();
            fileRequest.files.slice(i, Math.min(i + 20, length))
                .forEach((file, idx) => formData.append(`programs[]`, file));

            promises.push(new Promise<void>((resolve, reject) => api.post(url, formData, {
                    cancelToken: fileRequest.cancelSource?.token,
                    onUploadProgress: function (progressEvent) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        fileRequest.onProgressCallback(percentCompleted);
                    },
                    headers: {
                        "Content-Type": "multipart/form-data"
                    },
                })
                    .then(() => resolve())
                    .catch((error) => {
                        console.log(error);
                        // TODO axios.isCancel does not recognize a cancel properly.
                        if (axios.isCancel(error)) {
                            resolve();
                        } else {
                            reject(new Error(apiErrorMapper(error)))
                        }
                    })
            ));
        }
        await Promise.all(promises);
    }

    async downloadFolder(folderId: string, onProgressCallback: (_: number) => void): Promise<Blob> {
        return new Promise<Blob>((resolve, reject) => {
            api.get(`/folders/${folderId}/download`, {
                responseType: "arraybuffer",
                onDownloadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    onProgressCallback?.call(this, percentCompleted);
                }
            })
                .then(response => resolve(new Blob([response.data], {type: 'application/zip'})))
                .catch(error => {
                    let json = "";
                    (new Uint8Array(error.response.data)).forEach(function (byte: number) {
                        json += String.fromCharCode(byte);
                    });
                    error.response.data = JSON.parse(json);
                    reject(new Error(apiErrorMapper(error)));
                })
        });
    }

    async importPrograms(folderId: string): Promise<string> {
        return new Promise<string>(((resolve, reject) => {
            api.get(`/folders/${folderId}/download-link`)
                .then(response => resolve(response.data))
                .catch(error => reject(new Error(apiErrorMapper(error))));
        }));
    }

    async deleteFolder(folderId: string): Promise<void> {
        return new Promise<void>(((resolve, reject) => {
            api.delete(`/folders/${folderId}`)
                .then(() => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))));
        }));
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
                .catch(error => reject(new Error(apiErrorMapper(error))))
        })
    }

    async getAll(pagingRequest: PagingRequest): Promise<PagingResponse<Program>> {
        const query = builder.queryFromPagingRequest(pagingRequest);
        return new Promise<PagingResponse<Program>>((resolve, reject) => {
            api.get<PagingResponseJson<ProgramJson>>(`/programs?${query}`)
                .then(response => {
                    const programs = response.data;
                    resolve({
                        data: programs.data.map(json => transformers.programFromJson(json)),
                        total: programs.meta.total
                    });
                })
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async getAllForUser(user: User): Promise<Program[]> {
        return new Promise<Program[]>((resolve, reject) => {
            api.get<ProgramJson[]>(`/users/${user.id}/programs`)
                .then(response => resolve(response.data.map(json => transformers.programFromJson(json))))
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }

    async fetchHistory(user: User) : Promise<Program[]> {
        return new Promise<Program[]>((resolve, reject) => {
            api.get<ProgramJson[]>(`/users/${user.id}/history`)
                .then(response => resolve(response.data.map(json => transformers.programFromJson(json))))
                .catch(error => reject(new Error(apiErrorMapper(error))))
        });
    }
}

const programService = new ProgramService();
export default programService;
