// The login token lives in a client cookie. It is scoped to the site's base domain
// (watdenktnl.nl) so that it is shared by watdenktnl.nl and www.watdenktnl.nl:
// Stripe and other external redirects may return the user on either host.
const NAME = "token";

function baseDomain(): string | null {
  const host = window.location.hostname;
  if (host === "localhost" || /^[\d.]+$/.test(host) || host.includes(":") || !host.includes(".")) return null;
  return host.replace(/^www\./, "");
}

function write(value: string, maxAge: number, domain: string | null) {
  const parts = [
    `${NAME}=${encodeURIComponent(value)}`,
    "Path=/",
    `Max-Age=${maxAge}`,
    "SameSite=Lax",
  ];
  if (domain) parts.push(`Domain=${domain}`);
  if (window.location.protocol === "https:") parts.push("Secure");
  document.cookie = parts.join("; ");
}

export function readSessionCookie(): string | null {
  for (const part of document.cookie.split(";")) {
    const [key, ...rest] = part.trim().split("=");
    if (key === NAME && rest.length) {
      const value = decodeURIComponent(rest.join("="));
      if (value) return value;
    }
  }
  return null;
}

export function writeSessionCookie(value: string, maxAge: number) {
  // Drop an older host-only cookie first so only the shared one remains.
  write("", 0, null);
  write(value, maxAge, baseDomain());
}

export function clearSessionCookie() {
  write("", 0, null);
  const domain = baseDomain();
  if (domain) write("", 0, domain);
}
