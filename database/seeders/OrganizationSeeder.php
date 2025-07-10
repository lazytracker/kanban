<?php
// database/seeders/OrganizationSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run()
    {
        Organization::create([
            'name' => 'ТехКомпания',
            'description' => 'Разработка программного обеспечения'
        ]);

        Organization::create([
            'name' => 'Банк Альфа',
            'description' => 'Финансовые услуги'
        ]);

        Organization::create([
            'name' => 'Стартап Инновации',
            'description' => 'Инновационные решения'
        ]);

        Organization::create([
            'name' => 'Консалтинг Групп',
            'description' => 'Консультационные услуги'
        ]);
    }}