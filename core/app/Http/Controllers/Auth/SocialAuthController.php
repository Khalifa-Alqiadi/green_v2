<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Helper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Redirect;

class SocialAuthController extends Controller
{
    /**
     * List of providers configured in config/services acts as whitelist
     *
     * @var array
     */
    protected $providers = [
        'facebook',
        'twitter',
        'google',
        'linkedin',
        'github',
        'bitbucket'
    ];
    private $uploadPath = "users";
    private array $allowedAvatarExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct()
    {
        $this->middleware('guest');
    }


    /**
     * Redirect to provider for authentication
     *
     * @param $driver
     * @return mixed
     */
    public function redirectToProvider($driver)
    {
        if (!$this->isProviderAllowed($driver)) {
            return $this->sendFailedResponse("{$driver} " . __('backend.notCurrentlySupported'));
        }

        try {
            return Socialite::driver($driver)->redirect();
        } catch (Exception $e) {
            // You should show something simple fail message
            return $this->sendFailedResponse($e->getMessage());
        }
    }

    /**
     * Check for provider allowed and services configured
     *
     * @param $driver
     * @return bool
     */
    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }

    /**
     * Send a failed response with a msg
     *
     * @param null $msg
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedResponse($msg = null)
    {
        return Redirect::to(url('/login'))
            ->withErrors(['msg' => $msg ?: __('backend.UnableToLogin')]);
    }

    /**
     * Handle response of authentication redirect callback
     *
     * @param $driver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($driver)
    {
        try {
            $user = Socialite::driver($driver)->user();
        } catch (Exception $e) {
            return $this->sendFailedResponse($e->getMessage());
        }

        // check for email in returned user
        return empty($user->email)
            ? $this->sendFailedResponse(__('backend.NoEmailReturned') . " {$driver}")
            : $this->loginOrCreateAccount($user, $driver);
    }

    protected function loginOrCreateAccount($providerUser, $driver)
    {
        // check for already has account
        $user = User::where('email', $providerUser->getEmail())->first();

        // if user already found
        if ($user) {

            if ($user->photo != "" && $user->photo != "profile.jpg") {
                FileHelper::deleteFile($this->getUploadPath() . "/" . $user->photo);
            }
            $photo_filename = $this->storeProviderAvatar($providerUser->getAvatar());

            // update the avatar and provider that might have changed
            $user->update([
                'photo' => $photo_filename,
                'provider' => $driver,
                'provider_id' => $providerUser->id,
                'access_token' => $providerUser->token
            ]);
        } else {
            $photo_filename = $this->storeProviderAvatar($providerUser->getAvatar());

            // create a new user
            $user = User::create([
                'name' => $providerUser->getName(),
                'email' => $providerUser->getEmail(),
                'photo' => $photo_filename,
                'permissions_id' => Helper::GeneralWebmasterSettings("permission_group"),
                'status' => true,
                'provider' => $driver,
                'provider_id' => $providerUser->getId(),
                'access_token' => $providerUser->token,
                // user can use reset password to create a password
                'password' => ''
            ]);
        }

        // login the user
        Auth::login($user, true);

        return $this->sendSuccessResponse();
    }

    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = trim(str_replace('\\', '/', (string) $uploadPath), '/');
    }

    private function storeProviderAvatar(?string $avatarUrl): string
    {
        if (!$this->isSafeAvatarUrl($avatarUrl)) {
            return "";
        }

        try {
            $response = Http::timeout(5)->connectTimeout(3)->get($avatarUrl);
        } catch (Exception $e) {
            return "";
        }

        if (!$response->successful() || strlen($response->body()) > 5 * 1024 * 1024) {
            return "";
        }

        $imageInfo = @getimagesizefromstring($response->body());
        if ($imageInfo === false) {
            return "";
        }

        $extension = $this->avatarExtension($avatarUrl, $response->header('Content-Type'));
        if ($extension === null) {
            return "";
        }

        $filename = Str::uuid().'.'.$extension;
        Storage::disk(config('filesystems.default'))->put($this->getUploadPath()."/".$filename, $response->body());

        return $filename;
    }

    private function isSafeAvatarUrl(?string $avatarUrl): bool
    {
        if (!$avatarUrl) {
            return false;
        }

        $parts = parse_url($avatarUrl);
        if (!is_array($parts)) {
            return false;
        }

        if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        if (filter_var($parts['host'], FILTER_VALIDATE_IP)) {
            return (bool) filter_var(
                $parts['host'],
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
        }

        return true;
    }

    private function avatarExtension(string $avatarUrl, ?string $contentType): ?string
    {
        $extension = strtolower(pathinfo(parse_url($avatarUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, $this->allowedAvatarExtensions, true)) {
            return $extension;
        }

        return match (strtolower(trim(explode(';', (string) $contentType)[0]))) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    /**
     * Send a successful response
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendSuccessResponse()
    {
        return redirect()->intended('home');
    }
}
