import moment from "moment";
import { UserJson, User, UserProfileJson, UserProfile } from "@/store/models";

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
        "date_of_birth": userProfile.dateOfBirth ? moment(userProfile.dateOfBirth).format("YYYY-MM-DD"): null,
        "phone_number": userProfile.phoneNumber,
    } as UserProfileJson;
}

function userFromJson(json: UserJson): User {
    return {
        id: json.id,
        name: json.profile.name,
        profile: userProfileFromJson(json.profile),
        privileges: json.privileges,
        token: json.token
    } as User;
}

export default {
    userFromJson,
    userProfileFromJson,
    userProfileToJson
}