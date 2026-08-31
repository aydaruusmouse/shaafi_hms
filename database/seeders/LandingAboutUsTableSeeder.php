<?php

namespace Database\Seeders;

use App\Models\LandingAboutUs;
use Illuminate\Database\Seeder;

class LandingAboutUsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $input = [
            'text_main' => 'How It Work',
            'card_img_one' => ('/front-assets/landing-theme/images/banner/about_us.png'),
            'card_img_two' => ('/front-assets/landing-theme/images/banner/check-circle-regular.svg'),
            'card_img_three' => ('/front-assets/landing-theme/images/banner/credit-card-solid.svg'),
            'main_img_one' => ('/front-assets/landing-theme/images/about/12.png'),
            'main_img_two' => ('/front-assets/landing-theme/images/about/14.png'),
            'card_one_text' => 'Research',
            'card_two_text' => 'HMS Customization',
            'card_three_text' => 'Cost Effective',
            'card_one_text_secondary' => 'HMS specialises in developing innovative, efficient and smart healthcare solutions.',
            'card_two_text_secondary' => 'We offer complete HMS customization solutions. We are staffed by eLearning experts and we know how to get the most from HMS.',
            'card_three_text_secondary' => 'HMS not only saves time in the hospital but also is cost-effective in decreasing the number of people working on the Paper work.',
        ];

        $landingAboutUs = LandingAboutUs::create($input);

        $imageMappings = [
            'card_img_one' => LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_ONE,
            'card_img_two' => LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_TWO,
            'card_img_three' => LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_THREE,
            'main_img_one' => LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_ONE,
            'main_img_two' => LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_TWO,
        ];

        foreach ($imageMappings as $field => $collection) {
            if (file_exists(public_path($input[$field]))) {
                $landingAboutUs->addMedia(public_path($input[$field]))
                    ->preservingOriginal()
                    ->toMediaCollection($collection, config('app.media_disk'));
            }
        }

        $landingAboutUs->update([
            'card_img_one' => $landingAboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_ONE),
            'card_img_two' => $landingAboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_TWO),
            'card_img_three' => $landingAboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_CARD_IMG_THREE),
            'main_img_one' => $landingAboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_ONE),
            'main_img_two' => $landingAboutUs->getFirstMediaUrl(LandingAboutUs::LANDING_ABOUT_US_MAIN_IMG_TWO),
        ]);
    }
}
