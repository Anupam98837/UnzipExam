<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@hallienz.com');
        $password = env('ADMIN_PASSWORD', 'Admin@123');
        $name     = env('ADMIN_NAME', 'Admin');

        $user = User::firstOrNew(['email' => $email]);

        // Basics
        if (Schema::hasColumn('users', 'name'))     $user->name = $name;
        if (Schema::hasColumn('users', 'password')) $user->password = Hash::make($password);

        // Required columns in your DB (as per your errors)
        if (Schema::hasColumn('users', 'uuid') && empty($user->uuid)) {
            $user->uuid = (string) Str::uuid();
        }

        if (Schema::hasColumn('users', 'slug') && empty($user->slug)) {
            $base = Str::slug($user->name ?? 'admin');
            $user->slug = $base . '-' . Str::lower(Str::random(6));
        }

        // Optional / common columns (set only if present)
        if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        if (Schema::hasColumn('users', 'remember_token') && empty($user->remember_token)) {
            $user->remember_token = Str::random(10);
        }

        if (Schema::hasColumn('users', 'role') && empty($user->role)) $user->role = 'admin';
        if (Schema::hasColumn('users', 'user_type') && empty($user->user_type)) $user->user_type = 'admin';
        if (Schema::hasColumn('users', 'type') && empty($user->type)) $user->type = 'admin';

        if (Schema::hasColumn('users', 'status') && empty($user->status)) $user->status = 'active';
        if (Schema::hasColumn('users', 'is_admin')) $user->is_admin = 1;
        if (Schema::hasColumn('users', 'is_super_admin')) $user->is_super_admin = 1;

        $user->save();
    }
}
