"use client";

import { Suspense, useEffect, useState } from "react";
import Image from "next/image";
import useSWR, { mutate } from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import ClassPicker, { NoClasses, useTeacherClass } from "@/components/teacher/ClassPicker";
import { useToast } from "@/components/ui/Toast";
import { api, ApiError } from "@/lib/api";
import type {
  AssignableExperimentsResponse, AssignmentState, ClassMonitorResponse, TaskDetailResponse, TasksResponse,
} from "@/lib/api";
import { avatarSrc } from "@/lib/avatars";
import { fetcher } from "@/lib/fetcher";

/**
 * wf_S13. FR-09.4: assign an experiment to one student, a group or the whole class
 * with a future due date. FR-09.5: the states move by themselves; this page shows
 * them, refreshed every 5 seconds. FR-09.6: the server notifies each student.
 */
const STATE: Record<AssignmentState, { label: string; cls: string }> = {
  assigned:    { label: "Assigned",    cls: "bg-sky text-blue-d" },
  in_progress: { label: "In Progress", cls: "bg-lemon text-ink" },
  completed:   { label: "Completed",   cls: "bg-mint text-green-d" },
  overdue:     { label: "Overdue",     cls: "bg-coral text-ink" },
};

/** A datetime-local value for "tomorrow at 9:00", in the teacher's own time zone. */
function defaultDue(): string {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  d.setHours(9, 0, 0, 0);
  return toLocalInput(d);
}

function toLocalInput(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function formatDue(iso: string): string {
  return new Date(iso).toLocaleString(undefined, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" });
}

function CreateTask({ classId }: { classId: number }) {
  const toast = useToast();
  const { data: exps } = useSWR<AssignableExperimentsResponse>(`/teacher/classes/${classId}/experiments`, fetcher);
  const { data: roster } = useSWR<ClassMonitorResponse>(`/teacher/classes/${classId}/students`, fetcher);

  const [experimentId, setExperimentId] = useState("");
  const [target, setTarget] = useState<"class" | "students">("class");
  const [picked, setPicked] = useState<number[]>([]);
  const [due, setDue] = useState(defaultDue);
  const [instructions, setInstructions] = useState("");
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<string | null>(null);

  // A new class has a different roster and experiment list.
  useEffect(() => { setExperimentId(""); setPicked([]); setErrors(null); }, [classId]);

  const students = roster?.students ?? [];
  const toggle = (id: number) => setPicked(p => (p.includes(id) ? p.filter(x => x !== id) : [...p, id]));

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setErrors(null);
    if (!experimentId) { setErrors("Choose an experiment."); return; }
    if (target === "students" && picked.length === 0) { setErrors("Choose at least one student."); return; }
    const dueDate = new Date(due);
    if (Number.isNaN(dueDate.getTime()) || dueDate <= new Date()) {
      setErrors("The due date must be in the future.");
      return;
    }
    setBusy(true);
    try {
      const res = await api.post<{ task: { assigned: number } }>("/teacher/tasks", {
        class_id: classId,
        experiment_id: Number(experimentId),
        due_at: dueDate.toISOString(),
        target,
        student_ids: target === "students" ? picked : undefined,
        instructions: instructions.trim() || undefined,
      });
      toast(`Task sent to ${res.task.assigned} student${res.task.assigned === 1 ? "" : "s"}! 📝`, "success");
      setExperimentId(""); setPicked([]); setInstructions(""); setDue(defaultDue());
      await mutate("/teacher/tasks");
    } catch (err) {
      setErrors(err instanceof ApiError || err instanceof Error ? err.message : "Could not create the task.");
    } finally {
      setBusy(false);
    }
  }

  const field = "w-full px-3 py-2.5 rounded-xl bg-sky/40 border-2 border-transparent focus:border-blue outline-none font-nunito font-700 text-sm text-ink";

  return (
    <form onSubmit={submit} className="bg-white rounded-card shadow-card p-5 space-y-4" noValidate>
      <h2 className="font-fredoka font-600 text-lg text-ink">Create task</h2>

      <label className="block">
        <span className="block text-xs font-800 text-sub mb-1">Experiment</span>
        <select value={experimentId} onChange={e => setExperimentId(e.target.value)} className={field} required>
          <option value="">{exps ? "Choose an experiment" : "Loading…"}</option>
          {exps?.experiments.map(x => (
            <option key={x.id} value={x.id}>{x.emoji ? `${x.emoji} ` : ""}{x.title} (Class {x.grade_level})</option>
          ))}
        </select>
        {exps && exps.experiments.length === 0 && (
          <span className="block text-[11px] text-orange-d font-700 mt-1">
            No experiments are published for this class&apos;s licence yet.
          </span>
        )}
      </label>

      <fieldset>
        <legend className="block text-xs font-800 text-sub mb-1">Who</legend>
        <div className="flex gap-1 bg-sky/40 rounded-xl p-1 w-fit">
          {(["class", "students"] as const).map(t => (
            <button key={t} type="button" onClick={() => setTarget(t)} aria-pressed={target === t}
                    className={`px-3 py-1.5 rounded-lg text-xs font-800 ${target === t ? "bg-white text-blue-d shadow-sm" : "text-sub"}`}>
              {t === "class" ? `Whole class (${students.length})` : "Choose students"}
            </button>
          ))}
        </div>
        {target === "students" && (
          <div className="mt-2 max-h-48 overflow-y-auto grid sm:grid-cols-2 gap-1.5 p-1">
            {students.map(s => (
              <label key={s.id}
                     className={`flex items-center gap-2 px-2.5 py-1.5 rounded-xl cursor-pointer border-2 text-sm font-800
                                 ${picked.includes(s.id) ? "border-green bg-mint/50" : "border-transparent bg-sky/30"}`}>
                <input type="checkbox" checked={picked.includes(s.id)} onChange={() => toggle(s.id)} className="accent-green" />
                <Image src={avatarSrc(s.avatar_key)} alt="" width={22} height={22} className="rounded-full" />
                <span className="truncate text-ink">{s.display_name}</span>
              </label>
            ))}
            {students.length === 0 && <p className="text-xs text-sub font-700">No students in this class yet.</p>}
          </div>
        )}
      </fieldset>

      <label className="block">
        <span className="block text-xs font-800 text-sub mb-1">Due date</span>
        <input type="datetime-local" value={due} min={toLocalInput(new Date())} onChange={e => setDue(e.target.value)}
               className={field} required />
      </label>

      <label className="block">
        <span className="block text-xs font-800 text-sub mb-1">Instructions (optional)</span>
        <textarea value={instructions} onChange={e => setInstructions(e.target.value)} maxLength={500} rows={2}
                  className={`${field} resize-none`} placeholder="e.g. Read page 12 first." />
      </label>

      {errors && <p className="text-xs font-800 text-orange-d" role="alert">{errors}</p>}

      <button type="submit" disabled={busy}
              className="w-full py-3 rounded-2xl bg-green hover:bg-green-d text-white font-fredoka font-600 shadow-md
                         disabled:opacity-60 transition-colors">
        {busy ? "Assigning…" : "Assign"}
      </button>
    </form>
  );
}

function TaskDetail({ id }: { id: number }) {
  const { data } = useSWR<TaskDetailResponse>(`/teacher/tasks/${id}`, fetcher, { refreshInterval: 5000 });
  if (!data) return <p className="text-xs text-sub font-700 px-4 pb-3">Loading…</p>;

  return (
    <ul className="px-4 pb-4 grid sm:grid-cols-2 gap-1.5">
      {data.students.map(s => (
        <li key={s.id} className="flex items-center gap-2 bg-sky/30 rounded-xl px-2.5 py-1.5">
          <Image src={avatarSrc(s.avatar_key)} alt="" width={22} height={22} className="rounded-full" />
          <span className="flex-1 truncate text-sm font-800 text-ink">{s.display_name}</span>
          <span className={`px-2 py-0.5 rounded-full text-[11px] font-800 ${STATE[s.state].cls}`}>{STATE[s.state].label}</span>
        </li>
      ))}
    </ul>
  );
}

function TaskList() {
  const { data } = useSWR<TasksResponse>("/teacher/tasks", fetcher, { refreshInterval: 5000 });
  const [open, setOpen] = useState<number | null>(null);

  return (
    <section className="bg-white rounded-card shadow-card">
      <h2 className="font-fredoka font-600 text-lg text-ink p-5 pb-3">Current tasks</h2>
      {!data && <p className="text-sm text-sub font-700 px-5 pb-5">Loading…</p>}
      {data && data.tasks.length === 0 && (
        <p className="text-sm text-sub font-700 px-5 pb-8 text-center">No tasks yet. Create one to get started.</p>
      )}
      <ul>
        {data?.tasks.map(t => {
          const late = new Date(t.due_at) < new Date();
          return (
            <li key={t.id} className="border-t border-black/5">
              <button type="button" onClick={() => setOpen(o => (o === t.id ? null : t.id))} aria-expanded={open === t.id}
                      className="w-full text-left px-5 py-3 hover:bg-sky/30 transition-colors">
                <div className="flex items-start gap-3">
                  <span className="text-2xl" aria-hidden>{t.experiment_emoji ?? "🧪"}</span>
                  <div className="flex-1 min-w-0">
                    <p className="font-800 text-sm text-ink truncate">{t.experiment_title}</p>
                    <p className={`text-[11px] font-700 ${late ? "text-orange-d" : "text-sub"}`}>
                      {t.class_title} · due {formatDue(t.due_at)}
                    </p>
                    <div className="flex flex-wrap gap-1 mt-1.5">
                      {(["assigned", "in_progress", "completed", "overdue"] as AssignmentState[])
                        .filter(s => t[s] > 0)
                        .map(s => (
                          <span key={s} className={`px-2 py-0.5 rounded-full text-[11px] font-800 ${STATE[s].cls}`}>
                            {t[s]} {STATE[s].label}
                          </span>
                        ))}
                    </div>
                  </div>
                  <span className="text-xs font-800 text-sub whitespace-nowrap">
                    {t.completed}/{t.total} done
                  </span>
                </div>
              </button>
              {open === t.id && <TaskDetail id={t.id} />}
            </li>
          );
        })}
      </ul>
    </section>
  );
}

function Tasks() {
  const { classes, selected, select, isLoading } = useTeacherClass();

  if (isLoading) return <div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>;
  if (classes.length === 0 || !selected) return <NoClasses />;

  return (
    <>
      <div className="bg-white rounded-card shadow-card p-4">
        <ClassPicker classes={classes} selected={selected} onSelect={select} />
      </div>
      <div className="grid lg:grid-cols-[minmax(0,380px)_1fr] gap-5 items-start">
        <CreateTask classId={selected.id} />
        <TaskList />
      </div>
      <p className="text-[11px] text-muted font-700">
        Students are notified when you assign. States update by themselves when a student starts or finishes the experiment, or when the due date passes.
      </p>
    </>
  );
}

export default function TasksPage() {
  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-4">
        <h1 className="font-fredoka font-600 text-2xl text-ink">Tasks 📝</h1>
        <Suspense fallback={<div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>}>
          <Tasks />
        </Suspense>
      </div>
    </TeacherShell>
  );
}
