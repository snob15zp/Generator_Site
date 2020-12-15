import { Firmware, FirmwareJson } from '@/store/models';
import api from ".";
import transformers from "./transformers";

class FirmwareService {
    async getAll(): Promise<Firmware[]> {
        return new Promise<Firmware[]>((resolve, reject) => {
            api.get("/firmware")
                .then(response => resolve(response.data.map((json: FirmwareJson) => transformers.firmwareFromJson(json))))
                .catch(error => reject(new Error(error)));
        });
    }
}

const firmwareService = new FirmwareService();
export default firmwareService;