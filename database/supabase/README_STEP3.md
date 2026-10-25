# Step 3 — Postgres schema

## How to apply

1. Open [Supabase SQL Editor](https://supabase.com/dashboard/project/nvgbfiavbgupbwhsliqr/sql)
2. Paste contents of `001_step3_schema.sql`
3. Run
4. Confirm under **Table Editor**: `profiles`, `classes`, `class_members`, `announcements`, `lab_templates`, `practice_lab_activities`, `class_activities`, `activity_submissions`

## Firebase → Postgres map

| Firebase | Postgres |
|----------|----------|
| `users` | `profiles` |
| `classes` | `classes` |
| `classes.students[]` | `class_members` |
| `classes/{id}/announcements` | `announcements` |
| `labActivities` | `lab_templates` |
| `practiceLabActivities` | `practice_lab_activities` |
| `classActivities` | `class_activities` |
| `activitySubmissions` | `activity_submissions` |

## Notes

- Primary keys are **UUID** (Supabase Auth style). Firebase string IDs will be mapped when data is migrated later.
- Lab apparatus / elements / steps / quiz live in **JSONB** so Flutter payloads stay flexible.
- RLS is **enabled**; only open read policies on template/practice tables for now. Full policies come with Auth (Step 4).
- `profiles.id` is UUID without default — rows are created when a user signs up (Step 4) using `auth.users.id`.

## Verify from Laravel (optional)

After the SQL runs successfully, Step 3 is complete. Data access from the teacher web app is still Firebase until Step 5.
