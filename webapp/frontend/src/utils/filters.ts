import Vue from "vue";
import { formatDate } from "./dateUtils";

Vue.filter("formatDate", function(value: Date, format: string) {
  return format ? formatDate(value, format) : formatDate(value);
});
