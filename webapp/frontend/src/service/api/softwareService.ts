import {ErrorResponseJson, Software, SoftwareJson} from '@/store/models';
import api from ".";
import transformers from "./transformers";
import {AxiosError} from "axios";
import {apiErrorMapper} from "@/service/api/utils";

class SoftwareService {
    async getAll(): Promise<Software[]> {
        return new Promise<Software[]>((resolve, reject) => {
            api.get("/software")
                .then(response => resolve(response.data.map((json: SoftwareJson) => transformers.softwareFromJson(json))))
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async getLatest(): Promise<Software> {
        return new Promise<Software>((resolve, reject) => {
            api.get("/software/latest")
                .then(response => resolve(transformers.softwareFromJson(response.data)))
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async delete(version: string): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            api.delete(`/software/${version}`)
                .then(() => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async updateStatus(firmwareId: string, active: boolean): Promise<Software> {
        return new Promise<Software>((resolve, reject) => {
            api.put(`/software/${firmwareId}`, {active: active})
                .then((response) => resolve(transformers.softwareFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async upload(version: string, file: File, onProgressCallback: (_: number) => void): Promise<void> {
        const formData = new FormData();
        formData.append("version", version);
        formData.append("file", file);

        return new Promise<void>((resolve, reject) => {
            api.post(`/software`, formData, {
                onUploadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    onProgressCallback(percentCompleted);
                }
            })
                .then(() => resolve())
                .catch((error: AxiosError<ErrorResponseJson>) => {
                    reject(new Error(apiErrorMapper(error)));
                });
        });
    }
}

const softwareService = new SoftwareService();
export default softwareService;