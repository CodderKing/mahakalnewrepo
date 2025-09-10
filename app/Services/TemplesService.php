<?php

namespace App\Services;
use Illuminate\Support\Str;
use App\Traits\FileManagerTrait;
class TemplesService
{

    
    use FileManagerTrait;

    public function getProcessedImages(object $request): array
    {
        $imageNames = [];
        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $images = $this->upload(dir: 'temple/', format: 'webp', image: $image);
                $imageNames[] = $images;
                if($request->has('images_active') && $request->has('images') && count($request['images']) > 0){
                    $imageNames[] = [
                        'image_name' => $images,
                    ];
                }
            }
        }
        return [
            'image_names' => $imageNames ?? []
        ];

    }


    public function getAddTemplesData(object $request, string $addedBy): array
    {   
        // dd($addedBy);
        $processedImages = $this->getProcessedImages(request: $request);
        return [
            'added_by' => $addedBy,
            'user_id' => $addedBy == 'admin' ? auth('admin')->id() : auth('seller')->id(),
            'category_id'=>$request['category_id'],
            'name' => $request['name'][array_search('en', $request['lang'])],
            'slug' => $this->getSlug($request),
            'short_description' => $request['short_description'][array_search('en', $request['lang'])],
            'details' => $request['details'][array_search('en', $request['lang'])],
            'more_details' => $request['more_details'][array_search('en', $request['lang'])],
            'country_id'=>$request['country_id'],
            'city_id' => $request['city_id'],
            'state_id' => $request['state_id'],
            'entry_fee' => $request['entry_fee'],
            'opening_time' => $request['opening_time'],
            'closeing_time' => $request['closeing_time'],
            'facilities' => $request['facilities'][array_search('en', $request['lang'])],
            'tips_restrictions' => $request['tips_restrictions'][array_search('en', $request['lang'])],
            'require_time' => ($request['require_time']),
            'video_provider' => 'youtube',
            'video_url' => $request['video_url'],
            'status' => $addedBy == 'admin' ? 1 : 0,
            'images' => json_encode($processedImages['image_names']),
            'thumbnail' => $this->upload(dir: 'temple/thumbnail/', format: 'png', image: $request['image']),
            'meta_title' => $request['meta_title'],
            'meta_description' => $request['meta_description'],
            'meta_image' => $this->upload(dir: 'temple/meta/', format: 'png', image: $request['meta_image']),
            'longitude' => $request['longitude'],
            'latitude' => $request['latitude'],
            'expect_details'=> $request['expect_details'][array_search('en', $request['lang'])],
            'tips_details'=> $request['tips_details'][array_search('en', $request['lang'])],
            'temple_known'=> $request['temple_known'][array_search('en', $request['lang'])],
            'temple_services'=> $request['temple_services'][array_search('en', $request['lang'])],
            'temple_aarti'=> $request['temple_aarti'][array_search('en', $request['lang'])],
            'tourist_place'=> $request['tourist_place'][array_search('en', $request['lang'])],
            'temple_local_food'=>$request['temple_local_food'][array_search('en', $request['lang'])]
        ];
    }





    public function getSlug(object $request): string
    {
        return Str::slug($request['name'][array_search('en', $request['lang'])], '-') . '-' . Str::random(6);
    }

    public function getStatesDropdown(object $request, object $cities): string
    {
        $dropdown = '<option value="' . 0 . '" disabled selected>---'.translate("Select").'---</option>';
        // dd($cities);
        foreach ($cities as $row) {
            if ($row->id == $request['cities']) {
                $dropdown .= '<option value="' . $row->id . '" selected >' . $row->city . '</option>';
            } else {
                $dropdown .= '<option value="' . $row->id . '">' . $row->city . '</option>';
            }
        }

        return $dropdown;
    }

    public function deleteImages(object $temple): bool
    {
        if (!is_null($temple['images'])) {
            $images = json_decode($temple['images']);
            if (!is_null($images)) {
                foreach ($images as $image) {
                    $this->delete(filePath: '/temple/' . $image);
                }
            }
        }
        $this->delete(filePath: '/temple/thumbnail/' . $temple['thumbnail']);
        return true;
    }

  // Update Service GET
    public function getUpdateTempleData(object $request, object $temple, string $updateBy): array
    {
        $processedImages = $this->getProcessedUpdateImages(request: $request,  temple: $temple);
       
        $dataArray = [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'slug' => $this->getSlug($request),
            'category_id'=>$request['category_id'],
            'short_description' => $request['short_description'][array_search('en', $request['lang'])],
            'details' => $request['details'][array_search('en', $request['lang'])],
            'more_details' => $request['more_details'][array_search('en', $request['lang'])],
            'country_id'=>$request['country_id'],
            'city_id' => $request['city_id'],
            'state_id' => $request['state_id'],
            'entry_fee' => $request['entry_fee'],
            'opening_time' => $request['opening_time'],
            'closeing_time' => $request['closeing_time'],
            'facilities' => $request['facilities'][array_search('en', $request['lang'])],
            'tips_restrictions' => $request['tips_restrictions'][array_search('en', $request['lang'])],
            'require_time' => ($request['require_time']),
            'images' => json_encode($processedImages['image_names']),
            'video_provider' => 'youtube',
            'video_url' => $request['video_url'],          
            'meta_title' => $request['meta_title'],
            'meta_description' => $request['meta_description'],
            'meta_image' => $request->file('meta_image') ? $this->update(dir: 'temple/meta/', oldImage: $temple['meta_image'], format: 'png', image: $request['meta_image']) : $temple['meta_image'],
            'longitude' => $request['longitude'],
            'latitude' => $request['latitude'],
            'expect_details'=> $request['expect_details'][array_search('en', $request['lang'])],
            'tips_details'=> $request['tips_details'][array_search('en', $request['lang'])],
            'temple_known'=> $request['temple_known'][array_search('en', $request['lang'])],
            'temple_services'=> $request['temple_services'][array_search('en', $request['lang'])],
            'temple_aarti'=> $request['temple_aarti'][array_search('en', $request['lang'])],
            'tourist_place'=> $request['tourist_place'][array_search('en', $request['lang'])],
            'temple_local_food'=>$request['temple_local_food'][array_search('en', $request['lang'])]
        ];

        if ($request->file('image')) {
            $dataArray += [
                'thumbnail' => $this->update(dir: 'temple/thumbnail/', oldImage: $temple['thumbnail'], format: 'webp', image: $request['image'], fileType: 'image')
            ];
        }

       

        if($updateBy=='seller' && $temple->request_status == 2){
            $dataArray += [
                'request_status' => 0
            ];
        }

        if($updateBy=='admin' && $temple->added_by == 'seller' && $temple->request_status == 2){
            $dataArray += [
                'request_status' => 1
            ];
        }

        return $dataArray;
    }


    public function getProcessedUpdateImages(object $request, object $temple): array
    {
        $templeImages = json_decode($temple->images);
        $colorImageArray = [];
        if ($request->has('images_active') && $request->has('images') && count($request->images) > 0) {
            $dbColorImage = $temple->images ? json_decode($temple->images, true) : [];
            if (!$dbColorImage) {
                foreach ($templeImages as $image) {
                    $dbColorImage[] = [
                        'image_name' => $image,
                    ];
                }
            }
        }

        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = $this->upload(dir: 'temple/', format: 'webp', image: $image);
                $templeImages[] = $imageName;
                if ($request->has('images_active') && $request->has('images') && count($request->images) > 0) {
                    $colorImageArray[] = [
                        'image_name' => $imageName,
                    ];
                }
            }
        }

        return [
            'image_names' => $templeImages ?? []
        ];
    }

    public function locationRemove($name):bool|array{
        return $this->delete(filePath: '/temple/review/' . $name);
    }

}
?>