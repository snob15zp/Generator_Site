import Vue from "vue";
import VueRouter, {RouteConfig} from "vue-router";
import LoginView from "../views/LoginView.vue";
import HomePageView from "../views/HomePageView.vue";
import NotFoundView from "../views/NotFoundView.vue";
import UserModule from "@/store/modules/user";
import i18n from "@/i18n";
import ResetPasswordView from "@/views/ResetPasswordView.vue";
import ForgetPasswordView from "@/views/ForgetPasswordView.vue";
import FirmwareView from "@/views/FirmwareView.vue";
import SoftwareView from "@/views/SoftwareView.vue";
import ImportProgramsView from "@/views/ImportProgramsView.vue";
import UserView from "@/views/UserView.vue";
import {User} from "@/store/models";

Vue.use(VueRouter);

const routes: Array<RouteConfig> = [
    {
        path: "/",
        name: "home",
        component: HomePageView,
        meta: {
            icon: "mdi-account-multiple",
            navigation: true,
            requiresAuth: true,
            title: i18n.t("page.home"),
            privileges: ["manage-users", "manage-own-users"]
        }
    },
    {
        path: "/firmware",
        name: "firmware",
        component: FirmwareView,
        meta: {
            icon: "mdi-memory",
            navigation: true,
            requiresAuth: true,
            title: i18n.t("page.firmware"),
            privileges: ["manage-firmware"]
        }
    },
    {
        path: "/software",
        name: "software",
        component: SoftwareView,
        meta: {
            icon: "mdi-laptop",
            navigation: true,
            requiresAuth: true,
            title: i18n.t("page.software"),
            privileges: ["manage-software"]
        }
    },
    {
        path: "/user/:id",
        name: "user",
        component: UserView,
        meta: {
            navigation: false,
            requiresAuth: true,
            title: i18n.t("page.user-profile")
        }
    },
    {
        path: "/login",
        name: "login",
        component: LoginView,
        meta: {
            navigation: false,
            title: i18n.t("page.login")
        }
    },
    {
        path: "/reset-password/:hash",
        name: "reset-password",
        component: ResetPasswordView,
        meta: {
            navigation: false,
            title: i18n.t("page.reset-password")
        }
    },
    {
        path: "/forget-password",
        name: "forget-password",
        component: ForgetPasswordView,
        meta: {
            navigation: false,
            title: i18n.t("page.forget-password")
        }
    },
    {
        path: "/404",
        name: "404",
        component: NotFoundView,
        meta: {
            navigation: false,
            title: i18n.t("page.not-found")
        }
    },
    {
        path: "/import-programs/:id/:hash",
        name: "import-programs",
        component: ImportProgramsView,
        meta: {
            navigation: false,
            title: "Import programs"
        }
    },
    {
        path: "*",
        redirect: "/404"
    }
];

export function navigationRouters(user?: User | null, config?: RouteConfig[]) {
    return config?.filter(route => {
        const isRouterAllow = (route.meta?.privileges) ?
            UserModule.user?.privileges?.some(p => route.meta?.privileges?.indexOf(p) >= 0) : true;
        return route.meta?.navigation && isRouterAllow;
    });
}

const router = new VueRouter({
    mode: "history",
    base: process.env["BASE_URL"],
    routes
});

router.beforeEach((to, from, next) => {
    window.document.title = (to.meta?.title ? to.meta.title + " - " : "") + "InHealion";
    if (to.matched.some((record) => record.meta.requiresAuth)) {
        if (UserModule.isAuthorized) {
            next();
            return;
        }
        next("/login");
    } else {
        if (to.name === "login" && UserModule.isAuthorized) {
            next("/");
        } else {
            next();
        }

    }
});

export default router;
