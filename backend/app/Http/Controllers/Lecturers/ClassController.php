<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = Classes::where('lecturer_id', $request->user()->id)
            ->select('id', 'name', 'code')
            ->get();

        return response()->json($classes);
    }
}
