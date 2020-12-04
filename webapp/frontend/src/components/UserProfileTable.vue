<template>
  <v-data-table
    v-model="selected"
    :loading="loading"
    :server-items-length="total"
    :options.sync="options"
    :headers="headers"
    :items="items"
    :single-select="false"
    item-key="id"
    show-select>
    <template v-slot:top>
      <v-toolbar flat>
        <!-- Search bar -->
        <v-layout>
          <v-text-field
            v-model="search"
            append-icon="mdi-magnify"
            @input="onSearchChanged"
            :label="$t('user-profile.search')"
            single-line
            hide-details
          ></v-text-field>
        </v-layout>
        <v-spacer></v-spacer>
        <v-btn icon @click="onCreateItem">
          <v-icon>mdi-account-plus</v-icon>
        </v-btn>
        <v-btn icon :disabled="selected.length == 0" @click="onDeleteItems">
          <v-icon>mdi-delete</v-icon>
        </v-btn>
      </v-toolbar>
    </template>
    <template v-slot:[`item.name`]="{ item }">
      <a :href="'/profile/' + item.id">{{ item.name + " " + item.surname }}</a>
    </template>
    <template v-slot:[`item.created_at`]="{ item }">{{ $d(item.createdAt) }}</template>
    <template v-slot:[`item.updated_at`]="{ item }">{{ $d(item.updatedAt) }}</template>
    <template v-slot:[`item.actions`]="{ item }">
      <v-icon small class="mr-2" @click="onSaveItem(item)">mdi-pencil</v-icon>
      <v-icon small @click="onDeleteItem(item)">mdi-delete</v-icon>
    </template>
  </v-data-table>
</template>

<script lang="ts">
import { Component, Emit, Prop, Vue, Watch } from "vue-property-decorator";
import { UserProfile } from "../store/models";
import { DataOptions } from "vuetify";

const START_PAGE = 1;
const DEFAULT_ITEMS_PER_PAGE = 10;

@Component
export default class UserProfileTable extends Vue {
  @Prop({ default: [] }) private readonly items!: UserProfile[];
  @Prop({ default: 0 }) private readonly total!: number;
  @Prop({ default: false }) private readonly loading!: boolean;

  private searchTimeout?: number | null = null;

  private options: DataOptions | null = null;
  private selected: UserProfile[] = [];

  private search = "";

  private get headers() {
    return [
      { text: this.$t("user-profile.column-user"), value: "name" },
      { text: this.$t("user-profile.column-created-at"), value: "created_at", width: "20%" },
      { text: this.$t("user-profile.column-modified-at"), value: "updated_at", width: "20%" },
      { text: this.$t("user-profile.column-actions"), value: "actions", sortable: false, width: "90px" }
    ];
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchData();
  }

  @Emit()
  onDeleteItems() {
    return this.selected;
  }

  @Emit()
  onDeleteItem(userProfile: UserProfile) {
    return userProfile;
  }

  @Emit()
  onSaveItem(userProfile: UserProfile) {
    return userProfile;
  }

  @Emit()
  onCreateItem() {
    return;
  }

  @Emit()
  fetchData() {
    const { sortBy, sortDesc, page, itemsPerPage } = this.options!;
    return {
      page: page || 1,
      itemsPerPage: itemsPerPage || DEFAULT_ITEMS_PER_PAGE,
      sortBy: sortBy || [],
      sortDesc: sortDesc || [],
      query: this.search || ""
    };
  }

  private onSearchChanged() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchData(), 500);
  }
}
</script>
