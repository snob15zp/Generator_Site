import {Action, getModule, Module, Mutation, MutationAction, VuexModule} from "vuex-module-decorators";
import {User, UserCredetials} from "../models";
import authService from "../../service/api/authService";
import store from "@/store";

export const MANAGE_PROFILES = 'manage-profiles';
export const MANAGE_PROGRAMS = 'manage-programs';
const CREATE_USER = 'create-user';
const VIEW_PROFILE = 'view-profile';
const VIEW_PROGRAMS = 'view-programs';

@Module({
    namespaced: true,
    name: "users",
    store,
    dynamic: true,
    preserveState: Boolean(JSON.parse(localStorage["vuex"] || "{}")["users"])
})
class UserModule extends VuexModule {
    user: User | null = null;
    error: Error | null = null;

    get isAuthorized() {
        return this.user != null;
    }

    get canManageProfiles() {
        return this.user ? this.user.privileges.indexOf(MANAGE_PROFILES) != -1 : false;
    }

    get canManagePrograms() {
        return this.user ? this.user.privileges.indexOf(MANAGE_PROGRAMS) != -1 : false;
    }

    get userName() {
        console.log("Get User name: " + this.user);
        return this.user ? this.user.name : null;
    }

    get userRole() {
        switch(this.user?.role) {
            case "ROLE_ADMIN": return "Administrator";
            case "ROLE_USER": return "User";
            case "ROLE_GUEST": return "Guest";
            default: return "Guest";
        }
    }

    get token() {
        return this.user ? this.user.token : null;
    }

    @MutationAction
    async login(userCredentials: UserCredetials) {
        const user = await authService.login(userCredentials);
        return {user};
    }

    @Mutation
    setToken(token: string) {
        if (this.user) {
            Object.assign(this.user.token, token);
        }
    }

    @Action({commit: "reset"})
    async logout() {
        await authService.logout();
    }

    @Mutation
    reset() {
        console.log("Reset user");
        this.user = null;
    }
}

export default getModule(UserModule);
