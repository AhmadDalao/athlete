export type ApiMeta = {
  version: string;
  generatedAt: string;
  message?: string;
};

export type ApiEnvelope<T> = {
  data: T;
  meta: ApiMeta;
};

export type UserRole = {
  name: string;
  label: string;
};

export type Viewer = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  avatarUrl?: string | null;
  roles: UserRole[];
  primaryRole?: 'athlete' | 'coach' | 'admin' | 'owner' | string | null;
};

export type LoginResponse = {
  token: string;
  tokenType: 'Bearer';
  tokenName: string;
  abilities: string[];
  expiresAt: string;
  user: Viewer;
};

export type WorkoutExercise = {
  name: string;
  prescription?: string | null;
  sets?: number | null;
  reps?: string | null;
  load?: string | null;
  restSeconds?: number | null;
  target?: string | null;
  note?: string | null;
  section?: string | null;
  supersetLabel?: string | null;
  mediaUrl?: string | null;
  movementType?: string | null;
};

export type MediaItem = {
  type: 'video' | 'image' | string;
  url: string;
  title?: string | null;
  isPrimary: boolean;
};

export type TrainingSessionSummary = {
  id: number;
  title: string;
  scheduledDate?: string | null;
  focus?: string | null;
  instructions?: string | null;
  videoUrl?: string | null;
  mediaItems?: MediaItem[];
  mediaCount?: number;
  exerciseCount?: number;
  exercisePreview?: string[];
  exercises?: WorkoutExercise[];
  completionStatus?: string;
  workoutLog?: Record<string, unknown> | null;
  program?: {
    id: number;
    title: string;
    goal?: string | null;
    status: string;
  };
  coach?: {
    id: number;
    name: string;
    email: string;
  };
  athlete?: {
    id: number;
    name: string;
    email: string;
  };
};

export type TrainingProgramSummary = {
  id: number;
  title: string;
  goal?: string | null;
  status: string;
  startDate?: string | null;
  endDate?: string | null;
  notes?: string | null;
  sessionCount?: number;
  completedSessionCount?: number;
  pendingSessionCount?: number;
  nextSessionDate?: string | null;
  sessions?: TrainingSessionSummary[];
  coach?: {
    id: number;
    name: string;
    email: string;
  };
  athlete?: {
    id: number;
    name: string;
    email: string;
  };
};

export type Snapshot = {
  metricDate?: string | null;
  provider?: string;
  readinessScore?: number | null;
  readinessBand?: string | null;
  strainScore?: number | null;
  sleepHours?: number | null;
  sleepNeedHours?: number | null;
  steps?: number | null;
  caloriesBurned?: number | null;
  restingHeartRate?: number | null;
  heartRateVariability?: number | null;
};

export type AthleteHome = {
  role: 'athlete';
  viewer: Viewer;
  coaches: Array<{ id: number; name: string; email: string; goal?: string | null }>;
  programs: TrainingProgramSummary[];
  todaySessions: TrainingSessionSummary[];
  upcomingSessions: TrainingSessionSummary[];
  membership?: {
    planName?: string | null;
    status: string;
    statusLabel: string;
    daysRemaining?: number | null;
    endsAt?: string | null;
  } | null;
  wearable: {
    latestSnapshot?: Snapshot | null;
    connectedCount: number;
  };
  progress: {
    latestCheckIn?: Record<string, unknown> | null;
  };
  messages: {
    unreadCount: number;
  };
};

export type CoachHome = {
  role: 'coach';
  viewer: Viewer;
  summary: {
    assignedAthletes: number;
    activePrograms: number;
    upcomingSessions: number;
    pendingLogs: number;
    unreadMessages: number;
  };
  athletes: Array<{
    assignmentId: number;
    id: number;
    name: string;
    email: string;
    goal?: string | null;
    latestSnapshot?: Snapshot | null;
  }>;
  programs: TrainingProgramSummary[];
  schedule: TrainingSessionSummary[];
  pendingLogs: TrainingSessionSummary[];
  messages: {
    unreadCount: number;
  };
};

export type AppHome = AthleteHome | CoachHome;

export type CalendarDay = {
  date: string;
  dayNumber: number;
  weekday: string;
  isCurrentMonth: boolean;
  isToday: boolean;
  isSelected: boolean;
  sessionCount: number;
  hasVideo: boolean;
  status: 'rest' | 'workout' | string;
};

export type CalendarPayload = {
  month: string;
  monthLabel: string;
  selectedDate: string;
  days: CalendarDay[];
  selectedDaySessions: TrainingSessionSummary[];
};

export type WorkoutSetRow = {
  exerciseIndex: number;
  exerciseName: string;
  setNumber: number;
  targetReps?: string | null;
  targetLoad?: string | null;
  targetRestSeconds?: number | null;
  actualReps?: string | null;
  actualLoad?: string | null;
  actualRpe?: number | null;
  completedAt?: string | null;
  notes?: string | null;
};

export type WorkoutExecution = {
  session: TrainingSessionSummary;
  program: TrainingProgramSummary;
  coach: { id: number; name: string; email: string };
  athlete: { id: number; name: string; email: string };
  exercises: WorkoutExercise[];
  setLogs: WorkoutSetRow[];
  workoutLog?: Record<string, unknown> | null;
};

export type MessageThread = {
  assignmentId: number;
  participant: { id: number; name: string; email: string };
  goal?: string | null;
  unreadCount: number;
  messages: Array<{
    id: number;
    senderId: number;
    recipientId: number;
    senderName: string;
    body: string;
    sentAt?: string | null;
    readAt?: string | null;
    isMine: boolean;
  }>;
};
