import type { Metadata, Viewport } from "next";
import "./globals.css";
import { ToastHost } from "@/components/ui/Toast";

export const metadata: Metadata = {
  title: "Beaker Breakers Studio",
  description: "AI-powered gamified virtual chemistry laboratory for secondary schools",
  manifest: "/manifest.json",
  icons: { icon: "/assets/logo.png", apple: "/assets/logo.png" },
};

export const viewport: Viewport = {
  themeColor: "#4FC3F7",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        {children}
        <ToastHost />
      </body>
    </html>
  );
}
