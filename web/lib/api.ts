const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export class ApiError extends Error {
  constructor(public status: number, public code: string | null, message: string) {
    super(message);
  }
}

function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("bb_token");
}

export function clearToken() {
  if (typeof window !== "undefined") {
    localStorage.removeItem("bb_token");
    localStorage.removeItem("bb_user");
  }
}

export function saveToken(token: string, user: unknown) {
  if (typeof window !== "undefined") {
    localStorage.setItem("bb_token", token);
    localStorage.setItem("bb_user", JSON.stringify(user));
  }
}

async function request<T>(
  method: string,
  path: string,
  body?: unknown,
  options?: { skipAuth?: boolean }
): Promise<T> {
  const token = getToken();
  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  if (token && !options?.skipAuth) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const res = await fetch(`${BASE}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (res.status === 401) {
    clearToken();
    if (typeof window !== "undefined") {
      window.location.href = "/login";
    }
    throw new ApiError(401, "unauthenticated", "Session expired. Please log in again.");
  }

  if (!res.ok) {
    let message = "Something went wrong";
    let code: string | null = null;
    try {
      const data = await res.json();
      message = data.message ?? message;
      code = data.code ?? null;
    } catch {}
    throw new ApiError(res.status, code, message);
  }

  if (res.status === 204) return undefined as T;

  return res.json() as Promise<T>;
}

export const api = {
  get:    <T>(path: string) => request<T>("GET", path),
  post:   <T>(path: string, body?: unknown, opts?: { skipAuth?: boolean }) =>
    request<T>("POST", path, body, opts),
  patch:  <T>(path: string, body?: unknown) => request<T>("PATCH", path, body),
  delete: <T>(path: string) => request<T>("DELETE", path),
};

// ─── User / Auth ──────────────────────────────────────────────────────────────

export interface User {
  id: number;
  display_name: string;
  email: string;
  role: string;
  avatar_key: string;
  total_points: number;
  rank_level: number;
  rank_name: string;
  safety_tier_passed: number;
  streak_days: number;
  school?: string;
  class?: { id: number; name: string };
}

export interface LoginResponse {
  token: string;
  expires_at: string;
  home: string;
  user: User;
}

// ─── Profile ──────────────────────────────────────────────────────────────────

export interface Profile {
  display_name: string;
  avatar_key: string;
  total_points: number;
  rank_level: number;
  rank_name: string;
  streak_days: number;
  safety_tier_passed: number;
  experiments_completed: number;
  badges: Badge[];
  /** FR-06.3: how many badges exist to earn. */
  badges_total?: number;
  certificate: Certificate | null;
  /** The class title, e.g. "Class 10-A". */
  class: string | null;
}

/** FR-06.3: a badge as the server names it. */
export interface Badge {
  code: string;
  name: string;
  icon: string;
  /** What earns it, in one line. */
  description?: string;
}

// ─── Daily missions (FR-07.3) ────────────────────────────────────────────────

export interface Mission {
  code: string;
  title: string;
  icon: string;
  target: number;
  progress: number;
  completed: boolean;
}

export interface MissionsResponse {
  /** The mission day, in Pakistan time. */
  date: string;
  /** When the next set arrives: midnight Pakistan time, as an ISO date. */
  resets_at: string;
  missions: Mission[];
}

// ─── Safety ───────────────────────────────────────────────────────────────────

export interface SafetyTier {
  tier_number: number;
  title: string;
  available: boolean;
  passed: boolean;
  best_score: number | null;
  question_count: number;
  pass_mark: number;
}

export interface SafetyTiersResponse {
  tiers: SafetyTier[];
  certificate: Certificate | null;
  highest_passed: number;
}

export interface SafetyStartResponse {
  tier_number: number;
  title: string;
  /** Identifies this attempt's option shuffle; must be sent back with the answers. */
  _safety_session: string;
  questions: SafetyQuestion[];
}

export interface SafetyQuestion {
  id: number;
  prompt: string;
  options: string[];
}

export interface SafetySubmitResponse {
  score: number;
  passed: boolean;
  pass_mark: number;
  points_awarded: number;
  may_reattempt: boolean;
  next_tier_available: boolean;
  certificate: Certificate | null;
  badges_awarded?: Badge[];
}

export interface Certificate {
  id?: number;
  issued_at?: string;
  tier?: number;
}

// ─── Experiments ──────────────────────────────────────────────────────────────

export interface ExperimentSummary {
  id: number;
  slug: string;
  title: string;
  topic: string;
  difficulty: "easy" | "medium" | "hard";
  emoji: string;
  card_gradient: string;
  base_points: number;
  estimated_minutes: number;
  step_count: number;
  ar_available: boolean;
  completed: boolean;
  locked: boolean;
  lock_reason: string | null;
  lock_code: string | null;
}

export interface ExperimentsListResponse {
  topics: string[];
  experiments: ExperimentSummary[];
}

/** docs/experiment-engine.md: the nine step kinds. */
export type StepKind = "gear" | "place" | "choose" | "measure" | "add_until" | "heat" | "read" | "observe" | "confirm";

/** A step's config as the server sends it: the answers are never in it (FR-02.5). */
export interface StepConfig {
  items?: string[];
  zones?: string[];
  target?: string | number;
  simultaneous?: boolean;
  unit?: string;
  min?: number;
  max?: number;
  step?: number;
  increments?: number[];
  end?: number;
  visual?: { from: string; to: string; fade_from: number };
  levels?: string[];
  options?: Record<string, string>;
}

export interface Step {
  id: number;
  step_order: number;
  title: string;
  instruction_text: string;
  item_emoji: string | null;
  action_label: string | null;
  kind: StepKind;
  config: StepConfig;
  intro: string | null;
}

export type Mood = "point" | "happy" | "think" | "oops" | "worried" | "party";

/** POST /sessions/{session}/steps/{step}/actions */
export interface StepActionResponse {
  outcome: "correct" | "partial" | "mistake" | "revealed";
  category: "procedure" | "safety" | "reagent" | "concept" | null;
  mistake_code: string | null;
  message: string | null;
  mood: Mood;
  step_complete: boolean;
  mistakes: number;
  progress: { worn?: string[]; placed?: Record<string, string>; stage?: number };
  reveal: Record<string, unknown> | null;
  steps_completed: number;
  step_count: number;
  quiz_unlocked: boolean;
  /** FR-06.3: badges this move earned. */
  badges_awarded?: Badge[];
}

export interface ResumeInfo {
  session_id: number;
  steps_completed: number;
  completed_step_ids: number[];
  presentation_mode: string;
  /** NFR-05: progress inside the current step. */
  step_state: { step_id: number; mistakes: number; progress: StepActionResponse["progress"] } | null;
}

export interface ExperimentDetailResponse {
  experiment: {
    id: number;
    slug: string;
    title: string;
    topic: string;
    difficulty: "easy" | "medium" | "hard";
    emoji: string;
    card_gradient: string;
    chemical_equation: string | null;
    explanatory_note: string | null;
    base_points: number;
    estimated_minutes: number;
    ar_available: boolean;
    required_safety_tier: number;
  };
  access: { allowed: boolean; code: string | null; reason: string | null };
  steps: Step[];
  resume: ResumeInfo | null;
}

export interface SessionData {
  id: number;
  user_id: number;
  experiment_id: number;
  presentation_mode: string;
  started_at: string;
  steps_completed: number;
  completed_step_ids: number[];
}

export interface StartSessionResponse {
  session: SessionData;
}

export interface StepProgressResponse {
  session: SessionData;
  steps_completed: number;
  step_count: number;
  quiz_unlocked: boolean;
}

export interface GuidanceResponse {
  text: string;
  source: "ai" | "preauthored";
  reason?: string;
}

// ─── Quiz ─────────────────────────────────────────────────────────────────────

export interface QuizResponse {
  questions: QuizQuestion[];
}

export interface QuizQuestion {
  id: number;
  prompt: string;
  options: string[];
}

export interface QuizSubmitResponse {
  score: number;
  passed: boolean;
  pass_mark: number;
  points_awarded: number;
  feedback: Record<string, {
    correct: boolean;
    explanation?: string;
    correct_option?: number;
  }>;
}

// ─── Leaderboard ─────────────────────────────────────────────────────────────

export interface LeaderboardRow {
  position: number;
  display_name: string;
  avatar_key: string;
  rank_level: number;
  rank_name: string;
  total_points: number;
  is_me: boolean;
}

export interface LeaderboardResponse {
  me: LeaderboardRow;
  rows: LeaderboardRow[];
  scope: string;
}

// ─── Teacher (M3) ─────────────────────────────────────────────────────────────
// Shapes of the /teacher/* responses. FR-09.1: the server only ever returns
// classes the signed-in teacher is assigned to.

export interface TeacherClass {
  id: number;
  title: string;
  grade_level: number;
  academic_year: string;
  student_count: number;
  avg_xp: number;
  certified_count: number;
}

export interface TeacherClassesResponse {
  classes: TeacherClass[];
}

export interface AttentionStudent {
  id: number;
  display_name: string;
  avatar_key: string | null;
  class_id: number;
  class_title: string;
  reasons: string[];
}

export interface TeacherDashboardResponse {
  stats: { students: number; avg_xp: number; certified: number; ready_for_lab: number };
  classes: TeacherClass[];
  attention: AttentionStudent[];
}

/** FR-09.2: one row of the class monitor. */
export interface ClassStudent {
  id: number;
  display_name: string;
  avatar_key: string | null;
  total_points: number;
  rank_level: number;
  rank_name: string;
  experiments_completed: number;
  safety_tier_passed: number;
  has_certificate: boolean;
  readiness: ReadinessClassification | null;
  last_quiz_score: number | null;
  last_active_on: string | null;
}

export interface ClassMonitorResponse {
  class: { id: number; title: string };
  students: ClassStudent[];
}

export interface SafetyReviewStudent {
  id: number;
  display_name: string;
  avatar_key: string | null;
  tier1_passed: boolean;
  tier1_score: number | null;
  tier2_passed: boolean;
  tier2_score: number | null;
  tier3_passed: boolean;
  tier3_score: number | null;
  tier4_passed: boolean;
  tier4_score: number | null;
  certificate: string | null;
  certificate_issued_on: string | null;
}

export interface SafetyReviewResponse {
  class: { id: number; title: string };
  summary: { certified: number; total: number };
  students: SafetyReviewStudent[];
}

export type AssignmentState = "assigned" | "in_progress" | "completed" | "overdue";

export interface TeacherTask {
  id: number;
  title: string;
  experiment_title: string;
  experiment_emoji: string | null;
  class_id: number;
  class_title: string;
  due_at: string;
  instructions: string | null;
  assigned: number;
  in_progress: number;
  completed: number;
  overdue: number;
  total: number;
}

export interface TasksResponse {
  tasks: TeacherTask[];
}

export interface TaskDetailResponse {
  task: {
    id: number; title: string; experiment_title: string; experiment_emoji: string | null;
    class_title: string; due_at: string; instructions: string | null;
  };
  students: {
    id: number; display_name: string; avatar_key: string | null;
    state: AssignmentState; state_changed_at: string | null;
  }[];
}

export interface AssignableExperiment {
  id: number;
  title: string;
  emoji: string | null;
  grade_level: number;
  topic: string;
}

export interface AssignableExperimentsResponse {
  experiments: AssignableExperiment[];
}

export interface SentNotification {
  notification_type: string;
  subject: string;
  body: string;
  class_title: string | null;
  recipients: number;
  created_at: string;
}

export interface SentNotificationsResponse {
  notifications: SentNotification[];
}

// ─── Readiness ────────────────────────────────────────────────────────────────

export type ReadinessClassification = "ready" | "developing" | "not_ready";

export interface ReadinessDimensions {
  procedural_accuracy: number;
  safety_compliance: number;
  reagent_management: number;
  time_efficiency: number;
  conceptual_understanding: number;
  error_recovery: number;
}

export interface ReadinessReport {
  id: number;
  generated_at: string;
  ai_generated: boolean;
  classification: ReadinessClassification;
  narrative: string;
  dimensions: ReadinessDimensions;
  /** Dimensions estimated from session and quiz history until the experiment engine records each step. */
  estimated: (keyof ReadinessDimensions)[];
  student: { id: number; display_name: string; avatar_key: string | null };
}

export interface ClassReadinessStudent {
  id: number;
  display_name: string;
  avatar_key: string | null;
  completed: number;
  report: ReadinessReport | null;
}

export interface ClassReadinessResponse {
  class: { id: number; title: string };
  required: number;
  students: ClassReadinessStudent[];
}

export interface ReadinessReportResponse {
  report: ReadinessReport;
}

export interface ReadinessNotEligibleError {
  code: "not_eligible";
  message: string;
  completed: number;
  required: number;
}

// ─── Notifications (M7) ───────────────────────────────────────────────────────

export interface StudentNotification {
  id: number;
  notification_type: "task" | "reminder" | "safety_alert" | "announcement";
  subject: string;
  body: string;
  created_at: string;
  read_at: string | null;
}

export interface NotificationsResponse {
  notifications: StudentNotification[];
  unread_count: number;
}
