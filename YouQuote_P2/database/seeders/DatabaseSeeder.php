<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Vérifier et créer les rôles s'ils n'existent pas
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
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
        ];

        // Création des permissions sans duplication
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assignation des permissions aux rôles
        $adminRole->givePermissionTo($permissions);
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
