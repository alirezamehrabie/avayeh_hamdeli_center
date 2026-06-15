<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>به‌روزرسانی سیستم | ۵۰۳</title>
    <style>
        /* اتصال به فونت ایران‌سنس پروژه شما */
        @font-face {
            font-family: 'IRANSans';
            src: url('../fonts/IRANSansX-Bold.woff') format('woff2'),
            url('../fonts/IRANSansX-Bold.woff') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            --bg-gradient: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 50%, #fce7f3 100%);
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            width: 100%;
            height: 100vh;
            height: 100dvh; /* هماهنگی با نوار ابزار مرورگرهای موبایل */
            overflow: hidden; /* جلوگیری کامل از اسکرول */
        }

        body {
            font-family: 'IRANSans', 'Tahoma', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
        }

        /* حباب‌های نوری پس‌زمینه */
        .bg-glow {
            position: absolute;
            width: 250px;
            height: 250px;
            background: #c084fc;
            filter: blur(80px);
            opacity: 0.4;
            border-radius: 50%;
            z-index: 1;
        }
        .bg-glow-1 { top: -5%; left: -5%; }
        .bg-glow-2 { bottom: -5%; right: -5%; background: #f472b6; }

        /* کارت شیشه‌ای لایت - بهینه‌شده برای قد صفحه موبایل */
        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 380px; /* عرض ایده‌آل موبایل */
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 24px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* آیکون فشرده‌تر برای عدم خروج از صفحه */
        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: white;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.1);
            margin-bottom: 16px;
        }

        .icon-box svg {
            width: 30px;
            height: 30px;
            stroke: url(#gradient-color);
        }

        h1 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        /* باکس زمان‌بندی فشرده و شیک */
        .timer-container {
            width: 100%;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 12px 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
        }

        .timer-label {
            font-size: 10px;
            font-weight: bold;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .timer-values {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            direction: ltr;
        }

        .time-segment {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: white;
            padding: 6px 0;
            border-radius: 10px;
            width: 54px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.02);
        }

        .time-num {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
            line-height: 1.2;
        }

        .time-label {
            font-size: 8px;
            color: var(--text-muted);
            margin-top: 3px;
            font-weight: bold;
        }

        .divider {
            font-size: 14px;
            color: #7c3aed;
            font-weight: bold;
            opacity: 0.5;
            user-select: none;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<!-- المان مخفی جهت رنگ‌آمیزی گرادینت آیکون -->
<svg width="0" height="0" style="position: absolute;">
    <defs>
        <linearGradient id="gradient-color" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#4f46e5" />
            <stop offset="50%" stop-color="#7c3aed" />
            <stop offset="100%" stop-color="#db2777" />
        </linearGradient>
    </defs>
</svg>

<div class="bg-glow bg-glow-1"></div>
<div class="bg-glow bg-glow-2"></div>

<div class="card">

    <div class="icon-box">
        <img src="{{asset("images/logo-sm.png")}}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
    </div>

    <h1 style="color: #2c3e50;">بروزرسانی سامانه</h1>
    <p style="font-size: 0.8em; line-height: 1.6; max-width: 600px; margin: 0 auto; font-weight: normal">
        در حال بهبود زیرساخت و افزودن قابلیت‌های جدید هستیم تا تجربه کاربری بهتری ارائه دهیم. به‌زودی بازمی‌گردیم.
    </p>
    <!-- بخش تایمر زنده فشرده -->
    <div class="timer-container" style="margin-top: 30px">
        <div class="timer-label">زمان تقریبی تا راه‌اندازی مجدد</div>
        <div class="timer-values">
            <!-- ثانیه -->
            <div class="time-segment">
                <span class="time-num" id="seconds">۰۰</span>
                <span class="time-label">ثانیه</span>
            </div>
            <div class="divider">:</div>
            <!-- دقیقه -->
            <div class="time-segment">
                <span class="time-num" id="minutes">۰۰</span>
                <span class="time-label">دقیقه</span>
            </div>
            <div class="divider">:</div>
            <!-- ساعت -->
            <div class="time-segment">
                <span class="time-num" id="hours">۰۰</span>
                <span class="time-label">ساعت</span>
            </div>
            <div class="divider">:</div>
            <!-- روز -->
            <div class="time-segment">
                <span class="time-num" id="days">۰۰</span>
                <span class="time-label">روز</span>
            </div>
        </div>
    </div>

    <!-- فوتر -->
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name', 'پلتفرم ما') }}
    </div>
</div>

<script>
    // تاریخ هدف: شنبه ۲۳ می ۲۰۲۶ ساعت ۰۰:۰۰:۰۰
    const targetDate = new Date("2026-06-18T19:30:00").getTime();

    function toPersianDigits(num) {
        const id = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/[0-9]/g, function (w) {
            return id[+w];
        });
    }

    function padZero(num) {
        return num < 10 ? '0' + num : num;
    }

    function updateCountdown() {
        const now = new Date().getTime();
        const difference = targetDate - now;

        if (difference <= 0) {
            document.getElementById("days").innerText = toPersianDigits("۰۰");
            document.getElementById("hours").innerText = toPersianDigits("۰۰");
            document.getElementById("minutes").innerText = toPersianDigits("۰۰");
            document.getElementById("seconds").innerText = toPersianDigits("۰۰");
            return;
        }

        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        document.getElementById("days").innerText = toPersianDigits(padZero(days));
        document.getElementById("hours").innerText = toPersianDigits(padZero(hours));
        document.getElementById("minutes").innerText = toPersianDigits(padZero(minutes));
        document.getElementById("seconds").innerText = toPersianDigits(padZero(seconds));
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>
</body>
</html>
