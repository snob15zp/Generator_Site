<template>
  <v-card ref="container" outlined :loading="loading">
    <v-card-title class="headline">
      Programms
      <v-spacer />
      <v-btn icon @click="createFolder">
        <v-icon>mdi-folder-plus</v-icon>
      </v-btn>
      <div>
        <v-file-input
          dense
          v-model="fileInput"
          hide-input
          :disabled="selected === null"
          prepend-icon="mdi-file-upload"
        ></v-file-input>
      </div>
    </v-card-title>
    <v-divider />
    <v-layout row class="row--dense">
      <v-col cols="12" md="6">
        <v-list class="overflow-y-auto p-list" nav>
          <v-list-item-group active-class="primary--text" v-model="selected">
            <v-list-item v-for="folder in folders" :key="folder.hash">
              <v-list-item-avatar>
                <v-icon>
                  mdi-folder
                </v-icon>
              </v-list-item-avatar>
              <v-list-item-content>
                <v-list-item-title>{{ folder.name }} </v-list-item-title>
                <v-list-item-subtitle v-bind:class="{ 'error--text': isExpired(folder.expiredAt) }">
                  {{ folder.expiredAt | expiredAtInterval }}
                </v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-action v-if="folders[selected].hash == folder.hash">
                <v-icon>mdi-chevron-right</v-icon>
              </v-list-item-action>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-col>
      <v-col cols="12" md="6">
        <v-list class="overflow-y-auto p-list" nav dense>
          <v-list-item v-for="file in files" :key="file.hash">
            <v-list-item-content>
              <a :href="'/program/' + file.hash">{{ file.name }}</a>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-col>
    </v-layout>
  </v-card>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch, Emit, Ref } from "vue-property-decorator";
import { Folder, Program } from "../store/models";
import { isExpired, expiredAtInterval } from "../utils/dateUtils";
import { ResizeObserver } from "@juggle/resize-observer";

@Component({
  filters: {
    expiredAtInterval: function(value: Date) {
      return expiredAtInterval(value);
    }
  }
})
export default class Programs extends Vue {
  @Prop({ default: false }) readonly loading!: boolean;
  @Prop() readonly folders!: Folder[];
  @Prop({ default: () => [] }) readonly files!: Program[];

  @Ref() readonly container: Vue | undefined;

  private selected = 0;
  private fileInput: any | null = null;

  private observer = new ResizeObserver((entries) => {
    const height = Math.max(entries[0].contentBoxSize[0].blockSize - 80, 300);
    Array.from(document.getElementsByClassName("p-list") as HTMLCollectionOf<HTMLElement>).forEach((el) => {
      el.style.height = height + "px";
    });
  });

  mounted() {
    this.observer.observe(this.container!.$el);
  }

  @Watch("selected")
  private onSelectedChanged() {
    this.onFolderChanged(this.folders[this.selected]);
  }

  @Watch("fileInput")
  private onfileInputChanged() {
    this.uploadFile(this.fileInput.toString());
  }

  @Emit()
  onFolderChanged(folder: Folder) {
    return folder;
  }

  @Emit()
  createFolder() {
    return;
  }

  @Emit()
  uploadFile(file: string) {
    return file;
  }

  private isExpired(date: Date) {
    return isExpired(date);
  }
}
</script>
