<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RodoviaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('rodovias')->insert([
            ['nome' => '010'],
            ['nome' => '020'],
            ['nome' => '030'],
            ['nome' => '040'],
            ['nome' => '050'],
            ['nome' => '060'],
            ['nome' => '070'],
            ['nome' => '080'],
            ['nome' => '251'],
            ['nome' => '450'],
            ['nome' => '479'],
        ]);
    }
}
