import { UserJson, User, UserCredetials } from "@/store/models";
import { apiErrorMapper } from "./utils";
import axios from "axios";
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

  async logout(): Promise<boolean> {
    return new Promise((resolve) => resolve(true));
  }
}

const authService = new AuthService();
export default authService;
