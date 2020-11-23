import { VuexModule, Module, MutationAction, getModule, Mutation } from "vuex-module-decorators";
import { User, UserCredetials } from "../models";
import authService from "../../service/api/authService";
import store from "@/store";

@Module({
  namespaced: true,
  name: "users",
  store,
  dynamic: true,
  preserveState: Boolean(JSON.parse(localStorage["vuex"] || "{}")["users"])
})
class UserModule extends VuexModule {
  user: User | null = null;

  get isAuthorized() {
    return this.user != null;
  }

  get userName() {
    return this.user ? this.user.name : null;
  }

  @MutationAction
  async login(userCredentials: UserCredetials) {
    const user = await authService.login(userCredentials);
    return { user };
  }

  @Mutation
  logout() {
    this.user = null;
  }
}

export default getModule(UserModule);
