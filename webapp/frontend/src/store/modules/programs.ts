import { VuexModule, Module, MutationAction, getModule, Action } from "vuex-module-decorators";
import { UserProfile, Program, Folder } from "../models";
import programService from "../../service/api/programService";
import store from "@/store";

@Module({
  namespaced: true,
  name: "programs",
  store,
  dynamic: true,
  preserveState: false
})
class ProgramsModule extends VuexModule {
  files: Array<Program> | null = null;
  folders: Array<Folder> | null = null;

  @MutationAction
  async loadFoldersByUserProfile(userProfile: UserProfile) {
    const folders = await programService.fetchFolders(userProfile.id!);
    return { folders };
  }

  @MutationAction
  async loadFilesByFolder(folder: Folder) {
    const files = await programService.fetchPrograms(folder.hash!);
    return { files };
  }

  @Action
  async saveFolder(folder: Folder) {
    await programService.saveFolder(folder);
  }
}

export default getModule(ProgramsModule);
