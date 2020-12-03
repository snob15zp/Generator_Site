import moment from "moment";
import { UserJson, User, UserProfileJson, UserProfile } from "@/store/models";

function userProfileFromJson(json: UserProfileJson): UserProfile {
    return {
        id: json.id,
        name: json.name,
        surname: json.surname,
        email: json.email,
        userId: json.user_id,
        dateOfBirth: json.date_of_birth ? moment(json.date_of_birth, "YYYY-MM-DD").toDate() : null,
        phoneNumber: json.phone_number,
        createdAt: moment(json.created_at).toDate(),
        updatedAt: json.updated_at ? moment(json.updated_at).toDate() : null
    } as UserProfile;

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

export default { userFromJson, userProfileFromJson }