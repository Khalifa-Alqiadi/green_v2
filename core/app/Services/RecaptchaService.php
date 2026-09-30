<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public const VERSION_V2 = 'v2';
    public const VERSION_V3 = 'v3';

    public static function isEnabled(): bool
    {
        return (bool) config('smartend.nocaptcha_status');
    }

    public static function version(): string
    {
        $version = config('smartend.nocaptcha_version', self::VERSION_V2);

        return in_array($version, [self::VERSION_V2, self::VERSION_V3], true)
            ? $version
            : self::VERSION_V2;
    }

    public static function isV3(): bool
    {
        return self::version() === self::VERSION_V3;
    }

    public static function siteKey(): string
    {
        return (string) config('captcha.sitekey', '');
    }

    public static function validationRules(string $action = 'submit'): string|array
    {
        if (!self::isV3()) {
            return 'required|captcha';
        }

        return [
            'required',
            function ($attribute, $value, $fail) use ($action) {
                if (!self::verifyV3((string) $value, $action)) {
                    $fail('Google reCAPTCHA verification failed.');
                }
            },
        ];
    }

    public static function verifyV3(string $token, string $action = 'submit'): bool
    {
        if ($token === '' || config('captcha.secret') === null || config('captcha.secret') === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('captcha.options.timeout', 30))
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('captcha.secret'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable $e) {
            return false;
        }

        if (!$response->ok()) {
            return false;
        }

        $data = $response->json();
        $score = (float) ($data['score'] ?? 0);
        $expectedAction = $action !== '' ? $action : 'submit';

        return ($data['success'] ?? false) === true
            && ($data['action'] ?? '') === $expectedAction
            && $score >= (float) config('smartend.nocaptcha_v3_score', 0.5);
    }
}
