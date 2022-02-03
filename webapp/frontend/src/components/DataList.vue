<template>
  <v-card :loading="loading" elevation="0">
    <v-overlay :value="loading" absolute opacity="0.5" color="#ffffff"></v-overlay>
    <v-toolbar flat>
      <v-toolbar-items class="row row--dense">
        <v-checkbox
            class="pt-4 col col-1"
            @click.stop="onSelectAllChanged"
            :indeterminate="someNodesSelected"
            :input-value="allNodeSelected"/>
        <v-select
            class="col col-2"
            :items="sortItems"
            label="Sort By"
            item-text="label"
            item-value="label">
          <template v-slot:selection="data">
            <div class="text-truncate">
              <v-icon small class="mr-2">{{ getSortDirIcon(data.item) }}</v-icon>
              {{ data.item.label }}
            </div>
          </template>
          <template v-slot:item="data">
            <v-list-item-icon class="mr-1">
              <v-icon small>{{ getSortDirIcon(data.item) }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content @click="onSortChanged(data.item)">
              {{ data.item.label }}
            </v-list-item-content>
          </template>
        </v-select>
        <v-text-field
            class="col col-6"
            v-model="options.filter"
            append-icon="mdi-magnify"
            :label="$t('user-profile.search')"
            single-line
            hide-details
        ></v-text-field>
        <v-spacer/>
        <slot name="action"></slot>
      </v-toolbar-items>
    </v-toolbar>
    <v-list-item-group multiple v-model="selectedSync">
      <v-virtual-scroll :height="height" item-height="50" :items="filteredItems">
        <template v-slot:default="{ item }">
          <v-list-item :key="item.key" :value="item.item">
            <template v-slot:default="{ active, }">
              <v-list-item-action>
                <v-checkbox :input-value="active" :true-value="item.item"/>
              </v-list-item-action>
              <v-list-item-content @click="onItemClicked(item.item)">
                <slot name="item-content" :item="item.item"/>
              </v-list-item-content>
              <v-list-item-action>
                <v-row>
                  <slot name="item-action" :item="item.item"></slot>
                </v-row>
              </v-list-item-action>
            </template>
          </v-list-item>
        </template>
      </v-virtual-scroll>
    </v-list-item-group>
  </v-card>
</template>

<script lang="ts">

import {Component, Emit, Prop, PropSync, Vue} from "vue-property-decorator";
import {compare} from "@/utils/objects";

export interface DataListHeader {
  text: string,
  value: string | Function,
  sortable?: boolean,
  filtered?: boolean
}

interface Sort {
  label: string,
  field: string,
  isAscending?: boolean | null
}

interface Node {
  item: any,
  title?: string | null,
  subtitle?: string | null,
  href?: string | null,
  key: number
}

interface Options {
  sort?: Sort
  filter?: string
}

@Component
export default class DataList extends Vue {
  @Prop({default: null}) readonly height?: number;
  @Prop({default: null}) readonly title?: string;
  @Prop({default: false}) readonly loading?: boolean;
  @Prop({default: false}) readonly virtual?: boolean;
  @Prop() readonly headers!: DataListHeader[];
  @Prop() readonly items!: any[];
  @PropSync('selected', {default: () => []}) selectedSync!: any[];

  private options: Options = {}
  private selectAll = false;

  private filteredFields: (string | Function)[] = [];

  private get sortItems(): Sort[] {
    return this.headers
        .filter(header => header.sortable)
        .map(header => {
          return {
            label: header.text,
            field: header.value,
            isAscending: null
          } as Sort
        });
  }

  mounted() {
    this.filteredFields = [];
    this.headers.forEach(header => {
      if (header.filtered) this.filteredFields.push(header.value);
    });
  }

  private get someNodesSelected() {
    return this.selectedSync.length > 0 && !this.allNodeSelected;
  }

  private get allNodeSelected() {
    return this.selectedSync.length == this.filteredItems.length;
  }

  private get filteredItems(): Node[] {
    let result = this.search(this.items);
    result = this.sort(result);

    let idx = 0;
    return result.map(item => {
      return {
        item: item,
        key: idx++
      } as Node;
    });
  }

  private sort(items: any[]): any[] {
    if (this.options.sort) {
      const field = this.options.sort.field;
      const isAscending = this.options.sort.isAscending ?? true;
      items.sort((a: any, b: any) => {
        const v1 = DataList.getValueByKey(a, field);
        const v2 = DataList.getValueByKey(b, field)
        const result = compare(v1, v2);
        return isAscending ? result : -result;
      })
      this.selectedSync = [];
    }
    return items;

  }

  private search(items: any[]): any[] {
    if (this.options.filter && this.options.filter.length > 2) {
      const search = this.options.filter!!.toLocaleLowerCase();
      return items.filter(item =>
          this.filteredFields
              .map(value => DataList.getValueByKey(item, value).toLocaleLowerCase())
              .some(text => text.includes(search))
      );
    } else {
      return items;
    }
  }

  private static getValueByKey(item: any, key: string | Function): any {
    if (typeof key === 'string') {
      return item[key as string];
    } else {
      return (key as Function)(item);
    }
  }

  private onSelectAllChanged() {
    const items = this.filteredItems;
    if (this.selectedSync.length == 0 || this.someNodesSelected) {
      this.selectedSync = items.map(node => node.item);
    } else {
      this.selectedSync = [];
    }
  }

  private onSortChanged(item: Sort) {
    let sort = this.options.sort
    if (sort?.field != item.field) {
      sort = item;
      sort.isAscending = true;
    } else {
      sort.isAscending = !sort.isAscending;
    }
    Vue.set(this.options, "sort", sort);
  }

  private getSortDirIcon(item: Sort): string | null {
    if (item.field != this.options.sort?.field) return null;
    return this.options.sort?.isAscending ? "mdi-arrow-up" : "mdi-arrow-down";
  }

  @Emit("item-clicked")
  private onItemClicked(item: any) {
    return item;
  }
}

</script>

<style scoped lang="scss">
</style>