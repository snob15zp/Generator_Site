import { VuexModule, Module, MutationAction, getModule, Action } from "vuex-module-decorators";
import { UserProfile, Program, Folder } from "../models";
import { deleteUserProfiles, saveUserProfile, fetchFolders, fetchPrograms, saveFolder } from "../api";
import store from "@/store";
import moment from "moment";

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
    const folders = await fetchFolders(userProfile.hash!);
    return { folders };
  }

  @MutationAction
  async loadFilesByFolder(folder: Folder) {
    const files = await fetchPrograms(folder.hash!);
    return { files };
  }

  @Action
  async saveFolder(folder: Folder) {
    await saveFolder(folder);
  }

  @Action
  async deleteFile(profiles: Array<UserProfile>) {
    await deleteUserProfiles(profiles);
  }
}

export default getModule(ProgramsModule);
