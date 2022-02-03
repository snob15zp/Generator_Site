import {PagingRequest, PagingResponse, PagingResponseJson, User, UserJson} from "@/store/models";
import api from "@/service/api/index";
import transformers from "@/service/api/transformers";
import {apiErrorMapper} from "@/service/api/utils";
import {Role} from "@/store/modules/user";
import builder from "@/service/api/builder";

export interface UserFilter {
    owner?: User | null,
    roles?: Role[],
    unlinked?: boolean
}

const PREFIX = "/users";

class UserService {
    async fetchAll(pagingRequest: PagingRequest): Promise<PagingResponse<User>> {
        const query = builder.queryFromPagingRequest(pagingRequest);
        return new Promise<PagingResponse<User>>((resolve, reject) => {
            api.get<PagingResponseJson<UserJson>>(`${PREFIX}?${query}`)
                .then(response => {
                    const users = response.data;
                    resolve({
                        data: users.data.map(json => transformers.userFromJson(json)),
                        total: users.meta.total
                    });
                })
                .catch(error => reject(new Error(apiErrorMapper(error))));
        });
    }

    async save(user: User): Promise<User> {
        return new Promise<User>((resolve, reject) => {
            const data = transformers.userToJson(user);
            const ownerId = user.owner?.id;
            const url = ownerId ? `${PREFIX}/owners/${ownerId}` : PREFIX;
            console.log(user);
            const request = (user.id) ? api.put(`${url}/${user.id}`, data) : api.post(url, data);
            request
                .then((response) => resolve(transformers.userFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
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

    async checkEmail(email: string, userId?: string): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const path = userId ? `/${userId}` : '';
            api.get(`${PREFIX}${path}/check?email=${email}`)
                .then(() => resolve())
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async get(id: string): Promise<User> {
        return new Promise<User>((resolve, reject) => {
            api.get(`${PREFIX}/${id}`)
                .then((response) => resolve(transformers.userFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async add(owner: User, users: User[]): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const query = users.map((user) => `ids[]=${user.id}`).join('&');
            api.post(`${PREFIX}/${owner.id}/owner?${query}`)
                .then(() => resolve())
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }
}

const userService = new UserService();
export default userService;