import moment from "moment";
import {
    Firmware,
    FirmwareJson,
    Folder,
    FolderJson,
    Program,
    ProgramJson,
    Software,
    SoftwareJson,
    User,
    UserJson,
    UserProfile,
    UserProfileJson
} from "@/store/models";
import {Role} from "@/store/modules/user";

function userProfileFromJson(json: UserProfileJson): UserProfile {
    return {
        id: json.id,
        name: json.name,
        surname: json.surname,
        address: json.address,
        email: json.email,
        dateOfBirth: json.date_of_birth ? moment(json.date_of_birth, "YYYY-MM-DD").toDate() : null,
        phoneNumber: json.phone_number,
        createdAt: moment(json.created_at).toDate(),
        updatedAt: json.updated_at ? moment(json.updated_at).toDate() : null
    } as UserProfile;
}

function userProfileToJson(userProfile: UserProfile): UserProfileJson {
    return {
        "id": userProfile.id,
        "name": userProfile.name,
        "surname": userProfile.surname,
        "address": userProfile.address,
        "email": userProfile.email,
        "date_of_birth": userProfile.dateOfBirth ? moment(userProfile.dateOfBirth).format("YYYY-MM-DD") : null,
        "phone_number": userProfile.phoneNumber,
    } as UserProfileJson;
}

function userFromJson(json: UserJson): User {
    return {
        id: json.id,
        profile: userProfileFromJson(json.profile),
        privileges: json.privileges,
        token: json.token,
        role: Role.parse(json.role)
    } as User;
}

function userToJson(user: User): UserJson {
    return {
        "profile": userProfileToJson(user.profile),
        "role": user.role.name,
        "password": user.password
    } as UserJson;
}

function programFromJson(json: ProgramJson): Program {
    return {
        id: json.id,
        name: json.name,
        owner: json.owner ? userFromJson(json.owner) : null,
        createdAt: moment(json.created_at).toDate()
    }
}

function folderFromJson(json: FolderJson): Folder {
    return {
        id: json.id,
        name: json.name,
        expiredAt: moment(json.created_at).add(json.expires_in, "s").toDate(),
        createdAt: moment(json.created_at).toDate(),
        isEncrypted: json.is_encrypted || false,
        active: json.active || true
    }
}

function folderToJson(folder: Folder): FolderJson {
    return {
        "id": folder.id,
        "name": folder.name,
        "expires_in": Math.ceil((folder.expiredAt.getTime() - (new Date()).getTime()) / 1000),
    }
}

function firmwareFromJson(json: FirmwareJson): Firmware {
    return {
        id: json.id,
        active: Boolean(json.active),
        version: json.version,
        createdAt: moment(json.createdAt).toDate(),
        files: json.files.map($fileJson => {
            return {
                fileName: $fileJson.name
            };
        })
    } as Firmware;
}

function softwareFromJson(json: SoftwareJson): Software {
    return {
        id: json.id,
        active: Boolean(json.active),
        version: json.version,
        createdAt: moment(json.createdAt).toDate(),
        file: json.file,
        fileUrl: json.fileUrl
    } as Software;
}

export default {
    userFromJson,
    userToJson,
    userProfileFromJson,
    userProfileToJson,
    folderFromJson,
    programFromJson,
    folderToJson,
    firmwareFromJson,
    softwareFromJson
}