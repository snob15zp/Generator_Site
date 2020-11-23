import { User, UserCredetials } from "@/store/models";
import { apiErrorMapper } from "./utils";

class AuthService {
  async login(userCredentials: UserCredetials): Promise<User> {
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

  async logout(): Promise<Boolean> {
    return new Promise((resolve) => resolve(true));
  }
}

const authService = new AuthService();
export default authService;
