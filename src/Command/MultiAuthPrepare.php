<?php

namespace iMokhles\MultiAuthBackPack\Command;

use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MultiAuthPrepare extends BaseCommand
{
    /**
     * @var
     */
    protected $progressBar;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:multi-backpack {name} {--admin_theme= : chose the theme you want}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create multi authentication guards with backpack panel';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();
        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return boolean
     */
    public function handle()
    {

        $this->progressBar = $this->output->createProgressBar(14);
        $this->progressBar->start();

        $this->line(" Preparing For MultiAuth. Please wait...");
        $this->progressBar->advance();

        $name = ucfirst($this->getParsedNameInput());

        $admin_theme = $this->option('admin_theme');
        if (is_null($admin_theme)) {
            $admin_theme = 'adminlte';
        }

        if (file_exists(__DIR__ . '/../Backpack/Views/'.$admin_theme)) {
            if ($this->isAlreadySetup() == false) {

                $this->line(" installing migrations...");
                $this->installMigration();
                $this->progressBar->advance();

                $this->line(" installing models...");
                $this->installModel();
                $this->progressBar->advance();

                $this->line(" installing route maps...");
                $this->installRouteMaps();
                $this->progressBar->advance();

                $this->line(" installing route files...");
                $this->installRouteFiles();
                $this->progressBar->advance();

                $this->line(" installing controllers...");
                $this->installControllers();
                $this->progressBar->advance();

                $this->line(" installing requests...");
                $this->installRequests();
                $this->progressBar->advance();

                $this->line(" installing configs...");
                $this->installConfigs();
                $this->progressBar->advance();

                $this->line(" installing middleware...");
                $this->installMiddleware();
                $this->progressBar->advance();

                $this->line(" installing unauthenticated function...");
                $this->installUnauthenticated();
                $this->progressBar->advance();

                $this->line(" installing views...");
                $this->installView($admin_theme);
                $this->progressBar->advance();

                $this->line(" installing prologue alert...");
                $this->installPrologueAlert();
                $this->progressBar->advance();

                $this->line(" dump autoload...");
                $this->composer->dumpAutoloads();
                $this->progressBar->advance();

                $this->progressBar->finish();
                $this->info(" finished ".$name." setup with Backpack panel.");
            } else {
                $this->progressBar->finish();
                $this->line(" failed. already setup for: ".$name);
                $this->progressBar->advance();
            }
        } else {
            $this->progressBar->finish();
            $this->line(" failed: ".$admin_theme." theme not found");
            $this->progressBar->advance();
        }


        return true;
    }

    /**
     * Publish Prologue Alert
     *
     * @return boolean
     */
    public function installPrologueAlert() {
        $alertsConfigFile = $this->getConfigsFolderPath().DIRECTORY_SEPARATOR."prologue/alerts.php";
        if (!file_exists($alertsConfigFile)) {
            $this->executeProcess('php artisan vendor:publish --provider="Prologue\Alerts\AlertsServiceProvider"',
                'publishing config for notifications - prologue/alerts');
        }

        return true;
    }

    /**
     * Install Migration.
     *
     * @return boolean
     */
    public function installMigration()
    {
        $nameSmallPlural = str_plural(snake_case($this->getParsedNameInput()));
        $name = ucfirst($this->getParsedNameInput());
        $namePlural = str_plural($name);



        $modelTableContent = file_get_contents(__DIR__ . '/../Migration/modelTable.stub');
        $modelTableContentNew = str_replace([
            '{{$namePlural}}',
            '{{$nameSmallPlural}}',
        ], [
            $namePlural,
            $nameSmallPlural
        ], $modelTableContent);


        $modelResetPasswordTableContent = file_get_contents(__DIR__ . '/../Migration/passwordResetsTable.stub');
        $modelResetPasswordTableContentNew = str_replace([
            '{{$namePlural}}',
            '{{$nameSmallPlural}}',
        ], [
            $namePlural,
            $nameSmallPlural
        ], $modelResetPasswordTableContent);


        $migrationName = date('Y_m_d_His') . '_'.'create_' . str_plural(snake_case($name)) .'_table.php';
        $migrationModelPath = $this->getMigrationPath().DIRECTORY_SEPARATOR.$migrationName;
        file_put_contents($migrationModelPath, $modelTableContentNew);

        $migrationResetName = date('Y_m_d_His') . '_'
            .'create_' . str_plural(snake_case($name))
            .'_password_resets_table.php';
        $migrationResetModelPath = $this->getMigrationPath().DIRECTORY_SEPARATOR.$migrationResetName;
        file_put_contents($migrationResetModelPath, $modelResetPasswordTableContentNew);

        return true;

    }


    /**
     * Install Model.
     *
     * @return boolean
     */
    public function installModel()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $name = ucfirst($this->getParsedNameInput());


        $arrayToChange = [
            '{{$name}}',
        ];

        $newChanges = [
            $name,
        ];
        $nameSmallPlural = str_plural(snake_case($this->getParsedNameInput()));
        array_push($arrayToChange, '{{$nameSmallPlural}}');
        array_push($arrayToChange, '{{$nameSmall}}');
        array_push($newChanges, $nameSmallPlural);
        array_push($newChanges, $nameSmall);

        $modelContent = file_get_contents(__DIR__ . '/../Backpack/Model/model.stub');
        $modelContentNew = str_replace($arrayToChange, $newChanges, $modelContent);

        $createFolder = $this->getAppFolderPath().DIRECTORY_SEPARATOR."Models";
        if (!file_exists($createFolder)) {
            mkdir($createFolder);
        }

        $modelPath = $createFolder.DIRECTORY_SEPARATOR.$name.".php";
        file_put_contents($modelPath, $modelContentNew);



        $resetNotificationContent = file_get_contents(__DIR__ . '/../Notification/resetPasswordNotification.stub');
        $resetNotificationContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}',
        ], [
            $name,
            $nameSmall
        ], $resetNotificationContent);

        $verifyEmailContent = file_get_contents(__DIR__ . '/../Notification/verifyEmailNotification.stub');
        $verifyEmailContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}',
        ], [
            $name,
            $nameSmall
        ], $verifyEmailContent);

        $createFolder = $this->getAppFolderPath().DIRECTORY_SEPARATOR."Notifications";
        if (!file_exists($createFolder)) {
            mkdir($createFolder);
        }

        $resetNotificationPath = $createFolder.DIRECTORY_SEPARATOR.$name."ResetPasswordNotification.php";
        file_put_contents($resetNotificationPath, $resetNotificationContentNew);

        $verifyEmailPath = $createFolder.DIRECTORY_SEPARATOR.$name."VerifyEmailNotification.php";
        file_put_contents($verifyEmailPath, $verifyEmailContentNew);

        return true;

    }

    /**
     * Install View.
     *
     * @param string $theme_name
     * @return bool
     */
    public function installView($theme_name = 'adminlte')
    {
        if (file_exists(__DIR__ . '/../Backpack/Views/'.$theme_name)) {
            $nameSmall = snake_case($this->getParsedNameInput());
            $name = ucfirst($this->getParsedNameInput());

            // layouts
            $appBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/layouts/layout.blade.stub');
            $appGuestBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/layouts/layout_guest.blade.stub');

            // home
            $homeBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/home.blade.stub');

            // auth
            $loginBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/auth/login.blade.stub');
            $registerBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/auth/register.blade.stub');
            $verifyEmailBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/auth/verify.blade.stub');

            // auth/passwords
            $resetBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/auth/passwords/reset.blade.stub');
            $emailBlade = file_get_contents(__DIR__ . '/../Backpack/Views/'.$theme_name.'/auth/passwords/email.blade.stub');

            // auth/account
            $update_infoBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/auth/account/update_info.blade.stub');
            $change_passwordBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/auth/account/change_password.blade.stub');
            $sidemenuBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/auth/account/sidemenu.blade.stub');

            // inc
            $main_headerBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/main_header.blade.stub');
            $sidebarBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/sidebar.blade.stub');
            $alertsBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/alerts.blade.stub');
            $footerBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/footer.blade.stub');
            $footerGuestBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/footer_guest.blade.stub');
            $headBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/head.blade.stub');
            $scriptsBlade = file_get_contents(__DIR__
                . '/../Backpack/Views/'.$theme_name.'/inc/scripts.blade.stub');

            if ($theme_name == 'adminlte') {
                $menuBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/inc/menu.blade.stub');
                $sidebar_user_panelBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/inc/sidebar_user_panel.blade.stub');
                // style
                $authCSS = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/style/backpack_auth_css.blade.stub');
            } else {
                $account_infoBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/auth/account/account_info.blade.stub');
                $sidebar_user_panelBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/inc/user_menu.blade.stub');
                $breadcrumbBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/auth/inc/breadcrumb.blade.stub');
                $notifications_menuBlade = file_get_contents(__DIR__
                    . '/../Backpack/Views/'.$theme_name.'/auth/inc/notifications_menu.blade.stub');
            }




            $createFolder = $this->getViewsFolderPath().DIRECTORY_SEPARATOR."$nameSmall";
            if (!file_exists($createFolder)) {
                mkdir($createFolder);
            }

            $createFolderLayouts = $this->getViewsFolderPath().DIRECTORY_SEPARATOR
                ."$nameSmall"
                .DIRECTORY_SEPARATOR."layouts";
            if (!file_exists($createFolderLayouts)) {
                mkdir($createFolderLayouts);
            }

            $createFolderStyle = $this->getViewsFolderPath().DIRECTORY_SEPARATOR
                ."$nameSmall"
                .DIRECTORY_SEPARATOR."style";
            if (!file_exists($createFolderStyle)) {
                mkdir($createFolderStyle);
            }

            $createFolderInc = $this->getViewsFolderPath().DIRECTORY_SEPARATOR
                ."$nameSmall"
                .DIRECTORY_SEPARATOR."inc";
            if (!file_exists($createFolderInc)) {
                mkdir($createFolderInc);
            }

            $createFolderAuth = $this->getViewsFolderPath().DIRECTORY_SEPARATOR."$nameSmall"
                .DIRECTORY_SEPARATOR."auth";
            if (!file_exists($createFolderAuth)) {
                mkdir($createFolderAuth);
            }

            $createFolderAuthPasswords = $this->getViewsFolderPath().DIRECTORY_SEPARATOR.
                "$nameSmall".DIRECTORY_SEPARATOR
                ."auth".DIRECTORY_SEPARATOR."passwords";
            if (!file_exists($createFolderAuthPasswords)) {
                mkdir($createFolderAuthPasswords);
            }

            $createFolderAuthAccount = $this->getViewsFolderPath().DIRECTORY_SEPARATOR.
                "$nameSmall".DIRECTORY_SEPARATOR
                ."auth".DIRECTORY_SEPARATOR."account";
            if (!file_exists($createFolderAuthAccount)) {
                mkdir($createFolderAuthAccount);
            }

            $headBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $headBlade);

            $appBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $appBlade);

            $appGuestBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $appGuestBlade);

            $verifyEmailBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $verifyEmailBlade);

            $homeBladeNew = str_replace([
                '{{$nameSmall}}',
                '{{$name}}',

            ], [
                $nameSmall
            ], $homeBlade);

            $loginBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $loginBlade);

            $registerBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $registerBlade);

            $emailBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $emailBlade);

            $resetBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $resetBlade);

            $update_infoBladeNew = str_replace([
                '{{$nameSmall}}',
                '{{$name}}',
            ], [
                $nameSmall,
                $name
            ], $update_infoBlade);

            $change_passwordBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall,
            ], $change_passwordBlade);

            if ($theme_name != 'adminlte') {
                $account_infoBladeNew = str_replace([
                    '{{$nameSmall}}',
                ], [
                    $nameSmall,
                ], $account_infoBlade);
            }



            $sidemenuBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $sidemenuBlade);

            $main_headerBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $main_headerBlade);

            $menuBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $menuBlade);

            $sidebarBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $sidebarBlade);

            $sidebar_user_panelBladeNew = str_replace([
                '{{$nameSmall}}',
            ], [
                $nameSmall
            ], $sidebar_user_panelBlade);


            file_put_contents($createFolderLayouts.'/layout.blade.php', $appBladeNew);
            file_put_contents($createFolderLayouts.'/layout_guest.blade.php', $appGuestBladeNew);
            file_put_contents($createFolder.'/home.blade.php', $homeBladeNew);


            file_put_contents($createFolderInc.'/main_header.blade.php', $main_headerBladeNew);
            file_put_contents($createFolderInc.'/sidebar.blade.php', $sidebarBladeNew);

            file_put_contents($createFolderInc.'/alerts.blade.php', $alertsBlade);
            file_put_contents($createFolderInc.'/footer.blade.php', $footerBlade);
            file_put_contents($createFolderInc.'/footer_guest.blade.php', $footerGuestBlade);
            file_put_contents($createFolderInc.'/head.blade.php', $headBladeNew);
            file_put_contents($createFolderInc.'/scripts.blade.php', $scriptsBlade);

            file_put_contents($createFolderAuth.'/login.blade.php', $loginBladeNew);
            file_put_contents($createFolderAuth.'/verify.blade.php', $verifyEmailBladeNew);
            file_put_contents($createFolderAuth.'/register.blade.php', $registerBladeNew);
            file_put_contents($createFolderAuthPasswords.'/email.blade.php', $emailBladeNew);
            file_put_contents($createFolderAuthPasswords.'/reset.blade.php', $resetBladeNew);

            file_put_contents($createFolderAuthAccount.'/sidemenu.blade.php', $sidemenuBladeNew);
            file_put_contents($createFolderAuthAccount.'/update_info.blade.php', $update_infoBladeNew);
            file_put_contents($createFolderAuthAccount.'/change_password.blade.php', $change_passwordBladeNew);

            if ($theme_name == 'adminlte') {
                file_put_contents($createFolderStyle.'/backpack_auth_css.blade.php', $authCSS);
                file_put_contents($createFolderInc.'/menu.blade.php', $menuBladeNew);
                file_put_contents($createFolderInc.'/sidebar_user_panel.blade.php', $sidebar_user_panelBladeNew);
            } else {
                file_put_contents($createFolderAuthAccount.'/account_info.blade.php', $account_infoBladeNew);
                file_put_contents($createFolderInc.'/user_menu.blade.php', $sidebar_user_panelBladeNew);
                file_put_contents($createFolderInc.'/breadcrumb.blade.php', $breadcrumbBlade);
                file_put_contents($createFolderInc.'/notifications_menu.blade.php', $notifications_menuBlade);
            }


            return true;
        }
        return false;

    }

    /**
     * Install RouteMaps.
     *
     * @return boolean
     */

    public function installRouteMaps()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $name = ucfirst($this->getParsedNameInput());
        $mapCallFunction = file_get_contents(__DIR__ . '/../Route/mapRoute.stub');
        $mapCallFunctionNew = str_replace('{{$name}}', "$name", $mapCallFunction);
        $this->insert($this->getRouteServicesPath(), '$this->mapWebRoutes();', $mapCallFunctionNew, true);
        $mapFunction = file_get_contents(__DIR__ . '/../Route/mapRouteFunction.stub');
        $mapFunctionNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $mapFunction);
        $this->insert($this->getRouteServicesPath(), '        //
    }', $mapFunctionNew, true);
        return true;
    }

    public function isAlreadySetup() {
        $name = ucfirst($this->getParsedNameInput());

        $routeServicesContent = file_get_contents($this->getRouteServicesPath());

        if (str_contains($routeServicesContent,'$this->map'.$name.'Routes();')) {
            return true;
        }
        return false;
    }

    /**
     * Install RouteFile.
     *
     * @return boolean
     */

    public function installRouteFiles()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $name = ucfirst($this->getParsedNameInput());
        $createFolder = $this->getRoutesFolderPath().DIRECTORY_SEPARATOR.$nameSmall;
        if (!file_exists($createFolder)) {
            mkdir($createFolder);
        }
        $routeFileContent = file_get_contents(__DIR__ . '/../Route/routeFile.stub');
        $routeFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $routeFileContent);
        $routeFile = $createFolder.DIRECTORY_SEPARATOR.$nameSmall.".php";
        file_put_contents($routeFile, $routeFileContentNew);
        return true;
    }

    /**
     * Install Requests.
     *
     * @return boolean
     */

    public function installRequests()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $name = ucfirst($this->getParsedNameInput());

        $nameFolder = $this->getControllersPath().DIRECTORY_SEPARATOR.$name;
        if (!file_exists($nameFolder)) {
            mkdir($nameFolder);
        }

        $requestsFolder = $nameFolder.DIRECTORY_SEPARATOR."Requests";
        if (!file_exists($requestsFolder)) {
            mkdir($requestsFolder);
        }
        $accountInfoContent = file_get_contents(__DIR__ . '/../Request/AccountInfoRequest.stub');
        $changePasswordContent = file_get_contents(__DIR__ . '/../Request/ChangePasswordRequest.stub');

        $accountInfoContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $accountInfoContent);

        $changePasswordContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $changePasswordContent);

        $accountInfoFile = $requestsFolder.DIRECTORY_SEPARATOR."{$name}AccountInfoRequest.php";
        $changePasswordFile = $requestsFolder.DIRECTORY_SEPARATOR."{$name}ChangePasswordRequest.php";

        file_put_contents($accountInfoFile, $accountInfoContentNew);
        file_put_contents($changePasswordFile, $changePasswordContentNew);

        return true;

    }
    /**
     * Install Controller.
     *
     * @return boolean
     */

    public function installControllers()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $nameSmallPlural = str_plural(snake_case($this->getParsedNameInput()));
        $name = ucfirst($this->getParsedNameInput());

        $nameFolder = $this->getControllersPath().DIRECTORY_SEPARATOR.$name;
        if (!file_exists($nameFolder)) {
            mkdir($nameFolder);
        }

        $authFolder = $nameFolder.DIRECTORY_SEPARATOR."Auth";
        if (!file_exists($authFolder)) {
            mkdir($authFolder);
        }

        $controllerContent = file_get_contents(__DIR__ . '/../Controllers/Controller.stub');
        $homeControllerContent = file_get_contents(__DIR__ . '/../Controllers/HomeController.stub');
        $loginControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/LoginController.stub');
        $forgotControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/ForgotPasswordController.stub');
        $registerControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/RegisterController.stub');
        $resetControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/ResetPasswordController.stub');
        $myAccountControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/MyAccountController.stub');
        $verificationControllerContent = file_get_contents(__DIR__ . '/../Controllers/Auth/VerificationController.stub');

        $controllerFileContentNew = str_replace('{{$name}}', "$name", $controllerContent);

        $homeFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $homeControllerContent);

        $loginFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $loginControllerContent);

        $forgotFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}',
            '{{$nameSmallPlural}}'
        ], [
            "$name",
            "$nameSmall",
            "$nameSmallPlural"
        ], $forgotControllerContent);

        $registerFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}',
            '{{$nameSmallPlural}}'
        ], [
            "$name",
            "$nameSmall",
            "$nameSmallPlural"
        ], $registerControllerContent);

        $resetFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}',
            '{{$nameSmallPlural}}'
        ], [
            "$name",
            "$nameSmall",
            "$nameSmallPlural"
        ], $resetControllerContent);

        $myAccountFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $myAccountControllerContent);

        $verificationControllerContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmall}}'
        ], [
            "$name",
            "$nameSmall"
        ], $verificationControllerContent);

        $controllerFile = $nameFolder.DIRECTORY_SEPARATOR."Controller.php";
        $homeFile = $nameFolder.DIRECTORY_SEPARATOR."HomeController.php";
        $loginFile = $authFolder.DIRECTORY_SEPARATOR."LoginController.php";
        $forgotFile = $authFolder.DIRECTORY_SEPARATOR."ForgotPasswordController.php";
        $registerFile = $authFolder.DIRECTORY_SEPARATOR."RegisterController.php";
        $resetFile = $authFolder.DIRECTORY_SEPARATOR."ResetPasswordController.php";
        $verificationFile = $authFolder.DIRECTORY_SEPARATOR."VerificationController.php";

        $myAccountFile = $authFolder.DIRECTORY_SEPARATOR."{$name}AccountController.php";


        file_put_contents($controllerFile, $controllerFileContentNew);
        file_put_contents($homeFile, $homeFileContentNew);
        file_put_contents($loginFile, $loginFileContentNew);
        file_put_contents($forgotFile, $forgotFileContentNew);
        file_put_contents($registerFile, $registerFileContentNew);
        file_put_contents($resetFile, $resetFileContentNew);
        file_put_contents($myAccountFile, $myAccountFileContentNew);
        file_put_contents($verificationFile, $verificationControllerContentNew);

        return true;

    }

    /**
     * Install Configs.
     *
     * @return boolean
     */

    public function installConfigs()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $nameSmallPlural = str_plural(snake_case($this->getParsedNameInput()));
        $name = ucfirst($this->getParsedNameInput());

        $authConfigFile = $this->getConfigsFolderPath().DIRECTORY_SEPARATOR."auth.php";

        $guardContent = file_get_contents(__DIR__ . '/../Config/guard.stub');
        $passwordContent = file_get_contents(__DIR__ . '/../Config/password.stub');
        $providerContent = file_get_contents(__DIR__ . '/../Config/provider.stub');

        $guardFileContentNew = str_replace([
            '{{$nameSmall}}',
            '{{$nameSmallPlural}}'
        ], [
            "$nameSmall",
            "$nameSmallPlural"
        ], $guardContent);

        $passwordFileContentNew = str_replace('{{$nameSmallPlural}}', "$nameSmallPlural", $passwordContent);

        $providerFileContentNew = str_replace([
            '{{$name}}',
            '{{$nameSmallPlural}}'
        ], [
            "$name",
            "$nameSmallPlural"
        ], $providerContent);

        $this->insert($authConfigFile, '    \'guards\' => [', $guardFileContentNew, true);

        $this->insert($authConfigFile, '    \'passwords\' => [', $passwordFileContentNew, true);

        $this->insert($authConfigFile, '    \'providers\' => [', $providerFileContentNew, true);

        return true;

    }

    /**
     * Install Unauthenticated Handler.
     *
     * @return boolean
     */
    public function installUnauthenticated()
    {
        $nameSmall = snake_case($this->getParsedNameInput());
        $exceptionHandlerFile = $this->getAppFolderPath().DIRECTORY_SEPARATOR."Exceptions".DIRECTORY_SEPARATOR
            ."Handler.php";
        $exceptionHandlerFileContent = file_get_contents($exceptionHandlerFile);
        $exceptionHandlerFileContentNew = file_get_contents(__DIR__ . '/../Exceptions/handlerUnauthorized.stub');


        if (!str_contains($exceptionHandlerFileContent, 'MultiAuthUnAuthenticated')) {
            // replace old file
            $deleted = unlink($exceptionHandlerFile);
            if ($deleted) {
                file_put_contents($exceptionHandlerFile, $exceptionHandlerFileContentNew);
            }
        }

        $exceptionHandlerGuardContentNew = file_get_contents(__DIR__ . '/../Exceptions/handlerGuard.stub');
        $exceptionHandlerGuardContentNew2 = str_replace('{{$nameSmall}}', "$nameSmall",
            $exceptionHandlerGuardContentNew);

        $this->insert($exceptionHandlerFile, '        switch(array_get($exception->guards(), 0)) {',
            $exceptionHandlerGuardContentNew2, true);

        return true;

    }

    /**
     * Install Middleware.
     *
     * @return boolean
     */

    public function installMiddleware()
    {
        $nameSmall = snake_case($this->getParsedNameInput());

        $redirectIfMiddlewareFile = $this->getMiddlewarePath().DIRECTORY_SEPARATOR."RedirectIfAuthenticated.php";
        $authenticateMiddlewareFile = $this->getMiddlewarePath().DIRECTORY_SEPARATOR."Authenticate.php";
        $ensureEmailIsVerifiedMiddlewareFile = $this->getMiddlewarePath().DIRECTORY_SEPARATOR."EnsureEmailIsVerified.php";
        $middlewareKernelFile = $this->getHttpPath().DIRECTORY_SEPARATOR."Kernel.php";

        $redirectIfMiddlewareFileContent = file_get_contents($redirectIfMiddlewareFile);
        $authenticateMiddlewareFileContent = file_get_contents($redirectIfMiddlewareFile);

        $ensureEmailIsVerifiedMiddlewareFileeContent = file_get_contents(__DIR__ . '/../Middleware/ensureEmailIsVerified.stub');
        $redirectIfMiddlewareContentNew = file_get_contents(__DIR__ . '/../Middleware/redirectIf.stub');
        $authenticateMiddlewareContentNew = file_get_contents(__DIR__ . '/../Middleware/authenticate.stub');

        if (!file_exists($ensureEmailIsVerifiedMiddlewareFile)) {
            file_put_contents($ensureEmailIsVerifiedMiddlewareFile, $ensureEmailIsVerifiedMiddlewareFileeContent);
        }

        if (!str_contains($redirectIfMiddlewareFileContent, 'MultiAuthGuards')) {
            // replace old file
            $deleted = unlink($redirectIfMiddlewareFile);
            if ($deleted) {
                file_put_contents($redirectIfMiddlewareFile, $redirectIfMiddlewareContentNew);
            }
        }

        if (!str_contains($authenticateMiddlewareFileContent, 'MultiAuthGuards')) {
            // replace old file
            $deleted = unlink($authenticateMiddlewareFile);
            if ($deleted) {
                file_put_contents($authenticateMiddlewareFile, $authenticateMiddlewareContentNew);
            }
        }

        $redirectIfMiddlewareGroupContentNew = file_get_contents(__DIR__ . '/../Middleware/redirectMiddleware.stub');
        $redirectIfMiddlewareGuardContentNew = file_get_contents(__DIR__ . '/../Middleware/redirectMiddlewareGuard.stub');
        $authenticateIfMiddlewareGuardContentNew = file_get_contents(__DIR__ . '/../Middleware/authenticateIf.stub');

        $redirectIfMiddlewareGroupContentNew2 = str_replace('{{$nameSmall}}', "$nameSmall",
            $redirectIfMiddlewareGroupContentNew);
        $redirectIfMiddlewareGuardContentNew2 = str_replace('{{$nameSmall}}', "$nameSmall",
            $redirectIfMiddlewareGuardContentNew);
        $authenticateIfMiddlewareGuardContentNew2 = str_replace('{{$nameSmall}}', "$nameSmall",
            $authenticateIfMiddlewareGuardContentNew);

        $this->insert($middlewareKernelFile, '    protected $middlewareGroups = [',
            $redirectIfMiddlewareGroupContentNew2, true);

        $this->insert($redirectIfMiddlewareFile, '        switch ($guard) {',
            $redirectIfMiddlewareGuardContentNew2, true);

        $this->insert($authenticateMiddlewareFile, '        // MultiAuthGuards',
            $authenticateIfMiddlewareGuardContentNew2, true);

        return true;

    }

    /**
     * Run a SSH command.
     *
     * @param string $command      The SSH command that needs to be run
     * @param bool   $beforeNotice Information for the user before the command is run
     * @param bool   $afterNotice  Information for the user after the command is run
     *
     * @return mixed Command-line output
     */
    public function executeProcess($command, $beforeNotice = false, $afterNotice = false)
    {
        if ($beforeNotice) {
            $this->info('### '.$beforeNotice);
        } else {
            $this->info('### Running: '.$command);
        }
        $process = new Process($command);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo '... > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($afterNotice) {
            $this->info('### '.$afterNotice);
        }
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getParsedNameInput()
    {
        return mb_strtolower(str_singular($this->getNameInput()));
    }
    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return mixed
     */
    protected function writeMigration($name, $table, $create)
    {
        $file = pathinfo($this->creator->create(
            $name, $this->getMigrationPath(), $table, $create
        ), PATHINFO_FILENAME);
        $this->line("<info>Created Migration:</info> {$file}");
    }

    /**
     * Get migration path.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return parent::getMigrationPath();
    }

    /**
     * Get Routes Provider Path.
     *
     * @return string
     */
    protected function getRouteServicesPath()
    {
        return $this->getAppFolderPath().DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'RouteServiceProvider.php';
    }

    /**
     * Get Routes Folder Path.
     *
     * @return string
     */
    protected function getAppFolderPath()
    {
        return $this->laravel->basePath().DIRECTORY_SEPARATOR.'app';
    }

    /**
     * Get Routes Folder Path.
     *
     * @return string
     */
    protected function getRoutesFolderPath()
    {
        return $this->laravel->basePath().DIRECTORY_SEPARATOR.'routes';
    }

    /**
     * Get Config Folder Path.
     *
     * @return string
     */
    protected function getConfigsFolderPath()
    {
        return $this->laravel->basePath().DIRECTORY_SEPARATOR.'config';
    }

    /**
     * Get Config Folder Path.
     *
     * @return string
     */
    protected function getViewsFolderPath()
    {
        return $this->laravel->basePath().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
    }

    /**
     * Get Controllers Path.
     *
     * @return string
     */
    protected function getControllersPath()
    {
        return $this->getAppFolderPath().DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';
    }

    /**
     * Get Http Path.
     *
     * @return string
     */
    protected function getHttpPath()
    {
        return $this->getAppFolderPath().DIRECTORY_SEPARATOR.'Http';
    }

    /**
     * Get Middleware Path.
     *
     * @return string
     */
    protected function getMiddlewarePath()
    {
        return $this->getAppFolderPath().DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Middleware';
    }

    /**
     * insert text into file
     *
     * @param string $filePath
     * @param string $insertMarker
     * @param string $text
     * @param boolean $after
     *
     * @return integer
     */
    public function insertIntoFile($filePath, $insertMarker, $text, $after = true) {
        $contents = file_get_contents($filePath);
        $new_contents = preg_replace($insertMarker,($after) ? '$0' . $text : $text . '$0', $contents);
        return file_put_contents($filePath, $new_contents);
    }

    /**
     * insert text into file
     *
     * @param string $filePath
     * @param string $keyword
     * @param string $body
     * @param boolean $after
     *
     * @return integer
     */
    public function insert($filePath, $keyword, $body, $after = true) {

        $contents = file_get_contents($filePath);
        $new_contents = substr_replace($contents, PHP_EOL . $body,
            ($after) ? strpos($contents, $keyword) + strlen($keyword) : strpos($contents, $keyword)
            , 0);
        return file_put_contents($filePath, $new_contents);
    }
}
