<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Sakurairo
 */

// 移除不必要的 WordPress 样式
add_action('wp_head', function() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('entry-content');
}, 5);

// 确保 iro_opt 函数存在
if (!function_exists('iro_opt')) {
    function iro_opt($option, $default = '') {
        return $default;
    }
}

// 获取主题设置
$theme_matching_color = esc_attr(iro_opt('theme_skin_matching', '#8e95fb'));

// 获取优化后的背景图片（支持 WebP）
$random_bg_url = function_exists('DEFAULT_FEATURE_IMAGE') ? DEFAULT_FEATURE_IMAGE() : '';
if (function_exists('sakurairo_optimize_image') && $random_bg_url) {
    $random_bg_url = sakurairo_optimize_image($random_bg_url, ['format' => 'webp', 'quality' => 80]);
}
$random_bg_url = esc_url($random_bg_url);

// 随机提示语
$messages = [
    '您访问的页面可能已被删除、更名或暂时不可用。',
    '糟糕！看起来您迷路了，让我们带您回家吧！',
    '404？页面跑去度假了，试试搜索吧！'
];
$random_message = esc_html($messages[array_rand($messages)]);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="shortcut icon" href="<?php echo esc_url(iro_opt('favicon_link', '')); ?>" />
    <link rel="preload" href="<?php echo esc_url($random_bg_url); ?>" as="image">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://s4.zstatic.net">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;700&display=swap">
    <link rel="stylesheet" href="<?php echo esc_url(iro_opt('fontawesome_source', 'https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css')); ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo esc_url(iro_opt('fontawesome_source', 'https://s4.zstatic.net/ajax/libs/font-awesome/6.7.2/css/all.min.css')); ?>"></noscript>
    <title>404 - <?php echo esc_html(get_bloginfo('name')); ?></title>
    <?php wp_head(); ?>
    <style>
        :root {
            --primary: <?php echo esc_attr($theme_matching_color); ?>;
            --text: #2d2d2d;
            --background: #f0f2f5;
            --card-bg: rgba(255, 255, 255, 0.9);
            --shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            --gradient: radial-gradient(circle at center, var(--primary) 0%, #6b7280 100%);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --background: #1f2227;
                --card-bg: rgba(30, 30, 30, 0.9);
                --text: #f1f5f9;
            }
            .page-404-message {
                color: #94a3b8;
            }
        }

        @media (prefers-contrast: high) {
            :root {
                --text: #000;
            }
        }

        @media (forced-colors: active) {
            .page-404-button,
            .page-404-search-button {
                border: 1px solid CanvasText;
            }
        }

        .page-404 {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: var(--gradient);
            position: relative;
            font-family: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            overflow: hidden;
            transition: background-image 0.5s ease-in-out;
        }

        .page-404.loaded {
            background-image: url('<?php echo esc_url($random_bg_url); ?>');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .page-404-container {
            width: 100%;
            max-width: 600px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            backdrop-filter: blur(12px);
            animation: slideIn 0.6s ease-out forwards;
        }

        .page-404-number {
            font-size: 130px;
            font-weight: 800;
            line-height: 1;
            margin: 0;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -6px;
            animation: slideIn 0.6s ease-out 0.1s forwards;
        }

        .page-404-title {
            font-size: 30px;
            font-weight: 700;
            margin: 20px 0;
            color: var(--text);
            animation: slideIn 0.6s ease-out 0.2s forwards;
        }

        .page-404-message {
            font-size: 16px;
            line-height: 1.8;
            color: #4b5563;
            margin-bottom: 30px;
            animation: slideIn 0.6s ease-out 0.3s forwards;
        }

        .page-404-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            animation: slideIn 0.6s ease-out 0.4s forwards;
        }

        .page-404-button {
            position: relative;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .page-404-button:hover {
            background: linear-gradient(135deg, var(--primary), #333);
            color: #fff;
            border-color: var(--primary);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .page-404-button:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .page-404-button::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            min-width: 80px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .page-404-button:hover::after {
            opacity: 1;
            visibility: visible;
            bottom: 130%;
        }

        .page-404-search-form {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }

        .page-404-search-input {
            flex: 1;
            padding: 12px 16px;
            font-size: 14px;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            color: var(--text);
            min-width: 0;
            transition: all 0.3s ease;
        }

        .page-404-search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(142, 149, 251, 0.3);
            transform: scale(1.02);
        }

        .page-404-search-button {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-404-search-button:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .page-404-search-button:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .page-404-footer {
            margin-top: 30px;
            font-size: 13px;
            color: #6b7280;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        @media (max-width: 600px) {
            .page-404-container {
                padding: 40px 20px;
            }

            .page-404-number {
                font-size: 100px;
            }

            .page-404-title {
                font-size: 26px;
            }

            .page-404-search-form {
                max-width: 100%;
            }

            .page-404-button::after {
                min-width: 60px;
                font-size: 11px;
                padding: 5px 10px;
            }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="page-404">
    <main class="page-404-container">
        <div class="page-404-header">
            <h1 class="page-404-number">404</h1>
        </div>
        <h2 class="page-404-title">页面未找到</h2>
        <p class="page-404-message" aria-live="polite">
            <?php echo $random_message; ?>
        </p>
        <div class="page-404-actions">
            <a id="golast" href="javascript:history.go(-1);" class="page-404-button" data-tooltip="返回上一页" aria-label="返回上一页">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> 返回
            </a>
            <a id="gohome" href="<?php echo esc_url(home_url('/')); ?>" class="page-404-button" data-tooltip="返回首页" aria-label="返回首页">
                <i class="fa-solid fa-house" aria-hidden="true"></i> 首页
            </a>
        </div>
        <form class="page-404-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search" aria-label="搜索网站">
            <input class="page-404-search-input" type="search" name="s" placeholder="搜索内容..." aria-label="输入搜索关键词" required>
            <button type="submit" class="page-404-search-button" aria-label="提交搜索">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="sr-only">搜索</span>
            </button>
        </form>
        <footer class="page-404-footer">
            <?php echo esc_html(get_bloginfo('name')); ?> © <?php echo date('Y'); ?>
        </footer>
    </main>
    <?php wp_footer(); ?>
    <script nonce="<?php echo wp_create_nonce('inline-script'); ?>" defer>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.querySelector('.page-404');
            const bgImage = new Image();
            bgImage.src = '<?php echo esc_url($random_bg_url); ?>';
            bgImage.onload = () => page.classList.add('loaded');
            bgImage.onerror = () => page.classList.add('loaded');
        });
    </script>
</body>
</html>