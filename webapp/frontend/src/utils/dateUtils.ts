import moment from "moment";

const SIMPLE_DATE_FORMAT = "YYYY-MM-DD";

export function formatDate(date: Date, format: string = SIMPLE_DATE_FORMAT) {
  return moment(date).format(format);
}

export function isExpired(date: Date) {
  const interval = date.getTime() - new Date().getTime();
  return interval < 0;
}

export function expiredAtInterval(date: Date) {
  const interval = date.getTime() - new Date().getTime();
  return interval < 0 ? "expired" : "expires " + moment.duration(interval).humanize(true);
}
