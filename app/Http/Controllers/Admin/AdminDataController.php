<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Supabase\SupabaseRest;
use Illuminate\Http\Request;
use Throwable;

/**
 * Admin portal APIs (service role). Role gated by EnsureAdminRole middleware.
 */
class AdminDataController extends Controller
{
    public function __construct(
        protected SupabaseRest $db,
    ) {}

    // -------------------------------------------------------------------------
    // Teachers
    // -------------------------------------------------------------------------

    public function listTeachers()
    {
        $rows = $this->db->get('profiles', [
            'role' => 'in.(teacher,educator)',
            'order' => 'display_name.asc',
            'select' => 'id,email,display_name,photo_url,role,created_at,updated_at',
        ]);

        $out = [];
        foreach ($rows as $p) {
            $id = $p['id'] ?? null;
            $classCount = 0;
            if ($id) {
                try {
                    $classes = $this->db->get('classes', [
                        'teacher_id' => 'eq.'.$id,
                        'select' => 'id',
                    ]);
                    $classCount = is_array($classes) ? count($classes) : 0;
                } catch (Throwable $e) {
                    report($e);
                }
            }
            $out[] = [
                'id' => $id,
                'email' => $p['email'] ?? '',
                'display_name' => $p['display_name'] ?? 'Teacher',
                'photo_url' => $p['photo_url'] ?? null,
                'role' => $p['role'] ?? 'teacher',
                'class_count' => $classCount,
                'created_at' => $p['created_at'] ?? null,
            ];
        }

        return response()->json($out);
    }

    public function showTeacher(string $id)
    {
        $rows = $this->db->get('profiles', [
            'id' => 'eq.'.$id,
            'limit' => 1,
            'select' => 'id,email,display_name,photo_url,role,created_at',
        ]);
        if (! $rows) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }
        $p = $rows[0];
        $role = strtolower((string) ($p['role'] ?? ''));
        if (! in_array($role, ['teacher', 'educator'], true)) {
            return response()->json(['message' => 'Not a teacher profile.'], 404);
        }

        $classes = $this->db->get('classes', [
            'teacher_id' => 'eq.'.$id,
            'order' => 'created_at.desc',
        ]);

        $mapped = [];
        foreach ($classes as $c) {
            $mapped[] = $this->mapClass($c);
        }

        return response()->json([
            'teacher' => [
                'id' => $p['id'],
                'email' => $p['email'] ?? '',
                'display_name' => $p['display_name'] ?? 'Teacher',
                'photo_url' => $p['photo_url'] ?? null,
                'role' => $p['role'] ?? 'teacher',
                'created_at' => $p['created_at'] ?? null,
            ],
            'classes' => $mapped,
        ]);
    }

    public function classStudents(string $classId)
    {
        $classes = $this->db->get('classes', ['id' => 'eq.'.$classId, 'limit' => 1]);
        if (! $classes) {
            return response()->json(['message' => 'Class not found.'], 404);
        }
        $class = $this->mapClass($classes[0]);

        $members = $this->db->get('class_members', [
            'class_id' => 'eq.'.$classId,
            'select' => 'user_id,role,status,joined_at,profiles(id,display_name,email,photo_url,role)',
            'order' => 'joined_at.desc',
        ]);

        $students = [];
        foreach ($members as $m) {
            $role = strtolower((string) ($m['role'] ?? 'student'));
            if (in_array($role, ['teacher', 'co_teacher'], true)) {
                continue;
            }
            $prof = $m['profiles'] ?? null;
            if (is_array($prof) && isset($prof[0])) {
                $prof = $prof[0];
            }
            $students[] = [
                'user_id' => $m['user_id'] ?? null,
                'status' => $m['status'] ?? 'approved',
                'joined_at' => $m['joined_at'] ?? null,
                'display_name' => is_array($prof) ? ($prof['display_name'] ?? 'Student') : 'Student',
                'email' => is_array($prof) ? ($prof['email'] ?? '') : '',
                'photo_url' => is_array($prof) ? ($prof['photo_url'] ?? null) : null,
                'role' => is_array($prof) ? ($prof['role'] ?? 'student') : 'student',
            ];
        }

        return response()->json([
            'class' => $class,
            'students' => $students,
        ]);
    }


    public function addClassStudent(Request $request, string $classId)
    {
        $classes = $this->db->get('classes', ['id' => 'eq.'.$classId, 'limit' => 1]);
        if (! $classes) {
            return response()->json(['message' => 'Class not found.'], 404);
        }

        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'display_name' => 'nullable|string|max:120',
            'existing_user_id' => 'nullable|string',
        ]);

        $userId = $data['existing_user_id'] ?? null;

        if (! $userId) {
            // Find existing profile by email
            $byEmail = $this->db->get('profiles', [
                'email' => 'eq.'.$data['email'],
                'limit' => 1,
            ]);
            if ($byEmail) {
                $userId = $byEmail[0]['id'];
            } else {
                if (empty($data['password'])) {
                    return response()->json(['message' => 'Password is required when creating a new student.'], 422);
                }
                $created = $this->createUser(new Request([
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'display_name' => $data['display_name'] ?? $data['email'],
                    'role' => 'student',
                ]));
                $payload = $created->getData(true);
                if (($created->status() ?? 500) >= 400) {
                    return $created;
                }
                $userId = $payload['id'] ?? null;
            }
        }

        if (! $userId) {
            return response()->json(['message' => 'Could not resolve user.'], 500);
        }

        // Ensure role student if they were something else? Keep existing role unless new.
        $existingMembers = $this->db->get('class_members', [
            'class_id' => 'eq.'.$classId,
            'user_id' => 'eq.'.$userId,
            'limit' => 1,
        ]);
        if ($existingMembers) {
            $this->db->update('class_members', 'id', $existingMembers[0]['id'], [
                'status' => 'approved',
                'role' => 'student',
            ]);
        } else {
            $this->db->insert('class_members', [
                'class_id' => $classId,
                'user_id' => $userId,
                'role' => 'student',
                'status' => 'approved',
                'joined_at' => now()->toIso8601String(),
            ]);
        }

        return $this->classStudents($classId);
    }

    public function removeClassStudent(string $classId, string $userId)
    {
        $members = $this->db->get('class_members', [
            'class_id' => 'eq.'.$classId,
            'user_id' => 'eq.'.$userId,
            'select' => 'id',
        ]);
        foreach ($members as $m) {
            if (! empty($m['id'])) {
                $this->db->delete('class_members', 'id', $m['id']);
            }
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Lab templates CRUD
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

    public function createTemplate(Request $request)
    {
        $data = $this->validatedTemplate($request);
        $row = $this->templateToRow($data);
        $row['created_at'] = now()->toIso8601String();
        $row['updated_at'] = now()->toIso8601String();

        $created = $this->db->insert('lab_templates', $row);

        return response()->json($this->mapTemplate($created), 201);
    }

    public function updateTemplate(Request $request, string $id)
    {
        $rows = $this->db->get('lab_templates', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $data = $this->validatedTemplate($request);
        $patch = $this->templateToRow($data);
        $patch['updated_at'] = now()->toIso8601String();

        $updated = $this->db->update('lab_templates', 'id', $id, $patch);

        return response()->json($this->mapTemplate($updated ?? array_merge($rows[0], $patch)));
    }

    public function deleteTemplate(string $id)
    {
        $rows = $this->db->get('lab_templates', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->db->delete('lab_templates', 'id', $id);

        return response()->json(['ok' => true]);
    }


    // -------------------------------------------------------------------------
    // Profiles: teachers & students
    // -------------------------------------------------------------------------

    public function listStudents()
    {
        $rows = $this->db->get('profiles', [
            'role' => 'eq.student',
            'order' => 'display_name.asc',
            'select' => 'id,email,display_name,photo_url,role,created_at',
        ]);

        return response()->json(array_map(fn ($p) => [
            'id' => $p['id'] ?? null,
            'email' => $p['email'] ?? '',
            'display_name' => $p['display_name'] ?? 'Student',
            'photo_url' => $p['photo_url'] ?? null,
            'role' => $p['role'] ?? 'student',
            'created_at' => $p['created_at'] ?? null,
        ], $rows));
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'display_name' => 'nullable|string|max:120',
            'role' => 'required|in:teacher,educator,student,admin',
        ]);

        $url = \App\Services\Supabase\SupabaseClient::normalizeProjectUrl((string) config('supabase.url'));
        $serviceKey = (string) config('supabase.service_role_key');

        $response = \Illuminate\Support\Facades\Http::baseUrl($url)
            ->withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer '.$serviceKey,
                'Content-Type' => 'application/json',
            ])
            ->post('/auth/v1/admin/users', [
                'email' => $data['email'],
                'password' => $data['password'],
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $data['display_name'] ?? $data['email'],
                    'name' => $data['display_name'] ?? $data['email'],
                ],
            ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Could not create auth user: '.$response->body(),
            ], 422);
        }

        $authUser = $response->json();
        $id = $authUser['id'] ?? null;
        if (! $id) {
            return response()->json(['message' => 'Auth user created but id missing.'], 500);
        }

        $row = [
            'id' => $id,
            'email' => $data['email'],
            'display_name' => $data['display_name'] ?? $data['email'],
            'role' => $data['role'],
            'has_password_set' => true,
            'updated_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        try {
            $created = $this->db->insert('profiles', $row);
        } catch (\Throwable $e) {
            // Profile may already exist from trigger — patch role
            $this->db->update('profiles', 'id', $id, [
                'email' => $data['email'],
                'display_name' => $data['display_name'] ?? $data['email'],
                'role' => $data['role'],
                'has_password_set' => true,
                'updated_at' => now()->toIso8601String(),
            ]);
            $created = $row;
        }

        return response()->json([
            'id' => $id,
            'email' => $data['email'],
            'display_name' => $data['display_name'] ?? $data['email'],
            'role' => $data['role'],
        ], 201);
    }

    public function updateUserRole(Request $request, string $id)
    {
        $data = $request->validate([
            'role' => 'required|in:teacher,educator,student,admin',
            'display_name' => 'nullable|string|max:120',
        ]);

        $self = session('auth_uid') ?: session('firebase_uid');
        if ($self && $self === $id && $data['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove your own admin role.'], 422);
        }

        $rows = $this->db->get('profiles', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $patch = [
            'role' => $data['role'],
            'updated_at' => now()->toIso8601String(),
        ];
        if (array_key_exists('display_name', $data) && $data['display_name'] !== null) {
            $patch['display_name'] = $data['display_name'];
        }

        $updated = $this->db->update('profiles', 'id', $id, $patch);

        return response()->json([
            'id' => $id,
            'role' => $data['role'],
            'display_name' => $patch['display_name'] ?? ($rows[0]['display_name'] ?? ''),
            'email' => $rows[0]['email'] ?? '',
        ]);
    }

    public function deleteUser(string $id)
    {
        $self = session('auth_uid') ?: session('firebase_uid');
        if ($self && $self === $id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $rows = $this->db->get('profiles', ['id' => 'eq.'.$id, 'limit' => 1]);
        if (! $rows) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $role = strtolower((string) ($rows[0]['role'] ?? ''));
        if (in_array($role, ['teacher', 'educator'], true)) {
            $classes = $this->db->get('classes', [
                'teacher_id' => 'eq.'.$id,
                'select' => 'id',
            ]);
            if ($classes) {
                return response()->json([
                    'message' => 'This teacher still has '.count($classes).' class(es). Reassign or delete those classes first.',
                ], 422);
            }
        }

        // Remove class memberships
        try {
            $members = $this->db->get('class_members', [
                'user_id' => 'eq.'.$id,
                'select' => 'id',
            ]);
            foreach ($members as $m) {
                if (! empty($m['id'])) {
                    $this->db->delete('class_members', 'id', $m['id']);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->db->delete('profiles', 'id', $id);

        // Delete auth user (service role)
        try {
            $url = \App\Services\Supabase\SupabaseClient::normalizeProjectUrl((string) config('supabase.url'));
            $serviceKey = (string) config('supabase.service_role_key');
            \Illuminate\Support\Facades\Http::baseUrl($url)
                ->withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer '.$serviceKey,
                ])
                ->delete('/auth/v1/admin/users/'.$id);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function validatedTemplate(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'labCategory' => 'nullable|string|max:100',
            'safetyTips' => 'nullable|string|max:5000',
            'apparatus' => 'nullable|array',
            'elements' => 'nullable|array',
            'steps' => 'nullable|array',
            'quiz' => 'nullable|array',
        ]);
    }

    protected function templateToRow(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'lab_category' => $data['labCategory'] ?? '',
            'safety_tips' => $data['safetyTips'] ?? '',
            'apparatus' => $data['apparatus'] ?? [],
            'elements' => $data['elements'] ?? [],
            'steps' => $data['steps'] ?? [],
            'quiz' => $data['quiz'] ?? [],
        ];
    }

    protected function mapTemplate(array $r): array
    {
        return [
            'id' => $r['id'] ?? null,
            'title' => $r['title'] ?? '',
            'description' => $r['description'] ?? '',
            'labCategory' => $r['lab_category'] ?? '',
            'safetyTips' => $r['safety_tips'] ?? '',
            'apparatus' => $r['apparatus'] ?? [],
            'elements' => $r['elements'] ?? [],
            'steps' => $r['steps'] ?? [],
            'quiz' => $r['quiz'] ?? [],
            'createdAt' => $r['created_at'] ?? null,
            'updatedAt' => $r['updated_at'] ?? null,
        ];
    }

    protected function mapClass(array $r): array
    {
        $studentCount = 0;
        try {
            $members = $this->db->get('class_members', [
                'class_id' => 'eq.'.($r['id'] ?? ''),
                'select' => 'user_id,role,status',
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
                $studentCount++;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return [
            'id' => $r['id'] ?? null,
            'section_name' => $r['section_name'] ?? '',
            'grade_level' => $r['grade_level'] ?? '',
            'subject' => $r['subject'] ?? '',
            'access_code' => $r['access_code'] ?? '',
            'teacher_id' => $r['teacher_id'] ?? null,
            'academic_year' => $r['academic_year'] ?? null,
            'student_count' => $studentCount,
            'created_at' => $r['created_at'] ?? null,
        ];
    }
}
