<template>
  <v-card class="pa-0" elevation="0" outlined :min-height="height">
    <v-overlay :absolute="true" :value="disabled || loading" opacity="0.1"></v-overlay>
    <v-toolbar dense elevation="0">
      <v-text-field
          class="col col-6"
          v-model="filter"
          append-icon="mdi-magnify"
          :label="$t('user-profile.search')"
          single-line
          hide-details
      ></v-text-field>
      <v-spacer/>
      <v-tooltip bottom>
        <template v-slot:activator="{on, attrs}">
          <v-btn icon @click="fetchData()" v-bind="attrs" v-on="on" :loading="loading">
            <v-icon>mdi-refresh</v-icon>
          </v-btn>
        </template>
        <span>Refresh</span>
      </v-tooltip>
    </v-toolbar>
    <v-card-text :style="{overflow: 'auto', height:height}">
      <v-row dense class="overflow-y-auto ma-2" id="programs">
        <v-col cols="3" class="text-truncate"
               v-for="file in filteredItems" :key="file.id"
               v-bind:class="{selected: isItemSelected(file), disabled: isAlreadyPresent(file)}"
               @mousedown="onItemSelected(file, $event)">
          {{ file.name }}
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script lang="ts">

import {Component, Prop, PropSync, Watch} from "vue-property-decorator";
import DataList from "@/components/DataList.vue";
import {Program, User} from "@/store/models";
import programService from "@/service/api/programService";
import {EventBus} from "@/utils/event-bus";
import UploadDialog from "@/components/dialogs/UploadDialog.vue";
import MessageDialog from "@/components/dialogs/MessageDialog.vue";
import BaseVueComponent from "@/components/BaseVueComponent";

@Component({
  components: {MessageDialog, UploadDialog, DataList}
})
export default class ProgramDataList extends BaseVueComponent {
  @Prop({default: false}) readonly disabled!: boolean
  @Prop() readonly user!: User;
  @Prop({default: null}) readonly height?: number;
  @Prop({default: () => []}) readonly existsPrograms!: Program[];
  @PropSync('selected', {default: () => []}) selectedSync!: any[];

  private programs: Program[] = [];

  private loading = false;
  private filter: string | null = null;

  mounted() {
    this.fetchData();
    console.log("mounted");
  }

  @Watch("user")
  private onUserChanged() {
    this.fetchData();
  }

  private get filteredItems() {
    if (this.filter && this.filter.length > 3) {
      const search = this.filter.toLocaleLowerCase();
      return this.programs.filter(p => p.name.toLocaleLowerCase().includes(search));
    } else {
      return this.programs;
    }
  }

  private isAlreadyPresent(program: Program) {
    return this.existsPrograms.findIndex(p => p.name == program.name) >= 0;
  }

  private isItemSelected(program: Program) {
    return this.selectedSync.findIndex(p => p.id == program.id) >= 0;
  }

  private onItemSelected(program: Program, event: MouseEvent) {
    if (this.isAlreadyPresent(program)) return;

    if (event.shiftKey) {
      const indexes = this.selectedSync.map(p => this.programs.findIndex(f => f.id == p.id));
      const currentIdx = this.programs.indexOf(program);

      const minIdx = Math.min.apply(null, indexes);
      const maxIdx = Math.max.apply(null, indexes);
      console.log("Item selected " + currentIdx + ", " + minIdx + ", " + maxIdx);
      let _i: number;
      for (_i = currentIdx; _i < minIdx; _i++) {
        this.selectProgram(this.programs[_i]);
      }

      for (_i = maxIdx + 1; _i <= currentIdx; _i++) {
        this.selectProgram(this.programs[_i]);
      }
    } else if (event.ctrlKey || event.metaKey) {
      this.selectProgram(program);
    } else {
      this.selectedSync.length = 0;
      this.selectedSync.push(program);
    }
  }

  private selectProgram(program: Program) {
    if (this.isAlreadyPresent(program)) return;
    if (this.selectedSync.find(p => program.id == p.id) === undefined) {
      this.selectedSync.push(program);
    }
  }

  private fetchData() {
    this.loading = true;
    programService.getAllForUser(this.currentUser!)
        .then(programs => this.programs = programs.sort((a, b) => a.name.localeCompare(b.name)))
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

  .text-truncate {
    color: #0b2e13;

    &.disabled {
      color: #95999c;
    }

    &.selected {
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
}
</style>