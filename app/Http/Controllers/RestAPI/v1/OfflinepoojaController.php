<?php

namespace App\Http\Controllers\RestAPI\v1;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\OfflineLead;
use App\Models\OfflinepoojaCategory;
use App\Models\OfflinePoojaOrder;
use App\Models\OfflinepoojaRefundPolicy;
use App\Models\OfflinepoojaReview;
use App\Models\OfflinepoojaSchedule;
use App\Models\Package;
use App\Models\PoojaOffline;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OfflinepoojaController extends Controller
{
    public function category()
    {
        try {
            $category = OfflinepoojaCategory::where('status', 1)->with('translations')->get();
            if ($category) {
                $categoryList = [];
                foreach ($category as $key => $val) {
                    $translationKeys = ['name'];
                    $translate = $val->translations()->pluck('value', 'key')->toArray();
                    foreach ($translationKeys as $translationKey) {
                        $categoryList[$key]["en_{$translationKey}"] = ($val[$translationKey] ?? '');
                        $categoryList[$key]["hi_{$translationKey}"] = ($translate[$translationKey] ?? '');
                    }
                    $categoryList[$key]['id'] =  $val['id'];
                    $categoryList[$key]['image'] =  getValidImage(path: 'storage/app/public/offlinepooja/category/' . $val['image'], type: 'backend-product');
                }

                return response()->json(['status' => true, 'categoryList' => $categoryList], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'unable to get category'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'An error occurred', 'error' => $e], 400);
        }
    }

    public function list(Request $request)
    {
        try {
            if (!$request->has('type')) {
                return response()->json(['status' => false, 'message' => 'type is required']);
            }

            $query = PoojaOffline::query();
            if ($request->type == 'all') {
                $list = $query->select('id', 'slug', 'name', 'short_benifits', 'thumbnail')->where('status', 1)->get();
            } else {
                $list = $query->select('id', 'slug', 'name', 'short_benifits', 'thumbnail')->where('type', $request->type)->where('status', 1)->get();
            }
            if ($list) {
                $poojaList = [];
                foreach ($list as $key => $val) {
                    $translationKeys = ['name', 'short_benifits'];
                    $translate = $val->translations()->pluck('value', 'key')->toArray();
                    foreach ($translationKeys as $translationKey) {
                        $poojaList[$key]["en_{$translationKey}"] = ($val[$translationKey] ?? '');
                        $poojaList[$key]["hi_{$translationKey}"] = ($translate[$translationKey] ?? '');
                    }
                    $poojaList[$key]['id'] =  $val['id'];
                    $poojaList[$key]['slug'] =  $val['slug'];
                    $poojaList[$key]['thumbnail'] =  getValidImage(path: 'storage/app/public/offlinepooja/thumbnail/' . $val['thumbnail'], type: 'backend-product');
                }
                return response()->json(['status' => true, 'poojaList' => $poojaList], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'unable to get category'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'An error occurred', 'error' => $e], 400);
        }
    }

    public function details(Request $request)
    {
        try {
            if (!$request->has('slug')) {
                return response()->json(['status' => false, 'message' => 'slug is required']);
            }

            $details = PoojaOffline::where('slug', $request->slug)->first();
            if ($details) {
                $poojaDetails = [];
                $translationKeys = ['name', 'short_benifits', 'process', 'benefits', 'details', 'terms_conditions'];
                $translate = $details->translations()->pluck('value', 'key')->toArray();
                foreach ($translationKeys as $translationKey) {
                    $poojaDetails["en_{$translationKey}"] = ($details[$translationKey] ?? '');
                    $poojaDetails["hi_{$translationKey}"] = ($translate[$translationKey] ?? '');
                }
                $poojaDetails['id'] =  $details['id'];
                $poojaDetails['slug'] =  $details['slug'];
                $poojaDetails['thumbnail'] =  getValidImage(path: 'storage/app/public/offlinepooja/thumbnail/' . $details['thumbnail'], type: 'backend-product');
                $poojaDetails['count'] =  OfflinePoojaOrder::where('service_id', $details['id'])->count();

                $packages = json_decode($details->package_details, true);
                $servicePackages = [];
                if (is_array($packages)) {
                    foreach ($packages as $val) {
                        $packageId = $val['package_id'];
                        $packageModel = Package::find($packageId);
                        if ($packageModel) {
                            $packageTranslations = $packageModel->translations()->pluck('value', 'key')->toArray();
                            $servicePackages[] = [
                                'package_id' => $packageId,
                                'en_package_name' => $packageModel->title,
                                'hi_package_name' => $packageTranslations['title'] ?? null,
                                'person' => $packageModel->person,
                                'color' => $packageModel->color,
                                'en_description' => $packageModel->description,
                                'hi_description' => $packageTranslations['description'] ?? null,
                                'package_price' => $val['price'],
                                'package_percent' => $val['percent']
                            ];
                        }
                    }
                }

                $poojaDetails['package_details'] =  $servicePackages;

                return response()->json(['status' => true, 'poojaDetails' => $poojaDetails], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'unable to get category'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'An error occurred', 'error' => $e], 400);
        }
    }

    public function policy()
    {
        try {
            $refundPolicy = OfflinepoojaRefundPolicy::where('status', 1)->with('translations')->get();
            $schedulePolicy = OfflinepoojaSchedule::where('status', 1)->with('translations')->get();
            if ($refundPolicy && $schedulePolicy) {
                $refundList = [];
                $scheduleList = [];
                foreach ($refundPolicy as $key => $val) {
                    $refundTranslationKeys = ['message'];
                    $refundTranslate = $val->translations()->pluck('value', 'key')->toArray();
                    foreach ($refundTranslationKeys as $refundTranslationKey) {
                        $refundList[$key]["en_{$refundTranslationKey}"] = ($val[$refundTranslationKey] ?? '');
                        $refundList[$key]["hi_{$refundTranslationKey}"] = ($refundTranslate[$refundTranslationKey] ?? '');
                    }
                    $refundList[$key]['id'] =  $val['id'];
                    $refundList[$key]['days'] =  $val['days'];
                    $refundList[$key]['percent'] =  $val['percent'];
                }
                foreach ($schedulePolicy as $key => $val) {
                    $scheduleTranslationKeys = ['message'];
                    $scheduleTranslate = $val->translations()->pluck('value', 'key')->toArray();
                    foreach ($scheduleTranslationKeys as $scheduleTranslationKey) {
                        $scheduleList[$key]["en_{$scheduleTranslationKey}"] = ($val[$scheduleTranslationKey] ?? '');
                        $scheduleList[$key]["hi_{$scheduleTranslationKey}"] = ($scheduleTranslate[$scheduleTranslationKey] ?? '');
                    }
                    $scheduleList[$key]['id'] =  $val['id'];
                    $scheduleList[$key]['days'] =  $val['days'];
                    $scheduleList[$key]['percent'] =  $val['percent'];
                }

                return response()->json(['status' => true, 'refundList' => $refundList, 'scheduleList' => $scheduleList], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'unable to get category'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'An error occurred', 'error' => $e], 400);
        }
    }

    public function add_review(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $reviewStore = OfflinepoojaReview::where('order_id', $request->order_id)->first();
                    $reviewStore->astro_id = $request->astro_id;
                    $reviewStore->user_id = $userId;
                    $reviewStore->service_id = $request->service_id;
                    $reviewStore->comment = $request->comment;
                    $reviewStore->service_type = 'pooja';
                    $reviewStore->rating = $request->rating;
                    $reviewStore->is_edited = 1;
                    if ($reviewStore->save()) {
                        return response()->json(['status' => true, 'message' => 'Review stored successfully'], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to store review'], 200);
                }
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function get_review(Request $request)
    {
        if (!$request->has('serviceId')) {
            return response()->json(['status' => false, 'message' => 'Service Id is required'], 400);
        }

        $reviews = OfflinepoojaReview::where('service_id', $request->serviceId)
            ->where('status', 1)
            ->with(['userData'])
            ->get();

        $reviews->each(function ($item) {
            if ($item->userData) {
                $item->userData->image_url = $item->userData->image
                    ? url('storage/app/public/profile/' . $item->userData->image)
                    : null;
            }
        });

        return response()->json(['status' => true, 'review' => $reviews], 200);
    }

    public function coupon_list()
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $appliedServiceCoupons = OfflinePoojaOrder::where('customer_id', $userId)->pluck('coupon_code')->filter()->toArray();
                    $list = Coupon::where('coupon_type', 'offlinepooja')->where('status', 1)->where('limit', '>', 0)->where('start_date', '<=', date("Y-m-d"))->where('expire_date', '>=', date("Y-m-d"))->whereNotIn('code', $appliedServiceCoupons)->get();
                    if ($list) {
                        return response()->json(['status' => true, 'list' => $list]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Unable to find coupon list']);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'User not found'], 401);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function coupon_apply(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $appliedServiceCoupon = OfflinePoojaOrder::where('customer_id', $userId)->where('coupon_code', $request->coupon_code)->exists();
                    if (!$appliedServiceCoupon) {
                        $coupon = Coupon::where('coupon_type', 'offlinepooja')->where('status', 1)->where('limit', '>', 0)->where('start_date', '<=', date("Y-m-d"))->where('expire_date', '>=', date("Y-m-d"))->where('code', $request->coupon_code)->first();
                        if ($coupon) {
                            if ($coupon['coupon_type'] == 'offlinepooja') {
                                return response()->json(['status' => true, 'data' => $coupon]);
                            } else {
                                return response()->json(['status' => false, 'message' => 'Coupon is invalid']);
                            }
                        } else {
                            return response()->json(['status' => false, 'message' => 'Coupon limit exceeded or expired']);
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'You have already applied this coupon']);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'User not found'], 401);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function lead_store(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $leadLastId = OfflineLead::select('id')->latest()->first();
                    if (!empty($leadLastId['id'])) {
                        $leadNo = 'OPLN' . (100000 + $leadLastId['id'] + 1);
                    } else {
                        $leadNo = 'OPLN' . (100001);
                    }

                    $packageData = Package::where('id', $request->input('package_id'))->first();
                    $cust_details = [
                        'pooja_id' => $request->input('service_id'),
                        'lead_no' => $leadNo,
                        'package_id' => $request->input('package_id'),
                        'package_name' => $packageData['title'],
                        'noperson' => $packageData['person'],
                        'package_main_price' => $request->input('package_main_price'),
                        'package_price' => $request->input('package_price'),
                        'person_name' => $request->input('person_name'),
                        'person_phone' => $request->input('person_phone'),
                    ];
                    $store = OfflineLead::create($cust_details);
                    if ($store) {
                        return response()->json(['status' => true, 'message' => 'Lead stored successfully', 'lead_id' => $store->id], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to store lead detail'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function place_order(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $orderId = '';
                    $orderData = OfflinePoojaOrder::select('id')->latest()->first();
                    if (!empty($orderData['id'])) {
                        $orderId = 'OP' . (100000 + $orderData['id'] + 1);
                    } else {
                        $orderId = 'OP' . (100001);
                    }

                    if ($request->transection_amount > 0) {
                        if ($request->wallet_balance > 0) {
                            $wallet_transaction = new WalletTransaction;
                            $wallet_transaction->user_id = $request->customer_id;
                            $wallet_transaction->transaction_id = \Str::uuid();
                            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                            $wallet_transaction->reference = 'offline pooja order payment';
                            $wallet_transaction->balance = 0.00;
                            $wallet_transaction->debit = $request->wallet_balance;
                            $wallet_transaction->save();
                            User::where('id', $request->customer_id)->update(['wallet_balance' => 0]);
                        }
                        $serviceOrderAdd = new OfflinePoojaOrder;
                        $serviceOrderAdd->customer_id = $request->customer_id;
                        $serviceOrderAdd->service_id = $request->service_id;
                        $serviceOrderAdd->type = PoojaOffline::where('id', $request->service_id)->value('type');
                        $serviceOrderAdd->leads_id = $request->leads_id;
                        $serviceOrderAdd->package_id = $request->package_id;
                        $serviceOrderAdd->package_main_price = $request->package_main_price;
                        $serviceOrderAdd->package_price = $request->package_price;
                        $serviceOrderAdd->order_id = $orderId;
                        $serviceOrderAdd->coupon_amount = $request->coupon_amount;
                        $serviceOrderAdd->coupon_code = $request->coupon_code;
                        $serviceOrderAdd->payment_id = $request->payment_id;
                        $serviceOrderAdd->wallet_amount = $request->wallet_balance ?? 0;
                        $serviceOrderAdd->transection_amount = $request->transection_amount;
                        $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                        $serviceOrderAdd->pay_amount = $request->final_amount;
                        $serviceOrderAdd->remain_amount = $request->remain_amount;
                        $serviceOrderAdd->remain_amount_status = $request->remain_amount_status;
                        $orderSave = $serviceOrderAdd->save();
                        OfflineLead::where('id', $request->leads_id)->update([
                            'status' => 0,
                            'payment_status' => 'Complete',
                            'order_id' => $orderId,
                        ]);
                        if ($orderSave) {
                            return response()->json(['status' => true, 'message' => 'order placed successfully', 'orderId' => $orderId], 200);
                        }
                    } else {
                        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
                        $wallet_remain_amount = $wallet['wallet_balance'] - $request->wallet_balance;
                        $wallet_transaction = new WalletTransaction;
                        $wallet_transaction->user_id = $request->customer_id;
                        $wallet_transaction->transaction_id = \Str::uuid();
                        $wallet_transaction->reference = 'offline pooja order payment';
                        $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                        $wallet_transaction->balance = $wallet_remain_amount;
                        $wallet_transaction->debit = $request->wallet_balance;
                        $wallet_transaction->save();
                        User::where('id', $request['customer_id'])->update(['wallet_balance' => $wallet_remain_amount]);

                        // Service Transection Details
                        $serviceOrderAdd = new OfflinePoojaOrder();
                        $serviceOrderAdd->customer_id = $request->customer_id;
                        $serviceOrderAdd->service_id = $request->service_id;
                        $serviceOrderAdd->type = PoojaOffline::where('id', $request->service_id)->value('type');
                        $serviceOrderAdd->leads_id = $request->leads_id;
                        $serviceOrderAdd->package_id = $request->package_id;
                        $serviceOrderAdd->package_main_price = $request->package_main_price;
                        $serviceOrderAdd->package_price = $request->package_price;
                        $serviceOrderAdd->pay_amount = $request->final_amount;
                        $serviceOrderAdd->remain_amount = $request->remain_amount;
                        $serviceOrderAdd->remain_amount_status = $request->remain_amount_status;
                        $serviceOrderAdd->wallet_amount = $request->wallet_balance;
                        $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                        $serviceOrderAdd->order_id = $orderId;
                        $serviceOrderAdd->coupon_amount = $request->coupon_amount;
                        $serviceOrderAdd->coupon_code = $request->coupon_code;
                        $orderSave = $serviceOrderAdd->save();

                        // dd($serviceOrderAdd);
                        OfflineLead::where('id', $request->leads_id)->update([
                            'status' => 0,
                            'payment_status' => 'Complete',
                            'order_id' => $orderId,
                        ]);

                        if ($orderSave) {
                            return response()->json(['status' => true, 'message' => 'order placed successfully', 'orderId' => $orderId], 200);
                        }
                    }

                    return response()->json(['status' => false, 'message' => 'Unable to store lead detail'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function add_user_detail(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $store = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
                    $store->venue_address = $request->venue_address;
                    $store->state = $request->state;
                    $store->city = $request->city;
                    $store->pincode = $request->pincode;
                    $store->latitude = $request->latitude;
                    $store->longitude = $request->longitude;
                    $store->booking_date = $request->booking_date;
                    $store->landmark = $request->landmark;
                    $store->is_edited = 1;
                    $serviceId = $store->service_id;
                    if ($store->save()) {
                        OfflinepoojaReview::create([
                            'order_id' => $request->order_id,
                            'user_id' => $userId,
                            'service_id' => $serviceId,
                            'service_type' => 'pooja',
                            'rating' => 5,
                        ]);
                        return response()->json(['status' => true, 'message' => 'User detail stored successfully'], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to store user detail'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function order_list()
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $orderList = OfflinePoojaOrder::where('customer_id', $userId)->with('offlinePooja')->orderBy('created_at', 'desc')->get();
                    $response = $orderList->map(function ($order) {
                        $nameTranslation = $order->offlinepooja->translations()->where('key', 'name')->first()->value ?? null;
                        $url = url("storage/app/public/offlinepooja/thumbnail/{$order->offlinepooja->thumbnail}");
                        $status = '';
                        switch ($order->status) {
                            case 0:
                                $status = 'Pending';
                                break;
                            case 1:
                                $status = 'Complete';
                                break;
                            case 2:
                                $status = 'Cancel';
                                break;
                            default:
                                $status = 'Unknown Status';
                        }

                        return [
                            'id' => $order->id,
                            'service_id' => $order->service_id,
                            'order_id' => $order->order_id,
                            'pooja_price' => $order->package_main_price,
                            'pay_amount' => $order->pay_amount,
                            'status' => $status,
                            'booking_date' => $order->booking_date,
                            'services' => array_merge(
                                $order->offlinepooja->toArray(),
                                ['hi_name' => $nameTranslation, 'thumbnail' => $url]
                            ),
                            'created_at' => $order->created_at
                        ];
                    });

                    if ($response) {
                        return response()->json([
                            'status' => true,
                            'message' => 'Orders retrieved successfully',
                            'orders' => $response,
                        ]);
                    }

                    return response()->json(['status' => false, 'message' => 'Unable to get orders'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function order_details(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    if (!$request->has('orderId')) {
                        return response()->json(['status' => false, 'message' => 'orderId is required']);
                    }
                    $orderid = $request->orderId;
                    $serviceOrder = OfflinePoojaOrder::where('order_id', $orderid)
                        ->with([
                            'leads',
                            'offlinepooja:id,name,thumbnail',
                            'package',
                            'payments',
                            'pandit:id,name,email,mobile_no,image',
                        ])
                        ->first();

                    if (!$serviceOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid order ID or order not found.',
                        ], 404);
                    }

                    $customer = User::where('phone', $serviceOrder['leads']['person_phone'])
                        ->select('id', 'name', 'email', 'phone')
                        ->first();

                    if ($customer) {
                        $serviceOrder['customer'] = $customer;
                    } else {
                        $serviceOrder['customer'] = null;
                    }

                    if ($serviceOrder->offlinepooja) {
                        $serviceOrder->offlinepooja->hi_name = $serviceOrder->offlinepooja->translations()
                            ->where('key', 'name')
                            ->first()
                            ->value ?? null;

                        $serviceOrder->offlinepooja->thumbnail = $serviceOrder->offlinepooja->thumbnail
                            ? url("storage/app/public/offlinepooja/thumbnail/{$serviceOrder->offlinepooja->thumbnail}")
                            : null;

                        $serviceOrder->pooja_certificate = $serviceOrder->pooja_certificate
                            ? url('public/' . $serviceOrder->pooja_certificate)
                            : null;
                    }

                    $status = '';
                    switch ($serviceOrder->status) {
                        case 0:
                            $status = 'Pending';
                            break;
                        case 1:
                            $status = 'Complete';
                            break;
                        case 2:
                            $status = 'Cancel';
                            break;
                        default:
                            $status = 'Unknown';
                    }

                    $serviceOrder->status = $status;
                    $isEdited = OfflinepoojaReview::where('order_id', $orderid)
                        ->where('status', 1)
                        ->value('is_edited');
                    if ($serviceOrder) {
                        return response()->json([
                            'success' => true,
                            'order' => $serviceOrder,
                            'is_review' => $isEdited,
                            'current_date' => Carbon::now(),
                        ], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to get order details'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function remaining_pay(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    if ($request->payment_amount == 0) {
                        // amount will be deduct completely from wallet
                        $packageMainPrice = OfflinePoojaOrder::where('order_id', $request->order_id)->value('package_main_price');
                        $alreadyWalletDeducted = OfflinePoojaOrder::where('order_id', $request->order_id)->value('wallet_amount');
                        $totalWalletDeduction = $alreadyWalletDeducted + $request->wallet_deduction;
                        $remainingPay = OfflinePoojaOrder::where('order_id', $request->order_id)->update(['wallet_amount' => $totalWalletDeduction, 'pay_amount' => $packageMainPrice, 'remain_amount' => 0, 'remain_amount_status' => 1]);
                        if ($remainingPay) {
                            $prevWalletAmt = User::where('id', $request->customer_id)->value('wallet_balance');
                            $newWalletAmt = $prevWalletAmt - $request->wallet_deduction;
                            User::where('id', $request->customer_id)->update(['wallet_balance' => $newWalletAmt]);

                            $wallet_transaction = new WalletTransaction();
                            $wallet_transaction->user_id = $request->customer_id;
                            $wallet_transaction->transaction_id = \Str::uuid();
                            $wallet_transaction->reference = 'offline pooja order payment';
                            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                            $wallet_transaction->balance = $newWalletAmt;
                            $wallet_transaction->debit = $request->wallet_deduction;
                            $wallet_transaction->save();
                            return response()->json(['status' => true, 'message' => 'remaining amount paid successfully'], 200);
                        }
                    } else {
                        $orderData = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
                        $totalWalletDeduction = $orderData['wallet_amount'] + $request->wallet_deduction;
                        $totalPaymentAmount = $orderData['package_main_price'];
                        $totalTransactionAmount = $orderData['transection_amount'] + $request->payment_amount;

                        // service_transaction
                        $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
                        $serviceOrderAdd->payment_id = $request->payment_id;
                        $serviceOrderAdd->transection_amount = $totalTransactionAmount;
                        $serviceOrderAdd->pay_amount = $totalPaymentAmount;
                        if ($request->wallet_deduction > 0) {
                            $serviceOrderAdd->wallet_amount = $totalWalletDeduction;
                        }
                        $serviceOrderAdd->remain_amount = 0;
                        $serviceOrderAdd->remain_amount_status = 1;
                        if ($serviceOrderAdd->save()) {
                            if ($request->wallet_deduction > 0) {
                                $wallet_transaction = new WalletTransaction();
                                $wallet_transaction->user_id = $request->customer_id;
                                $wallet_transaction->transaction_id = \Str::uuid();
                                $wallet_transaction->reference = 'offline pooja order payment';
                                $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                                $wallet_transaction->balance = 0.00;
                                $wallet_transaction->debit = $request->wallet_deduction;
                                $wallet_transaction->save();
                                User::where('id', $request->customer_id)->update(['wallet_balance' => 0]);
                            }
                            return response()->json(['status' => true, 'message' => 'remaining amount paid successfully'], 200);
                        }
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to pay remaining amount'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function schedule_amount(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    if (!$request->has('orderId')) {
                        return response()->json(['status' => false, 'message' => 'orderId is required']);
                    }
                    $schedulePercent = 0;

                    // get days difference
                    $orderData = OfflinePoojaOrder::where('order_id', $request->orderId)->first();
                    $bookingDate = Carbon::parse($orderData->booking_date);
                    $today = Carbon::today();
                    $daysDiff = $today->diffInDays($bookingDate, false);

                    // get days percent
                    $scheduleData = OfflinepoojaSchedule::where('status', 1)->orderBy('days')->get();
                    $exactMatch = $scheduleData->firstWhere('days', $daysDiff);
                    if ($exactMatch) {
                        $schedulePercent = $exactMatch->percent;
                    } else {
                        $greaterMatch = $scheduleData->where('days', '>', $daysDiff)->first();
                        if ($greaterMatch) {
                            $schedulePercent = $greaterMatch->percent;
                        } else {
                            $schedulePercent = $scheduleData->last()->percent;
                        }
                    }

                    // schedule price
                    $poojaPrice = $orderData->package_main_price;
                    $schedulePrice = ($poojaPrice * $schedulePercent) / 100;
                    if ($schedulePrice) {
                        return response()->json(['status' => true, 'schedulePrice' => $schedulePrice], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to pay remaining amount'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function schedule_pay(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    if ($request->payment_amount == 0) {
                        // amount will be deduct completely from wallet
                        $schedulePooja = OfflinePoojaOrder::where('order_id', $request->order_id)->update(['venue_address' => $request->venue_address, 'state' => $request->state, 'city' => $request->city, 'pincode' => $request->pincode, 'latitude' => $request->latitude, 'longitude' => $request->longitude, 'landmark' => $request->landmark, 'booking_date' => $request->booking_date, 'schedule_status' => 1, 'schedule_amount' => $request->wallet_deduction]);
                        if ($schedulePooja) {
                            $prevWalletAmt = User::where('id', $request->customer_id)->value('wallet_balance');
                            $newWalletAmt = $prevWalletAmt - $request->wallet_deduction;
                            User::where('id', $request->customer_id)->update(['wallet_balance' => $newWalletAmt]);

                            $wallet_transaction = new WalletTransaction();
                            $wallet_transaction->user_id = $request->customer_id;
                            $wallet_transaction->transaction_id = \Str::uuid();
                            $wallet_transaction->reference = 'offline pooja order payment';
                            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                            $wallet_transaction->balance = $newWalletAmt;
                            $wallet_transaction->debit = $request->wallet_deduction;
                            $wallet_transaction->save();
                            return response()->json(['status' => true, 'message' => 'offline pooja has been scheduled'], 200);
                        }
                        return response()->json(['status' => false, 'message' => 'Unable to schedule offline pooja'], 200);
                    } else {
                        // service_transaction
                        $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
                        $serviceOrderAdd->venue_address = $request->venue_address;
                        $serviceOrderAdd->state = $request->state;
                        $serviceOrderAdd->city = $request->city;
                        $serviceOrderAdd->pincode = $request->pincode;
                        $serviceOrderAdd->latitude = $request->latitude;
                        $serviceOrderAdd->longitude = $request->longitude;
                        $serviceOrderAdd->landmark = $request->landmark;
                        $serviceOrderAdd->booking_date = $request->booking_date;
                        $serviceOrderAdd->schedule_status = 1;
                        $serviceOrderAdd->schedule_amount = $request->payment_amount + $request->wallet_deduction;
                        if ($serviceOrderAdd->save()) {
                            if ($request->wallet_deduction > 0) {
                                $wallet_transaction = new WalletTransaction();
                                $wallet_transaction->user_id = $request->customer_id;
                                $wallet_transaction->transaction_id = \Str::uuid();
                                $wallet_transaction->reference = 'offline pooja order payment';
                                $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                                $wallet_transaction->balance = 0.00;
                                $wallet_transaction->debit = $request->wallet_deduction;
                                $wallet_transaction->save();

                                $prevWalletAmt = User::where('id', $request->customer_id)->value('wallet_balance');
                                $newWalletAmt = $prevWalletAmt - $request->wallet_deduction;
                                User::where('id', $request->customer_id)->update(['wallet_balance' => $newWalletAmt]);
                            }
                            return response()->json(['status' => true, 'message' => 'offline pooja has been scheduled'], 200);
                        }
                        return response()->json(['status' => false, 'message' => 'Unable to schedule offline pooja'], 200);
                    }
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function edit_user_detail(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $edit = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
                    $edit->venue_address = $request->venue_address;
                    $edit->state = $request->state;
                    $edit->city = $request->city;
                    $edit->pincode = $request->pincode;
                    $edit->latitude = $request->latitude;
                    $edit->longitude = $request->longitude;
                    $edit->booking_date = $request->booking_date;
                    $edit->landmark = $request->landmark;
                    $edit->is_edited = 1;
                    $serviceId = $edit->service_id;
                    if ($store->save()) {
                        OfflinepoojaReview::create([
                            'order_id' => $request->order_id,
                            'user_id' => $userId,
                            'service_id' => $serviceId,
                            'service_type' => 'pooja',
                            'rating' => 5,
                        ]);

                        return response()->json(['status' => true, 'message' => 'User detail edited successfully'], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to edit user detail'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function cancel_amount(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    if (empty($request->orderId)) {
                        return response()->json(['status' => false, 'message' => 'orderId is required'], 200);
                    }

                    $refundPercent = 0;

                    // get days difference
                    $orderData = OfflinePoojaOrder::where('order_id', $request->orderId)->first();
                    $bookingDate = Carbon::parse($orderData->booking_date);
                    $today = Carbon::today();
                    $daysDiff = $today->diffInDays($bookingDate, false);

                    // get days percent
                    $scheduleData = OfflinepoojaRefundPolicy::where('status', 1)->orderBy('days')->get();
                    $exactMatch = $scheduleData->firstWhere('days', $daysDiff);
                    if ($exactMatch) {
                        $refundPercent = $exactMatch->percent;
                    } else {
                        $greaterMatch = $scheduleData->where('days', '>', $daysDiff)->first();
                        if ($greaterMatch) {
                            $refundPercent = $greaterMatch->percent;
                        } else {
                            $refundPercent = $scheduleData->last()->percent;
                        }
                    }

                    // schedule price
                    $userPaid = $orderData->pay_amount;
                    $refundPrice = ($userPaid * $refundPercent) / 100;
                    if ($refundPrice) {
                        return response()->json(['status' => true, 'refundPrice' => $refundPrice], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to get refund price'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function order_cancel(Request $request)
    {
        try {
            if (Auth::guard('api')->check()) {
                $userId = Auth::guard('api')->user()->id;
                if ($userId) {
                    $cancel = OfflinePoojaOrder::where('order_id', $request->order_id)->update(['status' => 2, 'is_edited' => 1, 'order_canceled' => now(), 'order_canceled_reason' => $request->order_canceled_reason, 'canceled_by' => 'user', 'refund_status' => 1, 'refund_amount' => $request->refund_amount]);
                    if ($cancel) {
                        $walletBal = User::where('id', $userId)->get()->value('wallet_balance');
                        $currentBal = $walletBal + $request->refund_amount;
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $userId;
                        $wallet_transaction->transaction_id = \Str::uuid();
                        $wallet_transaction->reference = 'offline pooja order payment';
                        $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                        $wallet_transaction->credit = $request->refund_amount;
                        $wallet_transaction->balance = $currentBal;
                        $wallet_transaction->save();
                        User::where('id', $userId)->update(['wallet_balance' => $currentBal]);
                        return response()->json(['status' => true, 'message' => 'order canceled'], 200);
                    }
                    return response()->json(['status' => false, 'message' => 'Unable to cancel order'], 200);
                }
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            } else {
                return response()->json(['status' => false, 'message' => 'Unauthorized user'], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}