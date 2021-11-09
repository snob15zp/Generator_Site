import {Action, getModule, Module, Mutation, MutationAction, VuexModule} from "vuex-module-decorators";
import {User, UserCredentials} from "../models";
import authService from "../../service/api/authService";
import store from "@/store";

export const CREATE_USER = 'create-user';
export const MANAGE_PROFILES = 'manage-profiles';
export const MANAGE_PROGRAMS = 'manage-programs';
export const MANAGE_FIRMWARE = 'manage-firmware';
export const UPLOAD_PROGRAMS = 'upload-programs';
export const VIEW_PROFILE = 'view-profile';
export const VIEW_PROGRAMS = 'view-programs';
export const VIEW_USERS = 'view-users';


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

    get canManageFirmware() {
        return this.user ? this.user.privileges.indexOf(MANAGE_FIRMWARE) != -1 : false;
    }

    get userName() {
        console.log("Get User name: " + this.user);
        return this.user ? this.user.name : null;
    }

    get userRoleName() {
        switch (this.user?.role) {
            case "ROLE_ADMIN":
                return "Administrator";
            case "ROLE_USER":
                return "User";
            case "ROLE_GUEST":
                return "Guest";
            case "ROLE_PROFESSIONAL":
                return "Professional";
            case "ROLE_SUPER_PROFESSIONAL":
                return "S.Professional";
            default:
                return "Guest";
        }
    }

    get token() {
        return this.user ? this.user.token : null;
    }

    @MutationAction
    async login(userCredentials: UserCredentials) {
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
