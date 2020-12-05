import {Action, getModule, Module, MutationAction, VuexModule} from "vuex-module-decorators";
import {Folder, Program, SaveFolderRequest, UploadFileRequest, User, UserProfile} from "../models";
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
        return {folders};
    }

    @MutationAction
    async loadFilesByFolder(folder: Folder) {
        const files = await programService.fetchPrograms(folder);
        return {files};
    }

    @Action({commit: "programs/fetchFolders"})
    async saveFolder(request: SaveFolderRequest) {
        await programService.saveFolder(request.userProfile, request.folder);
        return this.loadFoldersByUserProfile(request.userProfile);
    }

    @Action
    async uploadFile(request: UploadFileRequest) {
        await programService.uploadFile(request);
        return await this.loadFilesByFolder(request.folder);
    }
}

export default getModule(ProgramsModule);
