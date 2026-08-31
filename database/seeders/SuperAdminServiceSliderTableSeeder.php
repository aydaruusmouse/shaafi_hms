<?php

namespace Database\Seeders;

use App\Models\ServiceSlider;
use Illuminate\Database\Seeder;

class SuperAdminServiceSliderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inputs = [
            [
                'image' => '/front-assets/landing-theme/images/banner/treatment.png',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/diagnostics.png',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/emergency.png',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/qualified_doctors.png',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/anasthesia.jpeg',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/injection.png',
            ],
            [
                'image' => '/front-assets/landing-theme/images/banner/slider_img_seven.png',
            ],
        ];

        foreach ($inputs as $input) {
            $imagePath = public_path($input['image']);

            if (file_exists($imagePath)) {
                $serviceSlider = ServiceSlider::create();

                $serviceSlider->addMedia($imagePath)
                    ->preservingOriginal()
                    ->toMediaCollection(ServiceSlider::SERVICE_SLIDER, config('app.media_disk'));
            }
        }
    }
}
