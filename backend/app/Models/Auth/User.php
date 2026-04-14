<?php

namespace App\Models\Auth;
use App\Models\Academic\Classes;
use App\Models\Group\Group;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
// 1. QUAN TRỌNG: Phải dùng Authenticatable thay vì Model thường
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens; // Import thư viện Sanctum
use App\Models\Communication\Message;
class User extends Authenticatable
{
    // 2. QUAN TRỌNG: Khai báo sử dụng Trait ở đây để có hàm createToken()
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * Các trường được phép thêm sửa (Mass Assignment)
     */
    protected $fillable = [
        'code',       // Mã SV/GV
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',       // student, lecturer, admin
        'is_active',
    ];

    /**
     * Ẩn mật khẩu khi trả về JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Ép kiểu dữ liệu
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Tự động hash mật khẩu
        'is_active' => 'boolean',
    ];

    protected $appends = ['avatar_url'];
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return Storage::url($this->avatar);
        }

        // Trả về ảnh mặc định nếu chưa upload
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }
    // --- CÁC HÀM CHECK QUYỀN NHANH ---
    public function isAdmin() { return $this->role === 'admin'; }
    public function isLecturer() { return $this->role === 'lecturer'; }
    public function isStudent() { return $this->role === 'student'; }
    public function messages():HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    /* ── Relationships ─────────────────────────── */
 
    /**
     * Lớp mà sinh viên được add/import vào (qua class_students).
     * Dùng cho dashboard: "Tôi đang ở lớp nào?"
     */
    public function enrolledClasses()
    {
        return $this->belongsToMany(Classes::class, 'class_students', 'student_id', 'class_id')
                    ->withPivot('has_group', 'created_at')
                    ->withTimestamps();
    }
 
    /**
     * Lớp mà giảng viên phụ trách.
     */
    public function taughtClasses()
    {
        return $this->hasMany(Classes::class, 'lecturer_id');
    }
 
    /**
     * Nhóm mà user là thành viên.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }
 
    /**
     * Nhóm mà user là trưởng nhóm.
     */
    public function ledGroups()
    {
        return $this->hasMany(Group::class, 'leader_id');
    }
}