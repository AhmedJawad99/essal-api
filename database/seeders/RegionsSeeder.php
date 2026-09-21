<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            // باقي المحافظات
            ['name' => 'بغداد', 'default_delivery_cost' => 5000],
            ['name' => 'البصرة', 'default_delivery_cost' => 5000],
            ['name' => 'نينوى', 'default_delivery_cost' => 5000],
            ['name' => 'أربيل', 'default_delivery_cost' => 5000],
            ['name' => 'النجف', 'default_delivery_cost' => 5000],
            ['name' => 'كربلاء', 'default_delivery_cost' => 5000],
            ['name' => 'كركوك', 'default_delivery_cost' => 5000],
            ['name' => 'الأنبار', 'default_delivery_cost' => 5000],
            ['name' => 'ديالى', 'default_delivery_cost' => 5000],
            ['name' => 'صلاح الدين', 'default_delivery_cost' => 5000],
            ['name' => 'ميسان', 'default_delivery_cost' => 5000],
            ['name' => 'واسط', 'default_delivery_cost' => 5000],
            ['name' => 'بابل', 'default_delivery_cost' => 5000],
            ['name' => 'السليمانية', 'default_delivery_cost' => 5000],
            ['name' => 'دهوك', 'default_delivery_cost' => 5000],
            ['name' => 'المثنى', 'default_delivery_cost' => 5000],
            ['name' => 'الديوانية', 'default_delivery_cost' => 5000],
            ['name' => 'حلبجة', 'default_delivery_cost' => 5000],
            ['name' => 'ذي قار', 'default_delivery_cost' => 3000],
        ];

        foreach ($regions as $region) {
            Region::create(['name' => $region['name'], 'default_delivery_cost' => $region['default_delivery_cost']]);
        }
    }
}
