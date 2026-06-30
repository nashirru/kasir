<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat outlet default
        $outlet = Outlet::create([
            'nama' => 'Pusat',
            'alamat' => 'Jl. Contoh No. 1',
            'telepon' => '021-12345678',
            'is_active' => true,
        ]);

        // Buat gudang utama untuk outlet
        $outlet->warehouses()->create([
            'nama' => 'Gudang Utama',
            'tipe' => 'utama',
        ]);

        // Admin pusat (outlet_id = null — bisa lihat semua cabang)
        $admin = User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin@kasir.test',
            'password' => bcrypt('password'),
            'outlet_id' => null,
        ]);
        $admin->assignRole('admin');

        // Manajer
        $manajer = User::create([
            'name' => 'Manajer Outlet',
            'email' => 'manajer@kasir.test',
            'password' => bcrypt('password'),
            'outlet_id' => $outlet->id,
        ]);
        $manajer->assignRole('manajer');

        // Kasir
        $kasir = User::create([
            'name' => 'Kasir Outlet',
            'email' => 'kasir@kasir.test',
            'password' => bcrypt('password'),
            'outlet_id' => $outlet->id,
        ]);
        $kasir->assignRole('kasir');

        // Staff Gudang
        $staff = User::create([
            'name' => 'Staff Gudang',
            'email' => 'staff@kasir.test',
            'password' => bcrypt('password'),
            'outlet_id' => $outlet->id,
        ]);
        $staff->assignRole('staff_gudang');
    }
}
