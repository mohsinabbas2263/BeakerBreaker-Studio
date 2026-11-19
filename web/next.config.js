/** @type {import('next').NextConfig} */

// SR-02, SR-03: browser-side protections for the portal. The API sets its own
// headers (backend SecurityHeaders); these cover the pages Vercel serves.
const api = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";
const apiOrigin = new URL(api).origin;
const dev = process.env.NODE_ENV !== "production";

const csp = [
  "default-src 'self'",
  // Next.js writes small inline scripts for hydration; dev mode also needs eval.
  `script-src 'self' 'unsafe-inline'${dev ? " 'unsafe-eval'" : ""}`,
  "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
  "font-src 'self' data: https://fonts.gstatic.com",
  `img-src 'self' data: blob: ${apiOrigin}`,
  `connect-src 'self' ${apiOrigin}${dev ? " ws:" : ""}`,
  "worker-src 'self' blob:",
  `media-src 'self' blob: data: ${apiOrigin}`,
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
].join("; ");

const securityHeaders = [
  { key: "Content-Security-Policy", value: csp },
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "X-Frame-Options", value: "DENY" },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  // AR Mode (FR-05) needs the camera and WebXR on this site only; nothing else.
  { key: "Permissions-Policy", value: "camera=(self), xr-spatial-tracking=(self), microphone=(), geolocation=(), payment=()" },
  ...(dev ? [] : [{ key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains" }]),
];

const nextConfig = {
  images: {
    unoptimized: true,
  },
  poweredByHeader: false,
  async headers() {
    return [{ source: "/:path*", headers: securityHeaders }];
  },
};

module.exports = nextConfig;
