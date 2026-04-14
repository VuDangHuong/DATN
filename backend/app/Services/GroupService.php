<?php

namespace App\Services;

use App\Models\Academic\Classes;
use App\Models\Auth\User;
use App\Models\Group\Group;
use App\Models\Group\GroupMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class GroupService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function createGroup(User $user, int $classId, string $name): array
    {
        $class = Classes::findOrFail($classId);
 
        // Kiểm tra SV thuộc lớp
        if (!$class->students()->where('student_id', $user->id)->exists()) {
            return $this->error('Bạn không thuộc lớp này', 403);
        }
 
        // Kiểm tra đã có nhóm chưa
        $existingGroup = Group::where('class_id', $classId)
            ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->first();
 
        if ($existingGroup) {
            return $this->error('Bạn đã có nhóm trong lớp này rồi', 422);
        }
 
        $group = DB::transaction(function () use ($user, $class, $name) {
            $group = Group::create([
                'name'            => $name,
                'class_id'        => $class->id,
                'leader_id'       => $user->id,
                'invitation_code' => strtoupper(Str::random(8)),
                'is_locked'       => false,
            ]);
 
            GroupMember::create([
                'group_id'  => $group->id,
                'user_id'   => $user->id,
                'role'      => 'leader',
                'joined_at' => now(),
            ]);
 
            // Đánh dấu SV đã có nhóm
            $class->students()->updateExistingPivot($user->id, [
                'has_group'  => true,
                'updated_at' => now(),
            ]);
 
            return $group;
        });
 
        return $this->success('Tạo nhóm thành công', [
            'group' => $this->formatGroup($group->fresh(['leader', 'members.user'])),
        ]);
    }
 
    /**
     * Nhóm trưởng thêm thành viên vào nhóm bằng mã sinh viên.
     *
     * Kiểm tra:
     *   - Người thêm phải là leader
     *   - SV được thêm phải thuộc cùng lớp
     *   - SV chưa có nhóm
     *   - Nhóm chưa đầy (max_members từ classes)
     *   - Nhóm chưa bị khóa
     */
    public function addMember(User $leader, int $groupId, string $studentCode): array
    {
        $group = Group::with('classRoom')->findOrFail($groupId);
 
        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền thêm thành viên', 403);
        }
 
        if ($group->is_locked) {
            return $this->error('Nhóm đã bị khóa, không thể thêm thành viên', 422);
        }
 
        $student = User::where('code', $studentCode)->where('role', 'student')->first();
        if (!$student) {
            return $this->error("Không tìm thấy sinh viên với mã: {$studentCode}", 404);
        }
 
        $class = $group->classRoom;
 
        // SV phải thuộc cùng lớp
        if (!$class->students()->where('student_id', $student->id)->exists()) {
            return $this->error('Sinh viên không thuộc lớp này', 422);
        }
 
        // SV chưa có nhóm
        $alreadyInGroup = Group::where('class_id', $class->id)
            ->whereHas('members', fn($q) => $q->where('user_id', $student->id))
            ->exists();
 
        if ($alreadyInGroup) {
            return $this->error('Sinh viên đã có nhóm trong lớp này', 422);
        }
 
        // Kiểm tra sĩ số nhóm
        $maxMembers = $class->max_members ?? 5;
        if ($group->memberCount() >= $maxMembers) {
            return $this->error("Nhóm đã đạt số lượng tối đa ({$maxMembers} thành viên)", 422);
        }
 
        DB::transaction(function () use ($group, $student, $class) {
            GroupMember::create([
                'group_id'  => $group->id,
                'user_id'   => $student->id,
                'role'      => 'member',
                'joined_at' => now(),
            ]);
 
            $class->students()->updateExistingPivot($student->id, [
                'has_group'  => true,
                'updated_at' => now(),
            ]);
        });
 
        return $this->success('Đã thêm thành viên vào nhóm', [
            'member' => [
                'id'   => $student->id,
                'code' => $student->code,
                'name' => $student->name,
            ],
            'member_count' => $group->memberCount(),
        ]);
    }
 
    /**
     * Nhóm trưởng xóa thành viên khỏi nhóm.
     */
    public function removeMember(User $leader, int $groupId, int $memberId): array
    {
        $group = Group::with('classRoom')->findOrFail($groupId);
 
        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền xóa thành viên', 403);
        }
 
        if ($memberId === $leader->id) {
            return $this->error('Nhóm trưởng không thể tự xóa mình', 422);
        }
 
        $member = $group->members()->where('user_id', $memberId)->first();
        if (!$member) {
            return $this->error('Thành viên không có trong nhóm', 404);
        }
 
        DB::transaction(function () use ($member, $group) {
            $member->delete();
 
            // Reset has_group
            $group->classRoom->students()->updateExistingPivot($member->user_id, [
                'has_group'  => false,
                'updated_at' => now(),
            ]);
        });
 
        return $this->success('Đã xóa thành viên khỏi nhóm');
    }
 
    /**
     * Lấy chi tiết nhóm (chỉ thành viên mới xem được).
     */
    public function getGroupDetail(User $user, int $groupId): array
    {
        $group = Group::with(['leader:id,code,name,email', 'members.user:id,code,name,email', 'classRoom.subjects'])
            ->findOrFail($groupId);
 
        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        return $this->success('Chi tiết nhóm', [
            'group' => $this->formatGroup($group),
        ]);
    }
 
    /**
     * Lấy danh sách nhóm trong 1 lớp.
     */
    public function getGroupsByClass(int $classId): array
    {
        $groups = Group::where('class_id', $classId)
            ->with(['leader:id,code,name', 'members.user:id,code,name'])
            ->get()
            ->map([$this, 'formatGroup']);
        return $this->success('Danh sách nhóm', ['groups' => $groups]);
    }
 
    // ─────────────────────────────────────────────
    // Format helpers
    // ─────────────────────────────────────────────
 
    private function formatGroup(Group $group): array
    {
        return [
            'id'              => $group->id,
            'name'            => $group->name,
            'class_id'        => $group->class_id,
            'invitation_code' => $group->invitation_code,
            'is_locked'       => $group->is_locked,
            'leader'          => $group->leader ? [
                'id'   => $group->leader->id,
                'code' => $group->leader->code,
                'name' => $group->leader->name,
            ] : null,
            'members'         => $group->members->map(fn($m) => [
                'id'        => $m->user->id,
                'code'      => $m->user->code,
                'name'      => $m->user->name,
                'role'      => $m->role,
                'joined_at' => $m->joined_at,
            ]),
            'member_count'    => $group->members->count(),
            'created_at'      => $group->created_at,
        ];
    }
 
    private function success(string $message, array $data = []): array
    {
        return ['status' => 'success', 'message' => $message, 'data' => $data];
    }
 
    private function error(string $message, int $code = 400): array
    {
        return ['status' => 'error', 'message' => $message, 'code' => $code];
    }
}
