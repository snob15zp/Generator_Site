<template>
  <v-container>
    <v-card  outlined class="mt-8 mb-8">
      <v-card-title>
        Releases
        <v-spacer/>
            <v-file-input
              outlined
              dense
              placeholder="Select your file"
              v-model="fileInput"
              class="mr-4"
              style="height: 40px"
            ></v-file-input>
            <v-btn dark color="primary">Upload<v-icon right dark>mdi-file-upload</v-icon></v-btn>
      </v-card-title>

      <v-overlay :absolute="true" :value="loading" />
      <v-data-table
        v-model="selected"
        item-key="hash"
        :headers="headers"
        :single-select="false"
        :items="items"
        show-select
        disable-pagination
        hide-default-footer
      >
        <template v-slot:[`item.createdAt`]="{ item }">{{ $d(item.createdAt) }}</template>
        <template v-slot:[`item.actions`]="{ item }">
          <v-btn icon small><v-icon small @click="onDownloadItem(item)">mdi-download</v-icon></v-btn>
          <v-btn icon small><v-icon small @click="onDeleteItem(item)">mdi-delete</v-icon></v-btn>
        </template>
      </v-data-table>
    </v-card>
  </v-container>
</template>


<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import { DataOptions } from "vuetify";
import firmwareService from "@/service/api/firmwareService";
import { Firmware } from "@/store/models";

@Component
export default class FirmwareView extends Vue {
  private loading = false;
  private options: DataOptions | null = null;
  private items: Firmware[] = [];
  private selected: Firmware[] = [];

  private get headers() {
    return [
      { text: "Version", value: "version" },
      { text: "Device", value: "device" },
      { text: "Date", value: "createdAt" },
      {
        value: "actions",
        sortable: false,
        width: "90px",
      },
    ];
  }

  mounted() {
    this.fetchData();
  }

  @Watch("options")
  private onOptionsChanged() {
    this.fetchData();
  }

  fetchData() {
    this.loading = true;

    firmwareService
      .getAll()
      .then((data: Firmware[]) => (this.items = data))
      .catch((error: Error) => console.log(error))
      .finally(() => (this.loading = false));
  }

  onDeleteItem(item: Firmware) {
    console.log("delete");
  }

  onDonwloadItem(item: Firmware) {
    console.log("download");
  }
}
</script>