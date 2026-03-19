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
        ]);

        $question = trim($request->question);
        $adminId = Auth::id();

        //Tìm context
        $contexts = $this->gemini->findRelevantContexts($question, $request->category, 5);

        //Sinh câu trả lời
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

        //Sinh câu hỏi gợi ý song song sau khi có answer
        // $suggestedQuestions = $this->gemini->generateSuggestedQuestions($question, $answer);

        // 4. Lưu log
        $log = ChatHistory::create([
            'user_id' => $adminId,
            'question' => $question,
            'answer' => $answer,
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
                'is_answered' => $isAnswered,
                'sources' => $contexts,
                // 'suggested_questions' => $suggestedQuestions,
                'created_at' => $log->created_at,
            ],
        ]);
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