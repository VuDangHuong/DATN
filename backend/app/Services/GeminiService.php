<?php
namespace App\Services;

use App\Models\Chat\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            'model' => 'models/gemini-embedding-001',
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
    public function generateAnswer(string $question, string $context,  bool $isFirstMessage = false): string
    {
        $currentDate = now()->format('d/m/Y');
        $currentDay  = now()->locale('vi')->isoFormat('dddd');
        $greetingRule = $isFirstMessage
    ? "- Đây là câu hỏi ĐẦU TIÊN của người dùng trong phiên này. Hãy mở đầu bằng một lời chào thân thiện (vd: \"Chào bạn 👋\") trước khi trả lời."
    : "- Đây KHÔNG phải câu hỏi đầu tiên. TUYỆT ĐỐI KHÔNG chào hỏi lại (không \"Chào bạn\", không \"Xin chào\"). Vào thẳng nội dung trả lời.";
        // ========================================================
        // SYSTEM INSTRUCTION - Nhân cách cố định cho chatbot
        // ========================================================
        $systemInstruction = <<<SYSTEM
    Bạn là "TLU Bot" — trợ lý AI tư vấn tuyển sinh của Trường Đại học Thủy lợi (Thuyloi University - TLU).
    Ngày hôm nay: {$currentDay}, {$currentDate}.

    ## TÍNH CÁCH & GIỌNG ĐIỆU
    - Thân thiện, nhiệt tình như một anh/chị sinh viên năm cuối đang tư vấn cho em khóa dưới.
    - Trả lời ngắn gọn, dễ hiểu, tránh văn phong hành chính khô khan.
    - Dùng emoji vừa phải (1-2 emoji/câu trả lời) để tạo sự gần gũi.
    - Xưng hô: "mình" hoặc "TLU Bot", gọi người hỏi là "bạn".
    {$greetingRule}
    ## PHẠM VI CHUYÊN MÔN CHÍNH
    - Tư vấn tuyển sinh: phương thức xét tuyển, điểm chuẩn, ngành đào tạo, hồ sơ nhập học.
    - Thông tin trường: lịch sử, cơ sở vật chất, chương trình đào tạo, học phí, học bổng.
    - Đời sống sinh viên: KTX, CLB, hoạt động ngoại khóa, thủ tục hành chính.
    - Quy chế: thi cử, đăng ký học, tốt nghiệp, kỷ luật.

    ## QUY TẮC TRẢ LỜI
    1. Ưu tiên dùng dữ liệu trong [THÔNG TIN TRI THỨC] nếu có.
    2. Nếu dữ liệu tri thức không đủ chi tiết → bổ sung kiến thức chung hợp lý, nhưng ghi chú rõ: "📌 Thông tin này mang tính tham khảo, bạn nên liên hệ Phòng Đào tạo/Tuyển sinh để xác nhận chính xác nhé!"
    3. Nếu câu hỏi KHÔNG liên quan đến trường/tuyển sinh (tin tức, thời tiết, kiến thức chung...):
    → Vẫn trả lời hữu ích dựa trên kiến thức của bạn.
    → Với thông tin thời gian thực (giá vàng, thời tiết, tỷ giá, kết quả bóng đá...):
        nói rõ bạn không cập nhật real-time và gợi ý nguồn tra cứu cụ thể.
    4. KHÔNG BAO GIỜ bịa số liệu cụ thể (điểm chuẩn, học phí, SĐT) nếu không có trong dữ liệu.
    5. Khi trả lời về điểm chuẩn/học phí → luôn nhắc "số liệu có thể thay đổi theo năm" và hướng dẫn xem website chính thức: tlu.edu.vn
    6. Nếu hoàn toàn không biết → trả lời thật: "Mình chưa có thông tin về vấn đề này. Bạn có thể liên hệ trực tiếp qua hotline tuyển sinh hoặc website tlu.edu.vn để được hỗ trợ nhé! 😊"

    ## ĐỊNH DẠNG
    - Dùng markdown nhẹ: **in đậm** cho keyword, bullet list khi liệt kê ≥3 mục.
    - Mỗi câu trả lời nên kết thúc bằng 1 câu mời hỏi thêm hoặc gợi ý liên quan.
    - Giới hạn trả lời trong 200-400 từ, trừ khi câu hỏi cần giải thích dài.
    SYSTEM;

        // ========================================================
        // USER PROMPT - Thay đổi theo có/không có context
        // ========================================================
        if (empty($context) || $context === 'Không có dữ liệu tri thức liên quan.') {
            // Không có context từ Knowledge Base
            $userPrompt = <<<PROMPT
    [THÔNG TIN TRI THỨC]
    Không tìm thấy dữ liệu liên quan trong cơ sở tri thức.

    [CÂU HỎI CỦA NGƯỜI DÙNG]
    {$question}

    Hãy trả lời dựa trên kiến thức chung của bạn. 
    - Nếu liên quan đến TLU: trả lời những gì bạn biết + khuyên liên hệ trường để xác nhận.
    - Nếu là câu hỏi chung (tin tức, kiến thức, giải trí...): trả lời bình thường như một AI assistant thông minh.
    - Nếu là thông tin thời gian thực: nói rõ giới hạn và gợi ý nguồn tra cứu.
    PROMPT;
        } else {
            // Có context từ Knowledge Base
            $userPrompt = <<<PROMPT
    [THÔNG TIN TRI THỨC]
    {$context}

    [CÂU HỎI CỦA NGƯỜI DÙNG]
    {$question}

    Trả lời dựa trên [THÔNG TIN TRI THỨC] ở trên. 
    Nếu thông tin chưa đủ, bạn có thể bổ sung kiến thức hợp lý nhưng phải ghi chú rõ phần nào là từ dữ liệu, phần nào là tham khảo thêm.
    PROMPT;
        }

        // ========================================================
        // GỌI GEMINI API - Với system instruction tách riêng
        // ========================================================
        $models = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash-001',
            'gemini-2.5-flash',
        ];

        foreach ($models as $model) {
            $response = Http::timeout(30)->post(
                "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}",
                [
                    // System instruction → giữ nhân cách ổn định
                    'system_instruction' => [
                        'parts' => [['text' => $systemInstruction]],
                    ],
                    // User message
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => [['text' => $userPrompt]],
                        ],
                    ],
                    // Tham số sinh text
                    'generationConfig' => [
                        'temperature'     => 0.7,   // Cân bằng sáng tạo & chính xác
                        'topP'            => 0.9,
                        'topK'            => 40,
                        'maxOutputTokens' => 1024,
                    ],
                    // Chặn nội dung không phù hợp
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT',         'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH',        'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',  'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',  'threshold' => 'BLOCK_ONLY_HIGH'],
                    ],
                ]
            );

            if ($response->successful()) {
                $answer = $response->json('candidates.0.content.parts.0.text');
                // Model đôi khi vẫn chào dù đã cấm → cắt cứng khi không phải câu đầu
                if (!$isFirstMessage && $answer) {
                    $answer = preg_replace(
                        '/^\s*(Chào bạn|Xin chào|Chào)\b[^\n]*?[,.!\n]+\s*/iu',
                        '',
                        $answer,
                        1
                    );
                    $answer = ltrim($answer);
                }
                return $answer ?: 'Xin lỗi, mình chưa thể trả lời lúc này. Bạn thử hỏi lại nhé! 😊';
            }

            // 429 = rate limit → thử model tiếp theo
            if ($response->status() === 429) {
                Log::warning("Gemini rate limit on model: {$model}");
                continue;
            }

            // Lỗi khác → log và dừng
            Log::error("Gemini API error", [
                'model'  => $model,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            break;
        }

        return 'Hệ thống đang bận, bạn vui lòng thử lại sau ít phút nhé! 🙏';
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
                            //Phần file/ảnh base64
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileBase64,
                                ],
                            ],
                            // Phần câu hỏi text
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
            //Ảnh
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',

            // PDF
            'pdf' => 'application/pdf',

            // Excel
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv',

            // Word
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',

            default => null,
        };
    }
    // Thêm vào GeminiService.php — sau hàm generateAnswerWithFile

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

                // Parse ra mảng — hỗ trợ cả format "1." và "* " và "-"
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
                    array_filter($questions, fn($q) => mb_strlen($q) > 5),
                    0, 3
                );
            }

            if ($response->status() === 429) {
                \Log::warning("429 generateSuggestedQuestions: {$model}");
                continue;
            }

            \Log::error("generateSuggestedQuestions error: {$model}", [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            break;
        }

        return [];
    }
}