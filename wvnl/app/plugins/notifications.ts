import { useNotificationStore } from "~/stores/notifications";
import type { NotificationPayload } from "~/stores/notifications";

declare module "#app" {
  interface NuxtApp {
    $notify: (notification: NotificationPayload) => number;
  }
  interface NuxtPluginInjections {
    notify: (notification: NotificationPayload) => number;
  }
}

declare module "vue" {
  interface ComponentCustomProperties {
    $notify: (notification: NotificationPayload) => number;
  }
}

export default defineNuxtPlugin((nuxtApp) => {
  const store = useNotificationStore(nuxtApp.$pinia);

  const notify = (notification: NotificationPayload) =>
    store.addNotification(notification);

  nuxtApp.provide("notify", notify);
});
