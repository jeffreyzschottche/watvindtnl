export function adMode(demo: boolean, enabled: boolean, providerReady: boolean): "demo" | "live" | "off" {
  if (demo) return "demo";
  return enabled && providerReady ? "live" : "off";
}

/** Placements that show a live AdSense unit. Never on auth, payment, report or privacy pages. */
export const LIVE_AD_PLACEMENTS = [
  "home-tussen-secties",
  "spel-onder-stelling",
  "spel-pauze",
  "overzicht",
  "profiel-overzicht",
] as const;
export function isLivePlacement(placement: string): boolean {
  return (LIVE_AD_PLACEMENTS as readonly string[]).includes(placement);
}
