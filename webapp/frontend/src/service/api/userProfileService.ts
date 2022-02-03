import {PagingRequest, PagingResponse, PagingResponseJson, UserProfile, UserProfileJson} from "@/store/models";
import transformers from "./transformers";
import api from "."
import {apiErrorMapper} from "@/service/api/utils";
import builder from "@/service/api/builder";

class UserProfileService {
    async delete(userProfiles: Array<UserProfile>): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            const query = userProfiles.map((profile) => `ids[]=${profile.id}`).join('&');
            api.delete(`/profiles?${query}`)
                .then(() => resolve())
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async save(userProfile: UserProfile): Promise<UserProfile> {
        return new Promise<UserProfile>((resolve, reject) => {
            const data = transformers.userProfileToJson(userProfile);
            const request = (userProfile.id)
                ? api.put(`/profiles/${userProfile.id}`, data) : api.post('/profiles', data);

            request
                .then((response) => resolve(transformers.userProfileFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        })
    }

    async fetchAll(pagingRequest: PagingRequest): Promise<PagingResponse<UserProfile>> {
        const query = builder.queryFromPagingRequest(pagingRequest);

        return new Promise<PagingResponse<UserProfile>>((resolve, reject) => {
            api.get<PagingResponseJson<UserProfileJson>>("/profiles?" + query)
                .then((response) => {
                    const profiles = response.data;
                    resolve({
                        data: profiles.data.map(json => transformers.userProfileFromJson(json)),
                        total: profiles.meta.total
                    });
                }).catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async fetchById(id: string): Promise<UserProfile> {
        return new Promise<UserProfile>((resolve, reject) => {
            api.get(`/profiles/${id}`)
                .then((response) => resolve(transformers.userProfileFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async fetchByUserId(id: string): Promise<UserProfile> {
        return new Promise<UserProfile>((resolve, reject) => {
            api.get(`/users/${id}/profile`)
                .then((response) => resolve(transformers.userProfileFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }
}

const userProfileService = new UserProfileService();
export default userProfileService;
