import {PagingRequest, PagingResponse, PagingResponseJson, UserProfile, UserProfileJson} from "@/store/models";
import transformers from "./transformers";
import api from "."

class UserProfileService {
    async delete(userProfiles: Array<UserProfile>) {
        console.log(userProfiles);
        return new Promise((resolve, reject) => {
            const query = userProfiles.map((profile) => `ids[]=${profile.id}`).join('&');
            api.delete(`/profiles?${query}`)
                .then(() => resolve())
                .catch((error) => reject(new Error(error)));
        });
    }

    async save(userProfile: UserProfile): Promise<UserProfile> {
        return new Promise<UserProfile>((resolve, reject) => {
            const data = transformers.userProfileToJson(userProfile);
            const request = (userProfile.id)
                ? api.put(`/profiles/${userProfile.id}`, data) : api.post('/profiles', data);

            request
                .then((response) => resolve(transformers.userProfileFromJson(response.data)))
                .catch((error) => reject(new Error(error)))
        })
    }

    async fetchAll(pagingRequest: PagingRequest): Promise<PagingResponse<UserProfile>> {
        const query = `perPage=${pagingRequest.itemsPerPage}&page=${pagingRequest.page}&query=${pagingRequest.query}&`
            + pagingRequest.sortBy.map((item, index) =>
                `sortBy[]=${item}&sortDir[]=${pagingRequest.sortDesc[index] ? 'asc' : 'desc'}`
            ).join('&');

        return new Promise<PagingResponse<UserProfile>>((resolve, reject) => {
            api.get<PagingResponseJson<UserProfileJson>>("/profiles?" + query)
                .then((response) => {
                    resolve({
                        data: response.data.data.map(json => transformers.userProfileFromJson(json)),
                        total: response.data.meta.total
                    });
                }).catch((error) => reject(new Error(error)));
        });
    }

    async fetchById(id: string): Promise<UserProfile> {
        return new Promise<UserProfile>((resolve, reject) => {
            api.get(`/profiles/${id}`)
                .then((response) => resolve(transformers.userProfileFromJson(response.data)))
                .catch((error) => reject(new Error(error)))
        });
    }
}

const userProfileService = new UserProfileService();
export default userProfileService;
