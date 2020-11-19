import { VuexModule, Module, MutationAction, getModule, Action } from "vuex-module-decorators";
import { UserProfile, PagingRequest, PagingResponse } from "../models";
import { fetchUserProfiles, deleteUserProfiles, saveUserProfile, fetchUserProfilByHash } from "../api";
import store from "@/store";

@Module({
  namespaced: true,
  name: "user_profiles",
  store,
  dynamic: true,
  preserveState: false
})
class UsersProfileModule extends VuexModule {
  profiles: PagingResponse<UserProfile> | null = null;

  @MutationAction
  async load(pagingRequest: PagingRequest) {
    const profiles = await fetchUserProfiles(pagingRequest);
    return { profiles };
  }

  @Action
  async save(userProfile: UserProfile) {
    await saveUserProfile(userProfile);
  }

  @Action
  async deleteProfiles(profiles: Array<UserProfile>) {
    await deleteUserProfiles(profiles);
  }

  @Action
  async findByHash(hash: string) {
    const profile = await fetchUserProfilByHash(hash);
    return { profile };
  }
}

export default getModule(UsersProfileModule);
