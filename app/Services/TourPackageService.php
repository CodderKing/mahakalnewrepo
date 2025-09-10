<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Traits\FileManagerTrait;

class TourPackageService
{


    use FileManagerTrait;


    public function getAddTourData(object $request): array
    {
        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'title' => $request['title'][array_search('en', $request['lang'])],
            'type' => $request['type'],
            'seats' => $request['seats'],
            'description' => $request['description'][array_search('en', $request['lang'])],
            'image' => $this->upload(dir: 'tour_and_travels/package/', format: 'png', image: $request['image']),
        ];
    }

    public function getAddCabData(object $request)
    {
        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'seats' => $request['seats'],
            'description' => $request['description'][array_search('en', $request['lang'])],
            'image' => $this->upload(dir: 'tour_and_travels/cab/', format: 'png', image: $request['image']),
        ];
    }


    public function getUpdateTourData(object $request): array
    {
        $dataArray = [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'title' => $request['title'][array_search('en', $request['lang'])],
            'type' => $request['type'],
            'seats' => $request['seats'],
            'description' => $request['description'][array_search('en', $request['lang'])],
        ];

        if ($request->file('image')) {
            $dataArray['image'] = $this->upload(dir: 'tour_and_travels/package/', format: 'png', image: $request['image']);
        }

        return $dataArray;
    }

    public function getUpdateCabData(object $request): array
    {
        $dataArray = [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'seats' => $request['seats'],
            'description' => $request['description'][array_search('en', $request['lang'])],
        ];

        if ($request->file('image')) {
            $dataArray['image'] = $this->upload(dir: 'tour_and_travels/cab/', format: 'png', image: $request['image']);
        }

        return $dataArray;
    }

    public function deleteImage($old_data)
    {
        return $this->delete(filePath: '/tour_and_travels/package/' . $old_data['image']);
    }

    public function CapImageRemove($old_data)
    {
        return $this->delete(filePath: '/tour_and_travels/cab/' . $old_data['image']);
    }
}
