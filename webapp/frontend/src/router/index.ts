import Vue from "vue";
import VueRouter, { RouteConfig } from "vue-router";
import Login from "../views/Login.vue";
import Home from "../views/Home.vue";
import UserProfileDetails from "../views/UserProfileDetails.vue";
import NotFound from "../views/NotFound.vue";
import UserModule from "@/store/modules/user";
import i18n from "@/i18n";

Vue.use(VueRouter);

const routes: Array<RouteConfig> = [
  {
    path: "/",
    name: "Users",
    component: Home,
    meta: {
      requiresAuth: true,
      title: i18n.t("page.home")
    }
  },
  {
    path: "/profile/:id",
    name: "User Profile",
    component: UserProfileDetails,
    meta: {
      requiresAuth: true,
      title: i18n.t("page.user-profile")
    }
  },
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: {
      title: i18n.t("page.login")
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
  window.document.title = (to.name ? to.name + " - " : "") + "Generator";
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (UserModule.isAuthorized) {
      next();
      return;
    }
    next("/login");
  } else {
    next();
  }
});

export default router;
