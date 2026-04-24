<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .card { background: #fff; border-radius: 12px; max-width: 560px; margin: 0 auto; overflow: hidden; }
        .header { padding: 28px 32px; }
        .header-approved { background: #dcfce7; border-bottom: 3px solid #16a34a; }
        .header-rejected { background: #fee2e2; border-bottom: 3px solid #dc2626; }
        .icon { font-size: 36px; margin-bottom: 8px; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .title-approved { color: #15803d; }
        .title-rejected { color: #b91c1c; }
        .body { padding: 24px 32px; }
        .greeting { color: #374151; margin-bottom: 16px; }
        .info-box { background: #f9fafb; border-radius: 8px; padding: 16px; margin: 16px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; }
        .info-value { color: #111827; font-weight: 600; }
        .score { font-size: 32px; font-weight: 700; color: #4f46e5; text-align: center; padding: 12px; }
        .feedback-box { background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 8px 8px 0; padding: 12px 16px; margin: 16px 0; font-size: 14px; color: #1e3a8a; }
        .rejected-box { background: #fff7ed; border-left: 4px solid #f97316; border-radius: 0 8px 8px 0; padding: 12px 16px; margin: 16px 0; font-size: 14px; color: #9a3412; }
        .footer { padding: 16px 32px; background: #f9fafb; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div class="header {{ $submission->isApproved() ? 'header-approved' : 'header-rejected' }}">
        <div class="icon">{{ $submission->isApproved() ? '✅' : '❌' }}</div>
        <p class="title {{ $submission->isApproved() ? 'title-approved' : 'title-rejected' }}">
            {{ $submission->isApproved() ? 'Bài nộp được chấp nhận' : 'Bài nộp bị từ chối' }}
        </p>
    </div>

    <div class="body">
        <p class="greeting">Xin chào <strong>{{ $recipientName }}</strong>,</p>

        @if($submission->isApproved())
            <p style="color:#374151">Giảng viên đã <strong style="color:#15803d">chấp nhận</strong> bài nộp của bạn.</p>
        @else
            <p style="color:#374151">Giảng viên đã <strong style="color:#b91c1c">từ chối</strong> bài nộp của bạn. Vui lòng xem nhận xét và nộp lại nếu cần.</p>
        @endif

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Đợt nộp</span>
                <span class="info-value">{{ $submission->assignment->title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">File đã nộp</span>
                <span class="info-value">{{ $submission->file_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Thời gian nộp</span>
                <span class="info-value">{{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Thời gian duyệt</span>
                <span class="info-value">{{ $submission->reviewed_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Người duyệt</span>
                <span class="info-value">{{ $submission->reviewer->name }}</span>
            </div>
        </div>

        @if($submission->score !== null)
            <div class="score">{{ number_format($submission->score, 2) }} / 10</div>
        @endif

        @if($submission->feedback)
            <div class="{{ $submission->isApproved() ? 'feedback-box' : 'rejected-box' }}">
                <strong>Nhận xét của giảng viên:</strong><br>
                {{ $submission->feedback }}
            </div>
        @endif
    </div>

    <div class="footer">
        Email này được gửi tự động từ hệ thống EduGroup. Vui lòng không trả lời email này.
    </div>
</div>
</body>
</html>