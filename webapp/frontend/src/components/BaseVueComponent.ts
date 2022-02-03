import {Vue} from "vue-property-decorator";
import UserModule, {Privileges} from "@/store/modules/user";


export default class BaseVueComponent extends Vue {
    protected get canManageUsers() {
        return UserModule.canManageUsers;
    }

    protected get canManageOwnUsers() {
        return UserModule.canManageOwnUsers;
    }

    protected get canUploadPrograms() {
        return UserModule.canUploadPrograms;
    }

    protected get canManagePrograms() {
        return UserModule.canManagePrograms;
    }

    protected get canViewUsers() {
        return UserModule.canViewUsers;
    }

    protected get canAttachPrograms() {
        return UserModule.canAttachPrograms;
    }

    protected get canDownloadPrograms() {
        return UserModule.canDownloadPrograms;
    }

    protected get canImportPrograms() {
        return UserModule.canImportPrograms;
    }

    protected get isUser() {
        return UserModule.isUser;
    }

    protected get isAdmin() {
        return UserModule.isAdmin;
    }

    protected get currentUser() {
        return UserModule.user;
    }
}