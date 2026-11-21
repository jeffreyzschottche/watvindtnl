<script setup lang="ts">
import BrandLogo from "~/components/brand/BrandLogo.vue";
import AppIcon from "~/components/ui/AppIcon.vue";
const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const open = ref(false);
const profileOpen = ref(false);
const profileMenu = ref<HTMLElement | null>(null);
const profileToggle = ref<HTMLButtonElement | null>(null);
const profileMenuId = useId();
watch(
  () => route.fullPath,
  () => {
    open.value = false;
    profileOpen.value = false;
  },
);
function showProfileOnHover(event: PointerEvent) {
  if (event.pointerType === "mouse") profileOpen.value = true;
}
function leaveProfileOnHover(event: PointerEvent) {
  if (
    event.pointerType === "mouse" &&
    !profileMenu.value?.contains(document.activeElement)
  ) {
    profileOpen.value = false;
  }
}
function closeProfileOutside(event: FocusEvent | PointerEvent) {
  const target =
    event.type === "focusout"
      ? (event as FocusEvent).relatedTarget
      : event.target;
  if (!(target instanceof Node) || !profileMenu.value?.contains(target))
    profileOpen.value = false;
}
function dismissProfile() {
  if (!profileOpen.value) return;
  profileOpen.value = false;
  profileToggle.value?.focus();
}
async function logout() {
  profileOpen.value = false;
  await auth.logout();
  await router.replace("/inloggen");
}
onMounted(() => document.addEventListener("pointerdown", closeProfileOutside));
onBeforeUnmount(() =>
  document.removeEventListener("pointerdown", closeProfileOutside),
);
const links = [
  { to: "/stellingen", label: "Stellingen" },
  { to: "/kompas", label: "Kompas" },
  { to: "/over-ons", label: "Over ons" },
];
const tabs = [
  { to: "/spel", label: "Spel", icon: "play" },
  { to: "/stellingen", label: "Overzicht", icon: "grid" },
  { to: "/kompas", label: "Kompas", icon: "compass" },
  { to: "/profiel", label: "Profiel", icon: "user" },
];
function activeTab(to: string) {
  if (to === "/profiel")
    return (
      route.path.startsWith("/profiel") ||
      route.path === "/profile" ||
      route.path === "/mijn-stemmen"
    );
  if (to === "/spel") return route.path === "/spel" || route.path === "/issues";
  if (to === "/kompas")
    return (
      route.path.startsWith("/kompas") || route.path === "/politiek-kompas"
    );
  return route.path === to || route.path.startsWith(to + "/");
}
const { showTabs } = useAppShell();
</script>
<template>
  <header class="site-header" :class="{ 'site-header--app': showTabs }">
    <div class="container nav-container">
      <NuxtLink to="/" aria-label="WatDenktNederland — home" class="brand"
        ><BrandLogo
      /></NuxtLink>
      <nav class="desktop-navigation" aria-label="Hoofdnavigatie">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="nav-link"
          >{{ link.label }}</NuxtLink
        >
      </nav>
      <div class="desktop-auth">
        <div
          v-if="auth.isLoggedIn"
          ref="profileMenu"
          class="nav-profile"
          @pointerenter="showProfileOnHover"
          @pointerleave="leaveProfileOnHover"
          @focusout="closeProfileOutside"
          @keydown.esc="dismissProfile"
        >
          <NuxtLink
            to="/profiel"
            class="nav-login"
            @click="profileOpen = false"
          >
            <AppIcon name="user" :size="17" /> Mijn profiel
          </NuxtLink>
          <button
            ref="profileToggle"
            class="nav-profile__toggle"
            type="button"
            aria-label="Profielmenu"
            :aria-expanded="profileOpen"
            :aria-controls="profileMenuId"
            @click="profileOpen = !profileOpen"
          >
            <AppIcon name="chevron" :size="13" />
          </button>
          <div
            v-show="profileOpen"
            :id="profileMenuId"
            class="nav-profile__dropdown"
          >
            <div class="nav-profile__panel">
              <button type="button" @click="logout">
                <AppIcon name="logout" :size="18" /> Uitloggen
              </button>
            </div>
          </div>
        </div>
        <NuxtLink v-else to="/inloggen" class="nav-login">Inloggen</NuxtLink>
        <NuxtLink to="/spel" class="button button-red nav-cta"
          >Begin met stemmen <AppIcon name="arrow" :size="16"
        /></NuxtLink>
      </div>
      <!-- In the app the tab bar is the only menu (Hick); info pages live under Profiel. -->
      <button
        class="mobile-menu-toggle"
        type="button"
        :aria-expanded="open"
        aria-controls="mobile-navigation"
        :aria-label="open ? 'Menu sluiten' : 'Menu openen'"
        @click="open = !open"
      >
        <AppIcon :name="open ? 'close' : 'menu'" />
      </button>
    </div>
    <nav
      v-if="open"
      id="mobile-navigation"
      class="mobile-navigation container"
      aria-label="Mobiel hoofdmenu"
      @keydown.esc="open = false"
    >
      <NuxtLink v-for="link in links" :key="link.to" :to="link.to"
        >{{ link.label }} <AppIcon name="chevron" :size="16"
      /></NuxtLink>
      <NuxtLink :to="auth.isLoggedIn ? '/profiel' : '/inloggen'"
        >{{ auth.isLoggedIn ? "Mijn profiel" : "Inloggen" }}
        <AppIcon name="user" :size="18"
      /></NuxtLink>
      <NuxtLink to="/spel" class="button button-red"
        >Begin met stemmen <AppIcon name="arrow" :size="17"
      /></NuxtLink>
    </nav>
  </header>
  <nav v-if="showTabs" class="app-tabbar" aria-label="Appnavigatie">
    <NuxtLink
      v-for="tab in tabs"
      :key="tab.to"
      :to="tab.to"
      :class="{ 'tab-active': activeTab(tab.to) }"
      :aria-current="activeTab(tab.to) ? 'location' : undefined"
      ><AppIcon :name="tab.icon" /><span>{{ tab.label }}</span></NuxtLink
    >
  </nav>
</template>
<style scoped>
/* Laws of UX, mobile app mode: one navigation (Hick, Jakob), a compact header so the task gets
   the screen (cognitive load), large thumb-zone tabs with a clear active state (Fitts, Von Restorff). */
@media (max-width: 720px) {
  .site-header--app .nav-container {
    min-height: 56px;
  }
  .site-header--app .mobile-menu-toggle {
    display: none;
  }
  .site-header--app :deep(.wdnl-logo img) {
    width: 29px;
    height: 33px;
  }
  .site-header--app :deep(.wdnl-logo__name) {
    font-size: 1.02rem;
  }
  .app-tabbar.app-tabbar {
    height: calc(var(--app-tabbar-h) + env(safe-area-inset-bottom));
    box-sizing: border-box;
    padding: 0.45rem 0.6rem max(0.45rem, env(safe-area-inset-bottom));
    align-items: stretch;
  }
  .app-tabbar.app-tabbar a {
    align-content: center;
    min-height: 48px;
    border-radius: 14px;
    font-size: 0.68rem;
    transition:
      background 0.2s,
      color 0.2s;
  }
  .app-tabbar.app-tabbar .tab-active {
    background: #edf3ff;
  }
  .app-tabbar.app-tabbar .tab-active::before {
    display: none;
  }
}
.site-header {
  background: #fff;
  border-bottom: 1px solid #e5eaf1;
  position: sticky;
  top: 0;
  z-index: 60;
}
.nav-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  min-height: 82px;
}
.brand {
  display: inline-flex;
  text-decoration: none;
}
.desktop-navigation,
.desktop-auth {
  display: flex;
  align-items: center;
  gap: 1.8rem;
}
.nav-link,
.nav-login {
  color: #4b5a71;
  font-size: 0.83rem;
  font-weight: 650;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 44px;
}
.nav-link:hover,
.nav-link.router-link-active,
.nav-login:hover {
  color: #0057b8;
}
.desktop-auth {
  gap: 1.4rem;
}
.nav-profile {
  position: relative;
  display: flex;
  align-items: center;
  color: #4b5a71;
}
.nav-profile__toggle {
  display: grid;
  place-items: center;
  width: 34px;
  min-height: 44px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: inherit;
  cursor: pointer;
}
.nav-profile__toggle svg {
  transform: rotate(90deg);
}
.nav-profile__toggle:hover,
.nav-profile__toggle[aria-expanded="true"] {
  color: #0057b8;
}
.nav-profile__dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  padding-top: 0.45rem;
  min-width: 170px;
}
.nav-profile__panel {
  padding: 0.35rem;
  background: #fff;
  border: 1px solid #dce3ee;
  border-radius: 12px;
  box-shadow: 0 12px 30px #001f4d20;
}
.nav-profile__panel button {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-height: 44px;
  padding: 0.45rem 0.7rem;
  width: 100%;
  border: 0;
  border-radius: 8px;
  color: #001f4d;
  background: transparent;
  font: inherit;
  font-size: 0.82rem;
  font-weight: 700;
  text-align: left;
  cursor: pointer;
}
.nav-profile__panel button:hover,
.nav-profile__panel button:focus-visible {
  background: #edf3ff;
  color: #0057b8;
}
.nav-cta {
  font-size: 0.8rem;
  min-height: 44px;
  border-radius: 8px;
  padding: 0.7rem 1rem;
}
.mobile-menu-toggle {
  display: none;
  background: #fff;
  border: 1px solid #dce3ee;
  border-radius: 9px;
  width: 44px;
  height: 44px;
  align-items: center;
  justify-content: center;
  color: #001f4d;
}
.mobile-navigation {
  display: grid;
  gap: 0.25rem;
  padding-block: 0.5rem 1.25rem;
}
.mobile-navigation > a:not(.button) {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.8rem 0;
  color: #001f4d;
  border-bottom: 1px solid #edf0f5;
}
.app-tabbar {
  display: none;
}
@media (max-width: 1020px) {
  .desktop-navigation {
    gap: 1rem;
  }
  .desktop-auth {
    gap: 1rem;
  }
  .nav-cta {
    display: none;
  }
}
@media (max-width: 720px) {
  .nav-container {
    min-height: 72px;
    gap: 0.5rem;
  }
  .desktop-navigation,
  .desktop-auth {
    display: none;
  }
  .mobile-menu-toggle {
    display: flex;
  }
  .app-tabbar {
    position: fixed;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.55rem 0.7rem max(0.55rem, env(safe-area-inset-bottom));
    border-top: 1px solid #e0e6ef;
    background: #fffffffa;
    backdrop-filter: blur(12px);
    z-index: 70;
  }
  .app-tabbar a {
    display: grid;
    justify-items: center;
    gap: 0.3rem;
    padding: 0.4rem;
    font-size: 0.65rem;
    color: #66758b;
    text-decoration: none;
  }
  .app-tabbar .router-link-active,
  .app-tabbar .tab-active {
    color: #0057b8;
    font-weight: 750;
  }
}

.desktop-navigation {
  gap: 0.35rem;
}
.desktop-navigation .nav-link {
  border-radius: 24px;
  padding: 0.55rem 0.85rem;
  transition:
    color 0.2s,
    background 0.2s;
  font-weight: 750;
}
.desktop-navigation .nav-link:hover,
.desktop-navigation .router-link-active {
  color: #0057b8;
  background: #edf3ff;
  text-decoration: none;
}
.nav-cta {
  border-radius: 12px;
  box-shadow:
    0 3px 0 #b30024,
    0 7px 15px #e1062e0d;
  font-size: 0.76rem;
}
.app-tabbar {
  box-shadow: 0 -8px 35px #001f4d0a;
}
.app-tabbar .tab-active {
  position: relative;
}
.app-tabbar .tab-active::before {
  content: "";
  position: absolute;
  top: -7px;
  width: 22px;
  height: 3px;
  border-radius: 4px;
  background: #0057b8;
}
</style>
