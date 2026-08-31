<?php

namespace Database\Seeders;

use App\Models\SectionTwo;
use Illuminate\Database\Seeder;

class LandingSectionTwoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $input = [
            'text_main' => 'Protect Your Health',
            'text_secondary' => 'Our medical clinic provides quality care for the entire family while maintaining a personable atmosphere best services.',
            'card_one_image' => ('/front-assets/landing-theme/images/banner/appointment_schedule.png'),
            'card_one_text' => 'Schedule Appointment',
            'card_one_text_secondary' => 'Makes it Easy for patients to Book Appointment online from anywhere &amp; anytime.',
            'card_two_image' => ('/front-assets/landing-theme/images/banner/ipd_manage.png'),
            'card_two_text' => 'OPD Management',
            'card_two_text_secondary' => 'Easily Manage Appointments with one.',
            'card_third_image' => ('/front-assets/landing-theme/images/banner/opd_manage.png'),
            'card_third_text' => 'IPD Management',
            'card_third_text_secondary' => 'This module of hospital management system is designed to manage all Inpatient department',
        ];

        $section = SectionTwo::create($input);

        if (file_exists(public_path($input['card_one_image']))) {
            $section->addMedia(public_path($input['card_one_image']))
                ->preservingOriginal()
                ->toMediaCollection(SectionTwo::SECTION_TWO_CARD_ONE_PATH, config('app.media_disk'));
        }

        if (file_exists(public_path($input['card_two_image']))) {
            $section->addMedia(public_path($input['card_two_image']))
                ->preservingOriginal()
                ->toMediaCollection(SectionTwo::SECTION_TWO_CARD_TWO_PATH, config('app.media_disk'));
        }

        if (file_exists(public_path($input['card_third_image']))) {
            $section->addMedia(public_path($input['card_third_image']))
                ->preservingOriginal()
                ->toMediaCollection(SectionTwo::SECTION_TWO_CARD_THIRD_PATH, config('app.media_disk'));
        }

        $section->update([
            'card_one_image' => $section->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_ONE_PATH),
            'card_two_image' => $section->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_TWO_PATH),
            'card_third_image' => $section->getFirstMediaUrl(SectionTwo::SECTION_TWO_CARD_THIRD_PATH),
        ]);
    }
}
