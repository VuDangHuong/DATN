<?php
// app/Services/ClassMaterialService.php

namespace App\Services;

use App\Models\Academic\Classes;
use App\Models\Academic\ClassMaterial;
use App\Models\Academic\ClassMaterialFile;
use App\Models\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClassMaterialService
{
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'rar', '7z',
        'jpg', 'jpeg', 'png', 'gif',
        'mp4', 'avi', 'mov', 'mp3', 'wav',
        'txt', 'csv',
    ];

    /**
     *Tạo title MỚI với nhiều file cùng lúc
     *
     * @param array $files - mảng UploadedFile
     * @param array $data  - title, description, category
     */
    public function createMaterial(
        User $lecturer,
        int $classId,
        array $files,
        array $data
    ): array {
        $class = Classes::find($classId);

        if (!$class) {
            return $this->error('Lớp không tồn tại', 404);
        }

        if ($class->lecturer_id !== $lecturer->id) {
            return $this->error('Bạn không phải giảng viên của lớp này', 403);
        }

        if (empty($files)) {
            return $this->error('Cần ít nhất 1 file', 422);
        }

        // Validate từng file
        foreach ($files as $idx => $file) {
            $check = $this->validateFile($file);
            if ($check !== true) {
                return $this->error("File thứ " . ($idx + 1) . ": {$check}", 422);
            }
        }

        try {
            $material = DB::transaction(function () use ($lecturer, $classId, $files, $data) {
                // Tạo title trước
                $material = ClassMaterial::create([
                    'class_id'    => $classId,
                    'uploaded_by' => $lecturer->id,
                    'title'       => $data['title'],
                    'description' => $data['description'] ?? null,
                    'category'    => $data['category'] ?? 'lecture',
                ]);

                // Lưu từng file
                foreach ($files as $idx => $file) {
                    $this->storeFile($material->id, $classId, $file, $idx);
                }

                return $material;
            });

            return $this->success(
                "Đã tạo \"{$material->title}\" với " . count($files) . " file",
                ['material' => $this->formatMaterial($material->fresh(['uploader', 'files']))]
            );
        } catch (\Exception $e) {
            \Log::error('Create material failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Thêm file vào title đã có
     */
    public function addFilesToMaterial(
        User $lecturer,
        int $materialId,
        array $files
    ): array {
        $material = ClassMaterial::with('class')->find($materialId);

        if (!$material) {
            return $this->error('Tài liệu không tồn tại', 404);
        }

        if ($material->class?->lecturer_id !== $lecturer->id) {
            return $this->error('Bạn không phải giảng viên của lớp này', 403);
        }

        if (empty($files)) {
            return $this->error('Cần ít nhất 1 file', 422);
        }

        foreach ($files as $idx => $file) {
            $check = $this->validateFile($file);
            if ($check !== true) {
                return $this->error("File thứ " . ($idx + 1) . ": {$check}", 422);
            }
        }

        try {
            DB::transaction(function () use ($material, $files) {
                $maxOrder = $material->files()->max('sort_order') ?? 0;

                foreach ($files as $idx => $file) {
                    $this->storeFile($material->id, $material->class_id, $file, $maxOrder + 1 + $idx);
                }
            });

            return $this->success(
                "Đã thêm " . count($files) . " file vào \"{$material->title}\"",
                ['material' => $this->formatMaterial($material->fresh(['uploader', 'files']))]
            );
        } catch (\Exception $e) {
            \Log::error('Add files failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa 1 file con (không xóa title)
     */
    public function deleteFile(User $lecturer, int $fileId): array
    {
        $file = ClassMaterialFile::with('material.class')->find($fileId);

        if (!$file) {
            return $this->error('File không tồn tại', 404);
        }

        if ($file->material?->class?->lecturer_id !== $lecturer->id) {
            return $this->error('Bạn không có quyền xóa', 403);
        }

        try {
            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }
            $file->delete();

            return $this->success('Đã xóa file');
        } catch (\Exception $e) {
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa cả title (kéo theo tất cả file)
     */
    public function deleteMaterial(User $lecturer, int $materialId): array
    {
        $material = ClassMaterial::with(['class', 'files'])->find($materialId);

        if (!$material) {
            return $this->error('Tài liệu không tồn tại', 404);
        }

        if ($material->class?->lecturer_id !== $lecturer->id) {
            return $this->error('Bạn không có quyền xóa', 403);
        }

        try {
            DB::transaction(function () use ($material) {
                // Xóa file vật lý
                foreach ($material->files as $f) {
                    if (Storage::exists($f->file_path)) {
                        Storage::delete($f->file_path);
                    }
                }
                // Xóa material (cascade xóa class_material_files)
                $material->delete();
            });

            return $this->success('Đã xóa tài liệu');
        } catch (\Exception $e) {
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật title/description/category
     */
    public function updateMaterial(User $lecturer, int $materialId, array $data): array
    {
        $material = ClassMaterial::with('class')->find($materialId);

        if (!$material) {
            return $this->error('Tài liệu không tồn tại', 404);
        }

        if ($material->class?->lecturer_id !== $lecturer->id) {
            return $this->error('Bạn không có quyền sửa', 403);
        }

        $material->update(array_filter([
            'title'       => $data['title'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'category'    => $data['category'] ?? null,
        ], fn($v) => !is_null($v)));

        return $this->success('Đã cập nhật', [
            'material' => $this->formatMaterial($material->fresh(['uploader', 'files'])),
        ]);
    }

    /**
     * Lấy danh sách materials trong lớp
     */
    public function getMaterials(User $user, int $classId, array $filters = []): array
    {
        $class = Classes::find($classId);

        if (!$class) {
            return $this->error('Lớp không tồn tại', 404);
        }

        $isLecturer = $class->lecturer_id === $user->id;
        $isStudent  = $class->students()->where('student_id', $user->id)->exists();

        if (!$isLecturer && !$isStudent) {
            return $this->error('Bạn không thuộc lớp này', 403);
        }

        $query = ClassMaterial::where('class_id', $classId)
            ->with(['uploader:id,name,code', 'files']);

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('files', fn($q) => $q->where('file_name', 'like', "%{$search}%"));
            });
        }

        $materials = $query->latest()->get()
            ->map(fn($m) => $this->formatMaterial($m));

        $stats = ClassMaterial::where('class_id', $classId)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return $this->success('Danh sách tài liệu', [
            'materials' => $materials,
            'total'     => $materials->count(),
            'stats'     => $stats,
        ]);
    }

    /**
     * Sao chép title (kèm toàn bộ file con) sang lớp khác
     */
    public function copyMaterials(
        User $lecturer,
        array $materialIds,
        array $targetClassIds
    ): array {
        $targetClasses = Classes::whereIn('id', $targetClassIds)
            ->where('lecturer_id', $lecturer->id)
            ->get();

        if ($targetClasses->count() !== count($targetClassIds)) {
            return $this->error('Một số lớp đích không thuộc về bạn', 403);
        }

        $materials = ClassMaterial::with('files')
            ->whereIn('id', $materialIds)
            ->whereHas('class', fn($q) => $q->where('lecturer_id', $lecturer->id))
            ->get();

        if ($materials->count() !== count($materialIds)) {
            return $this->error('Một số tài liệu không thuộc lớp của bạn', 403);
        }

        $copiedCount    = 0;
        $skippedCount   = 0;
        $skippedReasons = [];

        try {
            DB::transaction(function () use (
                $materials, $targetClasses, $lecturer,
                &$copiedCount, &$skippedCount, &$skippedReasons
            ) {
                foreach ($targetClasses as $targetClass) {
                    foreach ($materials as $source) {
                        // Skip copy về chính lớp gốc
                        if ($source->class_id === $targetClass->id) {
                            $skippedCount++;
                            $skippedReasons[] = "Bỏ qua: \"{$source->title}\" trùng lớp gốc";
                            continue;
                        }

                        // Skip trùng title
                        $exists = ClassMaterial::where('class_id', $targetClass->id)
                            ->where('title', $source->title)
                            ->exists();

                        if ($exists) {
                            $skippedCount++;
                            $skippedReasons[] = "Bỏ qua: \"{$source->title}\" đã có trong {$targetClass->name}";
                            continue;
                        }

                        // Tạo title mới
                        $newMaterial = ClassMaterial::create([
                            'class_id'    => $targetClass->id,
                            'uploaded_by' => $lecturer->id,
                            'title'       => $source->title,
                            'description' => $source->description,
                            'category'    => $source->category,
                            'copied_from' => $source->id,
                        ]);

                        // Copy từng file con
                        foreach ($source->files as $sourceFile) {
                            $newFileName = Str::random(40) . '.' . $sourceFile->file_extension;
                            $newFilePath = "class_materials/{$targetClass->id}/{$newFileName}";

                            if (Storage::exists($sourceFile->file_path)) {
                                Storage::copy($sourceFile->file_path, $newFilePath);
                            }

                            ClassMaterialFile::create([
                                'material_id'    => $newMaterial->id,
                                'file_path'      => $newFilePath,
                                'file_name'      => $sourceFile->file_name,
                                'file_extension' => $sourceFile->file_extension,
                                'file_mime'      => $sourceFile->file_mime,
                                'file_size'      => $sourceFile->file_size,
                                'sort_order'     => $sourceFile->sort_order,
                            ]);
                        }

                        $copiedCount++;
                    }
                }
            });

            return $this->success(
                "Đã sao chép {$copiedCount} tài liệu"
                . ($skippedCount > 0 ? ", bỏ qua {$skippedCount}" : ''),
                [
                    'copied_count'    => $copiedCount,
                    'skipped_count'   => $skippedCount,
                    'skipped_reasons' => $skippedReasons,
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Copy materials failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Danh sách lớp đích (ưu tiên cùng môn)
     */
    public function getCopyTargetClasses(User $lecturer, int $sourceClassId): array
    {
        $sourceClass = Classes::with('subjects:id,code,name')->find($sourceClassId);

        if (!$sourceClass || $sourceClass->lecturer_id !== $lecturer->id) {
            return $this->error('Lớp nguồn không hợp lệ', 403);
        }

        $sourceSubjectIds = $sourceClass->subjects->pluck('id')->toArray();

        $allClasses = Classes::where('lecturer_id', $lecturer->id)
            ->where('id', '!=', $sourceClassId)
            ->with(['subjects:id,code,name', 'semester:id,name'])
            ->get()
            ->map(function ($c) use ($sourceSubjectIds) {
                $shared = $c->subjects->contains(fn($s) => in_array($s->id, $sourceSubjectIds));
                return [
                    'id'              => $c->id,
                    'name'            => $c->name,
                    'code'            => $c->code,
                    'semester_name'   => $c->semester?->name,
                    'subjects'        => $c->subjects->map(fn($s) => [
                        'id'   => $s->id,
                        'code' => $s->code,
                        'name' => $s->name,
                    ]),
                    'is_same_subject' => $shared,
                ];
            })
            ->sortByDesc('is_same_subject')
            ->values();

        return $this->success('Danh sách lớp đích', [
            'classes' => $allClasses,
            'source'  => [
                'id'       => $sourceClass->id,
                'name'     => $sourceClass->name,
                'subjects' => $sourceClass->subjects->map(fn($s) => [
                    'id'   => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                ]),
            ],
        ]);
    }

    /**
     * Track download của 1 file
     */
    public function trackDownload(User $user, int $fileId): array
    {
        $file = ClassMaterialFile::with('material.class')->find($fileId);

        if (!$file) {
            return $this->error('File không tồn tại', 404);
        }

        $class = $file->material?->class;
        if (!$class) {
            return $this->error('Tài liệu không thuộc lớp', 404);
        }

        $isLecturer = $class->lecturer_id === $user->id;
        $isStudent  = $class->students()->where('student_id', $user->id)->exists();

        if (!$isLecturer && !$isStudent) {
            return $this->error('Bạn không thuộc lớp này', 403);
        }

        if (!Storage::exists($file->file_path)) {
            return $this->error('File không tồn tại trên server', 404);
        }

        $file->increment('download_count');

        return $this->success('OK', [
            'download_url' => $file->download_url,
            'file_name'    => $file->file_name,
        ]);
    }

    // ─── Private helpers ──────────────────────

    private function validateFile(UploadedFile $file)
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return 'Vượt quá 50MB';
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
            return "Định dạng .{$ext} không được hỗ trợ";
        }

        return true;
    }

    private function storeFile(int $materialId, int $classId, UploadedFile $file, int $sortOrder): ClassMaterialFile
    {
        $extension  = $file->getClientOriginalExtension();
        $storedName = Str::random(40) . '.' . $extension;
        $filePath   = "class_materials/{$classId}/{$storedName}";

        Storage::put($filePath, file_get_contents($file->getRealPath()));

        return ClassMaterialFile::create([
            'material_id'    => $materialId,
            'file_path'      => $filePath,
            'file_name'      => $file->getClientOriginalName(),
            'file_extension' => strtolower($extension),
            'file_mime'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
            'sort_order'     => $sortOrder,
        ]);
    }

    private function formatMaterial(ClassMaterial $m): array
    {
        return [
            'id'             => $m->id,
            'title'          => $m->title,
            'description'    => $m->description,
            'category'       => $m->category,
            'category_label' => $m->category_label,
            'uploader'       => $m->uploader ? [
                'id'   => $m->uploader->id,
                'name' => $m->uploader->name,
                'code' => $m->uploader->code,
            ] : null,
            'is_copied'      => !is_null($m->copied_from),
            'file_count'     => $m->files->count(),
            'total_size'     => $m->files->sum('file_size'),
            'files'          => $m->files->map(fn($f) => [
                'id'                  => $f->id,
                'file_name'           => $f->file_name,
                'file_extension'      => $f->file_extension,
                'file_size'           => $f->file_size,
                'file_size_formatted' => $f->file_size_formatted,
                'icon'                => $f->icon,
                'sort_order'          => $f->sort_order,
                'download_count'      => $f->download_count,
            ]),
            'created_at'     => $m->created_at,
            'updated_at'     => $m->updated_at,
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