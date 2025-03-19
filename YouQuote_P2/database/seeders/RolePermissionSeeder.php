<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole  = Role::firstOrCreate(['name' => 'Admin']);
        $auteurRole = Role::firstOrCreate(['name' => 'Auteur']);

        // Liste des permissions
        $permissions = [
            'create quote',
            'edit quote',
            'delete quote',
            'view all quotes',
            'view my quotes',
            'create categories',
            'edit categories',
            'delete categories',
            'view all categories',
            'create tags',
            'edit tags',
            'delete tags',
            'view all tags',
            'like quote',
            'dislike quote',
            'add to favorites',
            'delete from favorites',
            'restore quote',
            'delete definitly quote',
            'view quote deleted',
            'view All quotes deleted',
            'validate quote',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin premissions
        $adminRole->givePermissionTo($permissions);

        // Auteur premissions
        $auteurRole->givePermissionTo([
            'create quote',
            'edit quote',
            'delete quote',
            'view all quotes',
            'view my quotes',
            'view all categories',
            'view all tags',
            'like quote',
            'dislike quote',
            'add to favorites',
            'delete from favorites',
        ]);
    }
}
