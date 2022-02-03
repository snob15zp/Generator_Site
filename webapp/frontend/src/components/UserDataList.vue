<template>
  <v-card class="pa-0" elevation="0" outlined :loading="loading" :min-height="height">
    <v-toolbar dense elevation="0">
      <v-text-field
          class="col col-6"
          v-model="filter"
          append-icon="mdi-magnify"
          :label="$t('user-profile.search')"
          single-line
          hide-details
      ></v-text-field>
    </v-toolbar>
    <v-card-text style="overflow: auto; height:600px">
      <v-row dense class="overflow-y-auto ma-2" id="programs">
        <v-col cols="3" class="text-truncate"
               v-for="user in filteredItems" :key="user.id"
               v-bind:class="{selected: isItemSelected(user)}"
               @mousedown="onItemSelected(user, $event)">
          {{ userName(user) }}
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script lang="ts">

import {Component, Prop, PropSync, Vue, Watch} from "vue-property-decorator";
import DataList from "@/components/DataList.vue";
import {PagingRequest, User} from "@/store/models";
import {EventBus} from "@/utils/event-bus";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import userService, {UserFilter} from "@/service/api/userService";
import {Role} from "@/store/modules/user";

@Component({
  components: {MessageDialog, UploadDialog, DataList}
})
export default class UserDataList extends Vue {
  @Prop() readonly user!: User;
  @Prop() readonly refresh!: boolean;
  @Prop({default: null}) readonly height?: number;
  @PropSync('selected', {default: () => []}) selectedSync!: any[];

  private users: User[] = [];

  private loading = false;
  private filter: string | null = null;

  mounted() {
    this.fetchData();
  }

  @Watch("refresh")
  private onUsersRefresh() {
    this.fetchData();
  }

  @Watch("user")
  private onUserChanged() {
    this.fetchData();
  }

  private get filteredItems() {
    if (this.filter && this.filter.length > 3) {
      const search = this.filter.toLocaleLowerCase();
      return this.users.filter(u => this.userName(u).toLocaleLowerCase().includes(search));
    } else {
      return this.users;
    }
  }

  private userName(user: User): string {
    return user.profile.name + (user.profile.surname ? " " + user.profile.surname : "");
  }

  private isItemSelected(user: User) {
    return this.selectedSync.findIndex(p => p.id == user.id) >= 0;
  }

  private onItemSelected(user: User, event: MouseEvent) {
    if (event.shiftKey) {
      const indexes = this.selectedSync.map(p => this.users.findIndex(f => f.id == p.id));
      const currentIdx = this.users.indexOf(user);

      const minIdx = Math.min.apply(null, indexes);
      const maxIdx = Math.max.apply(null, indexes);
      let _i: number;
      for (_i = currentIdx; _i < minIdx; _i++) {
        this.selectUser(this.users[_i]);
      }

      for (_i = maxIdx + 1; _i <= currentIdx; _i++) {
        this.selectUser(this.users[_i]);
      }
    } else if (event.ctrlKey || event.metaKey) {
      this.selectUser(user);
    } else {
      this.selectedSync.length = 0;
      this.selectedSync.push(user);
    }
  }

  private selectUser(user: User) {
    if (this.selectedSync.find(p => user.id == p.id) === undefined) {
      this.selectedSync.push(user);
    }
  }

  private fetchData() {
    this.loading = true;
    userService.fetchAll({
      page: 1,
      itemsPerPage: -1,
      sortBy: ['name'],
      sortDesc: [true],
      filter: {owner: this.user, roles: [Role.User], unlinked: true} as UserFilter
    } as PagingRequest)
        .then(users => {
          this.users = users.data.sort((a, b) => this.userName(a).localeCompare(this.userName(b)));
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.selectedSync = [];
          this.loading = false;
        });
  }
}

</script>

<style scoped lang="scss">
#programs {
  -moz-user-select: -moz-none;
  -khtml-user-select: none;
  -webkit-user-select: none;

  /*
    Introduced in IE 10.
    See http://ie.microsoft.com/testdrive/HTML5/msUserSelect/
  */
  -ms-user-select: none;
  user-select: none;

  .v-toolbar__content {
    padding: 0 !important;
  }

  .selected {
    color: #197bac;
    position: relative;

    &::before {
      background: currentColor;
      bottom: 2px;
      content: "";
      left: 0;
      opacity: 0.12;
      pointer-events: none;
      position: absolute;
      right: 4px;
      box-sizing: border-box;
      border-radius: 4px;
      top: 2px;
      transition: .3s cubic-bezier(.25, .8, .5, 1);
    }
  }
}
</style>