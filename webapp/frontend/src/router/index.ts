import Vue from "vue";
import VueRouter, { RouteConfig } from "vue-router";
import Login from "../views/Login.vue";
import Home from "../views/Home.vue";
import Profile from "../views/Profile.vue";
import NotFound from "../views/NotFound.vue";
import UserModule from "@/store/modules/user";
import i18n from "@/i18n";
import ResetPassword from "@/views/ResetPassword.vue";
import ForgetPassword from "@/views/ForgetPassword.vue";

Vue.use(VueRouter);

const routes: Array<RouteConfig> = [
  {
    path: "/",
    name: "home",
    component: Home,
    meta: {
      requiresAuth: true,
      title: i18n.t("page.home")
    }
  },
  {
    path: "/profile/:id",
    name: "user-profile",
    component: Profile,
    meta: {
      requiresAuth: true,
      title: i18n.t("page.user-profile")
    }
  },
  {
    path: "/login",
    name: "login",
    component: Login,
    meta: {
      title: i18n.t("page.login")
    }
  },
  {
    path: "/reset-password/:hash",
    name: "reset-password",
    component: ResetPassword,
    meta: {
      title: i18n.t("page.reset-password")
    }
  },
  {
    path: "/forget-password",
    name: "forget-password",
    component: ForgetPassword,
    meta: {
      title: i18n.t("page.forget-password")
    }
  },
  {
    path: "/404",
    name: "404",
    component: NotFound,
    meta: {
      title: i18n.t("page.not-found")
    }
  },
  {
    path: "*",
    redirect: "/404"
  }
];

const router = new VueRouter({
  mode: "history",
  routes
});

router.beforeEach((to, from, next) => {
  console.log("Route to " + to.name);
  window.document.title = (to.name ? to.name + " - " : "") + "Generator";
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
