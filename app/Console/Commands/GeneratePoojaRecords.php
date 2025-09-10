<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\PoojaForecast;
use Carbon\Carbon;

class GeneratePoojaRecords extends Command
{
    protected $signature = 'pooja:generate-forecast';
    protected $description = 'Generate pooja forecast records for the next 30 days';

    public function handle()
    {
        $services = Service::with('category')->get();
        $today = Carbon::now()->startOfDay();
        $endDay = Carbon::now()->addDays(30);

        foreach ($services as $service) {
            $categoryName = $service->category->name ?? 'Unknown';  // Fix: define once

            if ($service->pooja_type == 0) {
                // Weekly based
                $weekDays = json_decode($service->week_days ?? '[]', true);

                for ($date = $today->copy(); $date->lte($endDay); $date->addDay()) {
                    if (in_array(strtolower($date->format('l')), $weekDays)) {
                        $this->createOrUpdateForecast($service, $date, 'weekly', $categoryName);
                    }
                }
            } elseif ($service->pooja_type == 1) {
                // Special pooja
                $schedules = json_decode($service->schedule ?? '[]', true);
                foreach ($schedules as $s) {
                    if (!empty($s['schedule'])) {
                        $scheduleDate = Carbon::parse($s['schedule']);
                        if ($scheduleDate->between($today, $endDay)) {
                            $this->createOrUpdateForecast($service, $scheduleDate, 'special', $categoryName);
                        }
                    }
                }
            }
        }

        $this->info('Pooja forecast generated successfully.');
    }

    protected function createOrUpdateForecast($service, Carbon $date, $type, $categoryName)
    {
        // Insert multiple entries if needed (no deduplication)
        PoojaForecast::create([
            'service_id' => $service->id,
            'booking_date' => $date->format('Y-m-d'),
            'type' => $type,
            'category' => $categoryName,
            'total_orders' => 0,
            'total_users' => 0,
            'earnings' => 0
        ]);
    }

}
