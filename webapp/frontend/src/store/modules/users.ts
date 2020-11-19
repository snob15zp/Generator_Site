import { VuexModule, Module, MutationAction, getModule, Mutation } from "vuex-module-decorators";
import { User, UserCredetials } from "../models";
import { loginUser } from "../api";
import store from "@/store";

@Module({
  namespaced: true,
  name: "users",
  store,
  dynamic: true,
  preserveState: Boolean(JSON.parse(localStorage["vuex"] || "{}")["users"])
})
class UsersModule extends VuexModule {
  user: User | null = null;

  @MutationAction
  async login(userCredentials: UserCredetials) {
    const user = await loginUser(userCredentials);
    return { user };
  }

  @Mutation
  logout() {
    this.user = null;
  }
}

export default getModule(UsersModule);
