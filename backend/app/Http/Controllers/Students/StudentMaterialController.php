<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Services\ClassMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentMaterialController extends Controller
{
     public function __construct(private readonly ClassMaterialService $service) {}
 
    public function index(Request $request, int $classId): JsonResponse
    {
        $filters = $request->only(['category', 'search']);
        $result  = $this->service->getMaterials(auth()->user(), $classId, $filters);
 
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code'] ?? 400);
        }
        return response()->json($result['data']);
    }
 
    public function downloadFile(int $fileId): JsonResponse
    {
        $result = $this->service->trackDownload(auth()->user(), $fileId);
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code'] ?? 400);
        }
        return response()->json($result['data']);
    }
}
