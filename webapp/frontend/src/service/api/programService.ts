import { Folder, Program } from "@/store/models";
import { fakeFolders } from "./data";

const rawFolders = fakeFolders;

class ProgramService {
  async fetchFolders(userProfileHash: string): Promise<Folder[]> {
    return new Promise<Folder[]>((resolve, reject) => {
      setTimeout(() => {
        resolve(rawFolders.sort((a, b) => a.expiredAt.getTime() - b.expiredAt.getTime()));
      }, 100);
    });
  }

  async saveFolder(folder: Folder): Promise<Folder> {
    return new Promise<Folder>((resolve, reject) => {
      setTimeout(() => {
        const index = rawFolders.findIndex((f) => f.hash == folder.hash);
        if (index >= 0) {
          rawFolders[index] = folder;
        } else {
          folder.hash = Math.random()
            .toString(36)
            .substr(2, 5);
          rawFolders.push(folder);
        }
        resolve(folder);
      }, 100);
    });
  }

  async fetchPrograms(folderHash: string): Promise<Program[]> {
    return new Promise<Program[]>((resolve, reject) => {
      setTimeout(() => {
        resolve([
          { hash: "213123123", path: "/data/", name: "program1.txt" },
          { hash: "213123124", path: "/data/", name: "program2.txt" },
          { hash: "213123125", path: "/data/", name: "program3.txt" },
          { hash: "213123126", path: "/data/", name: "program4.txt" }
        ]);
      }, 100);
    });
  }
}

const programService = new ProgramService();
export default programService;
