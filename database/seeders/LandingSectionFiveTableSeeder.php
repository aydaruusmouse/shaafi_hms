<?php

namespace Database\Seeders;

use App\Models\SectionFive;
use Illuminate\Database\Seeder;

class LandingSectionFiveTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $input = [
            'main_img_url' => ('/front-assets/landing-theme/images/about/07.png'),
            'card_img_url_one' => ('/front-assets/landing-theme/images/banner/card_img_url_one.png'),
            'card_img_url_two' => ('/front-assets/landing-theme/images/banner/card_img_url_two.png'),
            'card_img_url_three' => ('/front-assets/landing-theme/images/banner/card_img_url_three.png'),
            'card_img_url_four' => ('/front-assets/landing-theme/images/banner/card_imf_url_four.png'),
            'card_one_number' => 234,
            'card_two_number' => 455,
            'card_three_number' => 365,
            'card_four_number' => 528,
            'card_one_text' => 'Services',
            'card_two_text' => 'Team Members',
            'card_three_text' => 'Happy Patients',
            'card_four_text' => 'Tonic Research',
        ];
        $section = SectionFive::create($input);

        $imageMappings = [
            'main_img_url' => SectionFive::SECTION_FIVE_MAIN_IMAGE_PATH,
            'card_img_url_one' => SectionFive::SECTION_FIVE_CARD_ONE_PATH,
            'card_img_url_two' => SectionFive::SECTION_FIVE_CARD_TWO_PATH,
            'card_img_url_three' => SectionFive::SECTION_FIVE_CARD_THREE_PATH,
            'card_img_url_four' => SectionFive::SECTION_FIVE_CARD_FOUR_PATH,
        ];

        foreach ($imageMappings as $field => $collection) {
            if (file_exists(public_path($input[$field]))) {
                $section->addMedia(public_path($input[$field]))
                    ->preservingOriginal()
                    ->toMediaCollection($collection, config('app.media_disk'));
            }
        }

        $section->update([
            'main_img_url' => $section->getFirstMediaUrl(SectionFive::SECTION_FIVE_MAIN_IMAGE_PATH),
            'card_img_url_one' => $section->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_ONE_PATH),
            'card_img_url_two' => $section->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_TWO_PATH),
            'card_img_url_three' => $section->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_THREE_PATH),
            'card_img_url_four' => $section->getFirstMediaUrl(SectionFive::SECTION_FIVE_CARD_FOUR_PATH),
        ]);
    }
}
