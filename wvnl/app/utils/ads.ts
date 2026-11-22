export function adMode(demo: boolean, enabled: boolean, providerReady: boolean): "demo" | "live" | "off" {
  if (demo) return "demo";
  return enabled && providerReady ? "live" : "off";
}

/** Live ads start on the homepage only; add placements here once consent is verified on the live site. */
export const LIVE_AD_PLACEMENTS = ["home-tussen-secties"] as const;
export function isLivePlacement(placement: string): boolean {
  return (LIVE_AD_PLACEMENTS as readonly string[]).includes(placement);
}
