<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat\KnowledgeBase;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function __construct(private GeminiService $gemini)
    {
    }

    public function index(Request $request)
    {
        $query = KnowledgeBase::query();

        if ($request->filled('search')) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('question', 'like', "%{$kw}%")
                    ->orWhere('answer', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // ── Sort ──────────────────────────────────────────────────────
        match ($request->input('sort_by', 'date')) {
            'name' => $query->orderBy('question', 'asc'),
            default => $query->orderByDesc('created_at'),
        };

        $data = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    //lấy danh sách category
    public function categories()
    {
        $cats = KnowledgeBase::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json(['success' => true, 'data' => $cats]);
    }

    //Kho tri thức post
    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
        ]);

        // Tạo embedding cho câu hỏi
        $data['embedding'] = $this->gemini->getEmbedding($data['question']);

        $item = KnowledgeBase::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm tri thức thành công!',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $item = KnowledgeBase::findOrFail($id);

        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
        ]);

        // Chỉ re-embed khi câu hỏi thay đổi
        if ($data['question'] !== $item->question) {
            $data['embedding'] = $this->gemini->getEmbedding($data['question']);
        }

        $item->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công!',
            'data' => $item,
        ]);
    }

    public function destroy(int $id)
    {
        KnowledgeBase::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tri thức.',
        ]);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $extension = $request->file('file')->getClientOriginalExtension();

        // Đọc xlsx/xls bằng maatwebsite/excel
        if (in_array($extension, ['xlsx', 'xls'])) {
            $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $request->file('file'))[0];
            $header = array_map('trim', array_shift($rows));
        } else {
            // Đọc CSV như cũ
            $path = $request->file('file')->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            $header = array_map('trim', array_shift($rows));
        }

        if (!in_array('question', $header) || !in_array('answer', $header)) {
            return response()->json([
                'success' => false,
                'message' => 'File phải có cột "question" và "answer".',
            ], 422);
        }

        $qIdx = array_search('question', $header);
        $aIdx = array_search('answer', $header);
        $cIdx = array_search('category', $header);

        $imported = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            if (empty(trim($row[$qIdx] ?? '')))
                continue;

            try {
                $question = trim($row[$qIdx]);
                $answer = trim($row[$aIdx] ?? '');
                $category = ($cIdx !== false) ? trim($row[$cIdx] ?? '') : null;

                KnowledgeBase::create([
                    'question' => $question,
                    'answer' => $answer,
                    'category' => $category ?: null,
                    'embedding' => $this->gemini->getEmbedding($question),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Dòng " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Import thành công {$imported} bản ghi.",
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }
}