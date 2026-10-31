<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\FcmPushService;
use App\Services\Supabase\SupabaseClient;
use App\Services\Supabase\SupabaseRest;
use Illuminate\Http\Request;
use Throwable;

/**
 * Teacher web data API backed by Supabase (Steps 5–6).
 */
class TeacherDataController extends Controller
{
    public function __construct(
        protected SupabaseRest $db,
        protected SupabaseClient $supabase,
        protected FcmPushService $fcm,
    ) {}

    protected function uid(Request $request): string
    {
        $uid = $request->session()->get('auth_uid') ?: $request->session()->get('firebase_uid');
        if (! $uid) {
            abort(401, 'Not signed in.');
        }

        return (string) $uid;
    }

    protected function assertClassOwner(Request $request, string $classId): string
    {
        $uid = $this->uid($request);
        $rows = $this->db->get('classes', ['id' => 'eq.'.$classId, 'limit' => 1]);
        if (! $rows || ($rows[0]['teacher_id'] ?? '') !== $uid) {
            abort(403, 'You do not own this class.');
        }

        return $uid;
    }

    protected function generateAccessCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    protected function nullIfEmpty(mixed $v): mixed
    {
        if ($v === null) {
            return null;
        }
        if (is_string($v) && trim($v) === '') {
            return null;
        }

        return $v;
    }

    // -------------------------------------------------------------------------
    // Classes
    // -------------------------------------------------------------------------

    public function listClasses(Request $request)
    {
        $uid = $this->uid($request);
        $rows = $this->db->get('classes', [
            'teacher_id' => 'eq.'.$uid,
            'order' => 'created_at.desc',
        ]);

        // Count approved students per class (dashboard cards)
        $counts = [];
        if ($rows) {
            $ids = array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $rows)));
            if ($ids) {
                // PostgREST: class_id=in.(uuid1,uuid2)
                $in = 'in.('.implode(',', $ids).')';
                try {
                    $members = $this->db->get('class_members', [
                        'class_id' => $in,
                        'select' => 'class_id,role,status',
                    ]);
                    foreach ($members as $m) {
                        $role = strtolower((string) ($m['role'] ?? 'student'));
                        if (in_array($role, ['teacher', 'co_teacher'], true)) {
                            continue;
                        }
                        $st = strtolower((string) ($m['status'] ?? 'approved'));
                        if ($st !== '' && $st !== 'approved') {
                            continue;
                        }
                        $cid = $m['class_id'] ?? null;
                        if ($cid) {
                            $counts[$cid] = ($counts[$cid] ?? 0) + 1;
                        }
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }

        $mapped = [];
        foreach ($rows as $r) {
            $c = $this->mapClass($r);
            $n = (int) ($counts[$r['id'] ?? ''] ?? 0);
            $c['student_count'] = $n;
            $c['students'] = array_fill(0, $n, true); // length used by older studentCount()
            $mapped[] = $c;
        }

        return response()->json($mapped);
    }

    public function showClass(Request $request, string $id)
    {
        $uid = $this->uid($request);
        $rows = $this->db->get('classes', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Class not found.'], 404);
        }
        if (($rows[0]['teacher_id'] ?? '') !== $uid) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $members = $this->db->get('class_members', [
            'class_id' => 'eq.'.$id,
            'select' => 'user_id,role,status,joined_at,profiles(id,display_name,email,photo_url)',
        ]);

        $students = [];
        $pendingStudents = [];
        foreach ($members as $m) {
            $status = strtolower((string) ($m['status'] ?? 'approved'));
            // Legacy rows without status column → treat as approved
            if ($status === '' || $status === 'null') {
                $status = 'approved';
            }
            $role = strtolower((string) ($m['role'] ?? 'student'));
            if (in_array($role, ['teacher', 'co_teacher'], true)) {
                continue;
            }

            $prof = $m['profiles'] ?? null;
            $entry = is_array($prof)
                ? [
                    'uid' => $prof['id'] ?? $m['user_id'],
                    'name' => $prof['display_name'] ?? null,
                    'email' => $prof['email'] ?? null,
                    'photoUrl' => $prof['photo_url'] ?? null,
                    'status' => $status,
                    'joined_at' => $m['joined_at'] ?? null,
                ]
                : [
                    'uid' => $m['user_id'],
                    'status' => $status,
                    'joined_at' => $m['joined_at'] ?? null,
                ];

            if ($status === 'pending') {
                $pendingStudents[] = $entry;
            } elseif ($status === 'approved') {
                $students[] = $entry;
            }
            // rejected: omit from lists
        }

        $mapped = $this->mapClass($rows[0]);
        $mapped['students'] = $students;
        $mapped['pendingStudents'] = $pendingStudents;

        return response()->json($mapped);
    }

    /**
     * Approve a pending class_members row (teacher only).
     */
    public function approveMember(Request $request, string $classId, string $userId)
    {
        $this->assertClassOwner($request, $classId);

        $resp = $this->supabase->service()
            ->withHeaders(['Prefer' => 'return=minimal'])
            ->patch(
                '/rest/v1/class_members?class_id=eq.'.$classId.'&user_id=eq.'.$userId,
                ['status' => 'approved']
            );
        if (! $resp->successful()) {
            return response()->json([
                'message' => 'Could not approve member: '.$resp->body(),
            ], 500);
        }

        // Attach classroom on student profile so mobile AuthGate opens MainScreen
        try {
            $classRows = $this->db->get('classes', [
                'id' => 'eq.'.$classId,
                'select' => 'section_name,access_code',
                'limit' => 1,
            ]);
            $section = $classRows[0]['section_name'] ?? null;
            $code = $classRows[0]['access_code'] ?? null;
            $this->db->update('profiles', 'id', $userId, [
                'classroom_id' => $classId,
                'classroom_name' => $section,
                'classroom_access_code' => $code,
                'classroom_joined_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true, 'status' => 'approved']);
    }

    /**
     * Reject / remove a pending join request (teacher only).
     */
    public function rejectMember(Request $request, string $classId, string $userId)
    {
        $this->assertClassOwner($request, $classId);

        $resp = $this->supabase->service()
            ->withHeaders(['Prefer' => 'return=minimal'])
            ->patch(
                '/rest/v1/class_members?class_id=eq.'.$classId.'&user_id=eq.'.$userId,
                ['status' => 'rejected']
            );
        if (! $resp->successful()) {
            // Fallback: delete row
            $this->supabase->service()->delete(
                '/rest/v1/class_members?class_id=eq.'.$classId.'&user_id=eq.'.$userId
            );
        }

        return response()->json(['ok' => true, 'status' => 'rejected']);
    }

    public function storeClass(Request $request)
    {
        $uid = $this->uid($request);
        $data = $request->validate([
            'section_name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:64',
            'subject' => 'nullable|string|max:128',
            'access_code' => 'nullable|string|max:32',
        ]);

        $code = strtoupper(trim($data['access_code'] ?? ''));
        if ($code === '') {
            $code = $this->generateAccessCode();
        }

        $row = $this->db->insert('classes', [
            'teacher_id' => $uid,
            'section_name' => $data['section_name'],
            'grade_level' => $data['grade_level'] ?? 'Grade 8',
            'subject' => $data['subject'] ?? '',
            'access_code' => $code,
        ]);

        return response()->json($this->mapClass($row), 201);
    }

    public function updateClass(Request $request, string $id)
    {
        $uid = $this->uid($request);
        $existing = $this->db->get('classes', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $existing || ($existing[0]['teacher_id'] ?? '') !== $uid) {
            return response()->json(['message' => 'Forbidden or not found.'], 403);
        }

        $data = $request->validate([
            'section_name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:64',
            'subject' => 'nullable|string|max:128',
            'access_code' => 'nullable|string|max:32',
        ]);

        $code = strtoupper(trim($data['access_code'] ?? $existing[0]['access_code'] ?? ''));
        $row = $this->db->update('classes', 'id', $id, [
            'section_name' => $data['section_name'],
            'grade_level' => $data['grade_level'] ?? 'Grade 8',
            'subject' => $data['subject'] ?? '',
            'access_code' => $code,
        ]);

        return response()->json($this->mapClass($row ?? $existing[0]));
    }

    public function destroyClass(Request $request, string $id)
    {
        $uid = $this->uid($request);
        $existing = $this->db->get('classes', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $existing || ($existing[0]['teacher_id'] ?? '') !== $uid) {
            return response()->json(['message' => 'Forbidden or not found.'], 403);
        }
        $this->deleteNotificationsForClass($id);
        $this->db->delete('classes', 'id', $id);

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Announcements
    // -------------------------------------------------------------------------

    public function listAnnouncements(Request $request, string $classId)
    {
        $this->assertClassOwner($request, $classId);
        $rows = $this->db->get('announcements', [
            'class_id' => 'eq.'.$classId,
            'order' => 'created_at.desc',
        ]);

        return response()->json(array_map(function ($r) {
            $atts = $r['attachments'] ?? [];
            if (is_string($atts)) {
                $atts = json_decode($atts, true) ?: [];
            }
            return [
                'id' => $r['id'],
                'body' => $r['body'] ?? '',
                'created_at' => $r['created_at'] ?? null,
                'createdAt' => $r['created_at'] ?? null,
                'author_id' => $r['author_id'] ?? null,
                'attachments' => is_array($atts) ? $atts : [],
            ];
        }, $rows));
    }

    public function storeAnnouncement(Request $request, string $classId)
    {
        $uid = $this->assertClassOwner($request, $classId);
        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
        ]);
        $atts = $data['attachments'] ?? [];
        $row = $this->db->insert('announcements', [
            'class_id' => $classId,
            'author_id' => $uid,
            'body' => $data['body'],
            'attachments' => $atts,
        ]);

        $this->notifyTeacher(
            $uid,
            'announcement_posted',
            'Announcement posted',
            $this->classLabel($classId).': '.$this->excerpt($data['body'], 80),
            $classId,
            null,
        );

        $this->notifyClassStudents(
            $classId,
            'announcement_posted',
            'New announcement',
            $this->excerpt($data['body'], 120),
            null,
        );

        return response()->json([
            'id' => $row['id'],
            'body' => $row['body'] ?? '',
            'created_at' => $row['created_at'] ?? null,
            'createdAt' => $row['created_at'] ?? null,
            'attachments' => $atts,
        ], 201);
    }

    /** Upload a file for a stream announcement (before or after post). */
    public function uploadAnnouncementFile(Request $request, string $classId)
    {
        $this->assertClassOwner($request, $classId);
        $request->validate([
            'file' => 'required|file|max:15360|mimes:pdf,doc,docx,png,jpg,jpeg,webp',
        ]);
        $file = $request->file('file');
        $bucket = (string) config('supabase.storage_bucket_attachments', 'activity-attachments');
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'file';
        $objectPath = $classId.'/announcements/'.uniqid('', true).'_'.$safeName;

        try {
            $meta = $this->supabase->uploadPublicObject(
                $bucket,
                $objectPath,
                file_get_contents($file->getRealPath()),
                $file->getMimeType() ?: 'application/octet-stream',
                $file->getClientOriginalName(),
            );

            return response()->json(['attachment' => $meta], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroyAnnouncement(Request $request, string $classId, string $id)
    {
        $this->assertClassOwner($request, $classId);
        $this->db->delete('announcements', 'id', $id);
        // Remove related teacher/student notification cards for this class stream post
        $this->deleteNotificationsByClassAndType($classId, 'announcement_posted');

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Class activities
    // -------------------------------------------------------------------------

    public function listActivities(Request $request, string $classId)
    {
        $this->assertClassOwner($request, $classId);
        $rows = $this->db->get('class_activities', [
            'class_id' => 'eq.'.$classId,
            'order' => 'created_at.desc',
        ]);

        return response()->json(array_map([$this, 'mapActivity'], $rows));
    }

    public function showActivity(Request $request, string $id)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);

        return response()->json($this->mapActivity($rows[0]));
    }

    public function storeActivity(Request $request)
    {
        $data = $request->validate([
            'classId' => 'required|string',
            'type' => 'required|in:regular,lab',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quiz' => 'nullable',
            'attachments' => 'nullable|array',
            'labCategory' => 'nullable|string',
            'templateId' => 'nullable|string',
            'safetyTips' => 'nullable|string',
            'apparatus' => 'nullable|array',
            'elements' => 'nullable|array',
            'steps' => 'nullable|array',
            'labExperiment' => 'nullable',
            'maxScore' => 'nullable|integer',
            'dueDate' => 'nullable|string',
            'dueTime' => 'nullable|string',
            'published' => 'nullable|boolean',
            'status' => 'nullable|string',
        ]);

        $uid = $this->assertClassOwner($request, $data['classId']);
        $published = (bool) ($data['published'] ?? false);

        $row = $this->db->insert('class_activities', [
            'class_id' => $data['classId'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'published' => $published,
            'status' => $data['status'] ?? ($published ? 'published' : 'draft'),
            'due_date' => $this->nullIfEmpty($data['dueDate'] ?? null),
            'due_time' => $this->nullIfEmpty($data['dueTime'] ?? null),
            'max_score' => (int) ($data['maxScore'] ?? 0),
            'quiz' => $data['quiz'] ?? ($data['type'] === 'regular' ? ['questions' => []] : []),
            'lab_category' => $data['labCategory'] ?? null,
            'template_id' => $this->nullIfEmpty($data['templateId'] ?? null),
            'safety_tips' => $data['safetyTips'] ?? null,
            'apparatus' => $data['apparatus'] ?? null,
            'elements' => $data['elements'] ?? null,
            'steps' => $data['steps'] ?? null,
            'lab_experiment' => $data['labExperiment'] ?? null,
            'attachments' => $data['attachments'] ?? [],
            'owner_id' => $uid,
        ]);

        return response()->json($this->mapActivity($row), 201);
    }

    public function updateActivity(Request $request, string $id)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);

        $patch = [];
        $map = [
            'title' => 'title',
            'description' => 'description',
            'published' => 'published',
            'status' => 'status',
            'dueDate' => 'due_date',
            'dueTime' => 'due_time',
            'maxScore' => 'max_score',
            'quiz' => 'quiz',
            'labCategory' => 'lab_category',
            'templateId' => 'template_id',
            'safetyTips' => 'safety_tips',
            'apparatus' => 'apparatus',
            'elements' => 'elements',
            'steps' => 'steps',
            'labExperiment' => 'lab_experiment',
            'attachments' => 'attachments',
            'publishedAt' => 'published_at',
        ];

        foreach ($map as $camel => $snake) {
            if ($request->exists($camel)) {
                $val = $request->input($camel);
                if (in_array($snake, ['due_date', 'due_time', 'template_id'], true)) {
                    $val = $this->nullIfEmpty($val);
                }
                $patch[$snake] = $val;
            }
        }

        if ($patch === []) {
            return response()->json($this->mapActivity($rows[0]));
        }

        $wasPublished = (bool) ($rows[0]['published'] ?? false);
        $row = $this->db->update('class_activities', 'id', $id, $patch);
        $mapped = $this->mapActivity($row ?? array_merge($rows[0], $patch));

        $nowPublished = (bool) ($mapped['published'] ?? false);
        if (! $wasPublished && $nowPublished) {
            $uid = $this->uid($request);
            $title = $mapped['title'] ?? 'Activity';
            $this->notifyTeacher(
                $uid,
                'activity_published',
                'Activity published',
                $this->classLabel($rows[0]['class_id']).': '.$title,
                $rows[0]['class_id'],
                $id,
            );

            $this->notifyClassStudents(
                $rows[0]['class_id'],
                'activity_published',
                'New activity',
                $title,
                $id,
            );
        }

        return response()->json($mapped);
    }

    public function destroyActivity(Request $request, string $id)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);
        $this->deleteNotificationsForActivity($id);
        $this->db->delete('class_activities', 'id', $id);

        return response()->json(['ok' => true]);
    }

    public function listSubmissions(Request $request, string $activityId)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$activityId, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);

        $subs = $this->db->get('activity_submissions', [
            'activity_id' => 'eq.'.$activityId,
        ]);

        $studentIds = array_values(array_unique(array_filter(array_map(
            fn ($s) => $s['student_id'] ?? null,
            $subs
        ))));
        $names = [];
        if ($studentIds) {
            $profiles = $this->db->get('profiles', [
                'id' => 'in.('.implode(',', $studentIds).')',
                'select' => 'id,display_name,email,photo_url',
            ]);
            foreach ($profiles as $p) {
                $names[$p['id']] = $p['display_name'] ?? $p['email'] ?? $p['id'];
            }
        }

        return response()->json(array_map(function ($s) use ($names) {
            $sid = $s['student_id'] ?? '';

            return [
                'id' => $s['id'],
                'activityId' => $s['activity_id'],
                'classId' => $s['class_id'],
                'studentId' => $sid,
                'studentName' => $names[$sid] ?? $sid,
                'activityType' => $s['activity_type'] ?? null,
                'status' => $s['status'] ?? null,
                'score' => $s['score'] ?? null,
                'feedback' => $s['feedback'] ?? null,
                'submittedAt' => $s['submitted_at'] ?? null,
                'returnedAt' => $s['returned_at'] ?? null,
                'answers' => $s['answers'] ?? [],
                'simulationCompleted' => (bool) ($s['simulation_completed'] ?? false),
            ];
        }, $subs));
    }

    /**
     * Teacher grades a submission and returns it to the student.
     * PATCH body: score (number), feedback (optional string)
     */
    public function returnSubmission(Request $request, string $activityId, string $submissionId)
    {
        $acts = $this->db->get('class_activities', ['id' => 'eq.'.$activityId, 'limit' => 1]);
        if (! $acts) {
            return response()->json(['message' => 'Activity not found.'], 404);
        }
        $this->assertClassOwner($request, $acts[0]['class_id']);

        $subs = $this->db->get('activity_submissions', [
            'id' => 'eq.'.$submissionId,
            'activity_id' => 'eq.'.$activityId,
            'limit' => 1,
        ]);
        if (! $subs) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        $data = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $max = (int) ($acts[0]['max_score'] ?? 0);
        if ($max > 0 && (float) $data['score'] > $max) {
            return response()->json(['message' => "Score cannot exceed max score ({$max})."], 422);
        }

        $now = now()->utc()->toIso8601String();
        $patch = [
            'status' => 'returned',
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'returned_at' => $now,
            'graded_at' => $now,
        ];

        $this->db->update('activity_submissions', 'id', $submissionId, $patch);

        $updated = $this->db->get('activity_submissions', [
            'id' => 'eq.'.$submissionId,
            'limit' => 1,
        ]);

        $row = $updated[0] ?? array_merge($subs[0], $patch);

        $studentId = $row['student_id'] ?? $subs[0]['student_id'] ?? null;
        $actTitle = $acts[0]['title'] ?? 'Activity';
        if ($studentId) {
            $this->notifyStudent(
                (string) $studentId,
                'activity_returned',
                'Activity returned',
                $actTitle.' — score: '.($row['score'] ?? $data['score']),
                $acts[0]['class_id'] ?? null,
                $activityId,
            );
        }

        return response()->json([
            'id' => $row['id'],
            'activityId' => $row['activity_id'] ?? $activityId,
            'studentId' => $row['student_id'] ?? null,
            'status' => $row['status'] ?? 'returned',
            'score' => $row['score'] ?? $data['score'],
            'feedback' => $row['feedback'] ?? ($data['feedback'] ?? null),
            'returnedAt' => $row['returned_at'] ?? $now,
            'answers' => $row['answers'] ?? [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Lab templates
    // -------------------------------------------------------------------------

    public function listTemplates()
    {
        $rows = $this->db->get('lab_templates', ['order' => 'title.asc']);

        return response()->json(array_map([$this, 'mapTemplate'], $rows));
    }

    public function showTemplate(string $id)
    {
        $rows = $this->db->get('lab_templates', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($this->mapTemplate($rows[0]));
    }

    // -------------------------------------------------------------------------
    // Attachments (Step 6 — Supabase Storage)
    // -------------------------------------------------------------------------

    public function uploadAttachment(Request $request, string $activityId)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$activityId, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Activity not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);

        $request->validate([
            'file' => 'required|file|max:15360|mimes:pdf,doc,docx,png,jpg,jpeg,webp',
        ]);

        $file = $request->file('file');
        $bucket = (string) config('supabase.storage_bucket_attachments', 'activity-attachments');
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'file';
        $objectPath = $rows[0]['class_id'].'/'.$activityId.'/'.uniqid('', true).'_'.$safeName;

        try {
            $meta = $this->supabase->uploadPublicObject(
                $bucket,
                $objectPath,
                file_get_contents($file->getRealPath()),
                $file->getMimeType() ?: 'application/octet-stream',
                $file->getClientOriginalName(),
            );

            $attachments = $rows[0]['attachments'] ?? [];
            if (is_string($attachments)) {
                $attachments = json_decode($attachments, true) ?: [];
            }
            if (! is_array($attachments)) {
                $attachments = [];
            }
            $attachments[] = $meta;

            $updated = $this->db->update('class_activities', 'id', $activityId, [
                'attachments' => $attachments,
            ]);

            return response()->json([
                'attachment' => $meta,
                'activity' => $this->mapActivity($updated ?? array_merge($rows[0], ['attachments' => $attachments])),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteAttachment(Request $request, string $activityId)
    {
        $rows = $this->db->get('class_activities', ['id' => 'eq.'.$activityId, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Activity not found.'], 404);
        }
        $this->assertClassOwner($request, $rows[0]['class_id']);

        $data = $request->validate([
            'storagePath' => 'required|string',
        ]);

        $attachments = $rows[0]['attachments'] ?? [];
        if (is_string($attachments)) {
            $attachments = json_decode($attachments, true) ?: [];
        }
        if (! is_array($attachments)) {
            $attachments = [];
        }

        $path = $data['storagePath'];
        // storagePath is "bucket/object..." — strip bucket prefix for Storage API
        $bucket = (string) config('supabase.storage_bucket_attachments', 'activity-attachments');
        $objectPath = $path;
        if (str_starts_with($objectPath, $bucket.'/')) {
            $objectPath = substr($objectPath, strlen($bucket) + 1);
        }

        try {
            $this->supabase->deleteObject($bucket, $objectPath);
        } catch (Throwable $e) {
            // still unlink from activity
        }

        $attachments = array_values(array_filter(
            $attachments,
            fn ($a) => ($a['storagePath'] ?? '') !== $path
        ));

        $updated = $this->db->update('class_activities', 'id', $activityId, [
            'attachments' => $attachments,
        ]);

        return response()->json([
            'ok' => true,
            'activity' => $this->mapActivity($updated ?? array_merge($rows[0], ['attachments' => $attachments])),
        ]);
    }

    // -------------------------------------------------------------------------
    // Mappers
    // -------------------------------------------------------------------------

    protected function mapClass(array $r): array
    {
        return [
            'id' => $r['id'],
            'section_name' => $r['section_name'] ?? '',
            'grade_level' => $r['grade_level'] ?? '',
            'subject' => $r['subject'] ?? '',
            'access_code' => $r['access_code'] ?? '',
            'teacher_uid' => $r['teacher_id'] ?? null,
            'teacher_id' => $r['teacher_id'] ?? null,
            'students' => [],
            'student_count' => 0,
            'created_at' => $r['created_at'] ?? null,
            'updated_at' => $r['updated_at'] ?? null,
        ];
    }


    // -------------------------------------------------------------------------
    // Notifications (dashboard)
    // -------------------------------------------------------------------------

    public function listNotifications(Request $request)
    {
        $uid = $this->uid($request);
        $rows = $this->db->get('teacher_notifications', [
            'teacher_id' => 'eq.'.$uid,
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        return response()->json(array_map(fn ($n) => $this->mapNotification($n), $rows));
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $uid = $this->uid($request);
        $rows = $this->db->get('teacher_notifications', [
            'id' => 'eq.'.$id,
            'teacher_id' => 'eq.'.$uid,
            'limit' => 1,
        ]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $now = now()->utc()->toIso8601String();
        $this->db->update('teacher_notifications', 'id', $id, ['read_at' => $now]);

        return response()->json(['ok' => true, 'readAt' => $now]);
    }


    protected function deleteNotificationsForClass(string $classId): void
    {
        try {
            foreach (['teacher_notifications', 'student_notifications'] as $table) {
                $rows = $this->db->get($table, ['class_id' => 'eq.'.$classId, 'select' => 'id']);
                foreach ($rows as $r) {
                    if (! empty($r['id'])) {
                        $this->db->delete($table, 'id', $r['id']);
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function deleteNotificationsByClassAndType(string $classId, string $type): void
    {
        try {
            foreach (['teacher_notifications', 'student_notifications'] as $table) {
                $rows = $this->db->get($table, [
                    'class_id' => 'eq.'.$classId,
                    'type' => 'eq.'.$type,
                    'select' => 'id',
                ]);
                foreach ($rows as $r) {
                    if (! empty($r['id'])) {
                        $this->db->delete($table, 'id', $r['id']);
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function deleteNotificationsForActivity(string $activityId): void
    {
        try {
            foreach (['teacher_notifications', 'student_notifications'] as $table) {
                $rows = $this->db->get($table, [
                    'activity_id' => 'eq.'.$activityId,
                    'select' => 'id',
                ]);
                foreach ($rows as $r) {
                    if (! empty($r['id'])) {
                        $this->db->delete($table, 'id', $r['id']);
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function notifyTeacher(
        string $teacherId,
        string $type,
        string $title,
        string $body,
        ?string $classId,
        ?string $activityId,
    ): void {
        try {
            $this->db->insert('teacher_notifications', array_filter([
                'teacher_id' => $teacherId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'class_id' => $classId,
                'activity_id' => $activityId,
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable $e) {
            // Non-fatal — do not fail the main action if notify fails
            report($e);
        }
    }

    
    protected function notifyStudent(
        string $userId,
        string $type,
        string $title,
        string $body,
        ?string $classId,
        ?string $activityId,
    ): void {
        try {
            $this->db->insert('student_notifications', array_filter([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'class_id' => $classId,
                'activity_id' => $activityId,
            ], fn ($v) => $v !== null && $v !== ''));
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $this->fcm->sendToUser($userId, $title, $body, [
                'type' => $type,
                'classId' => $classId ?? '',
                'activityId' => $activityId ?? '',
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Notify every student member of a class (not the teacher). */
    protected function notifyClassStudents(
        string $classId,
        string $type,
        string $title,
        string $body,
        ?string $activityId,
    ): void {
        try {
            $members = $this->db->get('class_members', [
                'class_id' => 'eq.'.$classId,
                'select' => 'user_id,role,status',
            ]);
            foreach ($members as $m) {
                $role = strtolower((string) ($m['role'] ?? 'student'));
                if ($role === 'co_teacher' || $role === 'teacher') {
                    continue;
                }
                $st = strtolower((string) ($m['status'] ?? 'approved'));
                if ($st !== '' && $st !== 'approved') {
                    continue;
                }
                $uid = $m['user_id'] ?? null;
                if (! $uid) {
                    continue;
                }
                $this->notifyStudent((string) $uid, $type, $title, $body, $classId, $activityId);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

protected function classLabel(string $classId): string
    {
        $rows = $this->db->get('classes', [
            'id' => 'eq.'.$classId,
            'select' => 'section_name,grade_level,subject',
            'limit' => 1,
        ]);
        if (! $rows) {
            return 'Class';
        }
        $c = $rows[0];
        $parts = array_filter([
            $c['subject'] ?? null,
            $c['section_name'] ?? null,
            $c['grade_level'] ?? null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Class';
    }

    protected function excerpt(string $text, int $max = 80): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    protected function mapNotification(array $n): array
    {
        return [
            'id' => $n['id'],
            'type' => $n['type'] ?? '',
            'title' => $n['title'] ?? '',
            'body' => $n['body'] ?? '',
            'classId' => $n['class_id'] ?? null,
            'activityId' => $n['activity_id'] ?? null,
            'readAt' => $n['read_at'] ?? null,
            'createdAt' => $n['created_at'] ?? null,
        ];
    }

    protected function mapActivity(array $r): array
    {
        return [
            'id' => $r['id'],
            'classId' => $r['class_id'],
            'type' => $r['type'],
            'title' => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
            'published' => (bool) ($r['published'] ?? false),
            'status' => $r['status'] ?? 'draft',
            'dueDate' => $r['due_date'] ?? '',
            'dueTime' => $r['due_time'] ?? '',
            'maxScore' => (int) ($r['max_score'] ?? 0),
            'quiz' => $r['quiz'] ?? [],
            'labCategory' => $r['lab_category'] ?? null,
            'templateId' => $r['template_id'] ?? null,
            'safetyTips' => $r['safety_tips'] ?? null,
            'apparatus' => $r['apparatus'] ?? [],
            'elements' => $r['elements'] ?? [],
            'steps' => $r['steps'] ?? [],
            'labExperiment' => $r['lab_experiment'] ?? null,
            'attachments' => $r['attachments'] ?? [],
            'createdAt' => $r['created_at'] ?? null,
            'publishedAt' => $r['published_at'] ?? null,
        ];
    }

    protected function mapTemplate(array $r): array
    {
        return [
            'id' => $r['id'],
            'title' => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
            'labCategory' => $r['lab_category'] ?? '',
            'safetyTips' => $r['safety_tips'] ?? '',
            'apparatus' => $r['apparatus'] ?? [],
            'elements' => $r['elements'] ?? [],
            'steps' => $r['steps'] ?? [],
            'quiz' => $r['quiz'] ?? [],
            'createdAt' => $r['created_at'] ?? null,
        ];
    }
}
