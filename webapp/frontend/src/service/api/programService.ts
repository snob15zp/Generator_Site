import { Folder, FolderJson, Program, UserProfile } from "@/store/models";
import api from ".";
import transformers from "./transformers";

class ProgramService {
  async fetchFolders(userProfileId: string): Promise<Folder[]> {
    return new Promise<Folder[]>((resolve, reject) => {
      api.get(`profiles/${userProfileId}/folders`)
        .then((response) => resolve(response.data.map((json: FolderJson) => transformers.folderFromJson(json))))
        .catch((error) => reject(new Error(error)));
    });
  }

  async saveFolder(userProfile: UserProfile, folder: Folder): Promise<Folder> {
    return new Promise<Folder>((resolve, reject) => {
      api.post(`profiles/${userProfile.id}/folders`, transformers.folderToJson(folder))
        .then((response) => resolve(transformers.folderFromJson(response.data)))
        .catch((error) => reject(new Error(error)));
    });
  }
}

const programService = new ProgramService();
export default programService;
