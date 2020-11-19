import axios from "axios";
import {
  User,
  ErrorReponse,
  UserCredetials,
  PagingRequest,
  UserProfile,
  PagingResponse,
  Folder,
  Program
} from "./models";
import i18n from "@/i18n";
import { fakeUserProfiles, fakeFolders } from "./data";
import moment from "moment";

const API_ERROR_CODES = {
  USER_NOT_FOUND: 100,
  USER_PROFILE_NOT_FOUND: 200
};

let rawProfiles = fakeUserProfiles;
const rawFolders = fakeFolders;

function apiErrorMapper(error: ErrorReponse): string {
  switch (error.code) {
    case API_ERROR_CODES.USER_NOT_FOUND: {
      return i18n.t("server-error.user_not_found") as string;
    }
    case API_ERROR_CODES.USER_PROFILE_NOT_FOUND: {
      return i18n.t("server-error.user_profile_not_found") as string;
    }
  }
  return i18n.t("server-error.general_error") as string;
}

export async function loginUser(userCredentials: UserCredetials): Promise<User> {
  return new Promise<User>((resolve, reject) => {
    setTimeout(() => {
      if (userCredentials.login === "admin" && userCredentials.password === "admin") {
        const user: User = {
          token: "123456",
          name: "Administrator"
        };
        resolve(user);
      } else {
        reject(new Error(apiErrorMapper({ code: 100 })));
      }
    }, 500);
  });
}

export async function deleteUserProfiles(userProfiles: Array<UserProfile>) {
  rawProfiles = rawProfiles.filter((profile) => !userProfiles.some((p) => p.hash == profile.hash));
  return Promise.resolve(userProfiles.length);
}

export async function saveUserProfile(userProfile: UserProfile) {
  const index = rawProfiles.findIndex((p) => p.hash == userProfile.hash);
  userProfile.modifiedAt = new Date();

  if (index >= 0) {
    rawProfiles[index] = userProfile;
  } else {
    userProfile.hash = Math.random()
      .toString(36)
      .substr(2, 5);
    rawProfiles.push(userProfile);
  }

  return Promise.resolve();
}

export async function fetchUserProfiles(pagingRequest: PagingRequest): Promise<PagingResponse<UserProfile>> {
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
          case "modifiedAt":
            items.sort((a, b) => a.modifiedAt!.getTime() - b.modifiedAt!.getTime());
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

export async function fetchUserProfilByHash(hash: string): Promise<UserProfile> {
  return new Promise<UserProfile>((resolve, reject) => {
    setTimeout(() => {
      const userProfile = rawProfiles.find((profile) => profile.hash == hash);
      if (userProfile) {
        resolve(userProfile);
      } else {
        reject(new Error(apiErrorMapper({ code: 200 })));
      }
    }, 100);
  });
}

export async function fetchFolders(userProfileHash: string): Promise<Folder[]> {
  return new Promise<Folder[]>((resolve, reject) => {
    setTimeout(() => {
      resolve(rawFolders.sort((a, b) => a.expiredAt.getTime() - b.expiredAt.getTime()));
    }, 100);
  });
}

export async function saveFolder(folder: Folder): Promise<Folder> {
  return new Promise<Folder>((resolve, reject) => {
    setTimeout(() => {
      const index = rawFolders.findIndex((f) => f.hash == folder.hash);
      if (index >= 0) {
        rawFolders[index] = folder;
      } else {
        folder.hash = Math.random()
          .toString(36)
          .substr(2, 5);
        rawFolders.push(folder);
      }
      resolve(folder);
    }, 100);
  });
}

export async function fetchPrograms(folderHash: string): Promise<Program[]> {
  return new Promise<Program[]>((resolve, reject) => {
    setTimeout(() => {
      resolve([
        { hash: "213123123", path: "/data/", name: "program1.txt" },
        { hash: "213123124", path: "/data/", name: "program2.txt" },
        { hash: "213123125", path: "/data/", name: "program3.txt" },
        { hash: "213123126", path: "/data/", name: "program4.txt" }
      ]);
    }, 100);
  });
}
