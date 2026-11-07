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
            'select' => 'user_id,role,status,joined_at,profiles(id,display_name,email,photo_url)',
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
            ];
        }

        return response()->json([
            'class' => $class,
            'students' => $students,
        ]);
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
