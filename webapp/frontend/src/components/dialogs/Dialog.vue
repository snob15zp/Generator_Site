<template>
     <v-dialog v-model="visible" max-width="500px">
      <v-card>
        <v-card-title class="headline">{{ title }}</v-card-title>
        <v-divider></v-divider>
        <v-card-text class="mt-4">
          {{ message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="onCancelClick">{{ $t("form.cancel") }}</v-btn>
          <v-btn color="blue darken-1" text @click="onOkClick">{{ $t("form.ok") }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
</template>

<script lang="ts">
import {Component, Emit, Prop, Ref, Vue, Watch} from "vue-property-decorator";

@Component
export default class Dialog extends Vue {
    @Prop({default:null}) readonly title?: string;
    @Prop({default:null}) readonly message?: string;

    private visible: boolean = false;

    show() {
        this.visible = true;
    }

    hide() {
        this.visible = false;
    }

    @Emit("cancel")
    private onCacncelClick() {
        this.hide();
        return false;
    }

    @Emit("ok")
    private onOnClick() {
        this.hide();
        return true;
    }

}
</script>