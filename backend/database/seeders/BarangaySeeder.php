<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Cebu City barangays with default door-to-door delivery fees (PHP).
     * Tier A (central) 50 · Tier B (mid) 75 · Tier C (outer/mountain) 120
     *
     * @return list<array{name: string, code: string, fee: int}>
     */
    public static function cebuCityBarangays(): array
    {
        return [
            // Tier A — central / downtown
            ['name' => 'Capitol Site', 'code' => 'CAP', 'fee' => 50],
            ['name' => 'Kamputhaw', 'code' => 'KMP', 'fee' => 50],
            ['name' => 'Cogon Ramos', 'code' => 'CGR', 'fee' => 50],
            ['name' => 'Day-as', 'code' => 'DAY', 'fee' => 50],
            ['name' => 'Kalubihan', 'code' => 'KLB', 'fee' => 50],
            ['name' => 'Kamagayan', 'code' => 'KMG', 'fee' => 50],
            ['name' => 'Pari-an', 'code' => 'PAR', 'fee' => 50],
            ['name' => 'San Roque', 'code' => 'SRQ', 'fee' => 50],
            ['name' => 'Santo Niño', 'code' => 'STN', 'fee' => 50],
            ['name' => 'Tinago', 'code' => 'TIN', 'fee' => 50],
            ['name' => 'Zapatera', 'code' => 'ZAP', 'fee' => 50],
            ['name' => 'T. Padilla', 'code' => 'TPD', 'fee' => 50],
            ['name' => 'Carreta', 'code' => 'CAR', 'fee' => 50],
            ['name' => 'Hipodromo', 'code' => 'HIP', 'fee' => 50],
            ['name' => 'Ermita', 'code' => 'ERM', 'fee' => 50],
            ['name' => 'Lorega-San Miguel', 'code' => 'LRG', 'fee' => 50],
            ['name' => 'Tejero', 'code' => 'TEJ', 'fee' => 50],
            ['name' => 'Pasil', 'code' => 'PSL', 'fee' => 50],
            ['name' => 'Suba', 'code' => 'SUB', 'fee' => 50],
            ['name' => 'Sawang Calero', 'code' => 'SWC', 'fee' => 50],
            ['name' => 'Duljo Fatima', 'code' => 'DLJ', 'fee' => 50],
            ['name' => 'Pahina Central', 'code' => 'PHC', 'fee' => 50],
            ['name' => 'Pahina San Nicolas', 'code' => 'PHS', 'fee' => 50],
            ['name' => 'San Nicolas Proper', 'code' => 'SNP', 'fee' => 50],
            ['name' => 'Santa Cruz', 'code' => 'SCR', 'fee' => 50],
            ['name' => 'San Antonio', 'code' => 'SAN', 'fee' => 50],

            // Tier B — mid city
            ['name' => 'Apas', 'code' => 'APA', 'fee' => 75],
            ['name' => 'Banilad', 'code' => 'BNL', 'fee' => 75],
            ['name' => 'Basak Pardo', 'code' => 'BSP', 'fee' => 75],
            ['name' => 'Basak San Nicolas', 'code' => 'BSN', 'fee' => 75],
            ['name' => 'Calamba', 'code' => 'CLB', 'fee' => 75],
            ['name' => 'Cogon Pardo', 'code' => 'CGP', 'fee' => 75],
            ['name' => 'Guadalupe', 'code' => 'GUA', 'fee' => 75],
            ['name' => 'Inayawan', 'code' => 'INY', 'fee' => 75],
            ['name' => 'Kalunasan', 'code' => 'KLN', 'fee' => 75],
            ['name' => 'Kasambagan', 'code' => 'KSB', 'fee' => 75],
            ['name' => 'Kinasang-an Pardo', 'code' => 'KIN', 'fee' => 75],
            ['name' => 'Labangon', 'code' => 'LAB', 'fee' => 75],
            ['name' => 'Lahug', 'code' => 'LAH', 'fee' => 75],
            ['name' => 'Luz', 'code' => 'LUZ', 'fee' => 75],
            ['name' => 'Mabini', 'code' => 'MAB', 'fee' => 75],
            ['name' => 'Mabolo', 'code' => 'MBO', 'fee' => 75],
            ['name' => 'Mambaling', 'code' => 'MAM', 'fee' => 75],
            ['name' => 'Punta Princesa', 'code' => 'PUN', 'fee' => 75],
            ['name' => 'Quiot Pardo', 'code' => 'QIO', 'fee' => 75],
            ['name' => 'Sambag I', 'code' => 'SB1', 'fee' => 75],
            ['name' => 'Sambag II', 'code' => 'SB2', 'fee' => 75],
            ['name' => 'San Jose', 'code' => 'SJO', 'fee' => 75],
            ['name' => 'Tisa', 'code' => 'TIS', 'fee' => 75],
            ['name' => 'Bulacao', 'code' => 'BUL', 'fee' => 75],
            ['name' => 'Bacayan', 'code' => 'BAC', 'fee' => 75],
            ['name' => 'Talamban', 'code' => 'TAL', 'fee' => 75],
            ['name' => 'Poblacion Pardo', 'code' => 'POB', 'fee' => 75],

            // Tier C — outer / mountain barangays
            ['name' => 'Adlaon', 'code' => 'ADL', 'fee' => 120],
            ['name' => 'Agsungot', 'code' => 'AGS', 'fee' => 120],
            ['name' => 'Babag', 'code' => 'BAB', 'fee' => 120],
            ['name' => 'Binaliw', 'code' => 'BIN', 'fee' => 120],
            ['name' => 'Bonbon', 'code' => 'BON', 'fee' => 120],
            ['name' => 'Budlaan', 'code' => 'BUD', 'fee' => 120],
            ['name' => 'Buhisan', 'code' => 'BUH', 'fee' => 120],
            ['name' => 'Buot', 'code' => 'BUO', 'fee' => 120],
            ['name' => 'Busay', 'code' => 'BUS', 'fee' => 120],
            ['name' => 'Cambinocot', 'code' => 'CAM', 'fee' => 120],
            ['name' => 'Guba', 'code' => 'GUB', 'fee' => 120],
            ['name' => 'Lusaran', 'code' => 'LUS', 'fee' => 120],
            ['name' => 'Malubog', 'code' => 'MLB', 'fee' => 120],
            ['name' => 'Pamutan', 'code' => 'PAM', 'fee' => 120],
            ['name' => 'Paril', 'code' => 'PRL', 'fee' => 120],
            ['name' => 'Pit-os', 'code' => 'PIT', 'fee' => 120],
            ['name' => 'Pulangbato', 'code' => 'PLB', 'fee' => 120],
            ['name' => 'Pung-ol Sibugay', 'code' => 'POG', 'fee' => 120],
            ['name' => 'Sapangdaku', 'code' => 'SAP', 'fee' => 120],
            ['name' => 'Sinsin', 'code' => 'SIN', 'fee' => 120],
            ['name' => 'Sirao', 'code' => 'SIR', 'fee' => 120],
            ['name' => 'Sudlon I', 'code' => 'SD1', 'fee' => 120],
            ['name' => 'Sudlon II', 'code' => 'SD2', 'fee' => 120],
            ['name' => 'Tabunan', 'code' => 'TAB', 'fee' => 120],
            ['name' => 'Tagba-o', 'code' => 'TAG', 'fee' => 120],
            ['name' => 'Taptap', 'code' => 'TAP', 'fee' => 120],
            ['name' => 'To-ong', 'code' => 'TOO', 'fee' => 120],
        ];
    }

    public function run(): void
    {
        $rows = self::cebuCityBarangays();
        $activeCodes = [];

        foreach ($rows as $row) {
            $activeCodes[] = $row['code'];

            $barangay = Barangay::query()->updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );

            BarangayDeliveryFee::query()->updateOrCreate(
                ['barangay_id' => $barangay->id],
                ['fee' => $row['fee'], 'is_active' => true]
            );
        }

        // Deactivate leftover sample barangays from earlier seeds (not Cebu City).
        Barangay::query()
            ->whereNotIn('code', $activeCodes)
            ->update(['is_active' => false]);
    }
}
