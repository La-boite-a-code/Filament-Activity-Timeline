<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use LaBoiteACode\FilamentActivityTimeline\FilamentActivityTimelineServiceProvider;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\TestPanelProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Outside an HTTP request the ShareErrorsFromSession middleware never
        // runs, so the "errors" bag Livewire and Filament expect is missing.
        View::share('errors', new ViewErrorBag);

        // Filament injects render behavior that expects a current panel, so a
        // test panel is registered and activated for component rendering.
        Filament::setCurrentPanel(Filament::getPanel('test'));

        // In this headless test context the shared instance Livewire registers
        // for its DataStore is dropped, which would make store() lose data
        // between set and get. Rebinding it as a singleton restores it.
        $this->app->singleton(DataStore::class);

        $this->setUpDatabase();
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            NotificationsServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            ActivitylogServiceProvider::class,
            TestPanelProvider::class,
            FilamentActivityTimelineServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('activitylog.activity_model', Activity::class);
        $app['config']->set('activitylog.table_name', 'activity_log');
    }

    protected function setUpDatabase(): void
    {
        $this->migrateActivityLogTable();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('internal_token')->nullable();
            $table->timestamps();
        });
    }

    protected function migrateActivityLogTable(): void
    {
        // activitylog v5 ships a single anonymous-class migration, while v4
        // splits the event and batch_uuid columns into named-class stubs. A
        // named class can only be declared once per process, so it is
        // instantiated directly when a previous test already included it.
        $stubs = [
            'create_activity_log_table.php.stub' => 'CreateActivityLogTable',
            'add_event_column_to_activity_log_table.php.stub' => 'AddEventColumnToActivityLogTable',
            'add_batch_uuid_column_to_activity_log_table.php.stub' => 'AddBatchUuidColumnToActivityLogTable',
        ];

        foreach ($stubs as $stub => $legacyClass) {
            $path = __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/'.$stub;

            if (! file_exists($path)) {
                continue;
            }

            $migration = class_exists($legacyClass) ? new $legacyClass : include $path;

            if (! $migration instanceof Migration) {
                $migration = new $legacyClass;
            }

            $migration->up();
        }
    }
}
