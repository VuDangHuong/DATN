<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation\Submission;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function pendingCount(Request $request)
    {
        $count = Submission::whereHas('assignment.class', function ($q) use ($request) {
                $q->where('lecturer_id', $request->user()->id);
            })
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }
}
