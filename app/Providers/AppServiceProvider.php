<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider {

    public function register()
    {
        //
    }

    public function boot() {
        Carbon::setLocale(config('app.locale'));
        Schema::defaultStringLength(191); 

        date_default_timezone_set(config('app.timezone'));

        Relation::morphMap([
            'module'    => \App\Models\PermissionModule::class,
            'submodule' => \App\Models\PermissionSubmodule::class,
            'operation' => \App\Models\PermissionOperation::class,
        ]);
    }
}
