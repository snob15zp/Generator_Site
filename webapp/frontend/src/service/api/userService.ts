import {User, UserJson} from "@/store/models";
import api from "@/service/api/index";
import transformers from "@/service/api/transformers";
import {apiErrorMapper} from "@/service/api/utils";
import {Role} from "@/store/modules/user";

export interface UserFilter {
    owner?: User,
    roles?: Role[]
}

const PREFIX = "/users";

class UserService {
    async getUsers(userFilter: UserFilter): Promise<User[]> {
        const query = (userFilter.owner ? `owner_id=${userFilter.owner.id}&` : '')
            + userFilter.roles?.map(role => `roles[]=${role.name}`).join('&');

        return new Promise<User[]>((resolve, reject) => {
            api.get<UserJson[]>(`${PREFIX}?${query}`)
                .then(response => resolve(response.data.map(user => transformers.userFromJson(user))))
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async delete(userIds: string[]) {
        return new Promise<void>((resolve, reject) => {
            const query = userIds.map((id) => `ids[]=${id}`).join('&');
            api.delete(`${PREFIX}?${query}`)
                .then(() => resolve())
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }
}

const userService = new UserService();
export default userService;