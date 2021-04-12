import moment from "moment";
import {
    UserJson,
    User,
    UserProfileJson,
    UserProfile,
    FolderJson,
    Folder,
    Program,
    ProgramJson,
    FirmwareJson,
    Firmware,
    Software,
    SoftwareJson
} from "@/store/models";

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
        "password": userProfile.password
    } as UserProfileJson;
}

function userFromJson(json: UserJson): User {
    return {
        id: json.id,
        name: json.profile.name,
        profile: userProfileFromJson(json.profile),
        privileges: json.privileges,
        token: json.token,
        role: json.role
    } as User;
}

function programFromJson(json: ProgramJson): Program {
    return {
        id: json.id,
        name: json.name
    }
}

function folderFromJson(json: FolderJson): Folder {
    return {
        id: json.id,
        name: json.name,
        expiredAt: moment(json.created_at).add(json.expires_in, "s").toDate(),
        createdAt: moment(json.created_at).toDate()
    }
}

function folderToJson(folder: Folder): FolderJson {
    return {
        "id": folder.id,
        "name": folder.name,
        "expires_in": Math.ceil((folder.expiredAt.getTime() - (new Date()).getTime()) / 1000)
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
    userProfileFromJson,
    userProfileToJson,
    folderFromJson,
    programFromJson,
    folderToJson,
    firmwareFromJson,
    softwareFromJson
}