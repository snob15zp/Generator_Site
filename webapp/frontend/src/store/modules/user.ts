import {Action, getModule, Module, Mutation, MutationAction, VuexModule} from "vuex-module-decorators";
import {User, UserCredentials} from "../models";
import authService from "../../service/api/authService";
import store from "@/store";

export class Privileges {
    private static UPLOAD_PROGRAMS = 'upload-programs';
    private static MANAGE_PROGRAMS = 'manage-programs';
    private static MANAGE_FIRMWARE = 'manage-firmware';
    private static MANAGE_USERS = 'manage-users';
    private static MANAGE_OWN_USERS = 'manage-own-users';
    private static VIEW_USERS = 'view-users';
    private static VIEW_PROFILE = 'view-profile';
    private static VIEW_PROGRAMS = 'view-programs';

    static canManageOwnUsers(user: User) {
        return user.privileges.indexOf(this.MANAGE_OWN_USERS) >= 0;
    }

    static canUploadPrograms(user: User) {
        return user.privileges.indexOf(this.UPLOAD_PROGRAMS) >= 0;
    }

    static canManagePrograms(user: User) {
        return user.privileges.indexOf(this.MANAGE_PROGRAMS) >= 0;
    }

    static canManageFirmware(user: User) {
        return user.privileges.indexOf(this.MANAGE_FIRMWARE) >= 0;
    }

    static canManageUsers(user: User) {
        return user.privileges.indexOf(this.MANAGE_USERS) >= 0;
    }

    static canViewUsers(user: User) {
        return user.privileges.indexOf(this.VIEW_USERS) >= 0;
    }

    static canViewProfile(user: User) {
        return user.privileges.indexOf(this.VIEW_PROFILE) >= 0;
    }

    static canViewPrograms(user: User) {
        return user.privileges.indexOf(this.VIEW_PROGRAMS) >= 0;
    }
}

export abstract class Role {
    private static ROLE_ADMIN = "ROLE_ADMIN";
    private static ROLE_PROFESSIONAL = "ROLE_PROFESSIONAL";
    private static ROLE_SUPER_PROFESSIONAL = "ROLE_SUPER_PROFESSIONAL";
    private static ROLE_USER = "ROLE_USER";
    private static ROLE_GUEST = "ROLE_GUEST";

    static readonly Admin = new class extends Role {
        constructor() {
            super(Role.ROLE_ADMIN);
        }
    }
    static readonly Professional = new class extends Role {
        constructor() {
            super(Role.ROLE_PROFESSIONAL);
        }
    };
    static readonly SuperProfessional = new class extends Role {
        constructor() {
            super(Role.ROLE_SUPER_PROFESSIONAL);
        }
    };
    static readonly User = new class extends Role {
        constructor() {
            super(Role.ROLE_USER);
        }
    };
    static readonly Guest = new class extends Role {
        constructor() {
            super(Role.ROLE_GUEST);
        }
    };

    readonly name: string;

    protected constructor(name: string) {
        this.name = name;
    }

    toString(): string {
        switch (this) {
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

    static parse(name: string): Role {
        switch (name) {
            case Role.ROLE_ADMIN:
                return Role.Admin;
            case Role.ROLE_PROFESSIONAL:
                return Role.Professional;
            case Role.ROLE_SUPER_PROFESSIONAL:
                return Role.SuperProfessional;
            case Role.ROLE_USER:
                return Role.User;
            case Role.ROLE_GUEST:
                return Role.Guest;
            default:
                return Role.Guest;
        }
    }
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
        return this.user ? Privileges.canManageUsers(this.user) : false;
    }


    get canManagePrograms() {
        return this.user ? Privileges.canManagePrograms(this.user) : false;
    }

    get canManageFirmware() {
        return this.user ? Privileges.canManageFirmware(this.user) : false;
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
