<?php
ob_start(); // 开始输出缓冲
/**
 * A class to integrate Cloudflare Turnstile CAPTCHA in WordPress.
 */
class Turnstile {
    private const API_SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private $sitekey;
    private $secret;
    private $theme;
    private $lang;

    /**
     * Turnstile constructor.
     *
     * @throws \Exception If sitekey or secret is not configured.
     */
    public function __construct() {
        if (!function_exists('iro_opt')) {
            throw new \Exception('Required WordPress function iro_opt is not available.');
        }
        $this->sitekey = iro_opt('turnstile_sitekey');
        $this->secret = iro_opt('turnstile_secret');
        if (empty($this->sitekey)) {
            throw new \Exception('Turnstile sitekey is not configured.');
        }
        if (empty($this->secret)) {
            throw new \Exception('Turnstile secret is not configured.');
        }
        $this->theme = $this->getTheme();
        $this->lang = $this->getLang();
    }

    /**
     * Generates HTML for the Turnstile widget.
     *
     * @param string $action The action name for the Turnstile widget (default: 'login').
     * @return string HTML markup for the Turnstile widget.
     */
    public function html($action = 'login') {
        $id = 'turnstile-' . esc_attr($action);
        $error_id = 'turnstile-error-' . esc_attr($action);
        return '<div style="display: flex; justify-content: center; align-items: center; width: 100%; min-height: 0; transition: min-height 0.8s ease; will-change: min-height;">' .
               '<div style="display: inline-block; text-align: center;">' .
               '<div id="' . $error_id . '" style="display: none; color: red; margin-bottom: 10px;"></div>' .
               '<div id="' . $id . '" class="cf-turnstile" data-sitekey="' . esc_attr($this->sitekey) . '" data-theme="' . esc_attr($this->theme) . '" data-language="' . esc_attr($this->lang) . '" data-action="' . esc_attr($action) . '" data-callback="onTurnstileSuccess" data-error-id="' . $error_id . '" style="filter: blur(5px); opacity: 0; transition: filter 0.8s ease, opacity 0.8s ease; will-change: filter, opacity;"></div>' .
               '</div>' .
               '</div>';
    }

    /**
     * Generates JavaScript for loading and handling the Turnstile widget.
     *
     * @return string JavaScript code for Turnstile widget initialization and interaction.
     */
    public function script() {
        $nonce = wp_create_nonce('turnstile-script');
        $script = '<script nonce="' . esc_attr($nonce) . '" src="' . esc_url(self::API_SCRIPT_URL) . '" async defer></script>';
        $script .= '<script nonce="' . esc_attr($nonce) . '">
            const TurnstileState = {
                token: null
            };

            function animateTurnstile(turnstileDiv, container) {
                if (!turnstileDiv || !container) {
                    console.warn("Turnstile animation failed: missing turnstileDiv or container");
                    return;
                }

                // 使用 requestAnimationFrame 确保动画流畅
                requestAnimationFrame(() => {
                    container.style.transition = "min-height 0.8s ease";
                    container.style.minHeight = "80px";

                    // 在拉伸动画完成后（800ms + 800ms延迟）执行渐显
                    setTimeout(() => {
                        requestAnimationFrame(() => {
                            turnstileDiv.style.transition = "filter 0.8s ease, opacity 0.8s ease";
                            turnstileDiv.style.filter = "blur(0)";
                            turnstileDiv.style.opacity = "1";

                            if (typeof turnstile !== "undefined" && turnstileDiv.id) {
                                turnstile.render(turnstileDiv.id, {
                                    sitekey: turnstileDiv.dataset.sitekey,
                                    theme: turnstileDiv.dataset.theme,
                                    language: turnstileDiv.dataset.language,
                                    action: turnstileDiv.dataset.action,
                                    callback: window.onTurnstileSuccess
                                });
                            } else {
                                console.warn("Turnstile API not loaded when trying to render");
                            }
                        });
                    }, 800); // 延迟800ms
                });
            }

            function onTurnstileSuccess(token) {
                const turnstileDiv = document.querySelector(".cf-turnstile");
                if (!turnstileDiv) return;
                const form = turnstileDiv.closest("form");
                const container = turnstileDiv.closest("div[style*=\'min-height\']");
                const errorDiv = document.getElementById(turnstileDiv.dataset.errorId);
                if (!form || !container) return;

                if (errorDiv) {
                    errorDiv.textContent = "";
                    errorDiv.style.display = "none";
                }

                TurnstileState.token = token;
                setTimeout(() => {
                    turnstileDiv.style.transition = "filter 0.5s ease, opacity 0.5s ease";
                    turnstileDiv.style.filter = "blur(5px)";
                    turnstileDiv.style.opacity = "0";
                    setTimeout(() => {
                        turnstileDiv.style.display = "none";
                        container.style.minHeight = "0";
                    }, 500);
                }, 1000);
            }

            function initializeTurnstile() {
                const turnstileDiv = document.querySelector(".cf-turnstile");
                const container = turnstileDiv ? turnstileDiv.closest("div[style*=\'min-height\']") : null;
                const errorDiv = turnstileDiv ? document.getElementById(turnstileDiv.dataset.errorId) : null;

                if (errorDiv) {
                    errorDiv.textContent = "";
                    errorDiv.style.display = "none";
                }

                if (turnstileDiv && container) {
                    if (typeof turnstile !== "undefined") {
                        animateTurnstile(turnstileDiv, container);
                    } else {
                        // 轮询检查 turnstile 是否可用
                        const checkInterval = setInterval(() => {
                            if (typeof turnstile !== "undefined") {
                                clearInterval(checkInterval);
                                animateTurnstile(turnstileDiv, container);
                            }
                        }, 100);
                        // 设置超时以防止无限轮询
                        setTimeout(() => {
                            clearInterval(checkInterval);
                            if (errorDiv) {
                                errorDiv.textContent = "Failed to load Turnstile API.";
                                errorDiv.style.display = "block";
                            }
                        }, 10000); // 10秒超时
                    }
                }
            }

            // 使用 DOMContentLoaded 替代 window.onload，确保 DOM 就绪后尽早初始化
            document.addEventListener("DOMContentLoaded", () => {
                const script = document.querySelector("script[src=\'' . esc_url(self::API_SCRIPT_URL) . '\']");
                if (script) {
                    script.addEventListener("error", () => {
                        const turnstileDiv = document.querySelector(".cf-turnstile");
                        const errorDiv = turnstileDiv ? document.getElementById(turnstileDiv.dataset.errorId) : null;
                        if (errorDiv) {
                            errorDiv.textContent = "Failed to load Turnstile API.";
                            errorDiv.style.display = "block";
                        }
                    });
                    script.addEventListener("load", initializeTurnstile);
                }
                // 如果脚本已加载完成，直接初始化
                if (typeof turnstile !== "undefined") {
                    initializeTurnstile();
                }
            });

            // 如果页面已完全加载，确保初始化
            if (document.readyState === "complete") {
                initializeTurnstile();
            }
        </script>';
        return $script;
    }

    /**
     * Determines the theme for the Turnstile widget based on configuration.
     *
     * @return string The theme ('light', 'dark', or 'auto').
     */
    private function getTheme() {
        $validThemes = ['light', 'dark', 'auto'];
        $theme = iro_opt('theme_skin');
        return in_array($theme, $validThemes) ? $theme : 'auto';
    }

    /**
     * Determines the language for the Turnstile widget based on WordPress locale.
     *
     * @return string The language code (e.g., 'en', 'zh-cn').
     */
    private function getLang() {
        $locale = get_locale();
        $lang = str_replace('_', '-', $locale);
        $supportedLangs = [
            'en', 'zh', 'zh-cn', 'zh-tw', 'ja', 'ko', 'fr', 'de', 'es', 'pt', 'ru',
            'it', 'nl', 'pl', 'ar', 'tr', 'vi', 'th', 'id', 'hi'
        ];
        if (in_array($lang, $supportedLangs)) {
            return $lang;
        }
        $mainLang = explode('-', $lang)[0];
        if (in_array($mainLang, $supportedLangs)) {
            return $mainLang;
        }
        error_log("Unsupported locale: $locale, falling back to 'en'");
        return 'en';
    }

    /**
     * Verifies a Turnstile token with Cloudflare's API.
     *
     * @param string $token The Turnstile token to verify.
     * @return object Verification response object with success status and error codes.
     */
    public function verify($token) {
        if (empty($this->secret)) {
            return (object) ['success' => false, 'error-codes' => ['missing-input-secret']];
        }
        $body = [
            'secret' => $this->secret,
            'response' => $token
        ];
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $body['remoteip'] = $_SERVER['REMOTE_ADDR'];
        }
        $response = wp_remote_post(self::VERIFY_URL, [
            'body' => $body,
            'timeout' => 30,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url()
        ]);

        if (is_wp_error($response)) {
            error_log("Turnstile verification failed: " . $response->get_error_message());
            return (object) ['success' => false, 'error-codes' => ['api_failure']];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log("Turnstile verification failed with HTTP status: $status_code");
            return (object) ['success' => false, 'error-codes' => ['http_error_' . $status_code]];
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        if (!$body) {
            error_log("Turnstile verification failed: Invalid response body");
            return (object) ['success' => false, 'error-codes' => ['invalid_response']];
        }

        return $body;
    }

    /**
     * Parses Turnstile error codes into human-readable messages.
     *
     * @param array $codes Array of error codes returned by the Turnstile API.
     * @return string Comma-separated list of error messages.
     */
    public function parseError($codes) {
        $messages = [
            'missing-input-secret' => __('Missing server configuration', 'sakurairo'),
            'invalid-input-secret' => __('Invalid server secret', 'sakurairo'),
            'missing-input-response' => __('Verification not completed', 'sakurairo'),
            'invalid-input-response' => __('Invalid or expired verification token', 'sakurairo'),
            'bad-request' => __('Invalid request format', 'sakurairo'),
            'timeout-or-duplicate' => __('Verification session timed out', 'sakurairo'),
            'internal-error' => __('Verification service temporarily unavailable', 'sakurairo'),
            'api_failure' => __('Unable to connect to verification service', 'sakurairo'),
            'invalid_response' => __('Verification service returned invalid response', 'sakurairo'),
            'http_error_400' => __('Verification service request error', 'sakurairo'),
            'http_error_429' => __('Too many requests to verification service', 'sakurairo'),
            'http_error_500' => __('Verification service internal error', 'sakurairo')
        ];

        if (empty($codes)) {
            return __('Unknown error', 'sakurairo');
        }

        return implode(', ', array_map(function($code) use ($messages) {
            return $messages[$code] ?? sprintf(__('Unknown error: %s', 'sakurairo'), $code);
        }, $codes));
    }
}
ob_end_clean(); // 清理并结束输出缓冲
?>