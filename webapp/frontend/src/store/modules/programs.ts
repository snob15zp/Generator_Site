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
    const files = folder.programs;
    return { files };
  }

  @Action
  async saveFolder(args: Array<any>) {
    await programService.saveFolder(args[0], args[1]);
  }
}

export default getModule(ProgramsModule);
