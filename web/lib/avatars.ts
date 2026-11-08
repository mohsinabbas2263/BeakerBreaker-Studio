// The four profile avatars from the prototype. The keys match User::AVATARS on the
// server, which refuses any other value (PATCH /profile).

export const AVATARS: { key: string; label: string; src: string }[] = [
  { key: "avatar-girl1", label: "Girl 1", src: "/avatars/avatar-girl1.png" },
  { key: "avatar-girl2", label: "Girl 2", src: "/avatars/avatar-girl2.png" },
  { key: "avatar-boy1",  label: "Boy 1",  src: "/avatars/avatar-boy1.png" },
  { key: "avatar-boy2",  label: "Boy 2",  src: "/avatars/avatar-boy2.png" },
];

/** Image for an avatar key; an unset or unknown key falls back to the first avatar. */
export function avatarSrc(key?: string | null): string {
  return AVATARS.find(a => a.key === key)?.src ?? AVATARS[0].src;
}
