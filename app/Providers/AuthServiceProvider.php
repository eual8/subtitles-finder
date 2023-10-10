<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Fragment;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use App\Policies\FragmentPolicy;
use App\Policies\PlaylistPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\VideoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Fragment::class => FragmentPolicy::class,
        Video::class => VideoPolicy::class,
        Playlist::class => PlaylistPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
