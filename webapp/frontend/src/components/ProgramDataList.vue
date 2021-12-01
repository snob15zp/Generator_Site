<template>
  <v-container class="pa-0">
    <upload-dialog v-model="upload" @success="onUploadSuccess"/>
    <message-dialog ref="messageDialog"/>
    <data-list
        :height="height"
        :headers="headers"
        :items="programs"
        :loading="loading"
        :title="title"
        :selected.sync="selected">
      <template v-slot:action>
        <v-btn icon @click="upload = true">
          <v-icon>mdi-upload-multiple</v-icon>
        </v-btn>
        <v-btn icon class="ml-2" @click="onClickDeleteSelected" :disabled="selected.length === 0">
          <v-icon>mdi-delete</v-icon>
        </v-btn>
      </template>
      <template v-slot:item-action="{item}">
        <v-btn @click="onClickDeleteProgram(item)" icon x-small>
          <v-icon>mdi-delete</v-icon>
        </v-btn>
      </template>
    </data-list>
  </v-container>
</template>

<script lang="ts">

import {Component, Prop, Ref, Vue, Watch} from "vue-property-decorator";
import DataList, {DataListHeader} from "@/components/DataList.vue";
import {Program} from "@/store/models";
import programService from "@/service/api/programService";
import {EventBus} from "@/utils/event-bus";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";

@Component({
  components: {MessageDialog, UploadDialog, DataList}
})
export default class ProgramDataList extends Vue {
  @Prop({default: null}) readonly height?: number;
  @Prop({default: null}) readonly title?: string;
  @Ref() readonly messageDialog: MessageDialog | undefined;

  private programs: Program[] = [];
  private loading = false;
  private upload = false;
  private selected: Program[] = [];

  private headers: DataListHeader[] = [
    {
      text: "Name",
      value: "name",
      sortable: true,
      title: true,
      filtered: true
    },
  ];

  mounted() {
    this.fetchData();
  }

  private onUploadSuccess() {
    this.fetchData();
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
            return programService.deletePrograms(programIds)
          } else {
            return Promise.reject();
          }
        })
        .then(() => this.fetchData())
        .catch((e) => {
              this.loading = false;
              e && EventBus.$emit("error", e)
            }
        );
  }

  private fetchData() {
    this.loading = true;
    programService.getAll()
        .then(programs => this.programs = programs)
        .catch((e) => EventBus.$emit("error", e))
        .finally(() => {
          this.selected = [];
          this.loading = false;
        });
  }
}

</script>

<style scoped>

</style>