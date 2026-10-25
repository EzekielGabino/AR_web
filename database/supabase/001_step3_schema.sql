-- =============================================================================
-- SciLens AR — Supabase schema (migration Step 3)
-- Run in: Supabase Dashboard → SQL Editor → New query → Run
-- Project: https://nvgbfiavbgupbwhsliqr.supabase.co
-- =============================================================================
-- Maps from Firebase:
--   users                  → public.profiles
--   classes                → public.classes + public.class_members
--   classes/*/announcements→ public.announcements
--   labActivities          → public.lab_templates
--   practiceLabActivities  → public.practice_lab_activities
--   classActivities        → public.class_activities
--   activitySubmissions    → public.activity_submissions
-- =============================================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- -----------------------------------------------------------------------------
-- profiles (1:1 with auth.users when Auth is wired in Step 4)
-- Firebase: users/{uid}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.profiles (
    id              UUID PRIMARY KEY,  -- will match auth.users.id after Step 4
    email           TEXT,
    display_name    TEXT,
    photo_url       TEXT,
    role            TEXT NOT NULL DEFAULT 'student'
                    CHECK (role IN ('teacher', 'educator', 'student')),
    has_password_set BOOLEAN NOT NULL DEFAULT false,
    -- optional denormalized “current class” (mobile join flow)
    classroom_id    UUID,
    classroom_name  TEXT,
    classroom_access_code TEXT,
    classroom_joined_at TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS profiles_email_idx ON public.profiles (email);
CREATE INDEX IF NOT EXISTS profiles_role_idx ON public.profiles (role);

COMMENT ON TABLE public.profiles IS 'App user profile; id aligns with auth.users after Step 4';

-- -----------------------------------------------------------------------------
-- classes
-- Firebase: classes/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.classes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    teacher_id      UUID NOT NULL REFERENCES public.profiles (id) ON DELETE CASCADE,
    section_name    TEXT NOT NULL,
    grade_level     TEXT,
    subject         TEXT,
    access_code     TEXT NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX IF NOT EXISTS classes_access_code_uidx ON public.classes (upper(access_code));
CREATE INDEX IF NOT EXISTS classes_teacher_id_idx ON public.classes (teacher_id);

COMMENT ON TABLE public.classes IS 'Teacher-owned class sections';

-- FK from profiles.classroom_id (added after classes exists)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'profiles_classroom_id_fkey'
    ) THEN
        ALTER TABLE public.profiles
            ADD CONSTRAINT profiles_classroom_id_fkey
            FOREIGN KEY (classroom_id) REFERENCES public.classes (id) ON DELETE SET NULL;
    END IF;
END $$;

-- -----------------------------------------------------------------------------
-- class_members (replaces embedded classes.students[])
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.class_members (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    class_id        UUID NOT NULL REFERENCES public.classes (id) ON DELETE CASCADE,
    user_id         UUID NOT NULL REFERENCES public.profiles (id) ON DELETE CASCADE,
    role            TEXT NOT NULL DEFAULT 'student'
                    CHECK (role IN ('student', 'co_teacher')),
    joined_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (class_id, user_id)
);

CREATE INDEX IF NOT EXISTS class_members_user_id_idx ON public.class_members (user_id);
CREATE INDEX IF NOT EXISTS class_members_class_id_idx ON public.class_members (class_id);

COMMENT ON TABLE public.class_members IS 'Students (and optional co-teachers) in a class';

-- -----------------------------------------------------------------------------
-- announcements
-- Firebase: classes/{classId}/announcements/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.announcements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    class_id        UUID NOT NULL REFERENCES public.classes (id) ON DELETE CASCADE,
    author_id       UUID REFERENCES public.profiles (id) ON DELETE SET NULL,
    body            TEXT NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS announcements_class_id_created_idx
    ON public.announcements (class_id, created_at DESC);

COMMENT ON TABLE public.announcements IS 'Class stream posts';

-- -----------------------------------------------------------------------------
-- lab_templates (global catalog; teacher Templates tab + mobile lab defs)
-- Firebase: labActivities/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.lab_templates (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title           TEXT NOT NULL,
    description     TEXT,
    lab_category    TEXT,
    safety_tips     TEXT,
    apparatus       JSONB NOT NULL DEFAULT '[]'::jsonb,
    elements        JSONB NOT NULL DEFAULT '[]'::jsonb,
    steps           JSONB NOT NULL DEFAULT '[]'::jsonb,
    -- lab-style quiz: [{ id, prompt, options:[{id,text}], correctOptionId }]
    quiz            JSONB NOT NULL DEFAULT '[]'::jsonb,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS lab_templates_category_idx ON public.lab_templates (lab_category);

COMMENT ON TABLE public.lab_templates IS 'Shared lab activity templates (read-mostly catalog)';

-- -----------------------------------------------------------------------------
-- practice_lab_activities
-- Firebase: practiceLabActivities/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.practice_lab_activities (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title           TEXT NOT NULL,
    description     TEXT,
    lab_category    TEXT,
    safety_tips     TEXT,
    apparatus       JSONB NOT NULL DEFAULT '[]'::jsonb,
    elements        JSONB NOT NULL DEFAULT '[]'::jsonb,
    steps           JSONB NOT NULL DEFAULT '[]'::jsonb,
    quiz            JSONB NOT NULL DEFAULT '[]'::jsonb,
    template_id     UUID REFERENCES public.lab_templates (id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS practice_lab_category_idx ON public.practice_lab_activities (lab_category);

COMMENT ON TABLE public.practice_lab_activities IS 'Practice labs for students (subset of template shape)';

-- -----------------------------------------------------------------------------
-- class_activities (regular + lab assignments for a class)
-- Firebase: classActivities/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.class_activities (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    class_id        UUID NOT NULL REFERENCES public.classes (id) ON DELETE CASCADE,
    type            TEXT NOT NULL CHECK (type IN ('regular', 'lab')),
    title           TEXT NOT NULL,
    description     TEXT,
    -- draft / published
    published       BOOLEAN NOT NULL DEFAULT false,
    status          TEXT NOT NULL DEFAULT 'draft'
                    CHECK (status IN ('draft', 'published', 'archived')),
    due_date        DATE,
    due_time        TIME,
    max_score       INTEGER NOT NULL DEFAULT 0,
    -- regular quiz: { "questions": [ { prompt, options, correctOption, order, explanation } ] }
    -- lab quiz: array form stored as JSONB as well
    quiz            JSONB NOT NULL DEFAULT '{}'::jsonb,
    -- lab-only payload
    lab_category    TEXT,
    template_id     UUID REFERENCES public.lab_templates (id) ON DELETE SET NULL,
    safety_tips     TEXT,
    apparatus       JSONB,
    elements        JSONB,
    steps           JSONB,
    lab_experiment  JSONB,
    -- attachments: [{ source, name, fileId?, url, downloadURL, storagePath?, ... }]
    attachments     JSONB NOT NULL DEFAULT '[]'::jsonb,
    owner_id        UUID REFERENCES public.profiles (id) ON DELETE SET NULL,
    published_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS class_activities_class_id_idx ON public.class_activities (class_id);
CREATE INDEX IF NOT EXISTS class_activities_type_idx ON public.class_activities (class_id, type);
CREATE INDEX IF NOT EXISTS class_activities_published_idx ON public.class_activities (class_id, published);

COMMENT ON TABLE public.class_activities IS 'Per-class regular and lab assignments';

-- -----------------------------------------------------------------------------
-- activity_submissions
-- Firebase: activitySubmissions/{id}
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.activity_submissions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id     UUID NOT NULL REFERENCES public.class_activities (id) ON DELETE CASCADE,
    class_id        UUID NOT NULL REFERENCES public.classes (id) ON DELETE CASCADE,
    student_id      UUID NOT NULL REFERENCES public.profiles (id) ON DELETE CASCADE,
    activity_type   TEXT,
    status          TEXT NOT NULL DEFAULT 'submitted'
                    CHECK (status IN ('started', 'submitted', 'graded', 'returned')),
    simulation_completed BOOLEAN DEFAULT false,
    answers         JSONB NOT NULL DEFAULT '[]'::jsonb,
    score           INTEGER,
    feedback        TEXT,
    started_at      TIMESTAMPTZ,
    submitted_at    TIMESTAMPTZ,
    graded_at       TIMESTAMPTZ,
    graded_by       UUID REFERENCES public.profiles (id) ON DELETE SET NULL,
    returned_at     TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (activity_id, student_id)
);

CREATE INDEX IF NOT EXISTS activity_submissions_class_idx ON public.activity_submissions (class_id);
CREATE INDEX IF NOT EXISTS activity_submissions_student_idx ON public.activity_submissions (student_id);
CREATE INDEX IF NOT EXISTS activity_submissions_status_idx ON public.activity_submissions (activity_id, status);

COMMENT ON TABLE public.activity_submissions IS 'Student work for class activities';

-- -----------------------------------------------------------------------------
-- updated_at helper
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.set_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;

DO $$
DECLARE
    t TEXT;
BEGIN
    FOREACH t IN ARRAY ARRAY[
        'profiles', 'classes', 'announcements', 'lab_templates',
        'practice_lab_activities', 'class_activities', 'activity_submissions'
    ]
    LOOP
        EXECUTE format(
            'DROP TRIGGER IF EXISTS set_updated_at ON public.%I;
             CREATE TRIGGER set_updated_at
             BEFORE UPDATE ON public.%I
             FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();',
            t, t
        );
    END LOOP;
END $$;

-- -----------------------------------------------------------------------------
-- RLS: enable now; policies filled in when Auth lands (Step 4+)
-- Service role bypasses RLS; anon/authenticated need policies later.
-- -----------------------------------------------------------------------------
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.classes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.class_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.lab_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.practice_lab_activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.class_activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.activity_submissions ENABLE ROW LEVEL SECURITY;

-- Temporary: allow service_role full access is automatic.
-- Optional read of templates for anon during early testing (remove when Auth ships):
DROP POLICY IF EXISTS lab_templates_public_read ON public.lab_templates;
CREATE POLICY lab_templates_public_read ON public.lab_templates
    FOR SELECT TO anon, authenticated USING (true);

DROP POLICY IF EXISTS practice_lab_public_read ON public.practice_lab_activities;
CREATE POLICY practice_lab_public_read ON public.practice_lab_activities
    FOR SELECT TO anon, authenticated USING (true);

-- =============================================================================
-- End Step 3 schema
-- =============================================================================
