<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Contracts\Repositories\DonateCategoryRepositoryInterface;
use App\Contracts\Repositories\DonateTrustAdsRepositoryInterface;
use App\Contracts\Repositories\DonateTrustRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\DonateAds;
use App\Models\DonateAllTransaction;
use App\Models\DonateTrust;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\DonateLeads;

class DonateController extends Controller
{

    public function __construct(
        private readonly DonateCategoryRepositoryInterface     $categoryRepo,
        private readonly DonateTrustAdsRepositoryInterface     $AdsRepo,
        private readonly DonateTrustRepositoryInterface     $trustRepo,
    ) {}

    public function getCategory()
    {
        $donate_translation = [];
        $getData = $this->categoryRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['types' => 'category', 'status' => 1], dataLimit: 'all');
        if (!empty($getData) && count($getData) > 0) {
            foreach ($getData as $ke => $img) {
                $translations = $img->translations()->pluck('value', 'key')->toArray();

                $donate_translation[$ke]["en_name"] = $img['name'];
                $donate_translation[$ke]["hi_name"] = $translations['name'];
                $donate_translation[$ke]['id'] =  $img['id'];
                $donate_translation[$ke]['slug'] = $img['slug'];
                $donate_translation[$ke]['image'] =  getValidImage(path: 'storage/app/public/donate/category/' . $img['image'], type: 'product');;
            }
            return response()->json(['status' => 1, 'message' => 'Category List', 'recode' => count($donate_translation), 'data' => $donate_translation], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Category', 'recode' => 0, 'data' => []], 400);
        }
    }

    public function getPurpose()
    {
        $getData = $this->categoryRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['types' => 'porpose', 'status' => 1], dataLimit: 'all');
        $donate_translation = [];
        if (!empty($getData) && count($getData) > 0) {
            foreach ($getData as $ke => $img) {
                $translations = $img->translations()->pluck('value', 'key')->toArray();

                $donate_translation[$ke]["en_name"] = $img['name'];
                $donate_translation[$ke]["hi_name"] = $translations['name'];
                $donate_translation[$ke]['id'] =  $img['id'];
                $donate_translation[$ke]['slug'] = $img['slug'];
                $donate_translation[$ke]['image'] =  getValidImage(path: 'storage/app/public/donate/purpose/' . $img['image'], type: 'product');
            }
            return response()->json(['status' => 1, 'message' => 'Purpose List', 'recode' => count($donate_translation), 'data' => $donate_translation], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Purpose', 'recode' => 0, 'data' => []], 400);
        }
    }

    public function DonateTrust(Request $request)
    {
        $request->validate([
            'type' => 'required|in:ads,trust,ads_inhouse',
        ]);
        $type = $request->get('type');
        if ($request->input('type') === 'trust') {
            $request->validate([
                'trust_category_id' => 'required',
            ]);

            $gettrustData = $this->trustRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['category_id' => $request->get('trust_category_id'), 'is_approve' => 1, 'status' => 1], relations: ['translations'], dataLimit: 'all');
            if (!empty($gettrustData) && count($gettrustData) > 0) {
                $donate_translation = [];
                foreach ($gettrustData as $key => $value) {
                    $translations = $value->translations()->pluck('value', 'key')->toArray();
                    $donate_translation[$key]['en_trust_name'] =  $value['trust_name'];
                    $donate_translation[$key]['hi_trust_name'] =  ($translations['trust_name'] ?? "");
                    $donate_translation[$key]['en_description'] =  $value['description'];
                    $donate_translation[$key]['hi_description'] =  ($translations['description'] ?? "");
                    $donate_translation[$key]['id'] =  $value['id'];
                    // $images = json_decode($value['gallery_image'], true);
                    // $donate_translation[$key]['image'] = getValidImage(path: 'storage/app/public/donate/trust/' . $images[0] ?? '', type: 'product');
                    $donate_translation[$key]['image'] = getValidImage(path: 'storage/app/public/donate/trust/' . $value['theme_image'] ?? '', type: 'product');
                }
                return response()->json(['status' => 1, 'message' => 'Trust List', 'recode' => count($donate_translation), 'data' => $donate_translation], 200);
            } else {
                return response()->json(['status' => 0, 'message' => 'Not Found Trust List', 'recode' => 0, 'data' => []], 400);
            }
        } else {
            if ($request->input('type') === 'ads_inhouse') {
                $getadsData = $this->AdsRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['type' => 'inhouse', 'purpura_id' => $request->get('trust_category_id'), 'is_approve' => 1, 'status' => 1], relations: ['translations', 'category', 'Trusts', 'Purpose'], dataLimit: 'all');
            } else {
                $getadsData = $this->AdsRepo->getListWhere(orderBy: ['id' => 'desc'], filters: ['purpura_id' => $request->get('trust_category_id'), 'is_approve' => 1, 'status' => 1], relations: ['translations', 'category', 'Trusts', 'Purpose'], dataLimit: 'all');
            }
            if (!empty($getadsData) && count($getadsData) > 0) {
                $donate_translation = [];
                foreach ($getadsData as $key => $value) {
                    $translations = $value->translations()->pluck('value', 'key')->toArray();
                    $donate_translation[$key]['en_name'] =  $value['name'];
                    $donate_translation[$key]['hi_name'] =  ($translations['name'] ?? "");
                    $donate_translation[$key]['en_description'] =  $value['description'];
                    $donate_translation[$key]['hi_description'] =  ($translations['description'] ?? "");
                    $donate_translation[$key]['purpose_id'] =  $value['purpose_id'];
                    $donate_translation[$key]['id'] =  $value['id'];
                    $donate_translation[$key]['image'] = getValidImage(path: 'storage/app/public/donate/ads/' . $value['image'], type: 'product');
                }
                return response()->json(['status' => 1, 'message' => 'Ads List', 'recode' => count($donate_translation), 'data' => $donate_translation], 200);
            } else {
                return response()->json(['status' => 0, 'message' => 'Not Found Ads List', 'recode' => 0, 'data' => []], 400);
            }
        }
    }

    public function TrustGet(Request $request)
    {
        $languages = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $languages[0];
        $request->validate([
            'type' => 'required|in:ads,trust',
            'id' => 'required',
        ]);
        $type = $request->get('type');
        $id = $request->get('id');
        if ($request->input('type') === 'trust') {
            //$this->trustRepo->getFirstWhere(params: ['id' => $request->get('id'), 'is_approve' => 1, 'status' => 1], relations: ['translations']);
            $gettrustData = DonateTrust::where("id", $request->get('id'))->where(['is_approve' => 1, 'status' => 1])->with(['translations'])->first();
            if (!empty($gettrustData)) {
                $donate_translation = [];
                $translations = $gettrustData->translations()->pluck('value', 'key')->toArray();
                $donate_translation['en_trust_name'] =  $gettrustData['trust_name'];
                $donate_translation['hi_trust_name'] =  $translations['trust_name'];
                $donate_translation['en_description'] =  $gettrustData['description'];
                $donate_translation['hi_description'] =  $translations['description'];
                $donate_translation['id'] =  $gettrustData['id'];
                if (!empty($gettrustData['gallery_image']) && json_decode($gettrustData['gallery_image'], true)) {
                    $images = json_decode($gettrustData['gallery_image'], true);
                    foreach ($images as $key => $img) {
                        $donate_translation['image'][$key] = getValidImage(path: 'storage/app/public/donate/trust/' . ($img ?? ''), type: 'product');
                    }
                }
                return response()->json(['status' => 1, 'message' => 'Trust List', 'recode' => 1, 'data' => $donate_translation], 200);
            } else {
                return response()->json(['status' => 0, 'message' => 'Not Found Trust List', 'recode' => 0, 'data' => []], 400);
            }
        } else {
            // $getadsData = $this->AdsRepo->getFirstWhere(params: ['id' => $request->get('id'), 'is_approve' => 1, 'status' => 1], relations: ['category', 'Trusts', 'Purpose']);
            $getadsData = DonateAds::where(['id' => $request->get('id'), 'is_approve' => 1, 'status' => 1])->with(['category', 'Trusts', 'Purpose'])->first();
            if (!empty($getadsData)) {
                $donate_translation = [];
                $translations = $getadsData->translations()->pluck('value', 'key')->toArray();
                $donate_translation['en_name'] =  $getadsData['name'];
                $donate_translation['hi_name'] =  $translations['name'];
                $donate_translation['en_description'] =  $getadsData['description'];
                $donate_translation['hi_description'] =  $translations['description'];

                $donate_translation['set_type'] =  ($getadsData['set_type'] ?? "");
                $donate_translation['set_amount'] =  ($getadsData['set_amount'] ?? "");
                $donate_translation['set_title'] =  ($getadsData['set_title'] ?? "");
                $donate_translation['set_number'] =  ($getadsData['set_number'] ?? "");
                $donate_translation['set_unit'] =  ($getadsData['set_unit'] ?? "");

                $donate_translation["en_trust_name"] = ($getadsData['trusts']['trust_name'] ?? "");

                $getTrust = \App\Models\DonateTrust::where('id', ($getadsData['trust_id'] ?? ""))->first();
                $trust_name = [];
                if (!empty($getTrust)) {
                    $trust_name = $getadsData['trusts']->translations()->pluck('value', 'key')->toArray();
                }
                $donate_translation["hi_trust_name"] = ($trust_name['trust_name'] ?? '');
                $donate_translation['id'] =  $getadsData['id'];
                $donate_translation['image'] = getValidImage(path: 'storage/app/public/donate/ads/' . $getadsData['image'], type: 'product');
                return response()->json(['status' => 1, 'message' => 'get Ads Data', 'recode' => 1, 'data' => $donate_translation], 200);
            } else {
                return response()->json(['status' => 0, 'message' => 'Not Found Ads Data', 'recode' => 0, 'data' => []], 400);
            }
        }
    }

    public function DonateAmount(Request $request)
    {

        $request->validate([
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
            'type' => 'required|in:ads,trust',
            'id' => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('type') == 'ads') {
                    if (!DonateAds::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                        $fail('The selected Id is invalid.');
                    }
                } else {
                    if (!DonateTrust::where('id', $value)->where('is_approve', 1)->where('status', 1)->exists()) {
                        $fail('The selected Id is invalid.');
                    }
                }
            },],
            'name' => 'required',
            'phone' => 'required',
            'amount' => 'required',
            'pan_card' => "nullable|numeric"
        ]);
        // dd($request->all());
        $userdata = User::where('id', $request->get('user_id'))->where('is_active', 1)->first();


        $trust_ids = 0;
        $ads_ids = 0;
        if ($request->get('type') == 'ads') {
            $ads_ids = $request->get('id');
            $getadsuse =  DonateAds::where('id', $ads_ids)->where('is_approve', 1)->where('status', 1)->first();
            if (!empty($getadsuse)) {
                $trust_ids = $getadsuse['trust_id'];
            }
        }
        if ($request->get('type') == 'trust') {
            $trust_ids = $request->get('id');
        }
        $AllTransaction = new DonateAllTransaction();
        $AllTransaction->type =  (($request->get('type') == 'trust') ? 'donate_trust' : 'donate_ads');
        $AllTransaction->user_id =  ($request->get('user_id') ?? "");
        $AllTransaction->user_name =  ($request->get('name') ?? "");
        $AllTransaction->user_phone =  ($request->get('phone') ?? "");
        $AllTransaction->pan_card =  ($request->get('pan_card') ?? "");
        $AllTransaction->trust_id =  $trust_ids;
        $AllTransaction->ads_id =  $ads_ids;
        $AllTransaction->amount =  ($request->get('amount') ?? "");
        $AllTransaction->save();

        $leads = DonateLeads::create([
            'amount' => $request->get('amount') ?? 0,
            'trust_id' => $trust_ids,
            'ads_id' => $ads_ids,
            'user_id' => $request->get('user_id') ?? '',
            'type' => $request->get('type') == 'trust' ? 'donate trust' : 'ads Donate',
            'status' => 0,
        ]);
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }

        $paymentReq = new PaymentRequest();
        $paymentReq->payer_id = $request->get('user_id');
        $paymentReq->receiver_id = '100';
        $paymentReq->payment_amount = $request->get('amount');
        $paymentReq->currency_code = $currency_code;
        $paymentReq->is_paid = 0;
        $paymentReq->payment_platform = 'app';
        $paymentReq->receiver_information = json_encode(['name' => 'receiver_name', "image" => "example.png"]);
        $paymentReq->payer_information = json_encode(["name" => $userdata['name'], "email" => $userdata['email'], "phone" => $userdata['phone'], "address" => '']);
        $paymentReq->additional_data = json_encode(["business_name" => "Mahakal.com", "business_logo" => "", "payment_mode" => "web", "leads_id" => $leads->id, "trust_id" => $trust_ids, "ads_id" => $ads_ids, "transaction_id" => $AllTransaction->id, "customer_id" => $request->get('user_id'), "payment_request_from" => "web"]);
        $paymentReq->save();

        DonateAllTransaction::where('id', $AllTransaction->id)->update(['payment_requests_id' => $paymentReq->id]);
        return response()->json(['status' => 1, 'message' => 'pay now', 'recode' => 1, 'data' => ['id' => $paymentReq->id]], 200);
    }

    public function DonateAmountUpdate(Request $request)
    {
        $request->validate([
            'id' => ['required', function ($attribute, $value, $fail) {
                if (!PaymentRequest::where('id', $value)->where('is_paid', 0)->exists()) {
                    $fail('Already Paid.');
                }
            },],
        ]);
        $getdata = PaymentRequest::find($request->get('id'));
        DonateAllTransaction::where('payment_requests_id',  $request->get('id'))->update(['amount' => $request->get('amount'), 'pan_card' => ($request->get('pan_card') ?? "")]);
        if (!empty($getdata['additional_data']) && json_decode($getdata['additional_data'], true)) {
            DonateLeads::where('id', (json_decode($getdata['additional_data'], true)['leads_id'] ?? ""))->update(['amount' => $request->get('amount')]);
        }
        PaymentRequest::where('id',  $request->get('id'))->update(['payment_amount' => $request->get('amount')]);
        return response()->json(['status' => 1, 'message' => 'amount update successfully', 'recode' => 1, 'data' => ['id' =>  $request->get('id')]], 200);
    }
    public function DonateAmountSuccess(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'amount' => 'required',
            'transaction_id' => "required",
            'payment_method' => "required",
            'wallet_type' => 'required|in:0,1',
            'online_pay' => 'required_unless:transaction_id,wallet',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $getIdRequest = PaymentRequest::where('id', $request->get('id'))->first();
            if ($getIdRequest['additional_data'] && json_decode($getIdRequest['additional_data'], true)) {
                $additional_data = json_decode($getIdRequest['additional_data'], true);

                if ($request->wallet_type == 1 && ($request['online_pay'] ?? 0) > 0) {
                    User::where('id', $additional_data['customer_id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance + ' . $request['online_pay'])]);
                    $wallet_transaction = new \App\Models\WalletTransaction();
                    $wallet_transaction->user_id = $additional_data['customer_id'];
                    $wallet_transaction->transaction_id = (($request->transaction_id) ? $request->transaction_id : \Illuminate\Support\Str::uuid());
                    $wallet_transaction->reference = 'add_funds_to_wallet';
                    $wallet_transaction->transaction_type = 'add_fund';
                    $wallet_transaction->balance = User::where('id', $additional_data['customer_id'])->first()['wallet_balance'];
                    $wallet_transaction->credit = $request['online_pay'];
                    $wallet_transaction->save();
                }
                $additional_data['leads_id'];

                $admin_commission = 0;
                $final_amount = ($request->amount ?? 0); //$getIdRequest['payment_amount'];

                $trustData = DonateTrust::where('id', $additional_data['trust_id'])->first();
                $AdsData = DonateAds::where('id', $additional_data['ads_id'])->first();
                if (!empty($additional_data['trust_id'])) {
                    if (!empty($request->ads_id) && $request->ads_id > 0) {
                        if (!empty($AdsData) && isset($AdsData['admin_commission']) && $AdsData['admin_commission'] > 0) {
                            $admin_commission = ((($request->amount ?? 0) * $AdsData['admin_commission']) / 100);
                            $final_amount = (($request->amount ?? 0) - $admin_commission);
                        } else {
                            $admin_commission = ((($request->amount ?? 0) * $trustData['ad_commission']) / 100);
                            $final_amount = (($request->amount ?? 0) - $admin_commission);
                        }
                    } else {
                        $admin_commission = ((($request->amount ?? 0) * $trustData['donate_commission']) / 100);
                        $final_amount = (($request->amount ?? 0) - $admin_commission);
                    }
                }
                $user = User::where('id', $additional_data['customer_id'])->first();
                if ($request->wallet_type == 1) {
                    if ($user['wallet_balance'] >= $request['amount']) {
                        User::where('id', $user['id'])->update(['wallet_balance' => \Illuminate\Support\Facades\DB::raw('wallet_balance - ' . $request['amount'])]);
                        DonateLeads::where('id', $additional_data['leads_id'])->update(['status' => 1]);
                        $wallet_transaction = new \App\Models\WalletTransaction();
                        $wallet_transaction->user_id = $additional_data['customer_id'];
                        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                        $wallet_transaction->reference = 'Donate';
                        $wallet_transaction->transaction_type = 'donate';
                        $wallet_transaction->balance = User::where('id', $additional_data['customer_id'])->first()['wallet_balance'];
                        $wallet_transaction->debit = $request->amount;
                        $wallet_transaction->save();
                        $updateTransaction = [
                            'admin_commission' => $admin_commission,
                            'pan_card' => ($request->pan_card ?? ''),
                            'amount' => $request->amount,
                            'final_amount' => $final_amount,
                            'amount_status' => 1,
                            'transaction_id' => 'wallet'
                        ];
                        if (!empty($request['user_name'])) {
                            $updateTransaction['user_name'] = $request['user_name'] ?? "";
                        }
                        if (!empty($request['user_phone'])) {
                            $updateTransaction['user_phone'] = $request['user_phone'] ?? "";
                        }
                        DonateAllTransaction::where('id', $additional_data['transaction_id'])->update($updateTransaction);
                        \Illuminate\Support\Facades\DB::commit();
                        // return response()->json(['status' => 1, 'message' => "Donate Successfully", 'data' => []], 200);
                    } else {
                        return response()->json(['status' => 0, 'message' => 'please wallet Amount Check', 'data' => []], 200);
                    }
                } else {
                    $updateTransaction = [
                        'admin_commission' => $admin_commission,
                        'pan_card' => ($request->pan_card ?? ''),
                        'amount' => $request->amount,
                        'final_amount' => $final_amount,
                        'amount_status' => 1,
                        'transaction_id' => $request->get('transaction_id')
                    ];
                    if (!empty($request['user_name'])) {
                        $updateTransaction['user_name'] = $request['user_name'] ?? "";
                    }
                    if (!empty($request['user_phone'])) {
                        $updateTransaction['user_phone'] = $request['user_phone'] ?? "";
                    }
                    DonateAllTransaction::where('id', $additional_data['transaction_id'])->update($updateTransaction);
                    DonateLeads::where('id', $additional_data['leads_id'])->update(['status' => 1]);
                    \Illuminate\Support\Facades\DB::commit();
                }
            }
            PaymentRequest::where('id', $request->get('id'))->update(['transaction_id' => $request->get('transaction_id'), 'payment_method' => $request->get('payment_method'), 'is_paid' => 1]);
            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['status' => 1, 'message' => 'Donate Successfully', 'recode' => 1, 'data' => []], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'An error occurred: ' . $e->getMessage(), 'data' => []], 200);
        }
    }

    public function DonateOrder(Request $request)
    {
        $request->validate([
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
        ]);
        if (!empty($request->id)) {
            $getData = DonateAllTransaction::where('id', $request->id)->where('user_id', $request->user_id)->with(['getTrust', 'adsTrust'])->first();
            $orderList['id'] = $getData['id'];
            $orderList['order_id'] = $getData['trans_id'];
            $orderList['type'] = $getData['type'];
            $orderList['amount'] = $getData['amount'];
            $orderList['amount_status'] = $getData['amount_status'];
            $orderList['date'] = date('d-m-Y h:i:s A', strtotime($getData['created_at']));
            $gettrans_ads = [];
            if (!empty($getData['adsTrust'])) {
                $gettrans_ads = $getData['adsTrust']->translations()->pluck('value', 'key')->toArray();
            }
            $orderList['en_ads_name'] = ($getData['adsTrust']['name'] ?? "");
            $orderList['hi_ads_name'] = ($gettrans_ads['name'] ?? "");

            $gettrans_trust = [];
            if (!empty($getData['getTrust'])) {
                $gettrans_trust = $getData['getTrust']->translations()->pluck('value', 'key')->toArray();
            }
            $orderList['en_trust_name'] = ($getData['getTrust']['trust_name'] ?? "");
            $orderList['hi_trust_name'] = ($gettrans_trust['trust_name'] ?? "");
            $orderList['invoice_url'] = url('api/v1/donate/invoice/' . $getData['id'] ?? '');
            if (empty($getData['ertiga_certificate'] ?? '')) {
                $this->create_donate_cetificate($getData['id']);
                $orderList['ertiga_certificate'] = getValidImage(path: 'storage/app/public/donate/certificate/' . 'ertiga_' . $getData['trans_id'] . '.jpg', type: 'product');
            } else {
                $orderList['ertiga_certificate'] = getValidImage(path: 'storage/app/public/donate/certificate/' . ($getData['ertiga_certificate'] ?? ''), type: 'product');
            }
            if ($getData['type'] == 'donate_trust') {
                $orderList['image'] =  getValidImage(path: 'storage/app/public/donate/trust/' . $getData['getTrust']['theme_image'], type: 'product');;
            } else {
                $orderList['image'] =  getValidImage(path: 'storage/app/public/donate/ads/' . $getData['adsTrust']['image'], type: 'product');;
            }
        } else {
            $getData = DonateAllTransaction::where('user_id', $request->user_id)->with(['getTrust', 'adsTrust'])->orderBy('id', "desc")
                ->get();
            $orderList = [];
            if ($getData) {
                foreach ($getData as $key => $value) {
                    $orderList[$key]['id'] = $value['id'];
                    $orderList[$key]['order_id'] = $value['trans_id'];
                    $orderList[$key]['type'] = $value['type'];
                    $orderList[$key]['amount'] = $value['amount'];
                    $orderList[$key]['amount_status'] = $value['amount_status'];
                    $orderList[$key]['date'] = date('d-m-Y h:i:s A', strtotime($value['created_at']));
                    $gettrans_ads = [];
                    if (!empty($value['adsTrust'])) {
                        $gettrans_ads = $value['adsTrust']->translations()->pluck('value', 'key')->toArray();
                    }
                    $orderList[$key]['en_ads_name'] = ($value['adsTrust']['name'] ?? "");
                    $orderList[$key]['hi_ads_name'] = ($gettrans_ads['name'] ?? "");

                    $gettrans_trust = [];
                    if (!empty($value['getTrust'])) {
                        $gettrans_trust = $value['getTrust']->translations()->pluck('value', 'key')->toArray();
                    }
                    $orderList[$key]['en_trust_name'] = ($value['getTrust']['trust_name'] ?? "");
                    $orderList[$key]['hi_trust_name'] = ($gettrans_trust['trust_name'] ?? "");
                    if ($value['type'] == 'donate_trust') {
                        $orderList[$key]['image'] =  getValidImage(path: 'storage/app/public/donate/trust/' . $value['getTrust']['theme_image'], type: 'product');;
                    } else {
                        $orderList[$key]['image'] =  getValidImage(path: 'storage/app/public/donate/ads/' . $value['adsTrust']['image'], type: 'product');;
                    }
                }
            }
        }
        if (!empty($orderList) && count($orderList) > 0) {
            return response()->json(['status' => 1, 'message' => 'Donate Successfully', 'recode' => count($orderList), 'data' => $orderList], 200);
        }
        return response()->json(['status' => 0, 'message' => 'Not Found Data', 'recode' => 0, 'data' => []], 400);
    }

    public function DonateInvoice(Request $request, $id)
    {
        $companyPhone = getWebConfig(name: 'company_phone');
        $companyEmail = getWebConfig(name: 'company_email');
        $companyName = getWebConfig(name: 'company_name');
        $companyWebLogo = getWebConfig(name: 'company_web_logo');
        $orderData = DonateAllTransaction::where('id', $id)->with(['users', 'getTrust', 'adsTrust'])->first();
        if ($orderData) {
            $mpdf_view = \Illuminate\Support\Facades\View::make('web-views.donate.invoice', compact('orderData'));
            \App\Utils\Helpers::gen_mpdf($mpdf_view, 'donate_order_', $orderData['id']);
            return response()->json(["status" => 1, "message" => "Invoice generated successfully."], 200);
        } else {
            return response()->json(["status" => 0, "message" => "Invoice generated Failed."], 400);
        }
    }

    public function TwoalACertificate(Request $request)
    {
        $orderData = DonateAllTransaction::where('id', $request->id)->with(['users', 'getTrust', 'adsTrust'])->first();

        if (!empty($orderData['pan_card'])) {
            $mpdf_view = \Illuminate\Support\Facades\View::make('web-views.donate.eighty-g-certificate', compact('orderData'));
            \App\Utils\Helpers::gen_mpdf($mpdf_view, '80G_', $request->id);
            return response()->json(["status" => 1, "message" => "80G generated successfully."], 200);
        } else {
            return response()->json(["status" => 0, "message" => "User Didn't Provide Pan-Card."], 400);
        }
    }

    static public function create_donate_cetificate($id)
    {
        $getData = DonateAllTransaction::where('id', $id)->with(['getTrust', 'adsTrust'])->first();
        $certificate = \Intervention\Image\Facades\Image::make(public_path('assets/back-end/img/certificate/format/ertiga-certificate-format.png'));
        $imageWidth = $certificate->width();
        $imageHeight = $certificate->height();
        $centerX = $imageWidth / 2;
        $centerY = 730;
        $certificate->text(ucwords($getData['user_name']), $centerX, $centerY, function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(90);
            $font->color('#0000');
            $font->align('center');
            $font->valign('center');
        });

        $serviceName = wordwrap("Pan Card " . (strtoupper($getData['pan_card'])) . ", has made a voluntary donation of " . webCurrencyConverter((float) ($getData['amount'] ?? 0)) . " to " . ($getData['getTrust']['trust_name'] ?? "Mahakal.com Organization") . " on " . date('d M,Y h:i A', strtotime($getData['created_at'])) . " through " . (($getData['transaction_id'] == 'wallet') ? 'Wallet' : 'Online') . ".", 62, "\n", false);

        $certificate->text($serviceName, $centerX, ($centerY + 178), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(37);
            $font->color('#955a00');
            $font->align('center');
            $font->valign('center');
        });

        $certificate->text($getData['trans_id'], ($centerX - 240), ($centerY + 525), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(35);
            $font->color('#ad8429');
            $font->align('right');
        });

        $certificate->text(strtoupper($getData['pan_card']), ($centerX + 120), ($centerY + 525), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(35);
            $font->color('#ad8429');
        });


        $certificate->text(date('d M,Y h:i A', strtotime($getData['created_at'])), ($centerX - 100), ($centerY + 650), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(35);
            $font->color('#ad8429');
            $font->align('right');
        });

        $certificate->text((($getData['transaction_id'] == 'wallet') ? 'Wallet' : 'Online'), ($centerX + 170), ($centerY + 625), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(35);
            $font->color('#ad8429');
        });

        $certificate->text(webCurrencyConverter((float) ($getData['amount'] ?? 0)), ($centerX - 270), ($centerY + 745), function ($font) {
            $font->file(public_path('fonts/Roboto-Bold.ttf'));
            $font->size(35);
            $font->color('#ad8429');
            $font->align('right');
        });

        $certificate->text(Helpers::get_business_settings("company_email") ?? '', ($centerX - 385), ($centerY + 850), function ($font) {
            $font->file(public_path('fonts/Roboto-Black.ttf'));
            $font->size(40);
            $font->color('#ad8429');
            $font->align('center');
            $font->valign('top');
        });
        $certificate->text(\Illuminate\Support\Str::after(url('/'), '://'), ($centerX), ($centerY + 850), function ($font) {
            $font->file(public_path('fonts/Roboto-Black.ttf'));
            $font->size(40);
            $font->color('#ad8429');
            $font->align('center');
            $font->valign('top');
        });

        $certificate->text(Helpers::get_business_settings("company_phone") ?? '', ($centerX - 370), ($centerY + 970), function ($font) {
            $font->file(public_path('fonts/Roboto-Black.ttf'));
            $font->size(40);
            $font->color('#ad8429');
            $font->align('center');
            $font->valign('top');
        });

        $certificate->text(($getData['getTrust']['eighty_g_number'] ?? (Helpers::get_business_settings("eighty_g_number") ?? '')), ($centerX), ($centerY + 990), function ($font) {
            $font->file(public_path('fonts/Roboto-Black.ttf'));
            $font->size(26);
            $font->color('#ad8429');
            $font->align('center');
            $font->valign('top');
        });


        $certificate->text(($getData['getTrust']['trust_pan_card'] ?? (Helpers::get_business_settings("trust_pan_card") ?? '')), ($centerX + 290), ($centerY + 970), function ($font) {
            $font->file(public_path('fonts/Roboto-Black.ttf'));
            $font->size(40);
            $font->color('#ad8429');
            $font->align('left');
            $font->valign('top');
        });

        $certificatePath = 'app/public/donate/certificate/ertiga_' . $getData['trans_id'] . '.jpg';
        if (!file_exists(storage_path('app/public/donate/certificate'))) {
            mkdir(storage_path('app/public/donate/certificate'), 0777, true);
        }
        $certificate->save(storage_path($certificatePath));
        DonateAllTransaction::where('id', $getData['id'])->update(['ertiga_certificate' => 'ertiga_' . $getData['trans_id'] . '.jpg']);
        return response()->download(storage_path($certificatePath), 'ertiga_' . $getData['trans_id'] . '.jpg');
    }
}