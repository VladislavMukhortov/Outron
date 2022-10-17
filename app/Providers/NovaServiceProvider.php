<?php

namespace App\Providers;

use App\Nova\Audit;
use App\Nova\City;
use App\Nova\Contact;
use App\Nova\Country;
use App\Nova\Dashboards\Main;
use App\Nova\Hotel;
use App\Nova\Lake;
use App\Nova\Region;
use App\Nova\Booking;
use App\Nova\Room;
use App\Nova\Tag;
use App\Nova\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard(Main::class)->icon('chart-bar'),
                MenuItem::resource(User::class),
                MenuItem::resource(Booking::class),
                MenuSection::make('Объекты', [
                    MenuItem::resource(Hotel::class),
                    MenuItem::resource(Room::class),
                    MenuItem::resource(Contact::class),
                    MenuItem::resource(Lake::class),
                    MenuItem::resource(Tag::class),
                ])->icon('home')->collapsable(),
                MenuSection::make('Локация', [
                    MenuItem::resource(Country::class),
                    MenuItem::resource(Region::class),
                    MenuItem::resource(City::class),
                ])->icon('globe')->collapsable(),
                MenuItem::resource(Audit::class),
            ];
        });
    }


    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     */
    protected function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     */
    public function tools(): array
    {
        return [];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
