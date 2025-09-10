<?php


namespace App\Services;
use Illuminate\Support\Str;

use App\Traits\FileManagerTrait;

class AstrologersService {

    use FileManagerTrait;

    public function getAddData(object $request): array
    {
        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
        ];
    }

    public function getCategoryAddData(object $request): array
    {
        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'image' => $this->upload('astrologer-category-img/', 'webp', $request->file('image'))
        ];   
    }

    public function getCategoryUpdateData(object $request, object $data): array
    {
        $image = $request->file('image') ? $this->update('astrologer-category-img/', $data['image'],'webp', $request->file('image')) : $data['image'];
        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'image' => $image
        ];
    }

}