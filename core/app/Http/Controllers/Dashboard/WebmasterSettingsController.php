<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsPage;
use App\Models\AnalyticsVisitor;
use App\Models\ContactsGroup;
use App\Models\Country;
use App\Models\Menu;
use App\Models\Permissions;
use App\Models\Section;
use App\Models\Topic;
use App\Models\Language;
use App\Models\WebmasterBanner;
use App\Models\WebmasterSection;
use App\Models\WebmasterSetting;
use App\Services\Ai\AiAccessService;
use App\Services\Ai\AiProviderRegistry;
use App\Services\Ai\AiSettingsService;
use App\Services\Dashboard\LanguageSchemaService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use mysql_xdevapi\Exception;
use Redirect;
use File;
use Helper;
use Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class WebmasterSettingsController extends Controller
{
    private $Page_Tab = "";
    private $Page_Message = "";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        Cache::forget('_Loader_WebmasterSettings');
        Cache::forget('_Loader_Web_Settings');

        // Check Permissions
        $canManageAi = app(AiAccessService::class)->canManageSettings(Auth::user());
        $canManageSystem = (bool) @Auth::user()->permissionsGroup->webmaster_status;
        if (!$canManageSystem && !$canManageAi) {
            return redirect()->route("NoPermission");
        }

        if ($canManageAi && (request('tab') === 'ai' || !$canManageSystem)) {
            session()->now('active_tab', 'aiSettingsTab');
        }

        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $ParentMenus = Menu::where('father_id', '0')->orderby('row_no', 'asc')->get();
        $WebmasterBanners = WebmasterBanner::orderby('row_no', 'asc')->get();
        $ContactsGroups = ContactsGroup::orderby('id', 'asc')->get();
        $PermissionsGroups = Permissions::orderby('id', 'asc')->get();
        $SitePages = Topic::where('webmaster_id', 1)->orderby('row_no', 'asc')->get();
        $Countries = Country::orderby('title_'.@Helper::currentLanguage()->code, 'asc')->get();
        $Languages = Language::orderby('id', 'asc')->get();

        $LandingPages = Topic::where('webmaster_id',
            WebmasterSection::select("id")->where("type", 10)->pluck("id")->toarray())->orderby('row_no', 'asc')->get();

        $WebmasterSetting = WebmasterSetting::find(1);
        if (!empty($WebmasterSetting)) {
            $aiViewData = [];
            if ($canManageAi) {
                $providers = app(AiProviderRegistry::class);
                $aiSettingsService = app(AiSettingsService::class);
                $configured = [];
                $storedCredentialFields = [];
                foreach (array_unique([...$providers->textProviders(), ...$providers->imageProviders()]) as $provider) {
                    $configured[$provider] = $aiSettingsService->providerReady($provider, $WebmasterSetting);
                    $storedCredentialFields[$provider] = array_keys($aiSettingsService->providerCredentials($provider, $WebmasterSetting));
                }
                $aiViewData = [
                    'settings' => $WebmasterSetting,
                    'providers' => $providers,
                    'configured' => $configured,
                    'storedCredentialFields' => $storedCredentialFields,
                    'textProvider' => $aiSettingsService->textProvider($WebmasterSetting),
                    'imageProvider' => $aiSettingsService->imageProvider($WebmasterSetting),
                ];
            }

            return view("dashboard.system_settings.home",
                compact("WebmasterSetting", "GeneralWebmasterSections", "ParentMenus", "WebmasterBanners",
                    "ContactsGroups", "SitePages", "PermissionsGroups", "Countries", "Languages", "LandingPages",
                    "canManageAi", "canManageSystem") + $aiViewData);

        } else {
            return redirect()->route('adminHome');
        }
    }

    public function update(Request $request)
    {
        //
        $WebmasterSetting = WebmasterSetting::find(1);
        if (!empty($WebmasterSetting)) {
            $WebmasterSetting->seo_status = 0;
            $WebmasterSetting->analytics_status = ($request->analytics_status) ? 1 : 0;
            $WebmasterSetting->banners_status = ($request->banners_status) ? 1 : 0;
            $WebmasterSetting->inbox_status = ($request->inbox_status) ? 1 : 0;
            $WebmasterSetting->calendar_status = ($request->calendar_status) ? 1 : 0;
            $WebmasterSetting->settings_status = ($request->settings_status) ? 1 : 0;
            $WebmasterSetting->authors_status = ($request->authors_status) ? 1 : 0;
            $WebmasterSetting->menus_status = ($request->menus_status) ? 1 : 0;
            $WebmasterSetting->file_manager_status = ($request->file_manager_status) ? 1 : 0;
            $WebmasterSetting->popups_status = ($request->popups_status) ? 1 : 0;
            $WebmasterSetting->tags_status = ($request->tags_status) ? 1 : 0;
            $WebmasterSetting->newsletter_status = ($request->newsletter_status) ? 1 : 0;
            $WebmasterSetting->members_status = 0; //$request->orders_status;
            $WebmasterSetting->orders_status = 0; //$request->orders_status;
            $WebmasterSetting->shop_status = 0; //$request->shop_status;
            $WebmasterSetting->shop_settings_status = 0; //$request->shop_settings_status;
            $WebmasterSetting->default_currency_id = ($request->default_currency_id) ? $request->default_currency_id : 0;
            $WebmasterSetting->languages_by_default = $request->languages_by_default;
            if ($request->languages_by_default == "") {
                $WebmasterSetting->languages_by_default = "en";
            }
            $WebmasterSetting->header_menu_id = $request->header_menu_id;
            $WebmasterSetting->footer_menu_id = $request->footer_menu_id;
            $WebmasterSetting->home_banners_section_id = $request->home_banners_section_id;
            $WebmasterSetting->home_text_banners_section_id = $request->home_text_banners_section_id;
            $WebmasterSetting->side_banners_section_id = $request->side_banners_section_id;
            $WebmasterSetting->contact_page_id = $request->contact_page_id;
            $WebmasterSetting->newsletter_contacts_group = $request->newsletter_contacts_group;
            $WebmasterSetting->new_comments_status = ($request->new_comments_status) ? 1 : 0;
            $WebmasterSetting->cookie_policy_status = ($request->cookie_policy_status) ? 1 : 0;
            $WebmasterSetting->links_status = 1;

            $WebmasterSetting->image_optimize = ($request->image_optimize) ? 1 : 0;
            $WebmasterSetting->image_resize = ($request->image_resize) ? 1 : 0;
            $WebmasterSetting->image_resize_width = ($request->image_resize_width > 0) ? $request->image_resize_width : 100;
            $WebmasterSetting->image_resize_height = ($request->image_resize_height > 0) ? $request->image_resize_height : 100;

            $WebmasterSetting->slug_translation = ($request->slug_translation) ? 1 : 0;
            $WebmasterSetting->instant_index = ($request->instant_index) ? 1 : 0;
            $WebmasterSetting->instant_index_on_create = ($request->instant_index_on_create) ? 1 : 0;
            $WebmasterSetting->instant_index_on_update = ($request->instant_index_on_update) ? 1 : 0;
            $WebmasterSetting->instant_index_on_delete = ($request->instant_index_on_delete) ? 1 : 0;

            //save json file
            $json_file = null;
            $formFileName = "instant_index_file";
            if ($request->hasFile($formFileName)) {
                // validate image
                $this->validate($request, [
                    $formFileName => ['file', 'mimetypes:application/json']
                ]);
                $FileInfo = FileHelper::uploadFile($request->file($formFileName), "settings", 1);
                $json_file = @$FileInfo['name'];
                if ($WebmasterSetting->instant_index_file != "" && $WebmasterSetting->instant_index_file != "noimg.png") {
                    FileHelper::deleteFile("settings/".$WebmasterSetting->instant_index_file);
                }
            }
            $WebmasterSetting->instant_index_file = $json_file;


            $WebmasterSetting->register_status = ($request->register_status) ? 1 : 0;
            $WebmasterSetting->permission_group = $request->permission_group;
            $WebmasterSetting->api_status = ($request->api_status) ? 1 : 0;
            $WebmasterSetting->api_key = $request->api_key;

            $WebmasterSetting->homepage_type = (boolean) $request->homepage_type;
            $WebmasterSetting->landing_page_id = $request->landing_page_id;

            $WebmasterSetting->home_content1_section_id = $request->home_content1_section_id;
            $WebmasterSetting->home_content2_section_id = $request->home_content2_section_id;
            $WebmasterSetting->home_content3_section_id = $request->home_content3_section_id;
            $WebmasterSetting->home_content4_section_id = $request->home_content4_section_id;
            $WebmasterSetting->home_content5_section_id = $request->home_content5_section_id;
            $WebmasterSetting->home_content6_section_id = $request->home_content6_section_id;
            $WebmasterSetting->home_content7_section_id = $request->home_content7_section_id;
            $WebmasterSetting->home_content8_section_id = $request->home_content8_section_id;
            $WebmasterSetting->home_contents_per_page = $request->home_contents_per_page;
            $WebmasterSetting->latest_news_section_id = 0;


            $mail_driver = preg_replace('/\s+/', '', $request->mail_driver);
            $mail_host = preg_replace('/\s+/', '', $request->mail_host);
            $mail_port = preg_replace('/\s+/', '', $request->mail_port);
            $mail_username = preg_replace('/\s+/', '', $request->mail_username);
            $mail_password = $request->mail_password;
            $mail_encryption = preg_replace('/\s+/', '', $request->mail_encryption);
            $mail_no_replay = preg_replace('/\s+/', '', $request->mail_no_replay);


            $WebmasterSetting->mail_driver = ($mail_driver != "") ? $mail_driver : "smtp";
            $WebmasterSetting->mail_host = $mail_host;
            $WebmasterSetting->mail_port = $mail_port;
            $WebmasterSetting->mail_username = $mail_username;
            $WebmasterSetting->mail_password = $mail_password;
            $WebmasterSetting->mail_encryption = $mail_encryption;
            $WebmasterSetting->mail_no_replay = $mail_no_replay;
            $WebmasterSetting->mail_title = $request->mail_title;
            $WebmasterSetting->mail_template = $request->mail_template;
            $nocaptcha_version = in_array($request->nocaptcha_version, ['v2', 'v3'], true) ? $request->nocaptcha_version : 'v2';
            $nocaptcha_v3_score = is_numeric($request->nocaptcha_v3_score) ? min(1, max(0, (float) $request->nocaptcha_v3_score)) : 0.5;
            $WebmasterSetting->nocaptcha_status = ($request->nocaptcha_status) ? 1 : 0;
            $WebmasterSetting->nocaptcha_secret = ($request->nocaptcha_secret != "") ? $request->nocaptcha_secret : "";
            $WebmasterSetting->nocaptcha_sitekey = ($request->nocaptcha_sitekey != "") ? $request->nocaptcha_sitekey : "";
            $WebmasterSetting->google_tags_status = ($request->google_tags_status) ? 1 : 0;
            $WebmasterSetting->google_tags_id = ($request->google_tags_id != "") ? $request->google_tags_id : "";

            $WebmasterSetting->login_facebook_status = ($request->login_facebook_status) ? 1 : 0;
            $WebmasterSetting->login_facebook_client_id = ($request->login_facebook_client_id != "") ? $request->login_facebook_client_id : "";
            $WebmasterSetting->login_facebook_client_secret = ($request->login_facebook_client_secret != "") ? $request->login_facebook_client_secret : "";
            $WebmasterSetting->login_twitter_status = ($request->login_twitter_status) ? 1 : 0;
            $WebmasterSetting->login_twitter_client_id = ($request->login_twitter_client_id != "") ? $request->login_twitter_client_id : "";
            $WebmasterSetting->login_twitter_client_secret = ($request->login_twitter_client_secret != "") ? $request->login_twitter_client_secret : "";
            $WebmasterSetting->login_google_status = ($request->login_google_status) ? 1 : 0;
            $WebmasterSetting->login_google_client_id = ($request->login_google_client_id != "") ? $request->login_google_client_id : "";
            $WebmasterSetting->login_google_client_secret = ($request->login_google_client_secret != "") ? $request->login_google_client_secret : "";
            $WebmasterSetting->login_linkedin_status = ($request->login_linkedin_status) ? 1 : 0;
            $WebmasterSetting->login_linkedin_client_id = ($request->login_linkedin_client_id != "") ? $request->login_linkedin_client_id : "";
            $WebmasterSetting->login_linkedin_client_secret = ($request->login_linkedin_client_secret != "") ? $request->login_linkedin_client_secret : "";
            $WebmasterSetting->login_github_status = ($request->login_github_status) ? 1 : 0;
            $WebmasterSetting->login_github_client_id = ($request->login_github_client_id != "") ? $request->login_github_client_id : "";
            $WebmasterSetting->login_github_client_secret = ($request->login_github_client_secret != "") ? $request->login_github_client_secret : "";
            $WebmasterSetting->login_bitbucket_status = ($request->login_bitbucket_status) ? 1 : 0;
            $WebmasterSetting->login_bitbucket_client_id = ($request->login_bitbucket_client_id != "") ? $request->login_bitbucket_client_id : "";
            $WebmasterSetting->login_bitbucket_client_secret = ($request->login_bitbucket_client_secret != "") ? $request->login_bitbucket_client_secret : "";

            $WebmasterSetting->dashboard_link_status = ($request->dashboard_link_status) ? 1 : 0;
            $WebmasterSetting->header_search_status = ($request->header_search_status) ? 1 : 0;
            $WebmasterSetting->text_editor = ($request->text_editor > 0) ? $request->text_editor : 0;
            $WebmasterSetting->tiny_key = $request->tiny_key;
            $WebmasterSetting->timezone = $request->timezone;

            $WebmasterSetting->updated_by = Auth::user()->id;
            $WebmasterSetting->save();

            $OLD_BACKEND_PATH = config('smartend.backend_path');
            if ($request->backend_path == "") {
                $request->backend_path = "admin";
            }
            // Update .env file
            $env_update = $this->changeEnv([
                'APP_DEBUG' => ($request->debug_mode_status) ? "true" : "false",
                'MAIL_MAILER' => $mail_driver,
                'MAIL_DRIVER' => $mail_driver,
                'MAIL_HOST' => $mail_host,
                'MAIL_PORT' => $mail_port,
                'MAIL_USERNAME' => $mail_username,
                'MAIL_PASSWORD' => $mail_password,
                'MAIL_ENCRYPTION' => $mail_encryption,
                'MAIL_FROM_ADDRESS' => $mail_no_replay,
                'NOCAPTCHA_STATUS' => ($request->nocaptcha_status) ? 1 : 0,
                'NOCAPTCHA_VERSION' => $nocaptcha_version,
                'NOCAPTCHA_SECRET' => $request->nocaptcha_secret,
                'NOCAPTCHA_SITEKEY' => $request->nocaptcha_sitekey,
                'NOCAPTCHA_V3_SCORE' => $nocaptcha_v3_score,
                'DEFAULT_LANGUAGE' => $request->languages_by_default,
                'FRONTEND_PAGINATION' => $request->home_contents_per_page,
                'FACEBOOK_STATUS' => ($request->login_facebook_status) ? 1 : 0,
                'FACEBOOK_ID' => $request->login_facebook_client_id,
                'FACEBOOK_SECRET' => $request->login_facebook_client_secret,
                'TWITTER_STATUS' => ($request->login_twitter_status) ? 1 : 0,
                'TWITTER_ID' => $request->login_twitter_client_id,
                'TWITTER_SECRET' => $request->login_twitter_client_secret,
                'GOOGLE_STATUS' => ($request->login_google_status) ? 1 : 0,
                'GOOGLE_ID' => $request->login_google_client_id,
                'GOOGLE_SECRET' => $request->login_google_client_secret,
                'LINKEDIN_STATUS' => ($request->login_linkedin_status) ? 1 : 0,
                'LINKEDIN_ID' => $request->login_linkedin_client_id,
                'LINKEDIN_SECRET' => $request->login_linkedin_client_secret,
                'GITHUB_STATUS' => ($request->login_github_status) ? 1 : 0,
                'GITHUB_ID' => $request->login_github_client_id,
                'GITHUB_SECRET' => $request->login_github_client_secret,
                'BITBUCKET_STATUS' => ($request->login_bitbucket_status) ? 1 : 0,
                'BITBUCKET_ID' => $request->login_bitbucket_client_id,
                'BITBUCKET_SECRET' => $request->login_bitbucket_client_secret,
                'TIMEZONE' => $request->timezone,
                'GOOGLE_MAPS_KEY' => $request->google_maps_key,
                'FRONTEND_TOPICS_ORDER' => $request->front_topics_order,
                'BACKEND_PATH' => $request->backend_path,
                'GEOIP_STATUS' => ($request->geoip_status) ? 1 : 0,
                'GEOIP_SERVICE' => $request->geoip_service,
                'GEOIP_SERVICE_KEY' => $request->geoip_service_key,
                'FIRST_DAY_OF_WEEK' => $request->first_day_of_week,
                'DATE_FORMAT' => $request->date_format,
                'NEWSLETTER_STATUS' => ($request->newsletter_provider_status) ? 1 : 0,
                'NEWSLETTER_PROVIDER' => ($request->newsletter_provider_status) ? $request->newsletter_provider : "",
                'NEWSLETTER_API_KEY' => ($request->newsletter_provider_status) ? $request->newsletter_api_key : "",
                'NEWSLETTER_ENDPOINT' => ($request->newsletter_provider_status && $request->newsletter_provider == "mailcoach") ? $request->newsletter_endpoint : "null",
                'NEWSLETTER_LIST_ID' => ($request->newsletter_provider_status) ? $request->newsletter_list_id : "",

                'FILESYSTEM_DISK' => $request->file_system,
                'LOCAL_UPLOADS_PATH' => $request->local_path,
                'AWS_ACCESS_KEY_ID' => $request->s3_key,
                'AWS_SECRET_ACCESS_KEY' => $request->s3_secret,
                'AWS_DEFAULT_REGION' => $request->s3_region,
                'AWS_BUCKET' => $request->s3_bucket,
                'AWS_URL' => $request->s3_url,
                'AWS_ENDPOINT' => $request->s3_endpoint,
                'AWS_USE_PATH_STYLE_ENDPOINT' => $request->s3_path_style,
            ]);

            // clear cache & views cache
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            // delete cache files manually
            if (\File::exists(base_path("bootstrap/cache/config.php"))) {
                \File::delete(base_path("bootstrap/cache/config.php"));
            }
            if (\File::exists(base_path("bootstrap/cache/routes-v7.php"))) {
                \File::delete(base_path("bootstrap/cache/routes-v7.php"));
            }

            // re cache routes
            Artisan::call('route:cache');
            // re cache config
            Artisan::call('config:cache');
            sleep(4);

            if ($OLD_BACKEND_PATH != $request->backend_path) {
                // redirect to new admin path
                return redirect()->to($request->backend_path."/webmaster")->with('doneMessage',
                    __('backend.saveDone'))->with('active_tab', $request->active_tab);
            }
            return redirect()->action([WebmasterSettingsController::class, 'saved'], ['tab' => $request->active_tab]);
        } else {
            return redirect()->route('adminHome');
        }
    }

    public function saved($tab = "")
    {
        return redirect()->action([WebmasterSettingsController::class, 'edit'])
            ->with('doneMessage', __('backend.saveDone'))
            ->with('active_tab', $tab);
    }

    public function clearAnalytics()
    {
        if (!@Auth::user()->permissionsGroup->webmaster_status) {
            return redirect()->route("NoPermission");
        }

        Schema::disableForeignKeyConstraints();

        try {
            AnalyticsPage::truncate();
            AnalyticsVisitor::truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->action([WebmasterSettingsController::class, 'edit'])
            ->with('doneMessage', __('backend.analyticsClearDone'))
            ->with('active_tab', 'analyticsTab');
    }

    public function language_store(Request $request)
    {

        $this->validate($request, [
            'code' => ['required', 'regex:/^[A-Za-z]{2}$/'],
            'title' => 'required',
            'direction' => 'required|in:ltr,rtl',
            'fields' => 'required|in:0,1'
        ]);
        $left = "left";
        $right = "right";
        if ($request->direction == "rtl") {
            $left = "right";
            $right = "left";
        }
        $code = $this->languageSchema()->normalizeLanguageCode($request->code);
        if ($code === null) {
            return redirect()->action([WebmasterSettingsController::class, 'edit'])
                ->with('errorMessage', __('backend.error'))
                ->with('active_tab', "languageSettingsTab");
        }

        // Add new BD Columns
        $this->db_language_add($code);
        $success = false;
        $Language = Language::where("code", $code)->first();
        if (empty($Language)) {
            // Generate Lang files
            if ($code == "en") {
                $success = true;
            } else {
                if (File::exists(base_path("lang/$code"))) {
                    $success = true;
                } else {
                    if (File::exists(base_path("lang/en"))) {
                        $success = \File::copyDirectory(base_path("lang/en"),
                            base_path("lang/$code"));
                    }
                }
            }
            if ($success) {
                $Language = new Language;
                $Language->title = $request->title;
                $Language->code = $code;
                $Language->direction = $request->direction;
                $Language->icon = trim(strtolower($request->icon));
                $Language->box_status = ($request->fields) ? 1 : 0;
                $Language->left = $left;
                $Language->right = $right;
                $Language->status = 1;
                $Language->created_by = Auth::user()->id;
                $Language->save();



                return redirect()->action([WebmasterSettingsController::class, 'edit'])
                    ->with('doneMessage', __('backend.saveDone'))
                    ->with('active_tab', "languageSettingsTab");
            } else {
                return redirect()->action([WebmasterSettingsController::class, 'edit'])
                    ->with('errorMessage', __('backend.error'))
                    ->with('active_tab', "languageSettingsTab");
            }
        } else {
            return redirect()->action([WebmasterSettingsController::class, 'edit'])
                ->with('errorMessage', __('backend.languageExist'))
                ->with('active_tab', "languageSettingsTab");
        }
    }

    public function language_update(Request $request)
    {
        if (@Auth::user()->permissionsGroup->edit_status) {
            $Language = Language::find($request->id);
            if (!empty($Language)) {
                if (config('smartend.default_language') == $Language->code && !$request->status) {
                    return redirect()->action([WebmasterSettingsController::class, 'edit'])
                        ->with('errorMessage', __('backend.defineAnotherDefaultLangFirst'))
                        ->with('active_tab', "languageSettingsTab");
                }

                $this->validate($request, [
                    'title' => 'required',
                    'direction' => 'required|in:ltr,rtl',
                    'fields' => 'required|in:0,1',
                    'status' => 'required|in:0,1'
                ]);
                $left = "left";
                $right = "right";
                if ($request->direction == "rtl") {
                    $left = "right";
                    $right = "left";
                }
                $Language->title = $request->title;
                $Language->direction = $request->direction;
                $Language->icon = trim(strtolower($request->icon));
                $Language->box_status = ($request->fields) ? 1 : 0;
                $Language->left = $left;
                $Language->right = $right;
                $Language->status = ($request->status) ? 1 : 0;
                $Language->updated_by = Auth::user()->id;
                $Language->save();



                return redirect()->action([WebmasterSettingsController::class, 'edit'])
                    ->with('doneMessage', __('backend.saveDone'))
                    ->with('active_tab', "languageSettingsTab");
            }
        }
        return redirect()->action([WebmasterSettingsController::class, 'edit'])->with('active_tab',
            "languageSettingsTab");
    }

    public function language_destroy($id)
    {
        if (@Auth::user()->permissionsGroup->delete_status) {
            $Language = Language::find($id);
            $Languages_count = Language::count();
            if ($Languages_count > 1) {
                if (!empty($Language)) {
                    if (config('smartend.default_language') == $Language->code) {
                        return redirect()->action([WebmasterSettingsController::class, 'edit'])
                            ->with('errorMessage', __('backend.defineAnotherDefaultLangFirst'))
                            ->with('active_tab', "languageSettingsTab");
                    }
                    // Delete BD Columns
                    $this->db_language_destroy($Language->code);

                    if ($Language->code == "en") {
                        $success = true;
                    } else {
                        try {
                            if (\File::exists(base_path("resources/lang/".$Language->code))) {
                                $success = \File::deleteDirectory(base_path("resources/lang/".$Language->code));
                            } else {
                                $success = true;
                            }
                        } catch (\Exception $e) {
                            $success = true;
                        }
                    }
                    if ($success) {
                        $Language->delete();



                        return redirect()->action([WebmasterSettingsController::class, 'edit'])
                            ->with('doneMessage', __('backend.deleteDone'))
                            ->with('active_tab', "languageSettingsTab");
                    }
                }
            }
            return redirect()->action([WebmasterSettingsController::class, 'edit'])
                ->with('errorMessage', __('backend.error'))
                ->with('active_tab', "languageSettingsTab");
        }
        return redirect()->action([WebmasterSettingsController::class, 'edit'])->with('active_tab',
            "languageSettingsTab");
    }

    public function changeEnv(array $data)
    {
        $envPath = base_path('.env');

        // Read the existing .env file as lines
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Convert lines into an associative array
        $env = [];
        foreach ($envLines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            } // skip comments
            $entry = explode('=', $line, 2);
            if (count($entry) == 2) {
                $env[$entry[0]] = $entry[1];
            }
        }

        // Update or add new keys
        foreach ($data as $key => $value) {
            // If value contains spaces, quotes, or special characters, wrap it in quotes
            if (preg_match('/\s|"|#/', $value)) {
                $value = '"'.addslashes($value).'"';
            }
            $env[$key] = $value;
        }

        // Rebuild the .env file content
        $envContent = '';
        foreach ($env as $key => $value) {
            $envContent .= "{$key}={$value}\n";
        }

        // Write back to .env
        file_put_contents($envPath, $envContent);
        return true;
    }

    public function db_language_add($code)
    {
        return $this->languageSchema()->addLanguage($code);
    }

    public function db_language_destroy($code)
    {
        return $this->languageSchema()->destroyLanguage($code);
    }

    private function languageSchema(): LanguageSchemaService
    {
        return app(LanguageSchemaService::class);
    }

    public function seo_repair()
    {
        $title_var2 = "title_".config('smartend.default_language');

        // WebmasterSection
        $WebmasterSections = WebmasterSection::all();
        foreach ($WebmasterSections as $WebmasterSection) {
            $id = $WebmasterSection->id;
            foreach (Helper::languagesList() as $ActiveLanguage) {
                try {
                    $slug = $WebmasterSection->{"seo_url_slug_".$ActiveLanguage->code};
                    if ($slug == "") {
                        $title_var = "title_".@$ActiveLanguage->code;
                        if ($WebmasterSection->$title_var != "") {
                            $slug = $WebmasterSection->$title_var;
                        } else {
                            $slug = $WebmasterSection->$title_var2;
                        }
                    }
                    $WebmasterSection->{"seo_url_slug_".$ActiveLanguage->code} = Helper::URLSlug($slug, "section", $id);
                    $WebmasterSection->save();
                } catch (\Exception $e) {

                }
            }
        }

        // Section
        $Sections = Section::all();
        foreach ($Sections as $Section) {
            $id = $Section->id;
            foreach (Helper::languagesList() as $ActiveLanguage) {
                try {
                    $slug = $Section->{"seo_url_slug_".$ActiveLanguage->code};
                    if ($slug == "") {
                        $title_var = "title_".@$ActiveLanguage->code;
                        if ($Section->$title_var != "") {
                            $slug = $Section->$title_var;
                        } else {
                            $slug = $Section->$title_var2;
                        }
                    }
                    $Section->{"seo_url_slug_".$ActiveLanguage->code} = Helper::URLSlug($slug, "category", $id);
                    $Section->save();
                } catch (\Exception $e) {

                }
            }
        }

        // Topic
        $Topics = Topic::all();
        foreach ($Topics as $Topic) {
            $id = $Topic->id;
            foreach (Helper::languagesList() as $ActiveLanguage) {
                try {
                    $slug = $Topic->{"seo_url_slug_".$ActiveLanguage->code};
                    if ($slug == "") {
                        $title_var = "title_".@$ActiveLanguage->code;
                        if ($Topic->$title_var != "") {
                            $slug = $Topic->$title_var;
                        } else {
                            $slug = $Topic->$title_var2;
                        }
                    }
                    $Topic->{"seo_url_slug_".$ActiveLanguage->code} = Helper::URLSlug($slug, "topic", $id);
                    $Topic->save();
                } catch (\Exception $e) {

                }
            }
        }

        return redirect()->action([WebmasterSettingsController::class, 'edit'])
            ->with('doneMessage', __('backend.seoFixUrlsDone'))
            ->with('active_tab', "SEOSettingTab");
    }

    public function mail_smtp_check(Request $request)
    {
        if ($request->mail_driver == "smtp" && $request->mail_host != "" && $request->mail_port != "") {

            //Connect to the host on the specified port
            $smtpServer = $request->mail_host;
            $username = $request->mail_username;
            $password = $request->mail_password;
            $port = $request->mail_port;
            $encryption = $request->mail_encryption;

            try {
                $transport = new EsmtpTransport($smtpServer, $port, $encryption);
                $transport->setUsername($username);
                $transport->setPassword($password);
                $transport->start();

                return json_encode(array("stat" => "success"));
            } catch (\Exception $e) {
                return json_encode(array("stat" => "error", "error" => $e->getMessage()));
            }

        }
        return json_encode(array("stat" => "error", "error" => "Failed .. no data to connect"));
    }

    public function mail_test(Request $request)
    {
        $WebmasterSetting = WebmasterSetting::find(1);
        if (!empty($WebmasterSetting)) {
            $mail_driver = preg_replace('/\s+/', '', $request->mail_driver);
            $mail_host = preg_replace('/\s+/', '', $request->mail_host);
            $mail_port = preg_replace('/\s+/', '', $request->mail_port);
            $mail_username = preg_replace('/\s+/', '', $request->mail_username);
            $mail_password = preg_replace('/\s+/', '', $request->mail_password);
            $mail_encryption = preg_replace('/\s+/', '', $request->mail_encryption);
            $mail_no_replay = preg_replace('/\s+/', '', $request->mail_no_replay);

            $WebmasterSetting->mail_driver = $mail_driver;
            $WebmasterSetting->mail_host = $mail_host;
            $WebmasterSetting->mail_port = $mail_port;
            $WebmasterSetting->mail_username = $mail_username;
            $WebmasterSetting->mail_password = $mail_password;
            $WebmasterSetting->mail_encryption = $mail_encryption;
            $WebmasterSetting->mail_no_replay = $mail_no_replay;
            $WebmasterSetting->save();


            $env_update = $this->changeEnv([
                'MAIL_DRIVER' => $mail_driver,
                'MAIL_HOST' => $mail_host,
                'MAIL_PORT' => $mail_port,
                'MAIL_USERNAME' => $mail_username,
                'MAIL_PASSWORD' => $mail_password,
                'MAIL_ENCRYPTION' => $mail_encryption,
                'MAIL_FROM_ADDRESS' => $mail_no_replay,
            ]);

            if ($request->mail_driver == "smtp" && $request->mail_host != "" && $request->mail_port != "") {
                try {
                    $email_subject = "Test Mail From ".config('app.name');
                    $email_body = "This is a Test Mail <br>
Mail Driver: ".$request->mail_driver."<br>
Mail Host: ".$request->mail_host."<br>
Mail Port: ".$request->mail_port."<br>
Mail Username: ".$request->mail_username."<br>
Email from: ".$request->mail_no_replay."<br>
Email to: ".$request->mail_test."
";
                    $to_email = $request->mail_test;
                    $to_name = $request->mail_test;
                    $from_email = $request->mail_no_replay;
                    $from_name = config('app.name');

                    Mail::send('emails.template', [
                        'title' => $email_subject,
                        'details' => $email_body
                    ], function ($message) use ($email_subject, $to_email, $to_name, $from_email, $from_name) {
                        $message->from($from_email, $from_name);
                        $message->to($to_email);
                        $message->replyTo($from_email, $from_name);
                        $message->subject($email_subject);

                    });

                    return json_encode(array("stat" => "success"));
                } catch (\Exception $e) {
                    return json_encode(array("stat" => "error"));
                }
            }
        }
        return json_encode(array("stat" => "error"));
    }
}
