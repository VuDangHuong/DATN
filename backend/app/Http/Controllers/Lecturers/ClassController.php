<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = Classes::where('lecturer_id', $request->user()->id)
            ->select('id', 'name', 'code')
            ->get();

        return response()->json($classes);
    }
    public function groups(int $classId): JsonResponse
    {
        $class = Classes::where('lecturer_id', Auth::id())->findOrFail($classId);
    
        $groups = $class->groups()->select('id', 'name', 'leader_id')->get();
    
        return response()->json(['groups' => $groups]);
    }
}
