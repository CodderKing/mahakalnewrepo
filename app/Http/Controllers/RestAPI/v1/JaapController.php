<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Jaap;
use App\Models\JaapCount;
use App\Models\RamLekhan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class JaapController extends Controller
{
    public function getAllJaap()
    {
        
        $jaap = Jaap::where('status', 1)
            ->select('id', 'name', 'created_at', 'updated_at')
            ->get();

        if ($jaap->isNotEmpty()) {
            $data = $jaap->map(function ($item) {
                
                $translations = $item->translations()->pluck('value', 'key')->toArray();

                return [
                    'id' => $item->id,
                    'en_name' => $item->name,
                    'hi_name' => $translations['name'] ?? null,
                ];
            });

            $filteredData = $data->filter(function ($item) {
                return !empty($item['en_name']);
            })->values();

            if ($filteredData->isEmpty()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No jaap found.',
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $filteredData,
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => 'No jaap available.',
        ]);
    }
    
    

    public function getMantraByJaap($id)
    {
        
        $jaap = Jaap::where('status', 1)
            ->where('id', $id)
            ->select('id', 'mantra', 'image', 'created_at', 'updated_at')
            ->get();

        if ($jaap->isNotEmpty()) {
            $data = $jaap->map(function ($item) {
                
                $translations = $item->translations()->pluck('value', 'key')->toArray();

                return [
                    'id' => $item->id,
                    'en_mantra' => $item->mantra,
                    'hi_mantra' => $translations['mantra'] ?? null,
                    'image' => $item->image,
                ];
            });

            $filteredData = $data->filter(function ($item) {
                return !empty($item['en_mantra']);
            })->values();

            if ($filteredData->isEmpty()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No mantra found.',
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $filteredData,
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => 'No mantra available.',
        ]);
    }

    public function jaapCount(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || empty($user->id)) {
            return response()->json(['message' => 'Unauthorized']);
        }

        $type = $request->input('type');
        $name = $request->input('name');
        $location = $request->input('location');
        $count = $request->input('count');
        $duration = $request->input('duration');
        $date = $request->input('date');
        $time = $request->input('time');

        if (empty($type) || empty($name) || empty($location) || empty($count) || empty($duration) || empty($date) || empty($time)) {
            return response()->json(['status' => 400, 'message' => 'Invalid request parameters'], 400);
        }

        $item = new JaapCount();
        $item->user_id = $user->id; 
        $item->type = $type;
        $item->name = $name;
        $item->location = $location;
        $item->count = $count;
        $item->duration = $duration;
        $item->date = $date;
        $item->time = $time;
        $item->save();

        return response()->json(['status' => 200, 'message' => 'created successfully']);
    }

    public function getJaapCount(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || empty($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $jaapData = JaapCount::where('user_id', $user->id)->get();

        if ($jaapData->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'No data found'], 404);
        }

        $totalCount = $jaapData->sum('count');

        return response()->json([
            'status' => 200,
            'data' => $jaapData,
            'total_count' => $totalCount 
        ], 200);
    }

    public function RamLekhan(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || empty($user->id)) {
            return response()->json(['message' => 'Unauthorized']);
        }

        $type = $request->input('type');
        $name = $request->input('name');
        $location = $request->input('location');
        $count = $request->input('count');
        $duration = $request->input('duration');
        $date = $request->input('date');
        $time = $request->input('time');

        if (empty($type) || empty($name) || empty($location) || empty($count) || empty($duration) || empty($date) || empty($time)) {
            return response()->json(['status' => 400, 'message' => 'Invalid request parameters'], 400);
        }

        $item = new RamLekhan();
        $item->user_id = $user->id; 
        $item->type = $type;
        $item->name = $name;
        $item->location = $location;
        $item->count = $count;
        $item->duration = $duration;
        $item->date = $date;
        $item->time = $time;
        $item->save();

        return response()->json(['status' => 200, 'message' => 'created successfully']);
    }

    public function getRamLekhan(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || empty($user->id)) {
            return response()->json(['message' => 'Unauthorized']);
        }

        $Data = RamLekhan::where('user_id', $user->id)->get();

        if ($Data->isEmpty()) {
            return response()->json(['status' => 400, 'message' => 'No data found'], 400);
        }

        $totalCount = $Data->sum('count');

        return response()->json([
            'status' => 200,
            'data' => $Data,
            'total_count' => $totalCount 
        ], 200);
    }

    public function deleteRamLekhan(Request $request, $id): JsonResponse
    {

        $item = RamLekhan::where('id', $id)->first();

        $item->delete();

        return response()->json(['status' => 200, 'message' => 'Data Deleted successfully']);
    }

    public function deleteJaapCount(Request $request, $id): JsonResponse
    {

        $item = JaapCount::where('id', $id)->first();

        $item->delete();

        return response()->json(['status' => 200, 'message' => 'Data Deleted successfully']);
    }
}