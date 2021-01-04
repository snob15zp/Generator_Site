import {ErrorResponseJson, Firmware, FirmwareJson} from '@/store/models';
import api from ".";
import transformers from "./transformers";
import {AxiosError} from "axios";
import {apiErrorMapper} from "@/service/api/utils";

class FirmwareService {
    async getAll(): Promise<Firmware[]> {
        return new Promise<Firmware[]>((resolve, reject) => {
            api.get("/firmware")
                .then(response => resolve(response.data.map((json: FirmwareJson) => transformers.firmwareFromJson(json))))
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async delete(version: string): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            api.delete(`/firmware/${version}`)
                .then(() => resolve())
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async downloadFile(version: string, onProgressCallback: ((_: number) => void) | null = null): Promise<Blob> {
        return new Promise<Blob>((resolve, reject) => {
            api.get(`/firmware/${version}/download`, {
                responseType: "arraybuffer",
                onDownloadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    console.log(percentCompleted);
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
        })
    }

    async updateStatus(firmwareId: string, active: boolean): Promise<Firmware> {
        return new Promise<Firmware>((resolve, reject) => {
            api.put(`/firmware/${firmwareId}`, {active: active})
                .then((response) => resolve(transformers.firmwareFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async upload(version: string, files: File[], onProgressCallback: (_: number) => void): Promise<void> {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("version", version);
            files.forEach((file, idx) => formData.append(`files[]`, file))
            api.post(`/firmware`, formData, {
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

const firmwareService = new FirmwareService();
export default firmwareService;