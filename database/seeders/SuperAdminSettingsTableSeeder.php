<?php

namespace Database\Seeders;

use App\Models\SuperAdminSetting;
use Illuminate\Database\Seeder;

class SuperAdminSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $files = [
            'app_logo' => public_path('web/img/hms-saas-logo.png'),
            'favicon' => public_path('web/img/hms-saas-favicon.ico'),
        ];

        foreach ($files as $key => $filePath) {
            if (file_exists($filePath)) {
                $setting = SuperAdminSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => '']
                );

                $setting->addMedia($filePath)->preservingOriginal()->toMediaCollection('super_admin_settings', config('app.media_disk'));

                $setting->update([
                    'value' => $setting->getFirstMediaUrl('super_admin_settings'),
                ]);
            }
        }

        SuperAdminSetting::create(['key' => 'app_name', 'value' => 'InfyHMS']);
    }
}
