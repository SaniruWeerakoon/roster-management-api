<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Roster;
use App\Models\ShiftType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoRosterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $people = [
            ['code' => 'HA', 'name' => 'HA'],
            ['code' => 'BU', 'name' => 'BU'],
            ['code' => 'PU', 'name' => 'PU'],
            ['code' => 'DN', 'name' => 'DN'],
        ];

        foreach ($people as $p) {
            Person::query()->firstOrCreate(['code' => $p['code']], $p);
        }

        $shifts = [
            ['code' => 'WARD', 'name' => 'Ward', 'category' => 'day', 'weight' => 1.0],
            ['code' => 'OPD', 'name' => 'OPD', 'category' => 'day', 'weight' => 1.0],
            ['code' => 'CLINIC', 'name' => 'Clinic', 'category' => 'day', 'weight' => 1.0],
            ['code' => 'NIGHT', 'name' => 'Night', 'category' => 'night', 'weight' => 2.0],
        ];

        foreach ($shifts as $s) {
            ShiftType::query()->firstOrCreate(['code' => $s['code']], $s);
        }

        $month = now()->startOfMonth()->toDateString(); // YYYY-MM-01
        Roster::query()->firstOrCreate(['month' => $month], ['name' => 'Demo Roster']);
    }
}
