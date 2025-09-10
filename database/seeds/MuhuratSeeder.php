<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Muhurat;
use Illuminate\Support\Facades\File;

class MuhuratSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = public_path('muhurat-json/muhurats.json');
        if (!File::exists($path)) return;

        $json = File::get($path);
        $data = json_decode($json, true);

        foreach ($data as $item) {
            if ($item['type'] === 'special-muhurat') continue;

            $timestamp = strtotime($item['titleLink']);
            if (!$timestamp) continue;

            // Parse details
            preg_match('/Muhurat:([^;]+);/', $item['details'] ?? '', $muhurat);
            preg_match('/Nakshatra:([^;]+);/', $item['details'] ?? '', $nakshatra);
            preg_match('/Tithi:([^;]+)/', $item['details'] ?? '', $tithi);

            Muhurat::create([
                'date' => date('Y-m-d', $timestamp),
                'type' => $item['type'],
                'muhurat' => trim($muhurat[1] ?? ''),
                'nakshatra' => trim($nakshatra[1] ?? ''),
                'tithi' => trim($tithi[1] ?? ''),
            ]);
        }
    }
}
