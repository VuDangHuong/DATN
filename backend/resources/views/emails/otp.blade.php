<!DOCTYPE html>
<html>
<head>
    <title>Mã xác thực OTP</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; padding: 20px;">
        <h2>Xin chào {{ $name }},</h2>
        
        <p>Bạn đã yêu cầu đặt lại mật khẩu. Đây là mã xác thực của bạn:</p>
        
        <h1 style="color: red; letter-spacing: 5px;">{{ $otp }}</h1>
        
        <p>Mã này có hiệu lực trong vòng <strong>10 phút</strong>.</p>
        <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
        <br>
        <p>Trân trọng,<br>Đội ngũ hỗ trợ.</p>
    </div>
</body>
</html>