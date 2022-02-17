<template>
  <v-card outlined>
    <upload-dialog v-model="upload" @success="onUploadSuccess" @failed="onUploadFailed" :user="user"/>
    <v-dialog v-model="isProgramAddDialogShow" max-width="800px">
      <v-card>
        <v-card-title class="headline">Add programs</v-card-title>
        <v-card-subtitle><small>Use SHIFT or CTRL with the mouse to select multiple items.</small></v-card-subtitle>
        <program-data-list
            height="600"
            :user="user"
            :exists-programs="items"
            :selected.sync="addProgramItems"
            :key="programAddDialogKey"/>
        <v-card-actions>
          <span>Total selected: {{ addProgramItems.length }}</span>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="onAddProgramsCancel()">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="onAddProgramsConfirm()" :disabled="addProgramItems.length === 0">
            {{ $t("form.ok") }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <message-dialog ref="messageDialog"/>
    <v-overlay
        :absolute="true"
        :value="loading"
    />
    <v-data-table
        v-model="selected"
        :loading="loading"
        :options.sync="options"
        :headers="headers"
        :items="items"
        :search="search"
        :single-select="false"
        :height="height"
        item-key="id"
        :show-select="canUploadPrograms">
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
          <v-spacer></v-spacer>
          <v-tooltip bottom v-if="showAttachButton">
            <template v-slot:activator="{on, attrs}">
              <v-btn color="primary" @click="onAddPrograms" icon v-bind="attrs" v-on="on">
                <v-icon dark>mdi-plus-box-multiple</v-icon>
              </v-btn>
            </template>
            <span>Add programs</span>
          </v-tooltip>
          <v-tooltip bottom v-if="canUploadPrograms">
            <template v-slot:activator="{on, attrs}">
              <v-btn color="primary" @click="upload = true" icon v-bind="attrs" v-on="on">
                <v-icon dark>mdi-upload-multiple</v-icon>
              </v-btn>
            </template>
            <span>Upload</span>
          </v-tooltip>
          <v-tooltip bottom v-if="canUploadPrograms">
            <template v-slot:activator="{on, attrs}">
              <v-btn color="primary" icon
                     @click="onClickDeleteSelected"
                     v-bind="attrs" v-on="on"
                     :disabled="selected.length === 0">
                <v-icon dark>mdi-delete</v-icon>
              </v-btn>
            </template>
            <span>Delete selected</span>
          </v-tooltip>
          <v-tooltip bottom>
            <template v-slot:activator="{on, attrs}">
              <v-btn color="primary" icon
                     @click="fetchData(true)"
                     v-bind="attrs" v-on="on">
                <v-icon dark>mdi-refresh</v-icon>
              </v-btn>
            </template>
            <span>Refresh</span>
          </v-tooltip>
        </v-toolbar>
      </template>
      <template v-slot:[`item.name`]="{ item }">{{ item.name }}</template>
      <template v-slot:[`item.created_at`]="{ item }">{{ $d(item.createdAt) }}</template>
      <template v-slot:[`item.actions`]="{ item }" v-if="canUploadPrograms">
        <v-icon small @click="onClickDeleteProgram(item)">mdi-delete</v-icon>
      </template>
    </v-data-table>
  </v-card>
</template>

<script lang="ts">

import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import {Folder, PagingRequest, Program, User} from "@/store/models";
import {DataOptions, DataTableHeader} from "vuetify";
import programService from "@/service/api/programService";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import {EventBus} from "@/utils/event-bus";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import UserModule, {Role} from "@/store/modules/user";
import BaseVueComponent from "@/components/BaseVueComponent";
import ProgramDataList from "@/components/ProgramDataList.vue";

const DEFAULT_ITEMS_PER_PAGE = 10;

@Component({
  components: {ProgramDataList, MessageDialog, UploadDialog}
})
export default class ProgramDataTable extends BaseVueComponent {
  @Prop() readonly height!: number;
  @Prop() readonly user?: User | null;

  @Ref() readonly messageDialog: MessageDialog | undefined;

  private loading = false;
  private total = 0;
  private search = '';
  private items: Program[] = [];
  private upload = false;
  private options: DataOptions | null = null;
  private selected: Program[] = [];
  private searchTimeout?: number | null = null;

  private isProgramAddDialogShow = false;
  private addProgramItems: Program[] = [];
  private programAddDialogKey = 0;

  private get headers() {
    const headers: DataTableHeader[] = [
      {text: 'Name', value: "name"},
      {text: 'Date of created', value: "created_at", width: "20%"}
    ];

    if (this.canUploadPrograms) {
      headers.push(
          {text: this.$t("user-profile.column-actions") as string, value: "actions", sortable: false, width: "90px"}
      );
    }

    return headers;
  }

  private get showAttachButton() {
    console.log(this.user);
    return this.canAttachPrograms && this.user?.id != this.currentUser?.id;
  }

  mounted() {
    this.fetchData(true);
  }

  @Watch("user")
  private onUserChanged() {
    this.fetchData(true);
  }

  private userName(user: User): string {
    return user.profile.name + (user.profile.surname ? " " + user.profile.surname : "");
  }

  private onSearchChanged() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    this.searchTimeout = setTimeout(() => this.fetchData(true), 500);
  }

  private onUploadSuccess() {
    this.fetchData(true);
  }

  private onUploadFailed(e: Error) {
    EventBus.$emit("error", new Error("Unable to upload files"));
    this.messageDialog?.show("Error", e.message, ['OK'])
        .then(() => this.fetchData(true));
  }

  private onClickDeleteSelected() {
    this.deletePrograms("Are you sure you want to delete the selected programs?", this.selected.map((p) => p.id))
  }

  private onClickDeleteProgram(program: Program) {
    this.deletePrograms("Are you sure you want to delete the program?", [program.id]);
  }

  private deletePrograms(message: string, programIds: string[]) {
    this.messageDialog?.show("Delete", message)
        .then((result) => {
          if (result) {
            this.loading = true;
            return programService.deleteProgramsForUser(this.user!, programIds)
          } else {
            return Promise.reject();
          }
        })
        .then(() => this.fetchData(true))
        .catch((e) => {
              this.loading = false;
              e && EventBus.$emit("error", e)
            }
        );
  }

  private onAddPrograms() {
    this.programAddDialogKey++;
    this.isProgramAddDialogShow = true;
  }

  private onAddProgramsCancel() {
    this.isProgramAddDialogShow = false;
  }

  private onAddProgramsConfirm() {
    this.isProgramAddDialogShow = false;
    this.loading = true;
    this.addPrograms();
  }

  private addPrograms() {
    programService.addProgramsForUser(this.user!, this.addProgramItems)
        .then(() => this.fetchData(true))
        .catch(e => {
          this.loading = false;
          EventBus.$emit("error", new Error("Unable to add files"));
          this.messageDialog?.show("Error", e.message, ['OK'])
              .then(() => this.fetchData(true));
        })
        .finally(() => {
          this.addProgramItems = [];
        })
  }

  private fetchData(reset: boolean) {
    if (reset && this.options) {
      this.options.page = 1;
    }
    this.loading = true;
    programService.getAllForUser(this.user!)
        .then(programs => {
          this.items = programs;
          this.total = programs.length;
        })
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.selected = [];
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