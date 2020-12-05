import { getModule, Module, Mutation, MutationAction, Action, VuexModule } from "vuex-module-decorators";
import { Vue } from "vue-property-decorator";
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

    get token() {
        return this.user ? this.user.token : null;
    }

    @MutationAction
    async login(userCredentials: UserCredetials) {
        const user = await authService.login(userCredentials);
        return { user };
    }

    @Mutation
    setToken(token: string) {
        if (this.user != null) {
            this.user.token = token;
        }
    }

    @MutationAction
    async logout() {
        await authService.logout();
        return { user: null as any }
    }
}

export default getModule(UserModule);
