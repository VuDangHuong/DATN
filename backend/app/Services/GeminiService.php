<?php
namespace App\Services;

use App\Models\Chat\KnowledgeBase;
use Illuminate\Support\Facades\Http;
class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Tạo vector embedding cho 1 đoạn text
     */
    public function getEmbedding(string $text): array
    {
        $response = Http::post("{$this->baseUrl}/models/gemini-embedding-001:embedContent?key={$this->apiKey}", [
            'model' => 'models/gemini-embedding-001', // ✅ đổi model
            'content' => ['parts' => [['text' => $text]]],
        ]);

        if ($response->failed()) {
            \Log::error('Gemini Embedding Error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return [];
        }

        return $response->json('embedding.values', []);
    }

    /**
     * Gọi Gemini sinh câu trả lời từ context (RAG)
     */
    public function generateAnswer(string $question, string $context): string
    {
        $prompt = <<<PROMPT
Bạn là trợ lý AI chăm sóc khách hàng chuyên nghiệp.
Dựa vào thông tin bên dưới để trả lời câu hỏi. 
Nếu không tìm thấy thông tin phù hợp, hãy trả lời: "Tôi chưa có thông tin về vấn đề này."

=== THÔNG TIN ===
{$context}

=== CÂU HỎI ===
{$question}
PROMPT;

        // Thử lần lượt các model — nếu model này hết quota thì sang model khác
        $models = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash-001',
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
        ];

        foreach ($models as $model) {
            $response = Http::post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);

            // Thành công → trả về luôn
            if ($response->successful()) {
                \Log::info("Generate OK with model: {$model}");
                return $response->json('candidates.0.content.parts.0.text', 'Không thể tạo câu trả lời.');
            }

            // 429 quota → thử model tiếp theo
            if ($response->status() === 429) {
                \Log::warning("429 quota exceeded for model: {$model}, trying next...");
                continue;
            }

            // Lỗi khác → log và dừng
            \Log::error("Generate Error model: {$model}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            break;
        }

        return 'Không thể tạo câu trả lời. Vui lòng thử lại sau.';
    }
    /**
     * Sinh 3 câu hỏi gợi ý liên quan đến câu trả lời vừa rồi
     */
    public function generateSuggestedQuestions(string $question, string $answer): array
{
    $prompt = <<<PROMPT
Dựa vào cuộc hội thoại sau, hãy sinh ra đúng 3 câu hỏi gợi ý ngắn gọn mà người dùng có thể muốn hỏi tiếp theo.

Câu hỏi vừa hỏi: {$question}
Câu trả lời: {$answer}

Yêu cầu:
- Mỗi câu hỏi trên 1 dòng riêng, bắt đầu bằng số thứ tự (1. 2. 3.)
- Ngắn gọn, rõ ràng, liên quan chủ đề vừa trả lời
- Chỉ trả về đúng 3 câu hỏi, không giải thích thêm
PROMPT;

    $models = [
        'gemini-2.0-flash-lite',
        'gemini-2.0-flash-001',
        'gemini-2.5-flash',
    ];

    foreach ($models as $model) {
        $response = Http::post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text', '');

            $lines = array_filter(
                explode("\n", trim($text)),
                fn($line) => trim($line) !== ''
            );

            $questions = array_values(
                array_map(
                    fn($line) => trim(preg_replace('/^[\d\*\-\.]+\s*/', '', trim($line))),
                    $lines
                )
            );

            return array_slice(
                array_filter($questions, fn($q) => strlen($q) > 5),
                0, 3
            );
        }

        if ($response->status() === 429) {
            \Log::warning("429 SuggestedQuestions: {$model}");
            continue; // thử model tiếp theo
        }

        break;
    }

    return [];
}
    /**
     * Cosine similarity — đo độ tương đồng giữa 2 vector
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b))
            return 0.0;

        $dot = array_sum(array_map(fn($x, $y) => $x * $y, $a, $b));
        $magA = sqrt(array_sum(array_map(fn($x) => $x * $x, $a)));
        $magB = sqrt(array_sum(array_map(fn($x) => $x * $x, $b)));

        return ($magA * $magB > 0) ? $dot / ($magA * $magB) : 0.0;
    }

    /**
     * Tìm top K context liên quan nhất từ knowledge_base
     */
    public function findRelevantContexts(string $question, string $type = null, int $topK = 5): array
    {
        $queryEmbedding = $this->getEmbedding($question);

        $query = KnowledgeBase::whereNotNull('embedding');
        if ($type)
            $query->where('category', $type);

        $results = $query->get()
            ->map(function ($item) use ($queryEmbedding) {
                return [
                    'item' => $item,
                    'score' => $this->cosineSimilarity($queryEmbedding, $item->embedding ?? []),
                ];
            })
            ->filter(fn($r) => $r['score'] >= 0.6)   // ngưỡng tối thiểu
            ->sortByDesc('score')
            ->take($topK);

        return $results->map(fn($r) => [
            'question' => $r['item']->question,
            'answer' => $r['item']->answer,
            'score' => round($r['score'], 4),
        ])->values()->toArray();
    }
}