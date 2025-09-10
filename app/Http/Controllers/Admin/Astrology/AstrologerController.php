<?php

namespace App\Http\Controllers\Admin\Astrology;

use App\Contracts\Repositories\AstrologersCategoryRepositoryInterface;
use Illuminate\Http\Request;
use App\Contracts\Repositories\AstrologersRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ViewPaths\Admin\Astrologer;
// use App\Http\Controllers\BaseController;
// use App\Http\Requests\Admin\CalculatorAddRequest;
use App\Services\AstrologersService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AstrologerCategoryAddRequest;
use App\Http\Requests\Admin\AstrologerCategoryUpdateRequest;
use Brian2694\Toastr\Facades\Toastr;
use App\Http\Requests\Admin\AstrologerSkillsAddRequest;
use App\Http\Requests\Admin\AstrologerSkillUpdateRequest;
use App\Models\Astrologer\Astrologer as AstrologerAstrologer;
use App\Models\Astrologer\AstrologerWithdraw;
use App\Models\Astrologer\Availability;
use App\Models\Astrologer\Skills;
use App\Models\AstrologerCategory;
use App\Models\BirthJournalKundali;
use App\Models\Category;
use App\Models\Chadhava;
use App\Models\Chadhava_orders;
use App\Models\OfflinePoojaOrder;
use App\Models\OfflinepoojaReview;
use App\Models\PanditTransectionPooja;
use App\Models\PoojaOffline;
use App\Models\Service;
use App\Models\Service_order;
use App\Models\ServiceReview;
use App\Models\ServiceTax;
use App\Models\ServiceTransaction;
use App\Models\Vippooja;
use App\Utils\Helpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use DB;

class AstrologerController extends Controller
{

    public function __construct(
        private readonly AstrologersRepositoryInterface        $astroRepo,
        private readonly AstrologersCategoryRepositoryInterface        $astroCatRepo,
        private readonly AstrologersService       $astroService,
        private readonly TranslationRepositoryInterface     $translationRepo,
    ) {}


    // block functions---------------------------------------------
    public function block_list()
    {
        $blocked = AstrologerAstrologer::where('status', 2)->with('primarySkill')->with('orders')->orderBy('created_at', 'desc')->paginate(10);
        return view(Astrologer::BLOCK_LIST[VIEW], compact('blocked'));
    }


    // manage functions---------------------------------------------
    public function manage_list(Request $request)
    {
        // dd($request->input());
        if ($request->has('search_name')) {
            $verified = AstrologerAstrologer::where('name', 'like', '%' . $request->search_name . '%')->where('status', 1)->with('primarySkill')->with('orders')->orderBy('created_at', 'desc')->get();
        } else if ($request->has('search_type') && !empty($request->search_type)) {
            $verified = AstrologerAstrologer::where('type', $request->search_type)->where('status', 1)->with('primarySkill')->with('orders')->orderBy('created_at', 'desc')->get();
        } else if ($request->has('search_service_type') && !empty($request->search_service_type)) {
            $verified = AstrologerAstrologer::where('primary_skills', $request->search_service_type)->where('status', 1)->with('primarySkill')->with('orders')->orderBy('created_at', 'desc')->get();
        } else {
            $verified = AstrologerAstrologer::where('status', 1)->with('primarySkill')->with('orders')->orderBy('created_at', 'desc')->paginate(10);
        }
        // dd($verified);
        return view(Astrologer::MANAGE_LIST[VIEW], compact('verified'));
    }

    public function detailManageOverview($id)
    {
        $overview = AstrologerAstrologer::where('id', $id)->with('primarySkill')->with('orders')->first();
        $transaction = ServiceTransaction::where('astro_id', $id)->with('serviceOrder')->with('chadhavaOrder')->with('offlinepoojaOrder')->get();
        $onlinePoojaReviews = ServiceReview::select(DB::raw('SUM(rating) as total_rate'), DB::raw('COUNT(*) as total_count'))->where('astro_id', $id)->first();
        $offlinePoojaReviews = ServiceReview::select(DB::raw('SUM(rating) as total_rate'), DB::raw('COUNT(*) as total_count'))->where('astro_id', $id)->first();
        $avalability = Availability::where('astrologer_id', $id)->first();
        return view(Astrologer::MANAGE_DETAIL_OVERVIEW[VIEW], compact('overview', 'transaction', 'onlinePoojaReviews', 'offlinePoojaReviews', 'avalability'));
    }

    public function detailManageOrder($id)
    {
        $poojaOrders = "";
        $consultationOrders = "";
        $ChadhavaOrder = "";
        $vipOrders = "";
        $anushthanOrders = "";
        $offlinepoojaOrders = "";
        $userData = AstrologerAstrologer::where('id', $id)->first();
        if (!empty($userData['is_pandit_pooja'])) {
            $poojaOrders = Service_order::where('pandit_assign', $id)->where('status', 0)->where('type', 'pooja')->with('services.category')
                ->with(['vippoojas', 'services', 'customers'])->selectRaw('service_id, COUNT(*) as total_orders,pandit_assign,booking_date, COUNT(created_at) as booking_count, SUM(pay_amount) as total_amount,type, 
            GROUP_CONCAT(order_id SEPARATOR "|") as orderId,GROUP_CONCAT(members SEPARATOR "|") as members,order_status,created_at,GROUP_CONCAT(gotra SEPARATOR "|") as gotra')->groupBy('service_id', 'booking_date', 'pandit_assign')->orderBy('total_orders', 'DESC')->paginate(10);
        }
        $ChadhavaOrder = Chadhava_orders::where('pandit_assign', $id)->where('type', 'chadhava')->where('status', 0)->with(['chadhava', 'customers'])->selectRaw('service_id, COUNT(*) as total_orders,pandit_assign,booking_date, COUNT(created_at) as booking_count, SUM(pay_amount) as total_amount,type, GROUP_CONCAT(members SEPARATOR "|") as members,order_status,created_at,GROUP_CONCAT(gotra SEPARATOR "|") as gotra')->groupBy('service_id', 'booking_date', 'pandit_assign')->orderBy('total_orders', 'DESC')->get();
        // Vip Pooja
        $vipOrders = Service_order::where('pandit_assign', $id)->where('status', 0)->where('type', 'vip')->with('services.category')
            ->with(['vippoojas', 'customers'])->selectRaw('service_id, COUNT(*) as total_orders,pandit_assign,booking_date, COUNT(created_at) as booking_count, SUM(pay_amount) as total_amount,type, 
        GROUP_CONCAT(order_id SEPARATOR "|") as orderId,GROUP_CONCAT(members SEPARATOR "|") as members,order_status,created_at,GROUP_CONCAT(gotra SEPARATOR "|") as gotra')->groupBy('service_id', 'booking_date', 'pandit_assign')->orderBy('total_orders', 'DESC')->paginate(10);
        $anushthanOrders = Service_order::where('pandit_assign', $id)->where('status', 0)->where('type', 'anushthan')->with('services.category')
            ->with(['vippoojas', 'customers'])->selectRaw('service_id, COUNT(*) as total_orders,pandit_assign,booking_date, COUNT(created_at) as booking_count, SUM(pay_amount) as total_amount,type, 
        GROUP_CONCAT(order_id SEPARATOR "|") as orderId,GROUP_CONCAT(members SEPARATOR "|") as members,order_status,created_at,GROUP_CONCAT(gotra SEPARATOR "|") as gotra')->groupBy('service_id', 'booking_date', 'pandit_assign')->orderBy('total_orders', 'DESC')->paginate(10);
        // dd($poojaOrders);
        if (!empty($userData['consultation_charge'])) {
            $consultationOrders = Service_order::where('pandit_assign', $id)->where('status', 0)->where('type', 'counselling')->with('services.category')->with('pandit')->with('counselling_user')->paginate(10);
        }
        $offlinepoojaOrders = OfflinePoojaOrder::where('pandit_assign', $id)->where('status', 0)->with('offlinePooja')->with('customers')->paginate(10);
        // $pendingOrder = Service_order::where('pandit_assign', $id)->where('status', 0)->whereIn('type', ['pooja', 'vip', 'anushthan'])->count();
        // $pendingOrder1 = Chadhava_orders::where('pandit_assign', $id)->where('status', 0)->where('type', 'chadhava')->count();
        // $pendingOrder2 = OfflinePoojaOrder::where('pandit_assign', $id)->where('status', 0)->count();
        // $totalPending = $pendingOrder + $pendingOrder1 + $pendingOrder2;
        // $completeOrder = Service_order::where('pandit_assign', $id)->where('status', 1)->whereIn('type', ['pooja', 'vip', 'anushthan'])->count();
        // $completeOrder1 = Chadhava_orders::where('pandit_assign', $id)->where('status', 1)->where('type', 'chadhava')->count();
        // $completeOrder2 = OfflinePoojaOrder::where('pandit_assign', $id)->where('status', 1)->count();
        // $totalcomplete = $completeOrder + $completeOrder1 + $completeOrder2;
        // dd($totalPending);
        $KundaliOrders = BirthJournalKundali::with(['userData', 'birthJournal_kundalimilan', 'country', 'country_female'])->whereHas('birthJournal_kundalimilan', function ($query) {
            $query->where('name', 'kundali_milan');
        })->where('assign_pandit', $id)->where('milan_verify', 0)->paginate(10, ['*'], 'kundli-page', request('kundli-page', 1));
        return view(Astrologer::MANAGE_DETAIL_ORDER[VIEW], compact('poojaOrders', 'ChadhavaOrder', 'vipOrders', 'anushthanOrders', 'consultationOrders', 'offlinepoojaOrders', 'KundaliOrders', 'id'));
    }

    public function detailManageService($id)
    {
        $service = AstrologerAstrologer::where('id', $id)->first();
        return view(Astrologer::MANAGE_DETAIL_SERVICE[VIEW], compact('service', 'id'));
    }

    public function detailManageSetting($id)
    {
        $setting = AstrologerAstrologer::where('id', $id)->first();
        return view(Astrologer::MANAGE_DETAIL_SETTING[VIEW], compact('setting', 'id'));
    }

    public function detailManageTransaction($id)
    {
        $transactions = [];
        $tax = ServiceTax::value('online_pooja') ?? 0;
        // dd($tax);
        $astrologer = AstrologerAstrologer::where('id', $id)->first();
        $transactionData = ServiceTransaction::where('astro_id', $id)->whereIn('type', ['counselling', 'offlinepooja', 'vip', 'anushthan'])->get();
        $poojaOrder = Service_order::selectRaw('service_id, booking_date, COUNT(*) as total, SUM(pay_amount) as amount')
            ->where('pandit_assign', $id)
            ->where('type', 'pooja')
            ->groupBy('booking_date')
            ->get();
        $chadhavaOrder = Chadhava_orders::selectRaw('service_id, booking_date, COUNT(*) as total, SUM(pay_amount) as amount')
            ->where('pandit_assign', $id)
            ->groupBy('booking_date')
            ->get();
        // dd($chadhavaOrder->toArray());

        if ($transactionData->isNotEmpty()) {
            foreach ($transactionData as $trans) {
                if ($trans->type == 'counselling') {
                    $order = Service_order::where('order_id', $trans->order_id)->first();
                    $service = Service::find($order->service_id);
                    $chargeArr = json_decode($astrologer->consultation_charge, true);
                } else if ($trans->type == 'vip') {
                    $order = Service_order::where('order_id', $trans->order_id)->first();
                    $service = Vippooja::find($order->service_id);
                    $chargeArr = json_decode($astrologer->is_pandit_vippooja, true);
                } else if ($trans->type == 'anushthan') {
                    $order = Service_order::where('order_id', $trans->order_id)->first();
                    $service = Vippooja::find($order->service_id);
                    $chargeArr = json_decode($astrologer->is_pandit_anushthan, true);
                } else if ($trans->type == 'offlinepooja') {
                    $order = OfflinePoojaOrder::where('order_id', $trans->order_id)->first();
                    $service = PoojaOffline::find($order->service_id);
                    $chargeArr = json_decode($astrologer->is_pandit_offlinepooja, true);
                }
                $astroAmount = $chargeArr[$order->service_id] ?? 0;

                $commissionAmount = ($trans->amount * $trans->commission) / 100;
                $taxAmount = ($trans->amount * $trans->tax) / 100;
                $companyReceived = $trans->amount - $commissionAmount - $taxAmount - $astroAmount;

                $transactions[] = [
                    'service_name'      => $service->name,
                    'total_order'       => 1,
                    'type'              => $trans->type,
                    'amount'            => $trans->amount,
                    'commission'        => $commissionAmount,
                    'tax'               => $taxAmount,
                    'astro_amount'      => $astroAmount,
                    'company_received'  => $companyReceived,
                ];
            }
        }

        if ($poojaOrder->isNotEmpty()) {
            foreach ($poojaOrder as $poojas) {
                $service = Service::find($poojas->service_id);
                $chargeArr = json_decode($astrologer->is_pandit_pooja, true);
                $commissionArr = json_decode($astrologer->is_pandit_pooja_commission, true);
                $astroAmount = $chargeArr[$poojas->service_id] ?? 0;
                $astroCommission = $commissionArr[$poojas->service_id] ?? 0;

                $commissionAmount = ($poojas->amount * $astroCommission) / 100;
                $taxAmount = ($poojas->amount * $tax) / 100;
                $companyReceived = $poojas->amount - $commissionAmount - $taxAmount - $astroAmount;

                $transactions[] = [
                    'service_name'      => $service->name,
                    'total_order'       => $poojas->total,
                    'type'              => 'pooja',
                    'amount'            => $poojas->amount,
                    'commission'        => $commissionAmount,
                    'tax'               => $taxAmount,
                    'astro_amount'      => $astroAmount,
                    'company_received'  => $companyReceived,
                ];
            }
        }

        if ($chadhavaOrder->isNotEmpty()) {
            foreach ($chadhavaOrder as $chadhava) {
                $service = Chadhava::find($chadhava->service_id);
                $chargeArr = json_decode($astrologer->is_pandit_chadhava, true);
                $commissionArr = json_decode($astrologer->is_pandit_chadhava, true);
                $astroAmount = $chargeArr[$chadhava->service_id] ?? 0;
                $astroCommission = $commissionArr[$chadhava->service_id] ?? 0;

                $commissionAmount = ($chadhava->amount * $astroCommission) / 100;
                $taxAmount = ($chadhava->amount * $tax) / 100;
                $companyReceived = $chadhava->amount - $commissionAmount - $taxAmount - $astroAmount;

                $transactions[] = [
                    'service_name'      => $service->name,
                    'total_order'       => $chadhava->total,
                    'type'              => 'chadhava',
                    'amount'            => $chadhava->amount,
                    'commission'        => $commissionAmount,
                    'tax'               => $taxAmount,
                    'astro_amount'      => $astroAmount,
                    'company_received'  => $companyReceived,
                ];
            }
        }

        $kundaliOrder = BirthJournalKundali::where('assign_pandit', $id)
            ->selectRaw('
                " " as service_id,
                created_at as booking_date,
                order_id,
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                SUM(admin_amount) as total_commission,
                SUM(tax_amount) as total_tax,
                SUM(final_amount) as final_amount,
                COUNT(*) as total_orders,
                SUM(pandit_price) as pandit_price,
                (SUM(final_amount) - SUM(pandit_price)) as company_received,
                birth_journal_id
            ')->with(['birthJournal_kundalimilan'])
            ->whereHas('birthJournal_kundalimilan', function ($query) {
                $query->where('name', 'kundali_milan');
            })
            ->where('milan_verify', 1)
            ->groupBy('birth_journal_id')
            ->orderBy('id', 'DESC')
            ->get();
        // dd($kundaliOrder);
        return view(Astrologer::MANAGE_DETAIL_TRANSACTION[VIEW], compact('transactions', 'kundaliOrder', 'id'));
    }

    public function detailManageReview($id)
    {
        $reviews = ServiceReview::select('*', DB::raw('SUM(rating) as total_rate'), DB::raw('COUNT(*) as total_count'))
            ->where('astro_id', $id)
            ->groupBy('service_id')
            ->with('services', 'vippoojas', 'chadhava')
            ->get();
        $offlinepoojaReviews = OfflinepoojaReview::select('*', DB::raw('SUM(rating) as total_rate'), DB::raw('COUNT(*) as total_count'))
            ->where('astro_id', $id)
            ->groupBy('service_id')
            ->with('offlinePooja')
            ->get();
        return view(Astrologer::MANAGE_DETAIL_REVIEW[VIEW], compact('reviews', 'offlinepoojaReviews', 'id'));
    }

    public function detailManageHistory($id)
    {
        $historyData = [];
        $userData = AstrologerAstrologer::where('id', $id)->first();
        if (!empty($userData['primary_skills'] == 3)) {

            $historyData['pooja'] = Service_order::where('pandit_assign', $id)->whereIn('status', [1, 2])->groupBy('service_id', DB::raw("DATE_FORMAT(order_completed, '%d/%M/%Y')"))->paginate(10);
            $historyData['chadhava']  = Chadhava_orders::where('pandit_assign', $id)->whereIn('status', [1, 2])->groupBy('service_id', 'order_completed')->with('chadhava')->paginate(10);
            $historyData['offlinepooja'] = OfflinePoojaOrder::where('pandit_assign', $id)->whereIn('status', [1, 2])->with('offlinePooja')->paginate(10);
            // dd($historyData);
        } elseif (!empty($userData['primary_skills'] == 4)) {
            $historyData['counselling'] = Service_order::where('pandit_assign', $id)->whereIn('status', [1, 2])->paginate(10);
        }
        $historyData['KundaliOrders'] = BirthJournalKundali::with(['userData', 'birthJournal_kundalimilan', 'country', 'country_female'])->whereHas('birthJournal_kundalimilan', function ($query) {
            $query->where('name', 'kundali_milan');
        })->where('assign_pandit', $id)->where('milan_verify', 1)->paginate(10, ['*'], 'kundli-page', request('kundli-page', 1));
        // dd($historyData['counselling']);
        return view(Astrologer::MANAGE_DETAIL_HISTORY[VIEW], compact('historyData', 'id'));
    }

    public function user_review_list($type, $serviceId, $astroId)
    {
        $userReview = "";
        if ($type == 'pooja') {
            $userReview = ServiceReview::where(['service_id' => $serviceId, 'astro_id' => $astroId])->with('users')->get();
            if ($userReview) {
                return response()->json(['status' => 200, 'data' => $userReview]);
            }
        } elseif ($type == 'offlinepooja') {
            $userReview = OfflinepoojaReview::where(['service_id' => $serviceId, 'astro_id' => $astroId])->with('users')->get();
            if ($userReview) {
                return response()->json(['status' => 200, 'data' => $userReview]);
            }
        }
        return response()->json(['status' => 400]);
    }

    public function user_review_delete($type, $id)
    {
        $userDelete = "";
        if ($type == 'pooja') {
            $userDelete = ServiceReview::where('id', $id)->delete();
            if ($userDelete) {
                Toastr::success(translate('Review Deleted'));
                Helpers::editDeleteLogs('Astrologer', 'Review', 'Delete');
                return back();
            }
        } elseif ($type == 'offlinepooja') {
            $userDelete = OfflinepoojaReview::where('id', $id)->delete();
            if ($userDelete) {
                Toastr::success(translate('Offline Pooja Review Deleted'));
                Helpers::editDeleteLogs('Astrologer', 'Offline Pooja Review', 'Delete');
                return back();
            }
        }
        Toastr::error(translate('an_error_occured'));
        return back();
    }

    public function commission_update(Request $request)
    {
        if ($request->type == 'pooja') {
            $poojaCommissionArr = json_encode(array_combine($request->pooja_commission_key, $request->pooja_commission_value));
            $poojaUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['is_pandit_pooja_commission' => $poojaCommissionArr]);
            if ($poojaUpdate) {
                Toastr::success(translate('Pooja Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Pooja Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'vippooja') {
            $vipPoojaCommissionArr = json_encode(array_combine($request->vipPooja_commission_key, $request->vipPooja_commission_value));
            $vipPoojaUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['is_pandit_vippooja_commission' => $vipPoojaCommissionArr]);
            if ($vipPoojaUpdate) {
                Toastr::success(translate('Vip Pooja Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Vip Pooja Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'anushthan') {
            $anushthanCommissionArr = json_encode(array_combine($request->anushthan_commission_key, $request->anushthan_commission_value));
            $anushthanUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['is_pandit_anushthan_commission' => $anushthanCommissionArr]);
            if ($anushthanUpdate) {
                Toastr::success(translate('Anushthan Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Anushthan Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'chadhava') {
            $chadhavaCommissionArr = json_encode(array_combine($request->chadhava_commission_key, $request->chadhava_commission_value));
            $chadhavaUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['is_pandit_chadhava_commission' => $chadhavaCommissionArr]);
            if ($chadhavaUpdate) {
                Toastr::success(translate('Chadhava Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Chadhava Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'consultation') {
            $consultationCommissionArr = json_encode(array_combine($request->consultation_commission_key, $request->consultation_commission_value));
            $consultationUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['consultation_commission' => $consultationCommissionArr]);
            if ($consultationUpdate) {
                Toastr::success(translate('Consultation Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Consultation Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'offlinepooja') {
            $offlinepoojaCommissionArr = json_encode(array_combine($request->offlinepooja_commission_key, $request->offlinepooja_commission_value));
            $offlinepoojaUpdate = AstrologerAstrologer::where('id', $request->user_id)->update(['is_pandit_offlinepooja_commission' => $offlinepoojaCommissionArr]);
            if ($offlinepoojaUpdate) {
                Toastr::success(translate('Offline Pooja Commission Updated'));
                Helpers::editDeleteLogs('Astrologer', 'Offline Pooja Commission Service', 'Update');
                return back();
            }
        } else if ($request->type == 'commission') {
            $commissionUpdate = AstrologerAstrologer::where('id', $request->user_id)->first();
            if ($request->has('pandit_live_stream_commission')) {
                $commissionUpdate->is_pandit_live_stream_commission = $request->pandit_live_stream_commission;
            }
            if ($request->has('live_stream_commission')) {
                $commissionUpdate->is_astrologer_live_stream_commission = $request->live_stream_commission;
            }
            if ($request->has('call_commission')) {
                $commissionUpdate->is_astrologer_call_commission = $request->call_commission;
            }
            if ($request->has('chat_commission')) {
                $commissionUpdate->is_astrologer_chat_commission = $request->chat_commission;
            }
            if ($request->has('report_commission')) {
                $commissionUpdate->is_astrologer_report_commission = $request->report_commission;
            }
            if ($commissionUpdate->save()) {
                Toastr::success(translate('commission_Update'));
                Helpers::editDeleteLogs('Astrologer', 'Commission', 'Update');
                return back();
            }
        } else if ($request->type == 'kundali') {
            $commissionUpdate = AstrologerAstrologer::where('id', $request->user_id)->first();
            if ($request->has('kundali_make_commission')) {
                $commissionUpdate->kundali_make_commission = $request->kundali_make_commission;
            }
            if ($request->has('kundali_make_commission_pro')) {
                $commissionUpdate->kundali_make_commission_pro = $request->kundali_make_commission_pro;
            }

            if ($commissionUpdate->save()) {
                Toastr::success(translate('commission_Update'));
                Helpers::editDeleteLogs('Astrologer', 'Commission', 'Update');
                return back();
            }
        } else {
            Toastr::error(translate('An Error Occured'));
            return back();
        }
    }

    public function getManageAddView(): View
    {
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $categories = AstrologerCategory::where('status', 1)->get();
        $skills = Skills::where('status', 1)->get();
        $panditCategories = Category::where('parent_id', 33)->get();
        $consultationList = Service::where('product_type', 'counselling')->where('status', 1)->get();
        $offlinepoojaList = PoojaOffline::where('status', 1)->get();
        return view(Astrologer::MANAGE_ADD[VIEW], compact('language', 'defaultLanguage', 'categories', 'skills', 'panditCategories', 'consultationList', 'offlinepoojaList'));
    }

    // public function pandit_pooja(Request $request)
    // {
    //     $ids = $request->input('id');
    //     $pooja = "";
    //     if (!empty($ids)) {
    //         $pooja = Service::whereIn('sub_category_id', $ids)->get();
    //     }
    //     return response()->json(['status' => 200, 'pooja' => $pooja]);
    // }

    public function pandit_pooja(Request $request)
    {
        $ids = $request->input('id');
        $categoryIds = [];
        $pooja = "";
        $vipPooja = "";
        $anushthan = "";
        $chadhava = "";

        if (!empty($ids)) {
            //for pooja
            if (in_array("34", $ids) || in_array("35", $ids) || in_array("36", $ids) || in_array("38", $ids)) {
                foreach ($ids as $id) {
                    if ($id == 34 || $id == 35 || $id == 36 || $id == 38) {
                        $categoryIds[] = $id;
                    }
                }
                $pooja = Service::whereIn('sub_category_id', $categoryIds)->where('status', 1)->get();
            }

            // for vip pooja
            if (in_array("50", $ids)) {
                $vipPooja = Vippooja::where('is_anushthan', 0)->where('status', 1)->get();
            }

            //for anushthan
            if (in_array("51", $ids)) {
                $anushthan = Vippooja::where('is_anushthan', 1)->where('status', 1)->get();
            }

            //for chadhava
            if (in_array("52", $ids)) {
                $chadhava = Chadhava::where('status', 1)->get();
            }
        }

        return response()->json(['status' => 200, 'pooja' => $pooja, 'vipPooja' => $vipPooja, 'anushthan' => $anushthan, 'chadhava' => $chadhava]);
    }

    public function check_email($email)
    {
        $checkEmail = AstrologerAstrologer::where('email', $email)->exists();
        if ($checkEmail) {
            return response(['status' => 200]);
        }
        return response(['status' => 400]);
    }

    public function check_mobileno($mobileno)
    {
        $checkMobileno = AstrologerAstrologer::where('mobile_no', $mobileno)->exists();
        if ($checkMobileno) {
            return response(['status' => 200]);
        }
        return response(['status' => 400]);
    }

    public function addManage(Request $request): RedirectResponse
    {
        // dd($request->all());
        // pooja charge array
        $poojaChargeJson = null;
        $poojaTimeJson = null;
        $poojaCommission = null;
        $poojaCommissionJson = null;
        if ($request->pooja_charge_id) {
            $poojaChargeArr = array_combine($request->pooja_charge_id, $request->pooja_charge);
            $poojaTimeArr = array_combine($request->pooja_charge_id, $request->pooja_time);
            $poojaChargeFilterArr = array_filter($poojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $poojaTimeFilterArr = array_filter($poojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($poojaChargeFilterArr) > 0 && count($poojaTimeFilterArr) > 0) {
                $poojaChargeJson = json_encode($poojaChargeFilterArr);
                $poojaTimeJson = json_encode($poojaTimeFilterArr);
                if ($request->type == 'freelancer') {
                    $poojaCommission = array_map(function ($value) {
                        return '5';
                    }, $poojaChargeFilterArr);
                } else {
                    $poojaCommission = array_map(function ($value) {
                        return '0';
                    }, $poojaChargeFilterArr);
                }
                $poojaCommissionJson = json_encode($poojaCommission);
            }
        }

        // vip pooja charge array
        $vipPoojaChargeJson = null;
        $vipPoojaTimeJson = null;
        $vipPoojaCommission = null;
        $vipPoojaCommissionJson = null;
        if ($request->vip_pooja_charge_id) {
            $vipPoojaChargeArr = array_combine($request->vip_pooja_charge_id, $request->vip_pooja_charge);
            $vipPoojaTimeArr = array_combine($request->vip_pooja_charge_id, $request->vip_pooja_time);
            $vipPoojaChargeFilterArr = array_filter($vipPoojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $vipPoojaTimeFilterArr = array_filter($vipPoojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($vipPoojaChargeFilterArr) > 0 && count($vipPoojaTimeFilterArr) > 0) {
                $vipPoojaChargeJson = json_encode($vipPoojaChargeFilterArr);
                $vipPoojaTimeJson = json_encode($vipPoojaTimeFilterArr);
                if ($request->type == 'freelancer') {
                    $vipPoojaCommission = array_map(function ($value) {
                        return '5';
                    }, $vipPoojaChargeFilterArr);
                } else {
                    $vipPoojaCommission = array_map(function ($value) {
                        return '0';
                    }, $vipPoojaChargeFilterArr);
                }
                $vipPoojaCommissionJson = json_encode($vipPoojaCommission);
            }
        }

        // anushthan pooja charge array
        $anushthanChargeJson = null;
        $anushthanTimeJson = null;
        $anushthanCommission = null;
        $anushthanCommissionJson = null;
        if ($request->anushthan_charge_id) {
            $anushthanChargeArr = array_combine($request->anushthan_charge_id, $request->anushthan_charge);
            $anushthanTimeArr = array_combine($request->anushthan_charge_id, $request->anushthan_time);
            $anushthanChargeFilterArr = array_filter($anushthanChargeArr, function ($value) {
                return !is_null($value);
            });
            $anushthanTimeFilterArr = array_filter($anushthanTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($anushthanChargeFilterArr) > 0 && count($anushthanTimeFilterArr) > 0) {
                $anushthanChargeJson = json_encode($anushthanChargeFilterArr);
                $anushthanTimeJson = json_encode($anushthanTimeFilterArr);
                if ($request->type == 'freelancer') {
                    $anushthanCommission = array_map(function ($value) {
                        return '5';
                    }, $anushthanChargeFilterArr);
                } else {
                    $anushthanCommission = array_map(function ($value) {
                        return '0';
                    }, $anushthanChargeFilterArr);
                }
                $anushthanCommissionJson = json_encode($anushthanCommission);
            }
        }

        // chadhava pooja charge array
        $chadhavaChargeJson = null;
        $chadhavaTimeJson = null;
        $chadhavaCommission = null;
        $chadhavaCommissionJson = null;
        if ($request->chadhava_charge_id) {
            $chadhavaChargeArr = array_combine($request->chadhava_charge_id, $request->chadhava_charge);
            $chadhavaTimeArr = array_combine($request->chadhava_charge_id, $request->chadhava_time);
            $chadhavaChargeFilterArr = array_filter($chadhavaChargeArr, function ($value) {
                return !is_null($value);
            });
            $chadhavaTimeFilterArr = array_filter($chadhavaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($chadhavaChargeFilterArr) > 0 && count($chadhavaTimeFilterArr) > 0) {
                $chadhavaChargeJson = json_encode($chadhavaChargeFilterArr);
                $chadhavaTimeJson = json_encode($chadhavaTimeFilterArr);
                if ($request->type == 'freelancer') {
                    $chadhavaCommission = array_map(function ($value) {
                        return '5';
                    }, $chadhavaChargeFilterArr);
                } else {
                    $chadhavaCommission = array_map(function ($value) {
                        return '0';
                    }, $chadhavaChargeFilterArr);
                }
                $chadhavaCommissionJson = json_encode($chadhavaCommission);
            }
        }

        // consultation charge array
        $consultationChargeJson = null;
        $consultationCommission = null;
        $consultationCommissionJson = null;
        if ($request->consultation_charge) {
            $consultationChargeArr = array_combine($request->consultation_charge_id, $request->consultation_charge);
            $consultationChargeFilterArr = array_filter($consultationChargeArr, function ($value) {
                return !is_null($value);
            });
            if (count($consultationChargeFilterArr) > 0) {
                $consultationChargeJson = json_encode($consultationChargeFilterArr);
                if ($request->type == 'freelancer') {
                    $consultationCommission = array_map(function ($value) {
                        return '5';
                    }, $consultationChargeFilterArr);
                } else {
                    $consultationCommission = array_map(function ($value) {
                        return '0';
                    }, $consultationChargeFilterArr);
                }
                $consultationCommissionJson = json_encode($consultationCommission);
            }
        }

        // offlinepooja charge array
        $offlinepoojaChargeJson = null;
        $offlinepoojaTimeJson = null;
        $offlinepoojaCommission = null;
        $offlinepoojaCommissionJson = null;
        if ($request->offlinepooja_charge && $request->offlinepooja_time) {
            $offlinepoojaChargeArr = array_combine($request->offlinepooja_charge_id, $request->offlinepooja_charge);
            $offlinepoojaTimeArr = array_combine($request->offlinepooja_charge_id, $request->offlinepooja_time);
            $offlinepoojaChargeFilterArr = array_filter($offlinepoojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $offlinepoojaTimeFilterArr = array_filter($offlinepoojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($offlinepoojaChargeFilterArr) > 0 && count($offlinepoojaTimeFilterArr) > 0) {
                $offlinepoojaChargeJson = json_encode($offlinepoojaChargeFilterArr);
                $offlinepoojaTimeJson = json_encode($offlinepoojaTimeFilterArr);
                if ($request->type == 'freelancer') {
                    $offlinepoojaCommission = array_map(function ($value) {
                        return '5';
                    }, $offlinepoojaChargeFilterArr);
                } else {
                    $offlinepoojaCommission = array_map(function ($value) {
                        return '0';
                    }, $offlinepoojaChargeFilterArr);
                }
                $offlinepoojaCommissionJson = json_encode($offlinepoojaCommission);
            }
        }

        // validation
        $request->validate([
            'password' => 'confirmed'
        ]);

        // image
        $imageName = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageName = time() . '-astrologer' . $file->getClientOriginalName();
            $file->storeAs('public/astrologers', $imageName);
        }

        // aadhar front image
        $adharFrontImageName = null;
        if ($request->hasFile('adhar_front_image')) {
            $adharFrontFile = $request->file('adhar_front_image');
            $adharFrontImageName = time() . '-aadharfront' . $adharFrontFile->getClientOriginalName();
            $adharFrontFile->storeAs('public/astrologers/aadhar', $adharFrontImageName);
        }

        // aadhar back image
        $adharBackImageName = null;
        if ($request->hasFile('adhar_back_image')) {
            $adharBackFile = $request->file('adhar_back_image');
            $adharBackImageName = time() . '-aadharback' . $adharBackFile->getClientOriginalName();
            $adharBackFile->storeAs('public/astrologers/aadhar', $adharBackImageName);
        }

        // pancard image
        $pancardImageName = null;
        if ($request->hasFile('pancard_image')) {
            $pancardFile = $request->file('pancard_image');
            $pancardImageName = time() . '-pancard' . $pancardFile->getClientOriginalName();
            $pancardFile->storeAs('public/astrologers/pancard', $pancardImageName);
        }

        // bank passbook image
        $bankPassbookImageName = null;
        if ($request->hasFile('bank_passbook_image')) {
            $bankPassbookFile = $request->file('bank_passbook_image');
            $bankPassbookImageName = time() . '-bankpassbook' . $bankPassbookFile->getClientOriginalName();
            $bankPassbookFile->storeAs('public/astrologers/bankpassbook', $bankPassbookImageName);
        }

        // availability
        $sunday = [];
        foreach ($request->sunday_from as $sf_key => $sf) {
            if ($sf != null && $request->sunday_to[$sf_key] != null) {
                $sunday[$sf_key] = $sf . '-' . $request->sunday_to[$sf_key];
            }
        }

        $monday = [];
        foreach ($request->monday_from as $mf_key => $mf) {
            if ($mf != null && $request->monday_to[$mf_key] != null) {
                $monday[$mf_key] = $mf . '-' . $request->monday_to[$mf_key];
            }
        }

        $tuesday = [];
        foreach ($request->tuesday_from as $tuf_key => $tuf) {
            if ($tuf != null && $request->tuesday_to[$tuf_key] != null) {
                $tuesday[$tuf_key] = $tuf . '-' . $request->tuesday_to[$tuf_key];
            }
        }

        $wednesday = [];
        foreach ($request->wednesday_from as $w_key => $w) {
            if ($w != null && $request->wednesday_to[$w_key] != null) {
                $wednesday[$w_key] = $w . '-' . $request->wednesday_to[$w_key];
            }
        }

        $thursday = [];
        foreach ($request->thursday_from as $tf_key => $tf) {
            if ($tf != null && $request->thursday_to[$tf_key] != null) {
                $thursday[$tf_key] = $tf . '-' . $request->thursday_to[$tf_key];
            }
        }

        $friday = [];
        foreach ($request->friday_from as $ff_key => $ff) {
            if ($ff != null && $request->friday_to[$ff_key] != null) {
                $friday[$ff_key] = $ff . '-' . $request->friday_to[$ff_key];
            }
        }

        $saturday = [];
        foreach ($request->saturday_from as $stf_key => $stf) {
            if ($stf != null && $request->saturday_to[$stf_key] != null) {
                $saturday[$stf_key] = $stf . '-' . $request->saturday_to[$stf_key];
            }
        }

        $astrologer = new AstrologerAstrologer;
        $astrologer->name = $request->name;
        $astrologer->email = $request->email;
        $astrologer->mobile_no = $request->mobile_no;
        $astrologer->image = $imageName;
        $astrologer->password = Hash::make($request->password);
        $astrologer->gender = $request->gender;
        $astrologer->dob = $request->dob;
        $astrologer->pancard = $request->pancard;
        $astrologer->pancard_image = $pancardImageName;
        $astrologer->adharcard = $request->adharcard;
        $astrologer->adharcard_front_image = $adharFrontImageName;
        $astrologer->adharcard_back_image = $adharBackImageName;
        $astrologer->type = $request->type;
        $astrologer->salary = $request->salary;
        $astrologer->state = $request->state;
        $astrologer->city = $request->city;
        $astrologer->address = $request->address;
        $astrologer->pincode = $request->pincode;
        $astrologer->latitude = $request->latitude;
        $astrologer->longitude = $request->longitude;
        $astrologer->primary_skills = $request->primary_skills;
        $astrologer->is_pandit_pooja_category = $request->is_pandit_pooja_category ? json_encode($request->is_pandit_pooja_category) : null;
        $astrologer->is_pandit_pooja = $poojaChargeJson;
        $astrologer->is_pandit_vippooja = $vipPoojaChargeJson;
        $astrologer->is_pandit_anushthan = $anushthanChargeJson;
        $astrologer->is_pandit_chadhava = $chadhavaChargeJson;
        $astrologer->is_pandit_offlinepooja = $offlinepoojaChargeJson;
        $astrologer->is_pandit_panda = $request->is_pandit_panda;
        $astrologer->is_pandit_gotra = $request->is_pandit_gotra;
        $astrologer->is_pandit_primary_mandir = $request->is_pandit_primary_mandir;
        $astrologer->is_pandit_primary_mandir_location = $request->is_pandit_primary_mandir_location;
        $astrologer->is_pandit_min_charge = $request->min_charge;
        $astrologer->is_pandit_max_charge = $request->max_charge;
        $astrologer->is_pandit_pooja_per_day = $request->pooja_per_day;
        $astrologer->is_pandit_pooja_commission = $poojaCommissionJson;
        $astrologer->is_pandit_vippooja_commission = $vipPoojaCommissionJson;
        $astrologer->is_pandit_anushthan_commission = $anushthanCommissionJson;
        $astrologer->is_pandit_chadhava_commission = $chadhavaCommissionJson;
        $astrologer->is_pandit_offlinepooja_commission = $offlinepoojaCommissionJson;
        $astrologer->is_pandit_pooja_time = $poojaTimeJson;
        $astrologer->is_pandit_vippooja_time = $vipPoojaTimeJson;
        $astrologer->is_pandit_anushthan_time = $anushthanTimeJson;
        $astrologer->is_pandit_chadhava_time = $chadhavaTimeJson;
        $astrologer->is_pandit_offlinepooja_time = $offlinepoojaTimeJson;
        $astrologer->is_pandit_live_stream_charge = $request->pandit_live_stream_charge;
        $astrologer->is_pandit_live_stream_commission = !empty($request->pandit_live_stream_charge) ? 5 : null;
        if ($request->has('other_skills')) {
            $astrologer->other_skills = $request->other_skills ? json_encode($request->other_skills) : null;
        }
        $astrologer->category = json_encode($request->category);
        $astrologer->language = json_encode($request->language);
        $astrologer->is_astrologer_live_stream_charge = $request->live_stream_charge;
        $astrologer->is_astrologer_live_stream_commission = !empty($request->live_stream_charge) ? 5 : null;
        $astrologer->is_astrologer_call_charge = $request->call_charge;
        $astrologer->is_astrologer_call_commission = !empty($request->call_charge) ? 5 : null;
        $astrologer->is_astrologer_chat_charge = $request->chat_charge;
        $astrologer->is_astrologer_chat_commission = !empty($request->chat_charge) ? 5 : null;
        $astrologer->is_astrologer_report_charge = $request->report_charge;
        $astrologer->is_astrologer_report_commission = !empty($request->report_charge) ? 5 : null;
        $astrologer->consultation_charge = $consultationChargeJson;
        $astrologer->consultation_commission = $consultationCommissionJson;
        if ($request->has('is_kundali_make')) {
            $astrologer->is_kundali_make = 1;
            $astrologer->kundali_make_charge = !empty($request->kundali_making_charge) ? $request->kundali_making_charge : 0;
            $astrologer->kundali_make_charge_pro = !empty($request->kundali_making_charge_pro) ? $request->kundali_making_charge_pro : 0;
            $astrologer->kundali_make_commission = !empty($request->kundali_making_charge) ? 5 : 0;
        }
        $astrologer->experience = $request->experience;
        $astrologer->daily_hours_contribution = $request->daily_hours_contribution;
        $astrologer->office_address = $request->office_address;
        $astrologer->highest_qualification = $request->highest_qualification;
        $astrologer->other_qualification = $request->other_qualification;
        $astrologer->college = $request->college;
        $astrologer->onboard_you = $request->onboard_you;
        $astrologer->interview_time = $request->interview_time;
        $astrologer->business_source = $request->business_source;
        $astrologer->learn_primary_skill = $request->learn_primary_skill;
        $astrologer->instagram = $request->instagram;
        $astrologer->facebook = $request->facebook;
        $astrologer->linkedin = $request->linkedin;
        $astrologer->youtube = $request->youtube;
        $astrologer->website = $request->website;
        $astrologer->min_earning = $request->min_earning;
        $astrologer->max_earning = $request->max_earning;
        $astrologer->bank_name = $request->bank_name;
        $astrologer->holder_name = $request->holder_name;
        $astrologer->branch_name = $request->branch_name;
        $astrologer->bank_ifsc = $request->bank_ifsc;
        $astrologer->account_no = $request->account_no;
        $astrologer->bank_passbook_image = $bankPassbookImageName;
        $astrologer->foreign_country = $request->foreign_country;
        $astrologer->working = $request->working;
        $astrologer->bio = $request->bio;
        $astrologer->qualities = $request->qualities;
        $astrologer->challenge = $request->challenge;
        $astrologer->repeat_question = $request->repeat_question;
        if ($astrologer->save()) {
            $astrologerId = AstrologerAstrologer::select('id')->latest()->first();
            if ($astrologerId) {
                $avalability = new Availability;
                $avalability->astrologer_id = $astrologerId['id'];
                $avalability->sunday = json_encode($sunday);
                $avalability->monday = json_encode($monday);
                $avalability->tuesday = json_encode($tuesday);
                $avalability->wednesday = json_encode($wednesday);
                $avalability->thursday = json_encode($thursday);
                $avalability->friday = json_encode($friday);
                $avalability->saturday = json_encode($saturday);
                $avalability->save();
            }
            Toastr::success(translate('registration_successfully'));
            Helpers::editDeleteLogs('Astrologer', 'Astrologer', 'Insert');
            return redirect()->route('admin.astrologers.pending.list');
        }
        Toastr::success(translate('unable_to_store_data'));
        return redirect()->back();
    }

    public function getManageUpdateView($id)
    {
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $categories = AstrologerCategory::get();
        $skills = Skills::where('status', 1)->get();
        $panditCategories = Category::where('parent_id', 33)->get();
        $consultationList = Service::where('product_type', 'counselling')->where('status', 1)->get();
        $astrologer = AstrologerAstrologer::where('id', $id)->with('primarySkill')->first();
        $availability = Availability::where('astrologer_id', $id)->first();
        $offlinepoojaList = PoojaOffline::where('status', 1)->get();
        return view(Astrologer::MANAGE_UPDATE[VIEW], compact('language', 'defaultLanguage', 'categories', 'skills', 'panditCategories', 'astrologer', 'availability', 'consultationList', 'offlinepoojaList'));
    }

    public function updateManage(Request $request, $id): RedirectResponse
    {
        // pooja charge array
        $poojaCommissionData = AstrologerAstrologer::select('is_pandit_pooja_commission')->where('id', $id)->first();
        $poojaCommissionDataArr = json_decode($poojaCommissionData['is_pandit_pooja_commission'], true);
        $poojaChargeJson = null;
        $poojaTimeJson = null;
        $poojaCommissionJson = null;
        if ($request->pooja_charge_id) {
            $poojaChargeArr = array_combine($request->pooja_charge_id, $request->pooja_charge);
            $poojaChargeFilterArr = array_filter($poojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $poojaTimeArr = array_combine($request->pooja_charge_id, $request->pooja_time);
            $poojaTimeFilterArr = array_filter($poojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($poojaChargeFilterArr) > 0 && count($poojaTimeFilterArr) > 0) {
                $poojaChargeJson = json_encode($poojaChargeFilterArr);
                $poojaTimeJson = json_encode($poojaTimeFilterArr);
                $poojaCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $poojaChargeFilterArr);
                if (!empty($poojaCommissionDataArr)) {
                    foreach ($poojaCommission as $key => $value) {
                        if (!array_key_exists($key, $poojaCommissionDataArr)) {
                            $poojaCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($poojaCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $poojaCommission)) {
                            unset($poojaCommissionDataArr[$key]);
                        }
                    }
                    ksort($poojaCommissionDataArr);
                    $poojaCommissionJson = json_encode($poojaCommissionDataArr);
                } else {
                    $poojaCommissionJson = json_encode($poojaCommission);
                }
            }
        }


        // vip pooja charge array
        $vipPoojaCommissionData = AstrologerAstrologer::select('is_pandit_vippooja_commission')->where('id', $id)->first();
        $vipPoojaCommissionDataArr = json_decode($vipPoojaCommissionData['is_pandit_vippooja_commission'], true);
        $vipPoojaChargeJson = null;
        $vipPoojaTimeJson = null;
        $vipPoojaCommissionJson = null;
        if ($request->vip_pooja_charge_id) {
            $vipPoojaChargeArr = array_combine($request->vip_pooja_charge_id, $request->vip_pooja_charge);
            $vipPoojaChargeFilterArr = array_filter($vipPoojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $vipPoojaTimeArr = array_combine($request->vip_pooja_charge_id, $request->vip_pooja_time);
            $vipPoojaTimeFilterArr = array_filter($vipPoojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($vipPoojaChargeFilterArr) > 0 && count($vipPoojaTimeFilterArr) > 0) {
                $vipPoojaChargeJson = json_encode($vipPoojaChargeFilterArr);
                $vipPoojaTimeJson = json_encode($vipPoojaTimeFilterArr);
                $vipPoojaCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $vipPoojaChargeFilterArr);
                // dd($vipPoojaCommission);
                if (!empty($vipPoojaCommissionDataArr)) {
                    foreach ($vipPoojaCommission as $key => $value) {
                        if (!array_key_exists($key, $vipPoojaCommissionDataArr)) {
                            $vipPoojaCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($vipPoojaCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $vipPoojaCommission)) {
                            unset($vipPoojaCommissionDataArr[$key]);
                        }
                    }
                    ksort($vipPoojaCommissionDataArr);
                    $vipPoojaCommissionJson = json_encode($vipPoojaCommissionDataArr);
                } else {
                    $vipPoojaCommissionJson = json_encode($vipPoojaCommission);
                }
            }
        }

        // anushthan charge array
        $anushthanCommissionData = AstrologerAstrologer::select('is_pandit_anushthan_commission')->where('id', $id)->first();
        $anushthanCommissionDataArr = json_decode($anushthanCommissionData['is_pandit_anushthan_commission'], true);
        $anushthanChargeJson = null;
        $anushthanTimeJson = null;
        $anushthanCommissionJson = null;
        if ($request->anushthan_charge_id) {
            $anushthanChargeArr = array_combine($request->anushthan_charge_id, $request->anushthan_charge);
            $anushthanChargeFilterArr = array_filter($anushthanChargeArr, function ($value) {
                return !is_null($value);
            });
            $anushthanTimeArr = array_combine($request->anushthan_charge_id, $request->anushthan_time);
            $anushthanTimeFilterArr = array_filter($anushthanTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($anushthanChargeFilterArr) > 0 && count($anushthanTimeFilterArr) > 0) {
                $anushthanChargeJson = json_encode($anushthanChargeFilterArr);
                $anushthanTimeJson = json_encode($anushthanTimeFilterArr);
                $anushthanCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $anushthanChargeFilterArr);
                if (!empty($anushthanCommissionDataArr)) {
                    foreach ($anushthanCommission as $key => $value) {
                        if (!array_key_exists($key, $anushthanCommissionDataArr)) {
                            $anushthanCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($anushthanCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $anushthanCommission)) {
                            unset($anushthanCommissionDataArr[$key]);
                        }
                    }
                    ksort($anushthanCommissionDataArr);
                    $anushthanCommissionJson = json_encode($anushthanCommissionDataArr);
                } else {
                    $anushthanCommissionJson = json_encode($anushthanCommission);
                }
            }
        }


        // chadhava charge array
        $chadhavaCommissionData = AstrologerAstrologer::select('is_pandit_chadhava_commission')->where('id', $id)->first();
        $chadhavaCommissionDataArr = json_decode($chadhavaCommissionData['is_pandit_chadhava_commission'], true);
        // dd($chadhavaCommissionDataArr);
        $chadhavaChargeJson = null;
        $chadhavaTimeJson = null;
        $chadhavaCommissionJson = null;
        if ($request->chadhava_charge_id) {
            $chadhavaChargeArr = array_combine($request->chadhava_charge_id, $request->chadhava_charge);
            $chadhavaChargeFilterArr = array_filter($chadhavaChargeArr, function ($value) {
                return !is_null($value);
            });
            $chadhavaTimeArr = array_combine($request->chadhava_charge_id, $request->chadhava_time);
            $chadhavaTimeFilterArr = array_filter($chadhavaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($chadhavaChargeFilterArr) > 0 && count($chadhavaTimeFilterArr) > 0) {
                $chadhavaChargeJson = json_encode($chadhavaChargeFilterArr);
                $chadhavaTimeJson = json_encode($chadhavaTimeFilterArr);
                $chadhavaCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $chadhavaChargeFilterArr);
                if (!empty($chadhavaCommissionDataArr)) {
                    foreach ($chadhavaCommission as $key => $value) {
                        if (!array_key_exists($key, $chadhavaCommissionDataArr)) {
                            $chadhavaCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($chadhavaCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $chadhavaCommission)) {
                            unset($chadhavaCommissionDataArr[$key]);
                        }
                    }
                    ksort($chadhavaCommissionDataArr);
                    $chadhavaCommissionJson = json_encode($chadhavaCommissionDataArr);
                } else {
                    $chadhavaCommissionJson = json_encode($chadhavaCommission);
                }
            }
        }

        // offline pooja charge array
        $offlinepoojaCommissionData = AstrologerAstrologer::select('is_pandit_offlinepooja_commission')->where('id', $id)->first();
        $offlinepoojaCommissionDataArr = json_decode($offlinepoojaCommissionData['is_pandit_offlinepooja_commission'], true);
        $offlinepoojaChargeJson = null;
        $offlinepoojaTimeJson = null;
        $offlinepoojaCommissionJson = null;
        if ($request->offlinepooja_charge) {
            $offlinepoojaChargeArr = array_combine($request->offlinepooja_charge_id, $request->offlinepooja_charge);
            $offlinepoojaChargeFilterArr = array_filter($offlinepoojaChargeArr, function ($value) {
                return !is_null($value);
            });
            $offlinepoojaTimeArr = array_combine($request->offlinepooja_charge_id, $request->offlinepooja_time);
            $offlinepoojaTimeFilterArr = array_filter($offlinepoojaTimeArr, function ($value) {
                return !is_null($value);
            });
            if (count($offlinepoojaChargeFilterArr) > 0 && count($offlinepoojaTimeFilterArr) > 0) {
                $offlinepoojaChargeJson = json_encode($offlinepoojaChargeFilterArr);
                $offlinepoojaTimeJson = json_encode($offlinepoojaTimeFilterArr);
                $offlinepoojaCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $offlinepoojaChargeFilterArr);
                if (!empty($offlinepoojaCommissionDataArr)) {
                    foreach ($offlinepoojaCommission as $key => $value) {
                        if (!array_key_exists($key, $offlinepoojaCommissionDataArr)) {
                            $offlinepoojaCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($offlinepoojaCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $offlinepoojaCommission)) {
                            unset($offlinepoojaCommissionDataArr[$key]);
                        }
                    }
                    ksort($offlinepoojaCommissionDataArr);
                    $offlinepoojaCommissionJson = json_encode($offlinepoojaCommissionDataArr);
                } else {
                    $offlinepoojaCommissionJson = json_encode($offlinepoojaCommission);
                }
            }
        }


        // consultation charge array
        $consultationCommissionData = AstrologerAstrologer::select('consultation_commission')->where('id', $id)->first();
        $consultationCommissionDataArr = json_decode($consultationCommissionData['consultation_commission'], true);
        $consultationChargeJson = null;
        $consultationCommissionJson = null;
        if ($request->consultation_charge) {
            $consultationChargeArr = array_combine($request->consultation_charge_id, $request->consultation_charge);
            $consultationChargeFilterArr = array_filter($consultationChargeArr, function ($value) {
                return !is_null($value);
            });
            if (count($consultationChargeFilterArr) > 0) {
                $consultationChargeJson = json_encode($consultationChargeFilterArr);
                $consultationCommission = array_map(function ($value) use ($request) {
                    if ($request->astro_type == 'freelancer') {
                        return '5';
                    } else {
                        return '0';
                    }
                }, $consultationChargeFilterArr);
                if (!empty($consultationCommissionDataArr)) {
                    foreach ($consultationCommission as $key => $value) {
                        if (!array_key_exists($key, $consultationCommissionDataArr)) {
                            $consultationCommissionDataArr[$key] = $value;
                        }
                    }
                    foreach ($consultationCommissionDataArr as $key => $value) {
                        if (!array_key_exists($key, $consultationCommission)) {
                            unset($consultationCommissionDataArr[$key]);
                        }
                    }
                    ksort($consultationCommissionDataArr);
                    $consultationCommissionJson = json_encode($consultationCommissionDataArr);
                } else {
                    $consultationCommissionJson = json_encode($consultationCommission);
                }
            }
        }

        //availability
        $sunday = [];
        foreach ($request->sunday_from as $sf_key => $sf) {
            if ($sf != null && $request->sunday_to[$sf_key] != null) {
                $sunday[$sf_key] = $sf . '-' . $request->sunday_to[$sf_key];
            }
        }

        $monday = [];
        foreach ($request->monday_from as $mf_key => $mf) {
            if ($mf != null && $request->monday_to[$mf_key] != null) {
                $monday[$mf_key] = $mf . '-' . $request->monday_to[$mf_key];
            }
        }

        $tuesday = [];
        foreach ($request->tuesday_from as $tuf_key => $tuf) {
            if ($tuf != null && $request->tuesday_to[$tuf_key] != null) {
                $tuesday[$tuf_key] = $tuf . '-' . $request->tuesday_to[$tuf_key];
            }
        }

        $wednesday = [];
        foreach ($request->wednesday_from as $w_key => $w) {
            if ($w != null && $request->wednesday_to[$w_key] != null) {
                $wednesday[$w_key] = $w . '-' . $request->wednesday_to[$w_key];
            }
        }

        $thursday = [];
        foreach ($request->thursday_from as $tf_key => $tf) {
            if ($tf != null && $request->thursday_to[$tf_key] != null) {
                $thursday[$tf_key] = $tf . '-' . $request->thursday_to[$tf_key];
            }
        }

        $friday = [];
        foreach ($request->friday_from as $ff_key => $ff) {
            if ($ff != null && $request->friday_to[$ff_key] != null) {
                $friday[$ff_key] = $ff . '-' . $request->friday_to[$ff_key];
            }
        }

        $saturday = [];
        foreach ($request->saturday_from as $stf_key => $stf) {
            if ($stf != null && $request->saturday_to[$stf_key] != null) {
                $saturday[$stf_key] = $stf . '-' . $request->saturday_to[$stf_key];
            }
        }

        $astrologer = AstrologerAstrologer::where('id', $id)->first();
        $astrologer->name = $request->name;
        // $astrologer->email = $request->email;
        // $astrologer->mobile_no = $request->mobile_no;

        //image
        if ($request->hasFile('image')) {
            $oldImagePath = storage_path('app/public/astrologers/' . $astrologer->image);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }

            $file = $request->file('image');
            $imageName = time() . '-astrologer' . $file->getClientOriginalName();
            $file->storeAs('public/astrologers', $imageName);
            $astrologer->image = $imageName;
        }

        //adhar front image
        if ($request->hasFile('adhar_front_image')) {
            $adharFrontOldImagePath = storage_path('app/public/astrologers/aadhar' . $astrologer->adharcard_front_image);
            if (File::exists($adharFrontOldImagePath)) {
                File::delete($adharFrontOldImagePath);
            }

            $adharFrontFile = $request->file('adhar_front_image');
            $adharFrontImageName = time() . '-aadharfront' . $adharFrontFile->getClientOriginalName();
            $adharFrontFile->storeAs('public/astrologers/aadhar', $adharFrontImageName);
            $astrologer->adharcard_front_image = $adharFrontImageName;
        }

        //adhar back image
        if ($request->hasFile('adhar_back_image')) {
            $adharBackOldImagePath = storage_path('app/public/astrologers/aadhar' . $astrologer->adharcard_back_image);
            if (File::exists($adharBackOldImagePath)) {
                File::delete($adharBackOldImagePath);
            }

            $adharBackFile = $request->file('adhar_back_image');
            $adharBackImageName = time() . '-aadharback' . $adharBackFile->getClientOriginalName();
            $adharBackFile->storeAs('public/astrologers/aadhar', $adharBackImageName);
            $astrologer->adharcard_back_image = $adharBackImageName;
        }

        //pancard image
        if ($request->hasFile('pancard_image')) {
            $pancardOldImagePath = storage_path('app/public/astrologers/pancard' . $astrologer->pancard_image);
            if (File::exists($pancardOldImagePath)) {
                File::delete($pancardOldImagePath);
            }

            $pancardFile = $request->file('pancard_image');
            $pancardImageName = time() . '-pancard' . $pancardFile->getClientOriginalName();
            $pancardFile->storeAs('public/astrologers/pancard', $pancardImageName);
            $astrologer->pancard_image = $pancardImageName;
        }

        //bank passbook image
        if ($request->hasFile('bank_passbook_image')) {
            $bankPassbookOldImagePath = storage_path('app/public/astrologers/bankpassbook' . $astrologer->bank_passbook_image);
            if (File::exists($bankPassbookOldImagePath)) {
                File::delete($bankPassbookOldImagePath);
            }

            $bankPassbookFile = $request->file('bank_passbook_image');
            $bankPassbookImageName = time() . '-bankpassbook' . $bankPassbookFile->getClientOriginalName();
            $bankPassbookFile->storeAs('public/astrologers/bankpassbook', $bankPassbookImageName);
            $astrologer->bank_passbook_image = $bankPassbookImageName;
        }

        $astrologer->gender = $request->gender;
        $astrologer->dob = $request->dob;
        $astrologer->pancard = $request->pancard;
        $astrologer->adharcard = $request->adharcard;
        // $astrologer->type = $request->type;
        $astrologer->salary = $request->salary;
        $astrologer->state = $request->state;
        $astrologer->city = $request->city;
        $astrologer->address = $request->address;
        $astrologer->pincode = $request->pincode;
        $astrologer->latitude = $request->latitude;
        $astrologer->longitude = $request->longitude;
        // $astrologer->primary_skills = $request->primary_skills;
        $astrologer->is_pandit_pooja_category = $request->is_pandit_pooja_category ? json_encode($request->is_pandit_pooja_category) : null;
        $astrologer->is_pandit_pooja = $poojaChargeJson;
        $astrologer->is_pandit_vippooja = $vipPoojaChargeJson;
        $astrologer->is_pandit_anushthan = $anushthanChargeJson;
        $astrologer->is_pandit_chadhava = $chadhavaChargeJson;
        $astrologer->is_pandit_offlinepooja = $offlinepoojaChargeJson;
        $astrologer->is_pandit_panda = $request->is_pandit_panda;
        $astrologer->is_pandit_gotra = $request->is_pandit_gotra;
        $astrologer->is_pandit_primary_mandir = $request->is_pandit_primary_mandir;
        $astrologer->is_pandit_primary_mandir_location = $request->is_pandit_primary_mandir_location;
        $astrologer->is_pandit_min_charge = $request->min_charge;
        $astrologer->is_pandit_max_charge = $request->max_charge;
        $astrologer->is_pandit_pooja_per_day = $request->pooja_per_day;
        $astrologer->is_pandit_live_stream_charge = $request->pandit_live_stream_charge;
        // $astrologer->is_pandit_live_stream_commission = empty($request->pandit_live_stream_charge) ? null : (!empty($request->pandit_live_stream_charge) && !empty($request->pandit_live_stream_commission) ? $request->pandit_live_stream_commission : 5);
        // if (!empty($poojaCommissionJson)) {
        $astrologer->is_pandit_pooja_commission = $poojaCommissionJson;
        // }
        // if (!empty($vipPoojaCommissionJson)) {
        $astrologer->is_pandit_vippooja_commission = $vipPoojaCommissionJson;
        // }
        // if (!empty($anushthanCommissionJson)) {
        $astrologer->is_pandit_anushthan_commission = $anushthanCommissionJson;
        // }
        // if (!empty($chadhavaCommissionJson)) {
        $astrologer->is_pandit_chadhava_commission = $chadhavaCommissionJson;
        // }
        // if (!empty($offlinepoojaCommissionJson)) {
        $astrologer->is_pandit_offlinepooja_commission = $offlinepoojaCommissionJson;
        // }
        $astrologer->is_pandit_pooja_time = $poojaTimeJson;
        $astrologer->is_pandit_vippooja_time = $vipPoojaTimeJson;
        $astrologer->is_pandit_anushthan_time = $anushthanTimeJson;
        $astrologer->is_pandit_chadhava_time = $chadhavaTimeJson;
        $astrologer->is_pandit_offlinepooja_time = $offlinepoojaTimeJson;
        if ($request->has('other_skills')) {
            $astrologer->other_skills = json_encode($request->other_skills);
        } else {
            $astrologer->other_skills = null;
        }
        $astrologer->category = json_encode($request->category);
        $astrologer->language = json_encode($request->language);
        $astrologer->is_astrologer_live_stream_charge = $request->live_stream_charge;
        $astrologer->is_astrologer_live_stream_commission = empty($request->live_stream_charge) ? null : (!empty($request->live_stream_charge) && !empty($request->live_stream_commission) ? $request->live_stream_commission : 5);
        $astrologer->is_astrologer_call_charge = $request->call_charge;
        $astrologer->is_astrologer_call_commission = empty($request->call_charge) ? null : (!empty($request->call_charge) && !empty($request->call_commission) ? $request->call_commission : 5);
        $astrologer->is_astrologer_chat_charge = $request->chat_charge;
        $astrologer->is_astrologer_chat_commission = empty($request->chat_charge) ? null : (!empty($request->chat_charge) && !empty($request->chat_commission) ? $request->chat_commission : 5);
        $astrologer->is_astrologer_report_charge = $request->report_charge;
        $astrologer->is_astrologer_report_commission = empty($request->report_charge) ? null : (!empty($request->report_charge) && !empty($request->report_commission) ? $request->report_commission : 5);
        $astrologer->consultation_charge = $consultationChargeJson;
        // if (!empty($consultationCommissionJson)) {
        $astrologer->consultation_commission = $consultationCommissionJson;
        // }
        if ($request->has('is_kundali_make')) {
            $astrologer->is_kundali_make = 1;
            $astrologer->kundali_make_charge = !empty($request->kundali_making_charge) ? $request->kundali_making_charge : 0;
            $astrologer->kundali_make_charge_pro = !empty($request->kundali_making_charge_pro) ? $request->kundali_making_charge_pro : 0;
            $astrologer->kundali_make_commission = !empty($request->kundali_making_charge) ? 5 : 0;
        } else {
            $astrologer->is_kundali_make = 0;
            $astrologer->kundali_make_charge = null;
            $astrologer->kundali_make_commission = null;
            $astrologer->kundali_make_charge_pro = null;
        }
        $astrologer->experience = $request->experience;
        $astrologer->daily_hours_contribution = $request->daily_hours_contribution;
        $astrologer->office_address = $request->office_address;
        $astrologer->highest_qualification = $request->highest_qualification;
        $astrologer->other_qualification = $request->other_qualification;
        $astrologer->college = $request->college;
        $astrologer->onboard_you = $request->onboard_you;
        $astrologer->interview_time = $request->interview_time;
        $astrologer->business_source = $request->business_source;
        $astrologer->learn_primary_skill = $request->learn_primary_skill;
        $astrologer->instagram = $request->instagram;
        $astrologer->facebook = $request->facebook;
        $astrologer->linkedin = $request->linkedin;
        $astrologer->youtube = $request->youtube;
        $astrologer->website = $request->website;
        $astrologer->min_earning = $request->min_earning;
        $astrologer->max_earning = $request->max_earning;
        $astrologer->bank_name = $request->bank_name;
        $astrologer->holder_name = $request->holder_name;
        $astrologer->branch_name = $request->branch_name;
        $astrologer->bank_ifsc = $request->bank_ifsc;
        $astrologer->account_no = $request->account_no;
        $astrologer->foreign_country = $request->foreign_country;
        $astrologer->working = $request->working;
        $astrologer->bio = $request->bio;
        $astrologer->qualities = $request->qualities;
        $astrologer->challenge = $request->challenge;
        $astrologer->repeat_question = $request->repeat_question;

        if ($astrologer->save()) {
            $avalability = Availability::where('astrologer_id', $id)->first();
            $avalability->sunday = json_encode($sunday);
            $avalability->monday = json_encode($monday);
            $avalability->tuesday = json_encode($tuesday);
            $avalability->wednesday = json_encode($wednesday);
            $avalability->thursday = json_encode($thursday);
            $avalability->friday = json_encode($friday);
            $avalability->saturday = json_encode($saturday);
            $avalability->save();
            Toastr::success(translate('astrologer_updated_successfully'));
            Helpers::editDeleteLogs('Astrologer', 'Astrologer', 'Update');
            return redirect()->route('admin.astrologers.manage.list');
        }
        Toastr::success(translate('unable_to_udpate_data'));
        return redirect()->back();
    }

    public function deleteManage(Request $request)
    {
        $delete = AstrologerAstrologer::where('id', $request->id)->delete();
        if ($delete) {
            Toastr::success(translate('astrologer_deleted_successfully'));
            Helpers::editDeleteLogs('Astrologer', 'Astrologer', 'Delete');
            return redirect()->route('admin.astrologers.manage.list');
        }
        Toastr::success(translate('unable_to_delete_data'));
        return redirect()->back();
    }

    public function statusManage(Request $request)
    {
        $status = AstrologerAstrologer::where('id', $request->id)->update(['status' => $request->status]);
        if ($status) {
            Toastr::success(translate('status_updated_successfully'));
            if ($request->status == 0) {
                return redirect()->route('admin.astrologers.pending.list');
            } else if ($request->status == 1) {
                return redirect()->route('admin.astrologers.manage.list');
            } else if ($request->status == 2) {
                return redirect()->route('admin.astrologers.block.list');
            }
        }
    }


    // pending functions---------------------------------------------
    public function pending_list()
    {
        $pending = AstrologerAstrologer::where('status', 0)->with('primarySkill')->orderBy('created_at', 'desc')->paginate(10);
        return view(Astrologer::PENDING_LIST[VIEW], compact('pending'));
    }


    // review functions---------------------------------------------
    public function review_list()
    {
        return view(Astrologer::REVIEW_LIST[VIEW]);
    }


    // gift functions---------------------------------------------
    public function gift_list()
    {
        return view(Astrologer::GIFT_LIST[VIEW]);
    }


    // skill functions---------------------------------------------
    public function skill_list(Request $request)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $skills = $this->astroRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(Astrologer::SKILL_LIST[VIEW], [
            'skills' => $skills,
            'languages' => $languages,
            'defaultLanguage' => $defaultLanguage,
        ]);
    }

    public function add_skills(AstrologerSkillsAddRequest $request, AstrologersService $astroService): RedirectResponse
    {
        // dd($request->input());
        $dataArray = $astroService->getAddData(request: $request);
        // dd($dataArray);
        $saveSkill = $this->astroRepo->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\Astrologer\Skills', id: $saveSkill->id);
        Helpers::editDeleteLogs('Astrologer', 'Skill', 'Insert');
        Toastr::success(translate('Skills Successfully Added'));
        return back();
    }

    public function getSkillUpdateView(string|int $id): View|RedirectResponse
    {
        $skill = $this->astroRepo->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        return view(Astrologer::SKILL_UPDATE[VIEW], compact('skill', 'language', 'defaultLanguage'));
    }

    public function skillUpdate(AstrologerSkillsAddRequest $request, $id, AstrologersService $astrologerService): RedirectResponse
    {
        $skill = $this->astroRepo->getFirstWhere(params: ['id' => $id]);
        $dataArray = $astrologerService->getSkillUpdateData(request: $request, data: $skill);
        $this->astroRepo->update(id: $id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\Astrologer\Skills', id: $id);

        Toastr::success(translate('skill_updated_successfully'));
        Helpers::editDeleteLogs('Astrologer', 'Skill', 'Update');
        return redirect()->route('admin.astrologers.skill.list');
    }

    public function statusSkill(Request $request)
    {
        $status = Skills::where('id', $request->id)->update(['status' => $request->status]);
        if ($status) {
            return response()->json(['status' => 200]);
        }
        return response()->json(['status' => 400]);
    }



    // category functions ----------------------------------
    public function category_list(Request $request)
    {
        $categories = $this->astroCatRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request->get('searchValue'), dataLimit: getWebConfig(name: 'pagination_limit'));
        return view(Astrologer::CATEGORY_LIST[VIEW], compact('categories'));
    }

    public function getCategoryAddView(): View
    {
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        return view(Astrologer::CATEGORY_ADD[VIEW], compact('language', 'defaultLanguage'));
    }

    public function addCategory(AstrologerCategoryAddRequest $request, AstrologersService $astrologerService): RedirectResponse
    {
        $dataArray = $astrologerService->getCategoryAddData(request: $request);
        $savedAttributes = $this->astroCatRepo->add(data: $dataArray);
        $this->translationRepo->add(request: $request, model: 'App\Models\AstrologerCategory', id: $savedAttributes->id);

        Toastr::success(translate('category_added_successfully'));
        Helpers::editDeleteLogs('Astrologer', 'Category', 'Insert');
        return redirect()->route('admin.astrologers.category.list');
    }

    public function getCategoryUpdateView(string|int $id): View|RedirectResponse
    {
        $category = $this->astroCatRepo->getFirstWhere(params: ['id' => $id], relations: ['translations']);
        $language = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        return view(Astrologer::CATEGORY_UPDATE[VIEW], compact('category', 'language', 'defaultLanguage'));
    }

    public function categoryUpdate(AstrologerCategoryUpdateRequest $request, $id, AstrologersService $astrologerService): RedirectResponse
    {
        $category = $this->astroCatRepo->getFirstWhere(params: ['id' => $id]);
        $dataArray = $astrologerService->getCategoryUpdateData(request: $request, data: $category);
        $this->astroCatRepo->update(id: $id, data: $dataArray);
        $this->translationRepo->update(request: $request, model: 'App\Models\AstrologerCategory', id: $id);

        Toastr::success(translate('category_updated_successfully'));
        Helpers::editDeleteLogs('Astrologer', 'Category', 'Update');
        return redirect()->route('admin.astrologers.category.list');
    }

    public function statusCategory(Request $request)
    {
        $status = AstrologerCategory::where('id', $request->id)->update(['status' => $request->status]);
        if ($status) {
            return response()->json(['status' => 200]);
        }
        return response()->json(['status' => 400]);
    }


    // comission functions ------------------------------------
    public function comission_list()
    {
        return view(Astrologer::COMISSION_LIST[VIEW]);
    }
    public function order_data(Request $request)
    {
        // dd($request->all());
        $serviceId = $request->serviceId;
        $bookingDate = $request->bookingDate;
        $orderDateGet = Service_order::where('booking_date', $bookingDate)
            ->where('service_id', $serviceId)
            ->with(['services', 'customers'])
            ->get();
        return response()->json(['data' => $orderDateGet]);
    }

    public function withdraw_list($status = null)
    {
        if (null == $status) {
            Toastr::success(('parameter_is_wrong'));
            return redirect()->back();
        }
        $withdrawList = AstrologerWithdraw::where('status', $status)->with('astrologer')->orderBy('created_at', 'desc')->paginate(10);
        if ($withdrawList) {
            return view('admin-views.astrologers.withdraw.list', compact('withdrawList'));
        }
        Toastr::success(('an error occurred'));
        return redirect()->back();
    }

    public function withdraw_approve(Request $request)
    {
        $update = AstrologerWithdraw::where('id', $request->id)->update(['status' => 1]);
        if ($update) {
            Toastr::success(('withdraw amount approved'));
            return redirect()->back();
        }
        Toastr::success(('an error occurred'));
        return redirect()->back();
    }

    public function withdraw_complete(Request $request)
    {
        $updateWithdrawBal = AstrologerWithdraw::where('id', $request->id)->update(['status' => 2]);
        if ($updateWithdrawBal) {
            Toastr::success(('withdraw amount completed'));
            return redirect()->back();
        }
        Toastr::success(('an error occurred'));
        return redirect()->back();
    }
    // Pandit Trasection details for the pooja
    public function pandit_transection(Request $request)
    {
        $query = PanditTransectionPooja::with([
            'serviceOrder.services',
            'serviceOrder.vippoojas',
            'chadhavaOrder.chadhava',
        ]);

        // Filter by type if present
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by service_id and type
        if ($request->filled('service_id')) {
            $serviceId = $request->service_id;
            $type = $request->type;

            if (in_array($type, ['pooja', 'vip', 'anushthan'])) {
                $query->whereHas('serviceOrder', function ($q) use ($serviceId) {
                    $q->where('service_id', $serviceId);
                });
            }

            if ($type === 'chadhava') {
                $query->whereHas('chadhavaOrder', function ($q) use ($serviceId) {
                    $q->where('service_id', $serviceId);
                });
            }
        }
        $pandit = $query->get();

        return view('admin-views.astrologers.transection.list', compact('pandit'));
    }

}