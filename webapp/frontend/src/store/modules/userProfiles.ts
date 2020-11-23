import { VuexModule, Module, MutationAction, getModule, Action } from "vuex-module-decorators";
import { UserProfile, PagingRequest, PagingResponse } from "../models";
import userProfileService from "../../service/api/userProfileService";
import store from "@/store";

@Module({
  namespaced: true,
  name: "user-profiles",
  store,
  dynamic: true,
  preserveState: false
})
class UserProfilesModule extends VuexModule {
  profiles: PagingResponse<UserProfile> | null = null;

  @MutationAction
  async load(pagingRequest: PagingRequest) {
    const profiles = await userProfileService.fetchAll(pagingRequest);
    return { profiles };
  }

  @Action
  async save(userProfile: UserProfile) {
    await userProfileService.save(userProfile);
  }

  @Action
  async remove(profiles: Array<UserProfile>) {
    await userProfileService.delete(profiles);
  }

  @Action
  async findByHash(hash: string) {
    const profile = await userProfileService.fetchByHash(hash);
    return { profile };
  }
}

export default getModule(UserProfilesModule);
