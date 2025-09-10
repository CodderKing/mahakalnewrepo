<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\TourCabRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\TourCabPath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourPackageRequest;
use App\Services\TourPackageService;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class TourCabController extends Controller
{
    
    use FileManagerTrait;
    public function __construct(
        private readonly TranslationRepositoryInterface     $translationRepo,
        private readonly TourCabRepositoryInterface  $tourcab,
    ) {}

    public function CabList(Request $request){
        $getData = $this->tourcab->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        return view(TourCabPath::ADDCAB[VIEW], compact('getData','defaultLanguage','languages'));
    }

    public function CabAdd(TourPackageRequest $request, TourPackageService $service){
        $dataArray = $service->getAddCabData($request);
        $insert = $this->tourcab->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\TourCab', id: $insert->id);
        Toastr::success(translate('Tour_Cab_added_successfully'));
        return redirect()->route(TourCabPath::ADDCAB[REDIRECT]);
    }

    public function CabStatus(Request $request){
        $data['status'] = $request->get('status', 0);
        $this->tourcab->update(id: $request['id'], data: $data);
        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function CabUpdate($id){
        $getData = $this->tourcab->getFirstWhere(params: ['id' => $id],relations:['translations']);
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        return view(TourCabPath::CABUPDATE[VIEW], compact('getData','defaultLanguage','languages'));
    }

    public function CabEdit(TourPackageRequest $request, TourPackageService $service){
        $dataArray = $service->getUpdateCabData($request);
        $this->tourcab->update(id:$request->id,data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\TourCab', id: $request->id);
        Toastr::success(translate('Tour_Cab_service_updated_successfully'));
        return redirect()->route(TourCabPath::ADDCAB[REDIRECT]);
    }

    public function CabDelete(Request $request,TourPackageService $service){
        $old_data = $this->tourcab->getFirstWhere(params: ['id' => $request['id']]);
        if ($old_data) {
            $service->CapImageRemove($old_data);
            $this->tourcab->delete(params: ['id' => $request['id']]);
            $this->translationRepo->delete('App\Models\TourCab', $request['id']);
            Toastr::success(translate('Tour_Cab_service_Deleted_successfully'));
            return response()->json(['success' => 1, 'message' => translate('Tour_Cab_service_deleted_successfully')], 200);
        } else {
            Toastr::error(translate('Tour_Cab_service_Deleted_Failed'));
            return response()->json(['success' => 0, 'message' => translate('Not_found_data')], 400);
        }
    }
}
