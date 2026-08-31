<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateModuleParentSeeder extends Seeder
{
    public function run(): void
    {
        $updates = [
            [
                'parent_name' => 'User',
                'child_pattern' => ['Pharmacists', 'Lab Technicians', 'Receptionists', 'Nurses', 'Accountants'],
            ],
            [
                'parent_name' => 'Appointments',
                'child_pattern' => ['Appointment Transactions', 'Patient Queue', 'Vital Signs'],
            ],
            [
                'parent_name' => 'Bills',
                'child_pattern' => ['Accounts', 'Invoices', 'Employee Payrolls', 'Payments', 'Payment Reports', 'Advance Payments'],
            ],
            [
                'parent_name' => 'Beds',
                'child_pattern' => ['Bed Types', 'Bed Assigns', 'Bed Status'],
            ],
            [
                'parent_name' => 'Blood Banks',
                'child_pattern' => ['Blood Donations', 'Blood Issues', 'Blood Donors'],
            ],
            [
                'parent_name' => 'Documents',
                'child_pattern' => ['Document Types'],
            ],
            [
                'parent_name' => 'Doctors',
                'child_pattern' => ['Schedules', 'Doctor Departments', 'Doctor Holidays', 'Breaks'],
            ],
            [
                'parent_name' => 'Prescriptions',
                'child_pattern' => ['Pharmacist'],
            ],
            [
                'parent_name' => 'Finance',
                'child_pattern' => ['Expense', 'Income'],
            ],
            [
                'parent_name' => 'Patients',
                'child_pattern' => ['Patient Admissions', 'Cases', 'Case Handlers', 'Patient Smart Card Templates', 'Generate Patient Smart Cards'],
            ],
            [
                'parent_name' => 'Front Office',
                'child_pattern' => ['Visitors', 'Call Logs', 'Postal Receive', 'Postal Dispatch'],
            ],
            [
                'parent_name' => 'Front CMS',
                'child_pattern' => ['Notice Boards', 'Testimonial', 'CMS', 'Front CMS Services'],
            ],
            [
                'parent_name' => 'Hospital Charges',
                'child_pattern' => ['Charges', 'Doctor OPD Charges', 'Charge Categories'],
            ],
            [
                'parent_name' => 'Inventory',
                'child_pattern' => ['Items Categories', 'Issued Items', 'Item Stocks', 'Items'],
            ],
            [
                'parent_name' => 'Live Consultations',
                'child_pattern' => ['Live Meetings'],
            ],
            [
                'parent_name' => 'Medicines',
                'child_pattern' => ['Medicine Categories', 'Medicine Brands', 'Used Medicine', 'Medicine Bills'],
            ],
            [
                'parent_name' => 'Pathology',
                'child_pattern' => ['Pathology Categories', 'Pathology Tests', 'Pathology Units', 'Pathology Parameters', 'Doctor Suggested Tests', 'Lab Report Payments'],
            ],
            [
                'parent_name' => 'Reports',
                'child_pattern' => ['Birth Reports', 'Operation Reports', 'Investigation Reports', 'Death Reports'],
            ],
            [
                'parent_name' => 'Radiology',
                'child_pattern' => ['Radiology Tests', 'Radiology Categories'],
            ],
            [
                'parent_name' => 'Services',
                'child_pattern' => ['Ambulances Calls', 'Ambulances', 'Insurances', 'Packages'],
            ],
            [
                'parent_name' => 'SMS',
                'child_pattern' => ['Mail'],
            ],
            [
                'parent_name' => 'Settings',
                'child_pattern' => ['Sidebar Settings', 'General Settings', 'Hospital Schedule'],
            ],
            [
                'parent_name' => 'Vaccinations',
                'child_pattern' => ['Vaccinated Patients'],
            ],
            [
                'parent_name' => 'Diagnosis',
                'child_pattern' => ['Diagnosis Categories', 'Diagnosis Tests'],
            ],
        ];

        foreach ($updates as $update) {
            $this->updateModuleParent($update['parent_name'], $update['child_pattern']);
        }
    }

    private function updateModuleParent(string $parentName, array $childPatterns): void
    {
        try {
            $parent = DB::table('modules')->where('name', $parentName)->first();

            if (! $parent) {
                $parentId = DB::table('modules')->insertGetId([
                    'name' => $parentName,
                    'parent_id' => 99999,
                    'is_active' => 1,
                    'is_hidden' => 0,
                    'route' => $this->generateRoute($parentName),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $parentId = $parent->id;
            }

            foreach ($childPatterns as $childPattern) {
                if ($childPattern === $parentName) {
                    continue;
                }

                $updated = DB::table('modules')
                    ->where('name', $childPattern)
                    ->update([
                        'parent_id' => $parentId,
                        'updated_at' => now(),
                    ]);

                if (! $updated) {
                    DB::table('modules')->insert([
                        'name' => $childPattern,
                        'parent_id' => $parentId,
                        'is_active' => 1,
                        'is_hidden' => 0,
                        'route' => $this->generateRoute($childPattern),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Module seeder error for {$parentName}: ".$e->getMessage());
        }
    }

    private function generateRoute(string $moduleName): string
    {
        $key = strtolower(str_replace([' ', '/'], ['_', '_'], $moduleName));

        return "admin.{$key}.index";
    }
}
