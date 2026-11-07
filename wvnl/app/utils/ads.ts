export function adMode(demo: boolean, enabled: boolean, providerReady: boolean): "demo" | "live" | "off" {
  if (demo) return "demo";
  return enabled && providerReady ? "live" : "off";
}
