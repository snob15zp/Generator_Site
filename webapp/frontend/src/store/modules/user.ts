import {Action, getModule, Module, Mutation, MutationAction, VuexModule} from "vuex-module-decorators";
import {User, UserCredentials} from "../models";
import authService from "../../service/api/authService";
import store from "@/store";

export enum Privileges {
    createUser = 'create-user',
    manageProfiles = 'manage-profiles',
    managePrograms = 'manage-programs',
    manageFirmware = 'manage-firmware',
    uploadPrograms = 'upload-programs',
    viewProfile = 'view-profile',
    viewPrograms = 'view-programs',
    viewUsers = 'view-users'
}

export enum Role {
    Admin = "ROLE_ADMIN",
    Professional = "ROLE_PROFESSIONAL",
    SuperProfessional = "ROLE_SUPER_PROFESSIONAL",
    User = "ROLE_USER",
    Guest = "ROLE_GUEST"
}

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
        return this.user ? this.user.privileges.indexOf(Privileges.manageProfiles) != -1 : false;
    }


    get canManagePrograms() {
        return this.user ? this.user.privileges.indexOf(Privileges.managePrograms) != -1 : false;
    }

    get canManageFirmware() {
        return this.user ? this.user.privileges.indexOf(Privileges.manageFirmware) != -1 : false;
    }

    get userName() {
        console.log("Get User name: " + this.user);
        return this.user ? this.user.name : null;
    }

    get userRoleName() {
        switch (this.user?.role) {
            case Role.Admin:
                return "Administrator";
            case Role.User:
                return "User";
            case Role.Guest:
                return "Guest";
            case Role.Professional:
                return "Professional";
            case Role.SuperProfessional:
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
