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
                .catch(error => reject(new Error(apiErrorMapper(error))))
        })
    }

    async upload(version: string, cpuFile: File, fpgaFile: File, onProgressCallback: (_: number) => void): Promise<void> {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("version", version);
            formData.append("cpu", cpuFile);
            formData.append("fpga", fpgaFile);
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