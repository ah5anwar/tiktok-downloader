<?php
// TikTok No-Watermark Downloader
// API: tikwm.com (free, no key required)

// Cloudflare / Browser cache bypass — PWA সঠিকভাবে কাজ করার জন্য
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$error = '';
$result = null;

function getTikTokVideo($url) {
    $apiUrl = 'https://tikwm.com/api/';
    
    $postData = [
        'url'  => $url,
        'hd'   => 1,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postData),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        return ['success' => false, 'msg' => 'সার্ভার থেকে সাড়া পাওয়া যায়নি। আবার চেষ্টা করুন।'];
    }

    $data = json_decode($response, true);

    if (!$data || $data['code'] !== 0) {
        $msg = $data['msg'] ?? 'ভিডিও খুঁজে পাওয়া যায়নি। সঠিক TikTok লিংক দিন।';
        return ['success' => false, 'msg' => $msg];
    }

    $videoData = $data['data'];
    return [
        'success'    => true,
        'title'      => $videoData['title'] ?? 'TikTok Video',
        'cover'      => $videoData['cover'] ?? '',
        'author'     => $videoData['author']['nickname'] ?? '',
        'avatar'     => $videoData['author']['avatar'] ?? '',
        'play'       => $videoData['play'] ?? '',      // no watermark
        'hdplay'     => $videoData['hdplay'] ?? '',    // HD no watermark
        'music'      => $videoData['music'] ?? '',     // audio only
        'duration'   => $videoData['duration'] ?? 0,
        'width'      => $videoData['width'] ?? 0,
        'height'     => $videoData['height'] ?? 0,
        'like_count' => $videoData['digg_count'] ?? 0,
        'comment_count' => $videoData['comment_count'] ?? 0,
        'share_count'   => $videoData['share_count'] ?? 0,
        'play_count'    => $videoData['play_count'] ?? 0,
    ];
}

// Handle download proxy
if (isset($_GET['dl']) && isset($_GET['url'])) {
    $fileUrl  = base64_decode($_GET['url']);
    $type     = $_GET['dl'];
    $filename = 'AH5 - video ' . date('Y-m-d') . ($type === 'audio' ? '.mp3' : '.mp4');

    header('Content-Type: ' . ($type === 'audio' ? 'audio/mpeg' : 'video/mp4'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Accel-Buffering: no');

    $ch = curl_init($fileUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
        CURLOPT_WRITEFUNCTION  => function($curl, $data) {
            echo $data;
            return strlen($data);
        },
    ]);
    curl_exec($ch);
    curl_close($ch);
    exit;
}

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = trim($_POST['url']);

    // Basic TikTok URL validation
    if (!preg_match('/tiktok\.com|vm\.tiktok|vt\.tiktok/i', $url)) {
        $error = 'সঠিক TikTok লিংক দিন। (যেমন: https://www.tiktok.com/@user/video/...)';
    } else {
        $result = getTikTokVideo($url);
        if (!$result['success']) {
            $error = $result['msg'];
            $result = null;
        }
    }
}

function formatNumber($n) {
    if ($n >= 1000000) return round($n/1000000, 1) . 'M';
    if ($n >= 1000) return round($n/1000, 1) . 'K';
    return $n;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Downloader — Watermark ছাড়া ডাউনলোড</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#fe2c55">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TikDL AH5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="TikDL AH5">

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Apple Touch Icons (iOS Home Screen) -->
    <link rel="apple-touch-icon" sizes="192x192" href="icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="icon-512.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="icon-192.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Hind+Siliguri:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0a0a0f;
            --surface:  #111118;
            --card:     #18181f;
            --border:   #2a2a38;
            --accent:   #fe2c55;
            --accent2:  #25f4ee;
            --text:     #f0f0f5;
            --muted:    #7a7a9a;
            --success:  #00e5a0;
            --radius:   16px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 40% at 20% 10%, rgba(254,44,85,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 90%, rgba(37,244,238,0.08) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .wrapper {
            position: relative;
            z-index: 1;
            max-width: 780px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }

        /* Header */
        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }

        .logo-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent), #ff6b8a);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            box-shadow: 0 0 30px rgba(254,44,85,0.4);
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tagline {
            font-size: 15px;
            color: var(--muted);
            margin-bottom: 40px;
            margin-left: 66px;
        }

        /* Search card */
        .search-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: 0 4px 40px rgba(0,0,0,0.4);
        }

        .search-label {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .search-row {
            display: flex;
            gap: 10px;
        }

        .url-input {
            flex: 1;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 14px 18px;
            color: var(--text);
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .url-input::placeholder { color: var(--muted); }
        .url-input:focus { border-color: var(--accent); }

        .btn-download {
            background: linear-gradient(135deg, var(--accent), #d41e40);
            border: none;
            border-radius: 12px;
            padding: 14px 26px;
            color: #fff;
            font-size: 15px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s, transform 0.15s;
            box-shadow: 0 4px 20px rgba(254,44,85,0.4);
        }
        .btn-download:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-download:active { transform: translateY(0); }

        /* Paste btn */
        .paste-hint {
            margin-top: 10px;
            font-size: 13px;
            color: var(--muted);
        }
        .paste-link {
            color: var(--accent2);
            cursor: pointer;
            border: none;
            background: none;
            font-size: 13px;
            font-family: inherit;
            text-decoration: underline;
            padding: 0;
        }

        /* Error */
        .error-box {
            background: rgba(254,44,85,0.12);
            border: 1px solid rgba(254,44,85,0.3);
            border-radius: 12px;
            padding: 14px 18px;
            color: #ff8095;
            font-size: 14px;
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        /* Result card */
        .result-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 40px rgba(0,0,0,0.5);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .video-preview {
            position: relative;
            width: 100%;
            background: #000;
        }

        .thumb-wrap {
            position: relative;
            width: 100%;
            max-height: 360px;
            overflow: hidden;
        }

        .thumb-wrap img {
            width: 100%;
            object-fit: cover;
            display: block;
        }

        .play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.3);
        }

        .play-btn {
            width: 60px; height: 60px;
            border-radius: 50%;
            background: rgba(254,44,85,0.9);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            box-shadow: 0 0 30px rgba(254,44,85,0.5);
        }

        .video-info {
            padding: 24px;
        }

        .author-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .author-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        .author-name {
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 15px;
            color: var(--accent2);
        }

        .video-title {
            font-size: 15px;
            color: var(--text);
            line-height: 1.5;
            margin-bottom: 18px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Stats */
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        .stat-icon { font-size: 16px; }

        /* Download buttons */
        .dl-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            margin-bottom: 12px;
            font-weight: 500;
        }

        .dl-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dl-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.15s, opacity 0.2s;
            border: none;
            cursor: pointer;
        }

        .dl-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .dl-btn:active { transform: translateY(0); }

        .dl-btn.primary {
            background: linear-gradient(135deg, var(--accent), #d41e40);
            color: #fff;
            box-shadow: 0 4px 18px rgba(254,44,85,0.4);
        }

        .dl-btn.hd {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: #fff;
            box-shadow: 0 4px 18px rgba(124,58,237,0.4);
        }

        .dl-btn.audio {
            background: linear-gradient(135deg, var(--accent2), #00b8b3);
            color: #000;
            box-shadow: 0 4px 18px rgba(37,244,238,0.3);
        }

        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 40px;
        }

        @media (max-width: 540px) {
            .features { grid-template-columns: 1fr; }
            .search-row { flex-direction: column; }
            .dl-buttons { flex-direction: column; }
        }

        .feat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }

        .feat-icon { font-size: 28px; margin-bottom: 10px; }
        .feat-title { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; margin-bottom: 6px; }
        .feat-desc { font-size: 13px; color: var(--muted); line-height: 1.5; }

        /* How to */
        .how-section { margin-top: 36px; }
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .steps {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }

        .step-num {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
        }

        .step-text { font-size: 14px; line-height: 1.5; padding-top: 5px; }

        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }

        .spinner {
            width: 40px; height: 40px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 14px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        footer {
            text-align: center;
            margin-top: 60px;
            font-size: 13px;
            color: var(--muted);
        }

        footer span { color: var(--accent); }

        /* ── PWA Install Banner ── */
        #pwa-install-banner {
            display: none;
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: calc(100% - 40px);
            max-width: 460px;
            background: var(--card);
            border: 1px solid rgba(254,44,85,0.35);
            border-radius: 18px;
            padding: 16px 20px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(254,44,85,0.1);
            animation: bannerSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes bannerSlide {
            from { opacity: 0; transform: translateX(-50%) translateY(30px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        #pwa-install-banner.hide {
            animation: bannerHide 0.3s ease forwards;
        }
        @keyframes bannerHide {
            to { opacity: 0; transform: translateX(-50%) translateY(30px); }
        }
        .pwa-banner-inner { display: flex; align-items: center; gap: 14px; }
        .pwa-app-icon {
            width: 52px; height: 52px;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--accent), #ff6b8a);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(254,44,85,0.4);
        }
        .pwa-banner-text { flex: 1; }
        .pwa-banner-title {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700;
            color: var(--text); margin-bottom: 3px;
        }
        .pwa-banner-desc { font-size: 12px; color: var(--muted); line-height: 1.4; }
        .pwa-banner-actions { display: flex; gap: 8px; margin-top: 14px; }
        .pwa-btn-install {
            flex: 1;
            background: linear-gradient(135deg, var(--accent), #d41e40);
            border: none; border-radius: 10px;
            padding: 11px 18px; color: #fff;
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s;
            box-shadow: 0 4px 14px rgba(254,44,85,0.4);
        }
        .pwa-btn-install:hover { opacity: 0.9; transform: translateY(-1px); }
        .pwa-btn-dismiss {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px; padding: 11px 14px;
            color: var(--muted); font-size: 13px;
            font-family: inherit; cursor: pointer;
            transition: color 0.2s; white-space: nowrap;
        }
        .pwa-btn-dismiss:hover { color: var(--text); }

        /* iOS install guide popup */
        #ios-install-guide {
            display: none;
            position: fixed; inset: 0;
            z-index: 10000;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            align-items: flex-end;
            justify-content: center;
        }
        #ios-install-guide.show { display: flex; }
        .ios-guide-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px 20px 0 0;
            padding: 24px 24px 36px;
            width: 100%; max-width: 460px;
            animation: slideUp 0.35s ease;
        }
        .ios-guide-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 700; margin-bottom: 18px;
        }
        .ios-steps { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .ios-steps li {
            display: flex; align-items: center; gap: 12px;
            font-size: 14px; line-height: 1.4; color: var(--text);
        }
        .ios-step-icon { font-size: 22px; flex-shrink: 0; }
        .ios-close-btn {
            width: 100%; margin-top: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px; padding: 13px;
            color: var(--muted); font-size: 14px;
            font-family: inherit; cursor: pointer;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- Header -->
    <div class="logo">
        <div class="logo-icon">AH5</div>
        <div class="logo-text">Anwar Hossain</div>
    </div>
    <div class="tagline">TikTok ভিডিও Watermark ছাড়া ডাউনলোড করুন — বিনামূল্যে</div>

    <!-- Search Card -->
    <div class="search-card">
        <div class="search-label">📎 TikTok ভিডিও লিংক পেস্ট করুন</div>
        <form method="POST" id="dlForm">
            <div class="search-row">
                <input
                    type="text"
                    name="url"
                    id="urlInput"
                    class="url-input"
                    placeholder="https://www.tiktok.com/@username/video/..."
                    value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
                    autocomplete="off"
                    required
                >
                <button type="submit" class="btn-download" id="submitBtn">
                    ⬇️ ডাউনলোড
                </button>
            </div>
            <div class="paste-hint">
                অথবা &nbsp;
                <button type="button" class="paste-link" onclick="pasteFromClipboard()">
                    📋 ক্লিপবোর্ড থেকে পেস্ট করুন
                </button>
            </div>
        </form>
    </div>

    <!-- Loading -->
    <div class="loading" id="loadingDiv">
        <div class="spinner"></div>
        ভিডিও তথ্য নিয়ে আসা হচ্ছে...
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="error-box">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Result -->
    <?php if ($result): ?>
    <div class="result-card">
        <?php if ($result['cover']): ?>
        <div class="video-preview">
            <div class="thumb-wrap">
                <img src="<?= htmlspecialchars($result['cover']) ?>" alt="thumbnail" loading="lazy">
                <div class="play-overlay">
                    <div class="play-btn">▶</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="video-info">
            <!-- Author -->
            <div class="author-row">
                <?php if ($result['avatar']): ?>
                <img src="<?= htmlspecialchars($result['avatar']) ?>" class="author-avatar" alt="avatar">
                <?php endif; ?>
                <div>
                    <div class="author-name">@<?= htmlspecialchars($result['author']) ?></div>
                </div>
            </div>

            <!-- Title -->
            <div class="video-title"><?= htmlspecialchars($result['title']) ?></div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat"><span class="stat-icon">❤️</span> <?= formatNumber($result['like_count']) ?></div>
                <div class="stat"><span class="stat-icon">💬</span> <?= formatNumber($result['comment_count']) ?></div>
                <div class="stat"><span class="stat-icon">📤</span> <?= formatNumber($result['share_count']) ?></div>
                <div class="stat"><span class="stat-icon">▶️</span> <?= formatNumber($result['play_count']) ?></div>
                <?php if ($result['duration']): ?>
                <div class="stat"><span class="stat-icon">⏱</span> <?= $result['duration'] ?>s</div>
                <?php endif; ?>
            </div>

            <!-- Download Buttons -->
            <div class="dl-label">📥 ডাউনলোড করুন</div>
            <div class="dl-buttons">
                <?php if ($result['play']): ?>
                <a href="?dl=video&url=<?= urlencode(base64_encode($result['play'])) ?>"
                   class="dl-btn primary" download>
                    📱 SD ভিডিও (No Watermark)
                </a>
                <?php endif; ?>

                <?php if ($result['hdplay']): ?>
                <a href="?dl=video&url=<?= urlencode(base64_encode($result['hdplay'])) ?>"
                   class="dl-btn hd" download>
                    🎬 HD ভিডিও (No Watermark)
                </a>
                <?php endif; ?>

                <?php if ($result['music']): ?>
                <a href="?dl=audio&url=<?= urlencode(base64_encode($result['music'])) ?>"
                   class="dl-btn audio" download>
                    🎵 শুধু অডিও (MP3)
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Features -->
    <div class="features">
        <div class="feat-card">
            <div class="feat-icon">🚫</div>
            <div class="feat-title">Watermark ছাড়া</div>
            <div class="feat-desc">TikTok লোগো ছাড়া পরিষ্কার ভিডিও পাবেন</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">⚡</div>
            <div class="feat-title">দ্রুত ডাউনলোড</div>
            <div class="feat-desc">মাত্র কয়েক সেকেন্ডে ভিডিও তৈরি হয়ে যায়</div>
        </div>
        <div class="feat-card">
            <div class="feat-icon">🎵</div>
            <div class="feat-title">MP3 সাপোর্ট</div>
            <div class="feat-desc">ভিডিও থেকে সরাসরি অডিও ডাউনলোড করুন</div>
        </div>
    </div>

    <footer>
        তৈরি করেছেন <span>Anwar Hossain</span> — Creatives iT &nbsp;|&nbsp; শুধুমাত্র ব্যক্তিগত ব্যবহারের জন্য
    </footer>
</div>

<!-- PWA Install Banner (Android / Desktop Chrome) -->
<div id="pwa-install-banner" role="dialog" aria-label="অ্যাপ ইন্সটল করুন">
    <div class="pwa-banner-inner">
        <div class="pwa-app-icon">AH5</div>
        <div class="pwa-banner-text">
            <div class="pwa-banner-title">TikTok Downloader ইন্সটল করুন</div>
            <div class="pwa-banner-desc">হোম স্ক্রিনে যোগ করুন — দ্রুত অ্যাক্সেস পান</div>
        </div>
    </div>
    <div class="pwa-banner-actions">
        <button class="pwa-btn-install" id="pwa-install-btn">📲 ইন্সটল করুন</button>
        <button class="pwa-btn-dismiss" id="pwa-dismiss-btn">পরে</button>
    </div>
</div>

<!-- iOS Install Guide (Safari) -->
<div id="ios-install-guide" role="dialog" aria-label="iOS ইন্সটল গাইড">
    <div class="ios-guide-card">
        <h3>📱 iPhone / iPad-এ ইন্সটল করুন</h3>
        <ul class="ios-steps">
            <li>
                <span class="ios-step-icon">1️⃣</span>
                <span>Safari-এর নিচে <strong>Share</strong> বাটনে ট্যাপ করুন <strong>⬆️</strong></span>
            </li>
            <li>
                <span class="ios-step-icon">2️⃣</span>
                <span><strong>"Add to Home Screen"</strong> অপশনটি খুঁজে ট্যাপ করুন</span>
            </li>
            <li>
                <span class="ios-step-icon">3️⃣</span>
                <span>উপরে ডানদিকে <strong>"Add"</strong> বাটনে ট্যাপ করুন — হয়ে গেল!</span>
            </li>
        </ul>
        <button class="ios-close-btn" id="ios-close-btn">বন্ধ করুন</button>
    </div>
</div>

<script>
// ── Clipboard paste ──
async function pasteFromClipboard() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('urlInput').value = text.trim();
    } catch {
        document.getElementById('urlInput').focus();
        alert('ক্লিপবোর্ড অ্যাক্সেস অনুমতি দিন অথবা ম্যানুয়ালি পেস্ট করুন (Ctrl+V)');
    }
}

// ── Form loading state ──
document.getElementById('dlForm').addEventListener('submit', function() {
    document.getElementById('loadingDiv').style.display = 'block';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = '⏳ অপেক্ষা করুন...';
});

// ── PWA: Service Worker Registration ──
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(reg => {
                console.log('[SW] Registered. Scope:', reg.scope);
                reg.update();
            })
            .catch(err => console.warn('[SW] Registration failed:', err));
    });
}

// ── PWA: Install Prompt Logic ──
let deferredPrompt = null;
const installBanner  = document.getElementById('pwa-install-banner');
const installBtn     = document.getElementById('pwa-install-btn');
const dismissBtn     = document.getElementById('pwa-dismiss-btn');
const iosGuide       = document.getElementById('ios-install-guide');
const iosCloseBtn    = document.getElementById('ios-close-btn');

const dismissed = sessionStorage.getItem('pwa-dismissed');

// Android / Chrome: beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (dismissed) return;
    setTimeout(() => { installBanner.style.display = 'block'; }, 1500);
});

// Install বাটন
installBtn.addEventListener('click', async () => {
    // iOS হলে guide দেখাই (নিচে handle করা আছে)
    if (!deferredPrompt) return;

    const prompt = deferredPrompt;
    deferredPrompt = null; // একবারই ব্যবহার করো

    try {
        await prompt.prompt();
        const { outcome } = await prompt.userChoice;
        console.log('[PWA] Install outcome:', outcome);
    } catch (err) {
        console.warn('[PWA] Prompt error:', err);
    }
    hideBanner();
});

// Dismiss
dismissBtn.addEventListener('click', () => {
    sessionStorage.setItem('pwa-dismissed', '1');
    hideBanner();
});

function hideBanner() {
    installBanner.classList.add('hide');
    setTimeout(() => {
        installBanner.style.display = 'none';
        installBanner.classList.remove('hide');
    }, 300);
}

// ইন্সটল সফল
window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    hideBanner();
    console.log('[PWA] Installed!');
});

// iOS Safari
const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
const isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

if (isIos && !isInStandaloneMode && !dismissed) {
    setTimeout(() => {
        installBanner.style.display = 'block';
        installBtn.textContent = '📲 ইন্সটল করুন';
        installBtn.addEventListener('click', () => {
            hideBanner();
            iosGuide.classList.add('show');
        }, { once: true });
    }, 1500);
}

// iOS guide close
iosCloseBtn.addEventListener('click', () => {
    iosGuide.classList.remove('show');
    sessionStorage.setItem('pwa-dismissed', '1');
});

iosGuide.addEventListener('click', (e) => {
    if (e.target === iosGuide) iosGuide.classList.remove('show');
});
</script>
</body>
</html>
