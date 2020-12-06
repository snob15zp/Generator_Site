import {User, UserCredetials, UserJson} from "@/store/models";
import axios from "axios";
import api from "."
import transformers from "./transformers"


class AuthService {
    async login(userCredentials: UserCredetials): Promise<User> {
        return new Promise((resolve, reject) => {
            axios.post<UserJson>("/users/login", userCredentials)
                .then(function (response) {
                    console.log(response);
                    if (response.status == 200) {
                        resolve(transformers.userFromJson(response.data));
                    } else {
                        reject(response.data);
                    }
                })
                .catch(function (error) {
                    console.log(error.response);
                    if (error.response) {
                        reject(new Error(error.response.data.errors.message));
                    } else {
                        reject(new Error());
                    }

                });
        });

    }

    async resetPassword(hash: string, password: string): Promise<boolean> {
        return new Promise((resolve, reject) => {
            api.post("/users/reset-password", {hash: hash, password: password})
                .then(() => resolve(true))
                .catch((error) => reject(new Error(error)))
        });
    }

    async forgetPassword(login: string): Promise<boolean> {
        return new Promise((resolve, reject) => {
            api.post("/users/forget-password", {login: login})
                .then(() => resolve(true))
                .catch((error) => reject(new Error(error)))
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
