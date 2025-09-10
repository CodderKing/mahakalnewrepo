<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use App\Contracts\Repositories\CitiesRepositoryInterface;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Contracts\Repositories\TempleReviewRepositoryInterface;
use App\Contracts\Repositories\TemplesRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\TemplePath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TemplesAddRequest;
use App\Models\Cities;
use App\Models\Country;
use App\Models\States;
use App\Models\TempleCategory;
use App\Services\TemplesService;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TempleController extends Controller
{

    public function __construct(
        private readonly TemplesRepositoryInterface          $templeRepo,
        private readonly CitiesRepositoryInterface          $citiesRepo,
        private readonly TranslationRepositoryInterface     $translationRepo,
        private readonly TempleReviewRepositoryInterface        $templereviewRepo,
        private readonly CountryRepositoryInterface $countryRepo,
        private readonly StateRepositoryInterface $stateRepo,
    ) {}

    public function index()
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $stateList = states::orderBy('name', 'asc')->get();
        $countryList = Country::orderBy('name', 'asc')->get();
        $templecategory = TempleCategory::where('status', 1)->orderBy('name', 'asc')->get();
        $citiesList = cities::orderBy('city', 'asc')->get();
        $googleMapsApiKey =  config('services.google_maps.api_key');
        return view(TemplePath::ADD[VIEW], compact('languages', 'countryList', 'defaultLanguage', 'templecategory', 'stateList', 'googleMapsApiKey', 'citiesList'));
    }

    public function add_temple(TemplesAddRequest $request, TemplesService $templeService): RedirectResponse
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


        if (!empty($request['city_id'])) {
            if (is_numeric($request['city_id'])) {
                $request['city_id'] = (int) $request['city_id'];
            } else {
                $cityName = ucwords(trim($request['city_id']));
                $getcities = $this->citiesRepo->getFirstWhere(params: ['city' => $cityName]);
                if (!$getcities) {
                    $insert = $this->citiesRepo->add(data: ['city' => $cityName, 'country_id'  => $request['country_id'], 'state_id' => $request['state_id'], 'short_desc'  => '', 'description' => '', 'images'      => '', 'famous_for'  => '', 'latitude'    => $request['city_latitude'] ?? '', 'longitude'   => $request['city_longitude'] ?? '']);
                    $request['city_id'] = $insert->id;
                } else {
                    $request['city_id'] = $getcities->id;
                }
            }
        }

        $dataArray = $templeService->getAddTemplesData($request, addedBy: 'admin');
        $savedTemple = $this->templeRepo->add(data: $dataArray);
        // $this->templeRepo->addTemplesTags(request: $request, temple: $savedTemple);
        $this->translationRepo->add(request: $request, model: 'App\Models\Temple', id: $savedTemple->id);
        Helpers::editDeleteLogs('Temple', 'Temple', 'Insert');
        Toastr::success(translate('temple_added_successfully'));
        return redirect()->route('admin.temple.list');
    }

    public function getCities(Request $request, TemplesService $templeService): JsonResponse
    {
        $parentId = $request['id'];
        $filter = ['id' => $parentId];
        $citiesList = cities::where('state_id', $parentId)->get();

        $dropdown = $templeService->getStatesDropdown(request: $request, cities: $citiesList);


        $childStates = '';
        if (count($citiesList) == 1) {
            $subCities = $this->citiesRepo->getListWhere(filters: ['state_id' => $citiesList[0]['id']], dataLimit: 'all');
            $childStates = $templeService->getStatesDropdown(request: $request, cities: $subCities);
        }


        return response()->json([
            'select_tag' => $dropdown,
            'sub_cities' => count($citiesList) == 1 ? $childStates : '',
        ], 200);
    }


    public function list(Request $request): Application|Factory|View
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $temple = $this->templeRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'), relations: ['cities', 'states']);
        // dd($temple);
        return view(TemplePath::LIST[VIEW], compact('temple', 'defaultLanguage'));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $data['status'] = $request->get('status', 0);
        $this->templeRepo->update(id: $request['id'], data: $data);

        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function delete(string|int $id, TemplesService $templeService): RedirectResponse
    {
        $temple = $this->templeRepo->getFirstWhere(params: ['id' => $id]);
        if ($temple) {
            $this->translationRepo->delete(model: 'App\Models\Temple', id: $id);
            $templeService->deleteImages(temple: $temple);
            $this->templeRepo->delete(params: ['id' => $id]);
            Toastr::success(translate('temple_removed_successfully'));
            Helpers::editDeleteLogs('Temple', 'Temple', 'Delete');
        } else {
            Toastr::error(translate('invalid_product'));
        }

        return back();
    }

    public function getUpdateView(Request $request, string|int $id): View|RedirectResponse
    {
        $temple = $this->templeRepo->getFirstWhere(params: ['id' => $id], relations: ['translations']);

        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        $countryList = Country::orderBy('name', 'asc')->get();
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $templecategory = TempleCategory::where('status', 1)->orderBy('name', 'asc')->get();
        $googleMapsApiKey =  config('services.google_maps.api_key');
        return view(TemplePath::UPDATE[VIEW], compact('temple', 'countryList', 'templecategory', 'googleMapsApiKey', 'citiesList', 'stateList', 'languages', 'defaultLanguage'));
    }

    public function update(TemplesAddRequest $request, TemplesService $templeService, $id): RedirectResponse
    {
        $temple = $this->templeRepo->getFirstWhereWithoutGlobalScope(params: ['id' => $id], relations: ['translations']);
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


        if (!empty($request['city_id'])) {
            if (is_numeric($request['city_id'])) {
                $request['city_id'] = (int) $request['city_id'];
            } else {
                $cityName = ucwords(trim($request['city_id']));
                $getcities = $this->citiesRepo->getFirstWhere(params: ['city' => $cityName]);
                if (!$getcities) {
                    $insert = $this->citiesRepo->add(data: ['city' => $cityName, 'country_id'  => $request['country_id'], 'state_id' => $request['state_id'], 'short_desc'  => '', 'description' => '', 'images'      => '', 'famous_for'  => '', 'latitude'    => $request['city_latitude'] ?? '', 'longitude'   => $request['city_longitude'] ?? '']);
                    $request['city_id'] = $insert->id;
                } else {
                    $request['city_id'] = $getcities->id;
                }
            }
        }

        $dataArray = $templeService->getUpdateTempleData(request: $request, temple: $temple, updateBy: 'admin');
        $this->templeRepo->update(id: $id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\Temple', id: $id);
        Helpers::editDeleteLogs('Temple', 'Temple', 'Update');
        Toastr::success(translate('temple_updated_successfully'));
        return redirect()->route('admin.temple.list');
    }


    public function review_list(Request $request)
    {
        $getData = $this->templereviewRepo->getListWhere(relations: ['userData', 'templeData'], orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(TemplePath::REVIEW[VIEW], compact('getData'));
    }

    public function review_status(Request $request)
    {
        $data['status'] = $request->get('status', 0);
        $this->templereviewRepo->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function review_delete(TemplesService $service, $id)
    {
        $old_data = $this->templereviewRepo->getFirstWhere(params: ['id' => $id]);
        if (!empty($old_data['image'])) {
            $service->locationRemove($old_data['image']);
        }
        $savedCities = $this->templereviewRepo->delete(params: ['id' => $id]);
        Toastr::success(translate('Review_Deleted_successfully'));
        return redirect()->route(TemplePath::REVIEW[REDIRECT]);
    }
}
