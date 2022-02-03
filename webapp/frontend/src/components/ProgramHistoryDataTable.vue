<template>
  <v-card outlined>
    <upload-dialog v-model="upload" @success="onUploadSuccess" @failed="onUploadFailed" :user="user"/>
    <message-dialog ref="messageDialog"/>
    <v-overlay
        :absolute="true"
        :value="loading"
    />
    <v-data-table
        :loading="loading"
        :options.sync="options"
        :headers="headers"
        :items="items"
        :search="search"
        :height="height"
        item-key="id">
      <template v-slot:top>
        <v-toolbar flat>
          <!-- Search bar -->
          <v-layout>
            <v-text-field
                v-model="search"
                append-icon="mdi-magnify"
                :label="$t('user-profile.search')"
                single-line
                hide-details
            ></v-text-field>
          </v-layout>
        </v-toolbar>
      </template>
      <template v-slot:[`item.name`]="{ item }">{{ item.name }}</template>
      <template v-slot:[`item.created_at`]="{ item }">{{ $d(item.createdAt) }}</template>
    </v-data-table>
  </v-card>
</template>

<script lang="ts">

import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {PagingRequest, Program, User} from "@/store/models";
import {DataOptions, DataTableHeader} from "vuetify";
import programService from "@/service/api/programService";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import {EventBus} from "@/utils/event-bus";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import UserModule, {Role} from "@/store/modules/user";

const DEFAULT_ITEMS_PER_PAGE = 10;

@Component({
  components: {MessageDialog, UploadDialog}
})
export default class ProgramHistoryDataTable extends Vue {
  @Prop() readonly height!: number;
  @Prop() readonly user?: User | null;

  @Ref() readonly messageDialog: MessageDialog | undefined;

  private loading = false;
  private total = 0;
  private search = '';
  private items: Program[] = [];
  private options: DataOptions | null = null;
  private searchTimeout?: number | null = null;

  private get headers() {
    const headers: DataTableHeader[] = [
      {text: 'Name', value: "name"},
      {text: 'Date of created', value: "created_at", width: "20%"}
    ];
    return headers;
  }

  mounted() {
    this.fetchData(true);
  }

  @Watch("user")
  private onUserChanged() {
    this.fetchData(true);
  }

  private onSearchChanged() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchData(true), 500);
  }

  private fetchData(reset: boolean) {
    if (reset && this.options) {
      this.options.page = 1;
    }
    this.loading = true;
    programService.fetchHistory(this.user!)
        .then(programs => {
          this.items = programs;
          this.total = programs.length;
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.loading = false;
        });
  }
}

</script>

<style scoped>
.theme--light.v-sheet--outlined {
  border: none !important;
}
</style>