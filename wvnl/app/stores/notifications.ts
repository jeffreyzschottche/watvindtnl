import { ref } from "vue";
import { defineStore } from "pinia";

export type NotificationType = "info" | "success" | "error";

export interface Notification {
  id: number;
  message: string;
  type: NotificationType;
  timeout?: number;
}

let nextId = 1;

export const useNotificationStore = defineStore("notifications", () => {
  const notifications = ref<Notification[]>([]);

  function addNotification(
    notification: Omit<Notification, "id"> & { id?: number }
  ): number {
    const id = notification.id ?? nextId++;
    const entry: Notification = {
      timeout: 4000,
      ...notification,
      id,
    };
    notifications.value.push(entry);

    if (entry.timeout !== 0) {
      setTimeout(() => removeNotification(id), entry.timeout);
    }

    return id;
  }

  function removeNotification(id: number) {
    notifications.value = notifications.value.filter((item) => item.id !== id);
  }

  return {
    notifications,
    addNotification,
    removeNotification,
  };
});
