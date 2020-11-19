<template>
  <v-layout child-flex>
    <v-col lg="10" offset-lg="1">
      <UserProfileTable />
    </v-col>
  </v-layout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import UserProfileTable from "@/components/UserProfileTable.vue";

@Component({
  components: { UserProfileTable }
})
export default class Home extends Vue {}
</script>
