<?php

namespace Database\Seeders;

use App\Models\SectionOne;
use Illuminate\Database\Seeder;

class LandingSectionOneTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $input = [
            'text_main' => 'Manage Hospital Never Before',
            'text_secondary' => 'A Next Level Evolution In Healthcare IT, Web Based EMR, Revenue Cycle Management Solution, Designed To Meet The Opportunities.',
            'img_url' => ('/front-assets/landing-theme/images/banner/section_one.png'),
        ];

        $section = SectionOne::create($input);

        if (file_exists(public_path($input['img_url']))) {
            $section->addMedia(public_path($input['img_url']))
                ->preservingOriginal()
                ->toMediaCollection(SectionOne::SECTION_ONE_PATH, config('app.media_disk'));

            $section->update([
                'img_url' => $section->getFirstMediaUrl(SectionOne::SECTION_ONE_PATH),
            ]);
        }
    }
}
