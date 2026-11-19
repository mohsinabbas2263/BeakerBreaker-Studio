import type { Badge } from "@/lib/api";

type Toast = (message: string, kind?: "success" | "error" | "info") => void;

/**
 * FR-06.3: tell the student about each badge a move, quiz or safety tier just
 * earned. The server decides; this only says so.
 */
export function announceBadges(badges: Badge[] | undefined, toast: Toast) {
  (badges ?? []).forEach((b, i) => {
    setTimeout(() => toast(`New badge! ${b.icon} ${b.name}`, "success"), 600 + i * 1400);
  });
}
