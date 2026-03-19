<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatHistory;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminChatbotController extends Controller
{
    public function __construct(private GeminiService $gemini)
    {
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,xlsx,xls,csv,doc,docx|max:10240',
        ]);

        // Phải có ít nhất question hoặc file
        if (!$request->filled('question') && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập câu hỏi hoặc đính kèm file.',
            ], 422);
        }

        $question = trim($request->input('question', 'Hãy mô tả nội dung file/ảnh này.'));
        $adminId = Auth::id();
        $fileName = null;
        $answer = '';
        $isAnswered = 2;
        $contexts = [];

        // =====================================================
        // TH1: Có file đính kèm
        // =====================================================
        // Thêm vào AdminChatbotController@ask
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $this->gemini->getSupportedMimeType($extension);

            // ✅ Excel/CSV/Word → đọc text rồi ghép vào prompt
            if (in_array($extension, ['xlsx', 'xls', 'csv', 'doc', 'docx'])) {
                $fileText = $this->extractTextFromFile($file, $extension);
                $answer = $this->gemini->generateAnswer(
                    $question,
                    "=== NỘI DUNG FILE ===\n{$fileText}"
                );
                $isAnswered = 2;

                // ✅ Ảnh/PDF → gửi base64 trực tiếp
            } else {
                $fileBase64 = base64_encode(file_get_contents($file->getRealPath()));
                $answer = $this->gemini->generateAnswerWithFile($question, '', $fileBase64, $mimeType);
                $isAnswered = 2;
            }
        } else {
            $contexts = $this->gemini->findRelevantContexts($question, $request->category, 5);

            if (empty($contexts)) {
                $answer = $this->gemini->generateAnswer($question, 'Không có dữ liệu tri thức liên quan.');
                $isAnswered = 2;
            } else {
                $contextText = collect($contexts)
                    ->map(fn($c) => "Hỏi: {$c['question']}\nĐáp: {$c['answer']}")
                    ->implode("\n\n---\n\n");

                $answer = $this->gemini->generateAnswer($question, $contextText);
                $isAnswered = 1;
            }
        }

        // Lưu log
        $log = ChatHistory::create([
            'user_id' => $adminId,
            'question' => $question,
            'answer' => $answer,
            'file_name' => $fileName,   // ✅ lưu tên file nếu có
            'source_text' => $contexts,
            'is_answered' => $isAnswered,
            'type' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'question' => $question,
                'answer' => $answer,
                'file_name' => $fileName,
                'is_answered' => $isAnswered,
                'sources' => $contexts,
                'created_at' => $log->created_at,
            ],
        ]);
    }
    private function extractTextFromFile($file, string $extension): string
    {
        // CSV → đọc thẳng
        if ($extension === 'csv') {
            $content = file_get_contents($file->getRealPath());
            return mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        // Excel (xlsx, xls) → dùng PhpSpreadsheet
        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $text = '';

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= "Sheet: {$sheet->getTitle()}\n";
                foreach ($sheet->toArray() as $row) {
                    $text .= implode(' | ', array_filter($row, fn($v) => $v !== null)) . "\n";
                }
            }
            return $text;
        }

        // Word (docx, doc) → dùng PhpWord
        if (in_array($extension, ['docx', 'doc'])) {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            return $text;
        }

        return '';
    }
    public function suggestedQuestions(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
        ]);

        $suggestions = $this->gemini->generateSuggestedQuestions(
            $request->question,
            $request->answer
        );

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }
    //history
    public function history(Request $request)
    {
        $logs = ChatHistory::where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    //user
                    'user_message' => [
                        'role' => 'user',
                        'content' => $log->question,
                        'created_at' => $log->created_at,
                    ],
                    //bot
                    'bot_message' => [
                        'role' => 'assistant',
                        'content' => $log->answer,
                        'is_answered' => $log->is_answered,
                        'is_liked' => $log->is_liked,
                        'star' => $log->star,
                        'sources' => $log->source_text ?? [],
                        'created_at' => $log->updated_at,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
            'total' => $logs->count(),
        ]);
    }
    //feedback
    public function feedback(Request $request, int $id)
    {
        $request->validate([
            'is_liked' => 'nullable|boolean',
            'star' => 'nullable|integer|min:1|max:5',
        ]);

        $log = ChatHistory::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $log->update($request->only(['is_liked', 'star']));

        return response()->json(['success' => true, 'message' => 'Cảm ơn phản hồi!']);
    }
}