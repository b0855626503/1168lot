<?php

namespace Gametech\Lotto\Providers;

use Gametech\Lotto\Console\Commands\BackfillLottoPayoutCommand;
use Gametech\Lotto\Console\Commands\BootstrapMemberMarketPoliciesCommand;
use Gametech\Lotto\Console\Commands\BootstrapMissingResultSourcesCommand;
use Gametech\Lotto\Console\Commands\CleanupBrowserRuntimeArtifactsCommand;
use Gametech\Lotto\Console\Commands\GenerateAutoLottoDrawsCommand;
use Gametech\Lotto\Console\Commands\InsertInternalResultSourceMappingsCommand;
use Gametech\Lotto\Console\Commands\LottoAutoResultMetricsCommand;
use Gametech\Lotto\Console\Commands\LottoFetchAutoResultsCommand;
use Gametech\Lotto\Console\Commands\LottoRelayConsumeStreamCommand;
use Gametech\Lotto\Console\Commands\LottoRelayHealthCommand;
use Gametech\Lotto\Console\Commands\LottoRelayPublishReadyCommand;
use Gametech\Lotto\Console\Commands\LottoWinningReportBackfillCommand;
use Gametech\Lotto\Console\Commands\LottoWinningReportReconcileCommand;
use Gametech\Lotto\Console\Commands\MigrateExphuaySourcesToExternalEndpointCommand;
use Gametech\Lotto\Console\Commands\MigrateInternalResultEndpointsCommand;
use Gametech\Lotto\Console\Commands\MigrateLegacyLottoPermissionsCommand;
use Gametech\Lotto\Console\Commands\MigrateRelayResultSourcesCommand;
use Gametech\Lotto\Console\Commands\SyncLottoDrawStatusesCommand;
use Gametech\Lotto\Models\LotteryGroupProxy;
use Gametech\Lotto\Models\LotteryMarketProxy;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSettingProxy;
use Gametech\Lotto\Models\LottoDrawProxy;
use Gametech\Lotto\Models\LottoMarketBetSettingProxy;
use Gametech\Lotto\Models\LottoNavbarItemProxy;
use Gametech\Lotto\Models\LottoNavbarProxy;
use Gametech\Lotto\Models\LottoNumberBlockProxy;
use Gametech\Lotto\Models\LottoNumberExposureProxy;
use Gametech\Lotto\Models\LottoTicketItemProxy;
use Gametech\Lotto\Models\LottoTicketProxy;
use Gametech\Lotto\Models\LottoWinningProxy;
use Gametech\Lotto\Models\SettlementBatchProxy;
use Gametech\Lotto\Observers\LottoAuditObserver;
use Gametech\Lotto\Observers\LottoDashboardSummaryObserver;
use Gametech\Lotto\Observers\LottoDrawAutoResultObserver;
use Gametech\Lotto\Observers\LottoDrawRealtimeObserver;
use Gametech\Lotto\Observers\LottoTicketRealtimeObserver;
use Gametech\Lotto\Services\AutoResultHardeningService;
use Gametech\Lotto\Services\BetService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\ExposureService;
use Gametech\Lotto\Services\LottoConfigResolver;
use Gametech\Lotto\Services\LottoPackageResolver;
use Gametech\Lotto\Services\MemberMarketPolicyService;
use Gametech\Lotto\Services\Relay\LotteryRelayPublisher;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayStream;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\WalletTransactionService;
use Gametech\Lotto\Services\WinningReport\WinningReportService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Lotto Feature Service Provider
 * Registers routes, views, and services
 */
class LottoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(BetService::class, function ($app) {
            return new BetService(
                $app->make(ExposureService::class),
                $app->make(LottoConfigResolver::class),
                $app->make(LottoPackageResolver::class),
                $app->make(WalletTransactionService::class)
            );
        });

        $this->app->singleton(ExposureService::class, function ($app) {
            return new ExposureService;
        });

        $this->app->singleton(DrawService::class, function ($app) {
            return new DrawService;
        });

        $this->app->singleton(SettlementService::class, function ($app) {
            return new SettlementService(
                $app->make(WalletTransactionService::class)
            );
        });

        $this->app->singleton(LottoConfigResolver::class, function ($app) {
            return new LottoConfigResolver;
        });

        $this->app->singleton(LottoPackageResolver::class, function ($app) {
            return new LottoPackageResolver;
        });

        $this->app->singleton(MemberMarketPolicyService::class, function ($app) {
            return new MemberMarketPolicyService;
        });

        $this->app->singleton(WalletTransactionService::class, function ($app) {
            return new WalletTransactionService;
        });

        $this->app->singleton(WinningReportService::class, function ($app) {
            return new WinningReportService;
        });

        $this->app->singleton(AutoResultHardeningService::class, function ($app) {
            return new AutoResultHardeningService;
        });

        $this->app->singleton(LotteryRelayRuntime::class, function () {
            return new LotteryRelayRuntime;
        });

        $this->app->singleton(LotteryRelayTypeRegistry::class, function () {
            return new LotteryRelayTypeRegistry;
        });

        $this->app->singleton(LotteryRelayStream::class, function () {
            return new LotteryRelayStream;
        });

        $this->app->singleton(LotteryRelayPublisher::class, function ($app) {
            return new LotteryRelayPublisher(
                $app->make(LotteryRelayRuntime::class),
                $app->make(LotteryRelayTypeRegistry::class),
                $app->make(LotteryRelayStream::class)
            );
        });

        $this->commands([
            BootstrapMemberMarketPoliciesCommand::class,
            BootstrapMissingResultSourcesCommand::class,
            BackfillLottoPayoutCommand::class,
            CleanupBrowserRuntimeArtifactsCommand::class,
            GenerateAutoLottoDrawsCommand::class,
            InsertInternalResultSourceMappingsCommand::class,
            LottoFetchAutoResultsCommand::class,
            LottoAutoResultMetricsCommand::class,
            LottoRelayConsumeStreamCommand::class,
            LottoRelayHealthCommand::class,
            LottoRelayPublishReadyCommand::class,
            MigrateExphuaySourcesToExternalEndpointCommand::class,
            MigrateInternalResultEndpointsCommand::class,
            MigrateLegacyLottoPermissionsCommand::class,
            MigrateRelayResultSourcesCommand::class,
            SyncLottoDrawStatusesCommand::class,
            LottoWinningReportBackfillCommand::class,
            LottoWinningReportReconcileCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views/admin', 'admin');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'lotto');

        $this->registerObservers();

        Event::listen('member.created.after', function ($member): void {
            if (! isset($member->code)) {
                return;
            }

            app(MemberMarketPolicyService::class)->bootstrapForMember((int) $member->code);
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );
    }

    protected function registerObservers(): void
    {
        // Audit create/update/delete for all lotto_* tables.
        LotteryGroupProxy::observe(LottoAuditObserver::class);
        LotteryMarketProxy::observe(LottoAuditObserver::class);
        LottoMarketBetSettingProxy::observe(LottoAuditObserver::class);
        LottoDraw::observe(LottoDrawRealtimeObserver::class);
        LottoDraw::observe(LottoDashboardSummaryObserver::class);
        LottoDraw::observe(LottoDrawAutoResultObserver::class);
        LottoDrawProxy::observe(LottoAuditObserver::class);
        LottoDrawBetSettingProxy::observe(LottoAuditObserver::class);
        LottoNumberExposureProxy::observe(LottoAuditObserver::class);
        LottoNumberExposureProxy::observe(LottoDashboardSummaryObserver::class);
        LottoNumberBlockProxy::observe(LottoAuditObserver::class);
        LottoNavbarProxy::observe(LottoAuditObserver::class);
        LottoNavbarItemProxy::observe(LottoAuditObserver::class);
        LottoTicketProxy::observe(LottoAuditObserver::class);
        LottoTicketProxy::observe(LottoTicketRealtimeObserver::class);
        LottoTicketProxy::observe(LottoDashboardSummaryObserver::class);
        LottoTicketItemProxy::observe(LottoAuditObserver::class);
        LottoTicketItemProxy::observe(LottoDashboardSummaryObserver::class);
        SettlementBatchProxy::observe(LottoAuditObserver::class);
        LottoWinningProxy::observe(LottoAuditObserver::class);
    }
}
