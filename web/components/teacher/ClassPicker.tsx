"use client";

import { useEffect } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import useSWR from "swr";
import { fetcher } from "@/lib/fetcher";
import type { TeacherClass, TeacherClassesResponse } from "@/lib/api";

/**
 * The teacher's assigned classes (FR-09.1) and which one is chosen. The choice
 * lives in the URL (?class=3), so a link from the dashboard opens the right class
 * and a refresh keeps it.
 */
export function useTeacherClass() {
  const router = useRouter();
  const pathname = usePathname();
  const params = useSearchParams();
  const { data, isLoading } = useSWR<TeacherClassesResponse>("/teacher/classes", fetcher);

  const classes: TeacherClass[] = data?.classes ?? [];
  const wanted = Number(params.get("class"));
  const selected = classes.find(c => c.id === wanted) ?? classes[0] ?? null;

  function select(id: number) {
    const next = new URLSearchParams(params.toString());
    next.set("class", String(id));
    router.replace(`${pathname}?${next.toString()}`, { scroll: false });
  }

  // Put the default class into the URL, so links copied from here keep it.
  useEffect(() => {
    if (selected && wanted !== selected.id) select(selected.id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selected?.id]);

  return { classes, selected, select, isLoading };
}

export default function ClassPicker({
  classes, selected, onSelect,
}: { classes: TeacherClass[]; selected: TeacherClass | null; onSelect: (id: number) => void }) {
  if (classes.length <= 1) {
    return selected
      ? <span className="font-fredoka font-600 text-ink">{selected.title}</span>
      : null;
  }

  return (
    <label className="inline-flex items-center gap-2">
      <span className="text-xs font-800 text-sub uppercase tracking-wide">Class</span>
      <select value={selected?.id ?? ""} onChange={e => onSelect(Number(e.target.value))}
              className="px-3 py-2 rounded-xl bg-white border-2 border-blue/20 focus:border-blue outline-none
                         font-nunito font-800 text-sm text-ink">
        {classes.map(c => <option key={c.id} value={c.id}>{c.title}</option>)}
      </select>
    </label>
  );
}

/** Shown when the teacher has no class yet: only the administrator can assign one (FR-09.1). */
export function NoClasses() {
  return (
    <div className="bg-white rounded-card shadow-card p-8 text-center">
      <p className="text-5xl">🏫</p>
      <p className="font-fredoka font-600 text-lg text-ink mt-3">No class assigned yet</p>
      <p className="text-sm text-sub font-700 mt-1">
        Ask your school administrator to assign you to a class. You will see your students here.
      </p>
    </div>
  );
}
