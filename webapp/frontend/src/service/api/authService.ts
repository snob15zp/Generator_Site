import {User, UserCredetials, UserJson} from "@/store/models";
import axios from "axios";
import api from "."
import transformers from "./transformers"
import {apiErrorMapper} from "@/service/api/utils";


class AuthService {
    async login(userCredentials: UserCredetials): Promise<User> {
        return new Promise((resolve, reject) => {
            axios.post<UserJson>("/users/login", userCredentials)
                .then((response) => resolve(transformers.userFromJson(response.data)))
                .catch((error) => reject(new Error(apiErrorMapper(error))));
        });
    }

    async resetPassword(hash: string, password: string): Promise<boolean> {
        return new Promise((resolve, reject) => {
            api.post("/users/reset-password", {hash: hash, password: password})
                .then(() => resolve(true))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async forgetPassword(login: string): Promise<boolean> {
        return new Promise((resolve, reject) => {
            api.post("/users/forget-password", {login: login})
                .then(() => resolve(true))
                .catch((error) => reject(new Error(apiErrorMapper(error))))
        });
    }

    async logout(): Promise<boolean> {
        return new Promise((resolve) => {
            api.post("/users/logout")
                .finally(() => resolve(true))
        });
    }
}

const authService = new AuthService();
export default authService;
