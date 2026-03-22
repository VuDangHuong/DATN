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
    // ✅ Nếu không có context → cho AI trả lời tự do
    if (empty($context) || $context === 'Không có dữ liệu tri thức liên quan.') {
        $prompt = <<<PROMPT
Bạn là trợ lý AI thông minh. Hãy trả lời câu hỏi sau một cách chính xác và hữu ích.
Lưu ý: Nếu câu hỏi yêu cầu thông tin thời gian thực (giá cả hôm nay, thời tiết...) 
hãy nói rõ bạn không có dữ liệu thời gian thực và gợi ý nguồn tra cứu.

=== CÂU HỎI ===
{$question}
PROMPT;
    } else {
        // Có context → trả lời dựa trên KB
        $prompt = <<<PROMPT
Bạn là trợ lý AI chăm sóc khách hàng chuyên nghiệp.
Dựa vào thông tin bên dưới để trả lời câu hỏi. 
Nếu không tìm thấy thông tin phù hợp, hãy trả lời: "Tôi chưa có thông tin về vấn đề này."

=== THÔNG TIN ===
{$context}

=== CÂU HỎI ===
{$question}
PROMPT;
    }

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
            return $response->json('candidates.0.content.parts.0.text', 'Không thể tạo câu trả lời.');
        }

        if ($response->status() === 429) continue;
        break;
    }

    return 'Không thể tạo câu trả lời.';
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

    /**
     * Gọi Gemini với file đính kèm (ảnh hoặc PDF)
     */
    public function generateAnswerWithFile(
        string $question,
        string $context,
        string $fileBase64,
        string $mimeType
    ): string {
        $contextPart = $context
            ? "=== THÔNG TIN HỆ THỐNG ===\n{$context}\n\n"
            : '';

        $prompt = <<<PROMPT
Bạn là trợ lý AI chăm sóc khách hàng chuyên nghiệp.
{$contextPart}
Người dùng đã gửi kèm file/ảnh. Hãy phân tích nội dung file/ảnh đó và trả lời câu hỏi bên dưới.
Nếu không đủ thông tin, hãy trả lời: "Tôi chưa có thông tin về vấn đề này."

=== CÂU HỎI ===
{$question}
PROMPT;

        $models = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash-001',
            'gemini-2.5-flash',
        ];

        foreach ($models as $model) {
            $response = Http::post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            // ✅ Phần file/ảnh base64
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileBase64,
                                ],
                            ],
                            // ✅ Phần câu hỏi text
                            ['text' => $prompt],
                        ],
                    ]
                ],
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text', 'Không thể tạo câu trả lời.');
            }

            if ($response->status() === 429) {
                \Log::warning("429 generateAnswerWithFile: {$model}");
                continue;
            }

            \Log::error("generateAnswerWithFile error: {$model}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            break;
        }

        return 'Không thể tạo câu trả lời.';
    }

    /**
     * Lấy MIME type được Gemini hỗ trợ
     */
    public function getSupportedMimeType(string $extension): ?string
    {
        return match (strtolower($extension)) {
            // ✅ Ảnh
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',

            // ✅ PDF
            'pdf' => 'application/pdf',

            // ✅ Excel
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv',

            // ✅ Word
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',

            default => null,
        };
    }
}