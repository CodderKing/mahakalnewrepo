<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use App\Contracts\Repositories\AttributeRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\CitiesRepositoryInterface;
use App\Contracts\Repositories\CitiesReviewRepositoryInterface;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\Cities;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CitiesAddRequest;
use App\Http\Requests\Admin\CitiesUpdateRequest;
use App\Models\Country;
use App\Models\States;
use App\Services\CitiesAddService;
use App\Traits\FileManagerTrait;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;



class CitiesController extends Controller
{
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }

    public function __construct(
        private readonly CitiesRepositoryInterface           $citiesRepo,
        private readonly CategoryRepositoryInterface         $categoryRepo,
        private readonly AttributeRepositoryInterface        $attributeRepo,
        private readonly TranslationRepositoryInterface      $translationRepo,
        private readonly ProductRepositoryInterface         $productRepo,
        private readonly CitiesReviewRepositoryInterface  $CitiesReviewRepo,

        private readonly CountryRepositoryInterface $countryRepo,
        private readonly StateRepositoryInterface $stateRepo,
    ) {}
    public function index()
    {
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $state_list = states::All();
        $country_list = Country::All();
        $googleMapsApiKey = config('services.google_maps.api_key');
        return view(cities::LIST[VIEW], compact('state_list', 'country_list', 'googleMapsApiKey', 'language', 'defaultLanguage'));
    }

    public function store(CitiesAddRequest $request, CitiesAddService $CitiesAdd): RedirectResponse
    {

        if (!empty($request['country_id'])) {
            if (is_numeric($request['country_id'])) {
                $request['country_id'] = (int) $request['country_id'];
            } else {
                $getcountry = $this->countryRepo->getFirstWhere(params: ['name' => (trim($request['country_id']))]);
                if (!$getcountry) {
                    $insert = $this->countryRepo->add(data: ['name' => (trim($request['country_id'])), 'sortname' => $request['country_id_short_name']]);
                    $request['country_id'] = $insert->id;
                } else {
                    $request['country_id'] = $getcountry->id;
                }
            }
        }
        if (!empty($request['state_id'])) {
            if (is_numeric($request['state_id'])) {
                $request['state_id'] = (int) $request['state_id'];
            } else {
                $getstate = $this->stateRepo->getFirstWhere(params: ['name' => strtoupper(trim($request['state_id']))]);
                if (!$getstate) {
                    $insert = $this->stateRepo->add(data: ['name' => strtoupper(trim($request['state_id'])), 'country_id' => $request['country_id']]);
                    $request['state_id'] = $insert->id;
                } else {
                    $request['state_id'] = $getstate->id;
                }
            }
        }
        $dataArray = $CitiesAdd->getAddData($request);
        $savedCities = $this->citiesRepo->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\Cities', id: $savedCities->id);
        Toastr::success(translate('cities_added_successfully'));
        Helpers::editDeleteLogs('Temple', 'City', 'Insert');
        return redirect()->route('admin.cities.list');
    }

    public function list(Request $request): Application|Factory|View
    {
        $list = $this->citiesRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        // echo '<pre>';print_r($list->toArray());die; 
        return view(cities::INDEX[VIEW], compact('list'));
    }

    public function update(Request $request, $id)
    {
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $state_list = states::All();
        $country_list = Country::All();
        $getData = $this->citiesRepo->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        $googleMapsApiKey = config('services.google_maps.api_key');
        return view(cities::EDIT[VIEW], compact('state_list', 'country_list', 'getData', 'googleMapsApiKey', 'language', 'defaultLanguage'));
    }

    public function edit(CitiesUpdateRequest $request, CitiesAddService $Citiesupdate, $id): RedirectResponse
    {
        if (!empty($request['country_id'])) {
            if (is_numeric($request['country_id'])) {
                $request['country_id'] = (int) $request['country_id'];
            } else {
                $getcountry = $this->countryRepo->getFirstWhere(params: ['name' => (trim($request['country_id']))]);
                if (!$getcountry) {
                    $insert = $this->countryRepo->add(data: ['name' => (trim($request['country_id'])), 'sortname' => $request['country_id_short_name']]);
                    $request['country_id'] = $insert->id;
                } else {
                    $request['country_id'] = $getcountry->id;
                }
            }
        }
        if (!empty($request['state_id'])) {
            if (is_numeric($request['state_id'])) {
                $request['state_id'] = (int) $request['state_id'];
            } else {
                $getstate = $this->stateRepo->getFirstWhere(params: ['name' => strtoupper(trim($request['state_id']))]);
                if (!$getstate) {
                    $insert = $this->stateRepo->add(data: ['name' => strtoupper(trim($request['state_id'])), 'country_id' => $request['country_id']]);
                    $request['state_id'] = $insert->id;
                } else {
                    $request['state_id'] = $getstate->id;
                }
            }
        }
        $old_data = $this->citiesRepo->getFirstWhere(params: ['id' => $id]);
        $dataArray = $Citiesupdate->getUpdateData($request, $old_data);
        $savedCities = $this->citiesRepo->update(id: $id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\Cities', id: $id);
        Toastr::success(translate('cities_update_successfully'));
        Helpers::editDeleteLogs('Temple', 'City', 'Update');
        return redirect()->route('admin.cities.list');
    }


    public function gallery(Request $request, $city_id)
    {
        $getData = $this->citiesRepo->getFirstWhere(params: ['id' => $city_id]);
        return view(cities::GALLERY[VIEW], compact('getData'));
    }

    public function add_gallery(Request $request, CitiesAddService $Citiesupdate, $city_id)
    {
        $old_data = $this->citiesRepo->getFirstWhere(params: ['id' => $city_id]);
        $dataArray = $Citiesupdate->addgalleryImages($request, $old_data);
        $savedCities = $this->citiesRepo->update(id: $old_data['id'], data: $dataArray);
        return response()->json(['success' => 1, 'message' => translate('Add_Image_successfully')], 200);
    }

    public function delete(Request $request, CitiesAddService $Citiesupdate, $id, $name)
    {
        $old_data = $this->citiesRepo->getFirstWhere(params: ['id' => $id]);
        $dataArray = $Citiesupdate->deletes($old_data, $name);
        $savedCities = $this->citiesRepo->update(id: $old_data['id'], data: $dataArray);
        Helpers::editDeleteLogs('Temple', 'City', 'Delete');
        Toastr::success(translate('Image_Deleted_successfully'));
        return redirect()->route(cities::GALLERY[REDIRECT], [$id]);
    }

    public function review_list(Request $request)
    {
        $searchValue = $_GET['searchValue'] ?? "";
        $getData = $this->CitiesReviewRepo->getListWhere(dataLimit: 10, relations: ['userData', 'cities'], searchValue: $searchValue);
        return view(cities::REVIEW[VIEW], compact('getData'));
    }

    public function review_delete(CitiesAddService $Citiesupdate, $id)
    {
        $old_data = $this->CitiesReviewRepo->getFirstWhere(params: ['id' => $id]);
        if (!empty($old_data['image'])) {
            $Citiesupdate->locationRemove($old_data['image']);
        }
        $savedCities = $this->CitiesReviewRepo->delete(params: ['id' => $id]);
        Toastr::success(translate('Review_Deleted_successfully'));
        Helpers::editDeleteLogs('Temple', 'City Review', 'Delete');
        return redirect()->route('admin.cities.review');
    }

    public function review_status(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->CitiesReviewRepo->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function SliderImageRemove(CitiesAddService $Citiesupdate, $id, $image_name)
    {
        $old_data = $this->citiesRepo->getFirstWhere(params: ['id' => $id]);
        $dataArray = $Citiesupdate->delete_SliderImages($old_data, $image_name);
        $this->citiesRepo->update(id: $old_data['id'], data: $dataArray);
        Helpers::editDeleteLogs('Temple', 'City', 'Delete');
        Toastr::success(translate('Image_Deleted_successfully'));
        return back();
    }
}
