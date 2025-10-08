import { storeToRefs } from "pinia";
import type { Ref } from "vue";
import { useNotificationStore } from "~/stores/notifications";
import type {
  Notification,
  NotificationPayload,
  NotificationType,
} from "~/stores/notifications";

export interface UseNotificationsResult {
  notifications: Ref<Notification[]>;
  addNotification: (notification: NotificationPayload) => number;
  removeNotification: (id: number) => void;
  createNotification: (
    message: string,
    type?: NotificationType,
    timeout?: number
  ) => number;
}

export function useNotifications(): UseNotificationsResult {
  const store = useNotificationStore();
  const { notifications } = storeToRefs(store);

  function createNotification(
    message: string,
    type: NotificationType = "info",
    timeout?: number
  ) {
    return store.addNotification({
      message,
      type,
      timeout,
    });
  }

  return {
    notifications,
    addNotification: store.addNotification,
    removeNotification: store.removeNotification,
    createNotification,
  };
}

export function addNotification(notification: NotificationPayload): number {
  const store = useNotificationStore();
  return store.addNotification(notification);
}

export function notify(
  message: string,
  type: NotificationType = "info",
  timeout?: number
): number {
  const store = useNotificationStore();
  return store.addNotification({
    message,
    type,
    timeout,
  });
}
