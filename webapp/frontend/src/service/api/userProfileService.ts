import { PagingRequest, PagingResponse, UserProfile } from "@/store/models";
import { fakeUserProfiles } from "./data";
import { apiErrorMapper } from "./utils";

let rawProfiles = fakeUserProfiles;

class UserProfileService {
  async delete(userProfiles: Array<UserProfile>) {
    rawProfiles = rawProfiles.filter((profile) => !userProfiles.some((p) => p.id == profile.id));
    return Promise.resolve(userProfiles.length);
  }

  async save(userProfile: UserProfile) {
    const index = rawProfiles.findIndex((p) => p.id == userProfile.id);
    userProfile.updatedAt = new Date();

    if (index >= 0) {
      rawProfiles[index] = userProfile;
    } else {
      userProfile.id = Math.random()
        .toString(36)
        .substr(2, 5);
      rawProfiles.push(userProfile);
    }

    return Promise.resolve();
  }

  async fetchAll(pagingRequest: PagingRequest): Promise<PagingResponse<UserProfile>> {
    return new Promise<PagingResponse<UserProfile>>((resolve) => {
      setTimeout(() => {
        const start = (pagingRequest.page - 1) * pagingRequest.itemsPerPage;
        const end = start + pagingRequest.itemsPerPage;
        const items =
          pagingRequest.query && pagingRequest.query.length > 0
            ? rawProfiles.filter((a) => {
                return (
                  (a.name + " " + a.surname).toLocaleLowerCase().indexOf(pagingRequest.query.toLocaleLowerCase()) >= 0
                );
              })
            : rawProfiles;

        pagingRequest.sortBy.forEach((col, index) => {
          switch (col) {
            case "user":
              items.sort((a, b) => (a.name + " " + a.surname).localeCompare(b.name + " " + b.surname));
              break;
            case "createdAt":
              items.sort((a, b) => a.createdAt!.getTime() - b.createdAt!.getTime());
              break;
            case "updatedAt":
              items.sort((a, b) => a.updatedAt!.getTime() - b.updatedAt!.getTime());
              break;
          }
          if (pagingRequest.sortDesc[index]) {
            items.reverse();
          }
        });

        resolve({ total: items.length, data: items.slice(start, end) });
      }, 100);
    });
  }

  async fetchByHash(hash: string): Promise<UserProfile> {
    return new Promise<UserProfile>((resolve, reject) => {
      setTimeout(() => {
        const userProfile = rawProfiles.find((profile) => profile.id == hash);
        if (userProfile) {
          resolve(userProfile);
        } else {
          reject(new Error(apiErrorMapper({ code: 200 })));
        }
      }, 100);
    });
  }
}

const userProfileService = new UserProfileService();
export default userProfileService;
