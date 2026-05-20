<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ClassMaterialService;
use Illuminate\Http\JsonResponse;
class ClassMaterialController extends Controller
{
    public function __construct(
        private readonly ClassMaterialService $service,
    ) {}
 
    /**
     * GET /api/lecturer/classes/{classId}/materials
     */
    public function index(Request $request, int $classId): JsonResponse
    {
        $filters = $request->only(['category', 'search']);
        $result  = $this->service->getMaterials(auth()->user(), $classId, $filters);
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/lecturer/classes/{classId}/materials
     * Multipart: title, description, category, files[]
     */
    public function store(Request $request, int $classId): JsonResponse
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'category'      => 'nullable|in:lecture,exercise,reference,exam,other',
            'files'         => 'required|array|min:1|max:20',
            'files.*'       => 'required|file|max:51200',   // 50MB / file
        ], [
            'files.required' => 'Cần ít nhất 1 file',
            'files.min'      => 'Cần ít nhất 1 file',
            'files.max'      => 'Tối đa 20 file mỗi lần upload',
            'files.*.max'    => 'Mỗi file tối đa 50MB',
        ]);
 
        $result = $this->service->createMaterial(
            auth()->user(),
            $classId,
            $request->file('files') ?? [],
            $request->only(['title', 'description', 'category']),
        );
 
        return $this->toResponse($result, 201);
    }
 
    /**
     * POST /api/lecturer/materials/{id}/files
     * Thêm file vào title đã có
     * Multipart: files[]
     */
    public function addFiles(Request $request, int $materialId): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|min:1|max:20',
            'files.*' => 'required|file|max:51200',
        ]);
 
        $result = $this->service->addFilesToMaterial(
            auth()->user(),
            $materialId,
            $request->file('files') ?? [],
        );
 
        return $this->toResponse($result);
    }
 
    /**
     * PATCH /api/lecturer/materials/{id}
     * Cập nhật title/description/category
     */
    public function update(Request $request, int $materialId): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:lecture,exercise,reference,exam,other',
        ]);
 
        $result = $this->service->updateMaterial(auth()->user(), $materialId, $data);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /api/lecturer/materials/{id}
     * Xóa cả title (kéo theo files)
     */
    public function destroy(int $materialId): JsonResponse
    {
        $result = $this->service->deleteMaterial(auth()->user(), $materialId);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /api/lecturer/material-files/{id}
     * Xóa 1 file con
     */
    public function deleteFile(int $fileId): JsonResponse
    {
        $result = $this->service->deleteFile(auth()->user(), $fileId);
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/lecturer/materials/copy
     * Body: { material_ids: [], target_class_ids: [] }
     */
    public function copy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'material_ids'       => 'required|array|min:1',
            'material_ids.*'     => 'integer|exists:class_materials,id',
            'target_class_ids'   => 'required|array|min:1',
            'target_class_ids.*' => 'integer|exists:classes,id',
        ]);
 
        $result = $this->service->copyMaterials(
            auth()->user(),
            $data['material_ids'],
            $data['target_class_ids'],
        );
 
        return $this->toResponse($result);
    }
 
    /**
     * GET /api/lecturer/classes/{classId}/copy-targets
     */
    public function copyTargets(int $classId): JsonResponse
    {
        $result = $this->service->getCopyTargetClasses(auth()->user(), $classId);
        return $this->toResponse($result);
    }
 
    /**
     * GET /api/lecturer/material-files/{id}/download
     */
    public function downloadFile(int $fileId): JsonResponse
    {
        $result = $this->service->trackDownload(auth()->user(), $fileId);
        return $this->toResponse($result);
    }
 
    private function toResponse(array $result, int $successCode = 200): JsonResponse
    {
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code'] ?? 400);
        }
        return response()->json([
            'message' => $result['message'],
            ...$result['data'],
        ], $successCode);
    }
}
