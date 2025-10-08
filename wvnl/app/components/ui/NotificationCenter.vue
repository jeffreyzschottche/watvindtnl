<template>
  <Teleport to="body">
    <div class="notification-center" role="status" aria-live="polite">
      <transition-group name="notification" tag="div">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification"
          :class="`notification--${notification.type}`"
        >
          <p class="notification__message">{{ notification.message }}</p>
          <button
            type="button"
            class="notification__close"
            aria-label="Sluit melding"
            @click="remove(notification.id)"
          >
            ×
          </button>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useNotifications } from "~/composables/useNotifications";

const { notifications, removeNotification } = useNotifications();

function remove(id: number) {
  removeNotification(id);
}
</script>

<style scoped>
.notification-center {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 2000;
  display: grid;
  gap: 0.75rem;
  pointer-events: none;
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.2s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.notification {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 280px;
  max-width: 360px;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background: #1e293b;
  color: #ffffff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.25);
  pointer-events: auto;
}

.notification--success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.notification--error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.notification--info {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.notification__message {
  flex: 1;
  margin: 0;
  font-weight: 500;
}

.notification__close {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border: none;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.25);
  color: inherit;
  font-size: 1.1rem;
  cursor: pointer;
}

.notification__close:hover {
  background: rgba(15, 23, 42, 0.4);
}
</style>
