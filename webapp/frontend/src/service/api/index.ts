import axios from "axios";
import {settings} from "@/settings";
import UserModule from "@/store/modules/user";

axios.defaults.baseURL = settings.apiUrl;
axios.defaults.headers.post['Content-Type'] = 'application/json;charset=utf-8';
axios.defaults.headers.post['Access-Control-Allow-Origin'] = '*';

const api = axios.create({
    baseURL: settings.apiUrl,
    headers: {
        'Content-Type': 'application/json;charset=utf-8',
        'Access-Control-Allow-Origin': '*',
    }
});

api.interceptors.request.use((config) => {
    if (UserModule.isAuthorized) {
        config.headers['Authorization'] = `Bearer ${UserModule.user?.token}`;
    }
    return config;
}, error => Promise.reject(error));

api.interceptors.response.use((response) => response, (error) => {
    const originalRequest = error.config;
    if (error.response.status === 401 && !originalRequest._retry) {
        originalRequest._retry = true;
        return axios.put('/users/refresh', null, {headers: {'Authorization': `Bearer ${UserModule.token}`}})
            .then(response => {
                if (response.status === 200) {
                    // 1) put token to LocalStorage
                    UserModule.setToken(response.data.token);
                    // 3) return originalRequest object with Axios.
                    return axios(originalRequest);
                }
            })
    }
});

export default api;