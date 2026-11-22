const SCRIPT_ID = "wdnl-adsense";

/** Loads the AdSense tag once; Google's consent message (CMP) comes with it. */
export function loadAdSense(client: string): void {
  if (document.getElementById(SCRIPT_ID)) return;
  const script = document.createElement("script");
  script.id = SCRIPT_ID;
  script.async = true;
  script.crossOrigin = "anonymous";
  script.src = `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${encodeURIComponent(client)}`;
  document.head.appendChild(script);
}

/** Reopens Google's consent message so a visitor can change or withdraw their choice. */
export function openAdConsent(client: string): void {
  const w = window as unknown as {
    googlefc?: { callbackQueue?: unknown[]; showRevocationMessage?: () => void };
  };
  w.googlefc = w.googlefc || {};
  w.googlefc.callbackQueue = w.googlefc.callbackQueue || [];
  w.googlefc.callbackQueue.push(() => w.googlefc?.showRevocationMessage?.());
  loadAdSense(client);
}
