import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Beaker Breakers Studio",
  description: "AI-powered gamified virtual chemistry laboratory for secondary schools",
  manifest: "/manifest.json",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
