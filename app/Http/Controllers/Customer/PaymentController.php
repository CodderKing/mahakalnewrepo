<?php

namespace App\Http\Controllers\Customer;

use App\Contracts\Repositories\EventsRepositoryInterface;
use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Events\OrderStatusEvent;
use App\Utils\Helpers;
use App\Utils\ApiHelper;
use App\Http\Controllers\Controller;
use App\Library\Payer;
use App\Library\Payment as PaymentInfo;
use App\Library\Receiver;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\Chadhava;
use App\Models\WalletTransaction;
use App\Models\ShippingType;
use App\Models\PaymentRequest;
use App\Models\Chadhava_orders;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\Service_order;
use App\Models\Leads;
use App\Models\Currency;
use App\Models\EventApproTransaction;
use App\Models\EventOrder;
use App\Models\EventLeads;
use App\Models\EventOrderItems;
use App\Models\EventOrganizer;
use App\Models\Events;
use App\Models\BirthJournalKundali;
use App\Models\KundaliLeads;
use App\Models\DonateAds;
use App\Models\PoojaRecords;
use App\Models\DonateAllTransaction;
use App\Models\DonateLeads;
use App\Models\DonateTrust;
use App\Models\TourLeads;
use App\Models\TourOrder;
use App\Models\TourVisits;
use App\Traits\Payment;
use App\Utils\CartManager;
use App\Utils\Convert;
use App\Utils\OrderManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use function App\Utils\currency_converter;
use Razorpay\Api\Api;
use App\Traits\Whatsapp;
use App\Models\Admin;
use App\Models\OfflineLead;
use App\Models\OfflinePoojaOrder;
use App\Models\PoojaOffline;
use App\Models\WEventTemplate;
use App\Models\WhatsappTemplate;
use PhpParser\Node\Expr\Cast\Double;
use App\Models\OrderDetail;
use App\Models\ProductLeads;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\Service;
use App\Models\Vippooja;
use App\Http\Controllers\Customer\View;

class PaymentController extends Controller
{
    use Whatsapp;
    public function __construct(PaymentRequest $payment, private EventsRepositoryInterface $eventsRepository, private ServiceRepositoryInterface $serviceRepository)
    {
        $config = DB::table('addon_settings')->where('key_name', 'razor_pay')->where('settings_type', 'payment_config')->first();
        $razor = false;
        if (!is_null($config) && $config->mode == 'live') {
            $razor = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $razor = json_decode($config->test_values);
        }

        if ($razor) {
            $config = array(
                'api_key' => $razor->api_key,
                'api_secret' => $razor->api_secret
            );
            Config::set('razor_config', $config);
        }

        $this->payment = $payment;
        $this->eventsRepository = $eventsRepository;
    }

    public function payment(Request $request)
    {
        $user = Helpers::get_customer($request);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);

        $validator->sometimes('customer_id', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });
        $validator->sometimes('is_guest', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });

        if ($validator->fails()) { //api
            $errors = Helpers::error_processor($validator);
            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            } else {
                foreach ($errors as $value) {
                    Toastr::error(translate($value['message']));
                }
                return back();
            }
        }

        $cart_group_ids = CartManager::get_cart_group_ids();
        $carts = Cart::whereIn('cart_group_id', $cart_group_ids)->get();
        $product_stock = CartManager::product_stock_check($carts);
        if (!$product_stock && in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['errors' => ['code' => 'product-stock', 'message' => 'The following items in your cart are currently out of stock']], 403);
        } elseif (!$product_stock) {
            Toastr::error(translate('the_following_items_in_your_cart_are_currently_out_of_stock'));
            return redirect()->route('shop-cart');
        }

        $verifyStatus = OrderManager::minimum_order_amount_verify($request);
        if ($verifyStatus['status'] == 0 && in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['errors' => ['code' => 'Check the minimum order amount requirement']], 403);
        } elseif ($verifyStatus['status'] == 0) {
            Toastr::info('Check the minimum order amount requirement');
            return redirect()->route('shop-cart');
        }

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $shippingMethod = Helpers::get_business_settings('shipping_method');
            $physical_product = false;
            foreach ($carts as $cart) {
                if ($cart->product_type == 'physical') {
                    $physical_product = true;
                }

                if ($shippingMethod == 'inhouse_shipping') {
                    $admin_shipping = ShippingType::where('seller_id', 0)->first();
                    $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                } else {
                    if ($cart->seller_is == 'admin') {
                        $admin_shipping = ShippingType::where('seller_id', 0)->first();
                        $shipping_type = isset($admin_shipping) == true ? $admin_shipping->shipping_type : 'order_wise';
                    } else {
                        $seller_shipping = ShippingType::where('seller_id', $cart->seller_id)->first();
                        $shipping_type = isset($seller_shipping) == true ? $seller_shipping->shipping_type : 'order_wise';
                    }
                }

                if ($shipping_type == 'order_wise') {
                    $cart_shipping = CartShipping::where('cart_group_id', $cart->cart_group_id)->first();
                    if (!isset($cart_shipping) && $physical_product) {
                        return response()->json(['errors' => ['code' => 'shipping-method', 'message' => 'Data not found']], 403);
                    }
                }
            }
        }

        $redirect_link = $this->customer_payment_request($request);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }

    public function success()
    {
        return response()->json(['message' => 'Payment succeeded'], 200);
    }

    public function fail(Request $request)
    {
        $paymentId = $request->payment_id;
        $data = PaymentRequest::where('id', $paymentId)->first();
        $serviceData = json_decode($data->additional_data);
        if($data->attribute == 'tour_order' || $data->attribute == "Tour Order"){
            TourLeads::where('id',$serviceData->leads_id)->update(['amount_status'=>2]);
        }elseif($data->attribute == 'vip_darshan_order'){

        }elseif($data->attribute == "Donate" || $data->attribute == "Donate Order"){

        }elseif($data->attribute == "Event Order" || $data->attribute == "event_order"){

        }elseif($data->attribute == "Kundli Order" || $data->attribute == "Birth_journal"){

        }elseif($data->attribute == "Kundli Order" || $data->attribute == "Birth_journal"){

        }
        elseif($data->attribute == "pandit_booking"){
            OfflineLead::where('id', $serviceData->leads_id)->update(['payment_status' => 'failed']);
            OfflinePoojaOrder::where('order_id', $serviceData->order_id)->update(['payment_status' => 2]);
        }
        elseif (in_array($data->attribute, ['puja', 'anushthan', 'counselling', 'vippuja','chadhava'])) {
            \App\Models\Leads::where('id', $serviceData->leads_id)->update(['payment_status' => 'failed',]);
        }
        if (in_array($data->attribute, ['puja', 'anushthan', 'vippuja'])) {
            \App\Models\Service_order::where('order_id', $serviceData->order_id)->update(['payment_status' => 2]);
        }
        elseif (in_array($data->attribute, ['chadhava'])) {
            \App\Models\Chadhava_orders::where('order_id', $serviceData->order_id)->update(['payment_status' => 2]);
        }
        $user = User::where('id', $data->payer_id)->first();
        if (!$user &&  $data->attribute == 'Birth_journal') {
            $getUsers = json_decode($data->additional_data ?? "[]", true);
            $user = User::where('id', $getUsers['user_id'])->first();
        }        
        if ($data->payment_platform == 'web') {
            $message_data = [
                'order_amount' => $data->payment_amount,
                'customer_id' => $user['id'] ?? '',
                'booking_date' => date('d M Y', strtotime($data->created_at)),
                'transaction_id' => $data->id,
                'puja' => ucwords(str_replace('_', ' ', ($data->attribute ?? ''))),
                'link' => $data->previous_url ?? 'mahakal.com',
                'user_name' => $user['f_name'] . ' ' . $user['l_name'],
                'number' => $user->phone,
            ];
            $messages =  Helpers::whatsappMessage('whatsapp', 'Payment Fail', $message_data);
            Toastr::error(translate('Payment_failed'));
            if (empty($data->previous_url)) {
                return redirect(url('/'));
            }
            return redirect($data->previous_url);
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

    public function web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {

            $orderId = \App\Models\Order::latest()->value('id');
            $latestOrder = \App\Models\Order::latest()->first();
            $customerId = $latestOrder->customer_id ?? null;

            if (!$customerId) {
                Toastr::error('Customer not found!');
                return redirect(url('/'));
            }

            $userInfo = \App\Models\User::find($customerId);
            $orders = \App\Models\Order::where('customer_id', $customerId)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->latest()
                ->get();

            foreach ($orders as $order) {
                $orderDetails = \App\Models\OrderDetail::where('order_id', $order->id)->get();
                $productIds = $orderDetails->pluck('product_id')->toArray();
                $productNames = \App\Models\Product::whereIn('id', $productIds)->pluck('name')->toArray();
                $sellerIds = $orderDetails->pluck('seller_id')->unique();
                $sellers = Seller::whereIn('id', $sellerIds)->get();
                $shops = Shop::whereIn('seller_id', $sellerIds)->pluck('name', 'seller_id');

                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'ecommerece';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Order #' . $order->id;
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make(
                        'admin-views.email.email-template.ecom-template',
                        compact('userInfo', 'order', 'orderDetails', 'productNames', 'shops', 'sellers')
                    )->render();

                    Helpers::emailSendMessage($data);
                }

                $message_data = [
                    'product_name' => implode(', ', $productNames),
                    'orderId' =>  $order->id,
                    'order_amount' => webCurrencyConverter(amount: (float)$order->order_amount ?? 0),
                    'customer_id' => ($order->customer_id ?? ""),
                ];

                $messages =  Helpers::whatsappMessage('ecom', 'Order placed', $message_data);
            }

            // Response Handling
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                return view(VIEW_FILE_NAMES['order_complete']);
            }
        } else {
            // Payment failed
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    public function customer_payment_request(Request $request)
    {
        $additional_data = [
            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['address_id'] = $request['address_id'];
            $additional_data['billing_address_id'] = $request['billing_address_id'];
            $additional_data['coupon_code'] = $request['coupon_code'];
            $additional_data['coupon_discount'] = $request['coupon_discount'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }

        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $cart_group_ids = CartManager::get_cart_group_ids($request);
            $cart_amount = 0;
            $shipping_cost_saved = 0;
            foreach ($cart_group_ids as $group_id) {
                $cart_amount += CartManager::api_cart_grand_total($request, $group_id);
                $shipping_cost_saved += CartManager::get_shipping_cost_saved_for_free_delivery($group_id);
            }
            $payment_amount = $cart_amount - $request['coupon_discount'] - $shipping_cost_saved;
        } else {
            $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
            $order_wise_shipping_discount = CartManager::order_wise_shipping_discount();
            $shipping_cost_saved = CartManager::get_shipping_cost_saved_for_free_delivery();
            $payment_amount = CartManager::cart_grand_total() - $discount - $order_wise_shipping_discount - $shipping_cost_saved;
        }

        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $payment_amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'order',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function customer_add_to_fund_request(Request $request)
    {
        if (Helpers::get_business_settings('add_funds_to_wallet') != 1) {
            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['message' => 'Add funds to wallet is deactivated'], 403);
            }

            Toastr::error(translate('add_funds_to_wallet_is_deactivated'));
            return back();
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = Helpers::error_processor($validator);
            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['errors' => $errors]);
            } else {
                foreach ($errors as $value) {
                    Toastr::error(translate($value['message']));
                }
                return back();
            }
        }

        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $default_currency = Currency::find(Helpers::get_business_settings('system_default_currency'));
            $currency_code = $default_currency['code'];
            $current_currency = $request->current_currency_code ?? session('currency_code');
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
            $current_currency = $currency_code;
        }


        $minimum_add_fund_amount = Helpers::get_business_settings('minimum_add_fund_amount') ?? 0;
        $maximum_add_fund_amount = Helpers::get_business_settings('maximum_add_fund_amount') ?? 0;

        if (!(Convert::usdPaymentModule($request->amount, $current_currency) >= Convert::usdPaymentModule($minimum_add_fund_amount, 'USD')) || !(Convert::usdPaymentModule($request->amount, $current_currency) <= Convert::usdPaymentModule($maximum_add_fund_amount, 'USD'))) {
            $errors = [
                'minimum_amount' => $minimum_add_fund_amount ?? 0,
                'maximum_amount' => $maximum_add_fund_amount ?? 1000,
            ];
            if (in_array($request->payment_request_from, ['app'])) {
                return response()->json($errors, 202);
            } elseif (in_array($request->payment_request_from, ['react'])) {
                return response()->json($errors, 403);
            } else {
                Toastr::error(translate('the_amount_needs_to_be_between') . ' ' . currency_converter($minimum_add_fund_amount) . ' - ' . currency_converter($maximum_add_fund_amount));
                return back();
            }
        }

        $additional_data = [
            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
        ];

        $customer = Helpers::get_customer($request);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $customer->id;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }

        $payer = new Payer(
            $customer->f_name . ' ' . $customer->l_name,
            $customer['email'],
            $customer->phone,
            ''
        );

        $payment_info = new PaymentInfo(
            success_hook: 'add_fund_to_wallet_success',
            failure_hook: 'add_fund_to_wallet_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer->id,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: Convert::usdPaymentModule($request->amount, $current_currency),
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'add_funds_to_wallet',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }
  
    public function servicespayment(Request $request)
    {
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet['wallet_balance'] ?? 0;

        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }

        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_pooja', 0);
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount);
        $totalAmount = $amount + $couponDiscount;

        // Wallet payment if payment_amount == 0
        if ($request->payment_amount == 0) {
            if ($actualWalletBalance < $amount) {
                return redirect()->back()->with('error', 'Insufficient wallet balance.');
            }

            $remainingWalletBalance = $actualWalletBalance - $amount;
            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }

            $orderId = '';
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = 'PJ' . (100000 + ($orderData['id'] ?? 0) + 1);

            Leads::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'add_product_id' => json_encode($add_product_array),
                'final_amount' => $totalAmount,
                'via_wallet' => $amount,
                'coupon_amount' => $couponDiscount,
                'order_id'  => $orderId,
            ]);
            $additional_data = [
                'leads_id' => $leadsdata->id,
                'package_id' => $leadsdata->package_id,
                'service_id' => $leadsdata->service_id,
                'customer_id' => $request->customer_id,
                'package_price' => $leadsdata->package_price,
                'booking_date' => $leadsdata->booking_date,
                'pandit_assign' => $request->pandit_assign,
                'wallet_balance' => $remainingWalletBalance,
                'final_amount' => $amount,
            ];

            User::where('id', $additional_data['customer_id'])->update(['wallet_balance' => $remainingWalletBalance]);

            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $additional_data['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'pooja order payment';
            $wallet_transaction->transaction_type = 'pooja_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();


            $serviceOrderAdd = new Service_order();
            $serviceOrderAdd->customer_id = $additional_data['customer_id'];
            $serviceOrderAdd->service_id = $additional_data['service_id'];
            $serviceOrderAdd->type = 'pooja';
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_pooja');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_pooja');
            $serviceOrderAdd->leads_id = $additional_data['leads_id'];
            $serviceOrderAdd->package_id = $additional_data['package_id'];
            $serviceOrderAdd->package_price = $additional_data['package_price'];
            $serviceOrderAdd->booking_date = $additional_data['booking_date'];
            $serviceOrderAdd->pandit_assign = $additional_data['pandit_assign'];
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $additional_data['final_amount'] + $couponAmount;
            $serviceOrderAdd->wallet_amount = $additional_data['final_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->payment_status = '1';
            $serviceOrderAdd->payment_id = 'pay_wallet';
            $serviceOrderAdd->save();

            PoojaRecords::create([
                'customer_id' => $serviceOrderAdd->customer_id,
                'service_id' => $serviceOrderAdd->service_id,
                'product_id' => json_encode($add_product_array),
                'service_order_id' => $serviceOrderAdd->order_id,
                'package_id' => $serviceOrderAdd->package_id,
                'package_price' => $serviceOrderAdd->package_price ?? 0.00,
                'amount' => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon' => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet' => $serviceOrderAdd->wallet_amount ?? 0.00,
                'booking_date' => $serviceOrderAdd->booking_date,
            ]);

            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);

            Toastr::success(translate('Payment_success'));
            session()->forget('coupon_discount_pooja');
            session()->forget('coupon_code_pooja');

            $userInfo = User::find($additional_data['customer_id']);
            $service_name = Service::where('id', $additional_data['service_id'])->where('product_type', 'pooja')->first();
            $bookingDetails = Service_order::where('service_id', $additional_data['service_id'])
                ->where('type', 'pooja')
                ->where('booking_date', $additional_data['booking_date'])
                ->where('customer_id', $additional_data['customer_id'])
                ->where('order_id', $orderId)
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                'booking_date' => date('d-m-Y', strtotime($additional_data['booking_date'])),
                'puja_venue' => $service_name['pooja_venue'],
                'orderId' => $orderId,
                'final_amount' => webCurrencyConverter((float)($amount)),
                'customer_id' => $additional_data['customer_id'],
            ];
            Helpers::whatsappMessage('whatsapp', 'Pooja Confirmed', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of Your Service Purchase';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                Helpers::emailSendMessage($data);
            }

            return redirect()->route('sankalp', [$orderId]);
        }

        // Online Payment Handling
        $user = Helpers::get_customer($request);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        $validator->sometimes('customer_id', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });
      
        $redirect_link = $this->services_customer_payment_request($request);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }

    public function services_customer_payment_request(Request $request)
    {
        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        // dd($add_product_array);
        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_pooja', 0);
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount - $requestedWalletUse);  
        $totalAmount =  $amount  + $couponDiscount + $requestedWalletUse;
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));
      
        if (!empty($leadsdata->order_id)) {
            $orderId = $leadsdata->order_id;
        } else {
            $orderData = Service_order::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'PJ' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'PJ' . (100001);
            }
        }        
        Leads::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' =>json_encode($add_product_array),
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
            'order_id' => $orderId,
        ]);
        
        $serviceOrderData = Leads::where('id', $leadsdata->id)->first();

        $existingServiceOrder = Service_order::where('order_id', $serviceOrderData->order_id)->first();

        $serviceOrderAdd = [
            'order_id' => $serviceOrderData->order_id,
            'customer_id' => $serviceOrderData->customer_id,
            'service_id' =>  $serviceOrderData->service_id,
            'type' => $serviceOrderData->type,
            'leads_id' => $serviceOrderData->id,
            'package_id' => $serviceOrderData->package_id,
            'coupon_amount' => $couponDiscount,
            'package_price' =>  $serviceOrderData->package_price,
            'booking_date' => $serviceOrderData->booking_date,
            'wallet_amount' => $serviceOrderData->via_wallet ?? 0,
            'transection_amount' => $amount,
            'coupon_code' => session()->get('coupon_code_pooja'),
            'pay_amount' => $totalAmount,
        ];

        if ($existingServiceOrder) {
            // Update existing record
            $existingServiceOrder->update($serviceOrderAdd);
        } else {
            // Create new record only if not exists
            Service_order::create($serviceOrderAdd);
        }

        session()->forget('coupon_discount_pooja');
        session()->forget('coupon_code_pooja');
        //Payment Getway Open 
        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $leadsdata->id,
            'order_id'        => $orderId,
            'package_id'      => $leadsdata->package_id,
            'service_id'      => $leadsdata->service_id,
            'customer_id'     => $request->customer_id,
            'package_price'   => $leadsdata->package_price,
            'booking_date'    => $leadsdata->booking_date,
            'pandit_assign'   => $request->pandit_assign,
            'wallet_balance'  => $requestedWalletUse,
            'final_amount'    => $amount,   
        ];
        
        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }
        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount:  $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'puja',
            attribute_id: idate("U")
        );
        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        return $redirect_link;
    }

    public function service_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            // Wallet Maintan
            $leadsData = Leads::where('id', $serviceData->leads_id)->first();
            //serviceordder table main records updates
            $serviceOrderAdd = Service_order::where('order_id', $leadsData->order_id)->first();
            $wallet = User::select('wallet_balance')->where('id', $leadsData->customer_id)->first();
            $actualWalletBalance = $wallet->wallet_balance ?? 0;

            if ($actualWalletBalance > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $leadsData->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'pooja order payment';
                $wallet_transaction->transaction_type = 'pooja_order_place';
                $wallet_transaction->balance = 0.00;
                $wallet_transaction->debit = $leadsData->via_wallet;
                $wallet_transaction->save();
                User::where('id', $leadsData->customer_id)->update(['wallet_balance' => 0]);
            }

            if ($serviceOrderAdd) {
                $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }
            $productlist = ProductLeads::where('leads_id', $serviceData->leads_id)->get();
            $add_product_array = [];
            foreach ($productlist as $product) {
                $add_product_array[] = [
                    'product_id' => $product->product_id,
                    'price' => $product->product_price,
                    'qty' => $product->qty,
                ];
            }
           
            PoojaRecords::create([
                'customer_id'     => $serviceOrderAdd->customer_id,
                'service_id'      => $serviceOrderAdd->service_id,
                'product_id' => json_encode($add_product_array),
                'service_order_id'=> $serviceOrderAdd->order_id,
                'package_id'      => $serviceOrderAdd->package_id,
                'package_price'   => $serviceOrderAdd->package_price ?? 0.00,
                'amount'          => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon'          => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet'      => $serviceOrderAdd->wallet_amount ?? 0.00,
                'via_online'      => $serviceOrderAdd->transection_amount ?? 0.00,
                'booking_date'    => $serviceOrderAdd->booking_date,
            ]);
            $orderId=$serviceOrderAdd->order_id; 
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_pooja');
                session()->forget('coupon_code_pooja');
                $url = $_SERVER['HTTP_HOST'];

                // whatsapp
                $userInfo = User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = Service::where('id', ($serviceData->service_id ?? ""))->where('product_type', 'pooja')->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'attachment' => asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                    'booking_date' => date('d-m-Y', strtotime($serviceData->booking_date)),
                    'puja_venue' => $service_name['pooja_venue'],
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter((float)$additionalData['final_amount'] - ($bookingDetails->coupon_amount ?? 0)),
                    'type' => 'text-with-media',
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('whatsapp', 'Pooja Confirmed', $message_data);


                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('sankalp', [$orderId]);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }


    // counselling payment
    public function counsellingpayment(Request $request)
    {
        $servicePrice = Service::where('id', $request->service_id)->where('product_type', 'counselling')->first();

        // lead insert
        $lead_details = [
            'service_id' => $request->input('service_id'),
            'type' => 'counselling',
            'package_price' => $servicePrice->counselling_selling_price,
            'person_phone' => $request->input('person_phone'),
            'person_name' => $request->input('person_name'),
        ];
        $leads = Leads::create($lead_details);
        $leadId = $leads->id;
        if (!empty($leadId)) {
            $leadno = 'CL' . (100 + $leadId + 1);
        } else {
            $leadno = 'CL' . (101);
        }
        $storeleadsno = Leads::where('id', $leadId)->update(['leadno' => $leadno]);
        $request->merge(['lead_id' => $leadId]);

        //payment
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        if ($request->payment_amount > 0) {
            $user = Helpers::get_customer($request);
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ]);
            $validator->sometimes('customer_id', 'required', function ($input) {
                return in_array($input->payment_request_from, ['app', 'react']);
            });

            $redirect_link = $this->counselling_customer_payment_request($request);

            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['redirect_link' => $redirect_link], 200);
            } else {
                return redirect($redirect_link);
            }
        } else {
            $leadsdata = Leads::find($request->lead_id);
            if (!$leadsdata) {
                return redirect()->back()->with('error', 'Lead not found.');
            }
            $packageName = Service::where('id', $leadsdata->service_id)->first();
            $couponDiscount = session('coupon_discount_counselling', 0);
            $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount);
            $totalamount = $amount + $couponDiscount;
            $actualWalletBalance = $wallet['wallet_balance'] ?? 0;
            $remainingWalletBalance = $actualWalletBalance - $amount;

            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }
            Leads::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'final_amount' => $totalamount,
                'package_name' => $packageName->name,
                'via_wallet' => $amount,
                'coupon_amount' => $couponDiscount,
            ]);

            $additional_data = [
                'leads_id' => $request->lead_id,
                'service_id' => $request->service_id,
                'customer_id' => $request->customer_id,
            ];
            $orderId = '';
            $orderData = Service_order::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'CL' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'CL' . (100001);
            }

            // Wallet Transection Details
            $serviceData = $additional_data;
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $serviceData['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'counselling order payment';
            $wallet_transaction->transaction_type = 'counselling_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();
            User::where('id', $serviceData['customer_id'])->update(['wallet_balance' => $remainingWalletBalance]);
            // Service Transection Details
            $serviceOrderAdd = new Service_order();
            $serviceOrderAdd->customer_id = $serviceData['customer_id'];
            $serviceOrderAdd->service_id = $serviceData['service_id'];
            $serviceOrderAdd->type = 'counselling';
            $serviceOrderAdd->leads_id = $serviceData['leads_id'];
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_counselling');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_counselling');
            $serviceOrderAdd->wallet_amount = $amount;
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->order_id = $orderId;
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $amount + $couponAmount;
            $serviceOrderAdd->save();
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'counselling_0', type: 'counselling', order: $order));

            // dd($serviceOrderAdd);
            Leads::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'order_id' => $orderId,
            ]);
            Toastr::success(translate('Payment_success'));
            session()->forget('coupon_discount_counselling');
            session()->forget('counselling_order_place');

            $userInfo = \App\Models\User::where('id', ($serviceData['customer_id'] ?? ""))->first();
            $service_name = \App\Models\Service::where('id', ($serviceData['service_id'] ?? ""))->where('product_type', 'counselling')->first();
            $bookingDetails = \App\Models\Service_order::where('service_id', ($serviceData['service_id'] ?? ""))->where('type', 'counselling')
                ->where('customer_id', ($serviceData['customer_id'] ?? ""))
                ->where('order_id', ($orderId ?? ""))
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' =>  asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                'orderId' => $orderId,
                'final_amount' => webCurrencyConverter((float)($request->final_amount - ($serviceOrderAdd->coupon_amount ?? 0))),
                'customer_id' => ($serviceData['customer_id'] ?? ""),
            ];

            $messages =  Helpers::whatsappMessage('consultancy', 'Order Confirmed', $message_data);

            // Mail Setup for Pooja Management Send to  User Email Id
            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'counselling';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of Your Counselling Service Purchase';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                Helpers::emailSendMessage($data);
            }

            return redirect()->route('counselling.user.detail', [$orderId]);
        }
    }


    public function counselling_customer_payment_request(Request $request)
    {
        $leadsdata = Leads::find($request->lead_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $couponDiscount = session('coupon_discount_counselling', 0);
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $packageName = Service::where('id', $leadsdata->service_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse =  $actualWalletBalance;
        $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount - $requestedWalletUse);
        $totalamount = $amount + $couponDiscount + $requestedWalletUse;
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));
        Leads::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => 'pending',
            'platform' => 'web',
            'final_amount' => $totalamount,
            'package_name' => $packageName->name,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
        ]);
        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id' => $leadsdata->id,
            'service_id'      => $leadsdata->service_id,
            'package_price' => $leadsdata->package_price,
            'customer_id' => $request->customer_id,
            'wallet_balance' => $requestedWalletUse,
            'final_amount' => $amount,
        ];
        // dd($additional_data);    
        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'counselling',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function counselling_web_payment_success(Request $request)
    {

        if ($request->flag == 'success') {
            $orderId = '';
            $orderData = Service_order::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'CL' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'CL' . (100001);
            }
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            // Wallet Maintan
            if ($serviceData->wallet_balance > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $serviceData->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'counselling order payment';
                $wallet_transaction->transaction_type = 'counselling_order_place';
                $wallet_transaction->balance = 00.00;
                $wallet_transaction->debit = $serviceData->wallet_balance ?? 0;
                $wallet_transaction->save();
                User::where('id', $serviceData->customer_id)->update(['wallet_balance' => 0]);
            }

            $serviceOrderAdd = new Service_order();
            $serviceOrderAdd->customer_id = $serviceData->customer_id;
            $serviceOrderAdd->service_id = $serviceData->service_id;
            $serviceOrderAdd->package_price = $serviceData->package_price;
            $serviceOrderAdd->type = 'counselling';
            $serviceOrderAdd->leads_id = $serviceData->leads_id;
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
            $serviceOrderAdd->wallet_amount = $serviceData->wallet_balance ?? 0;
            $serviceOrderAdd->transection_amount = $serviceOrder['payment_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_counselling');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_counselling');
            $walletBalance = $serviceData->wallet_balance ?? 0;
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $additionalData['final_amount'] + $walletBalance + $couponAmount;
            $serviceOrderAdd->save();
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'counselling_0', type: 'counselling', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'order_id' => $orderId,
            ]);

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_counselling');
                session()->forget('coupon_code_counselling');

                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\Service::where('id', ($serviceData->service_id ?? ""))->where('product_type', 'counselling')->first();
                $bookingDetails = \App\Models\Service_order::where('service_id', ($serviceData->service_id ?? ""))
                    ->where('type', 'counselling')
                    ->where('customer_id', ($serviceData->customer_id ?? ""))
                    ->where('order_id', ($orderId ?? ""))
                    ->first();

                $message_data = [
                    'service_name' => $service_name['name'],
                    'attachment' => asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter((float)$additionalData['final_amount'] - ($bookingDetails->coupon_amount ?? 0)),
                    'type' => 'text-with-media',
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('consultancy', 'Order Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'counselling';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Counselling Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }

                return redirect()->route('counselling.user.detail', [$orderId]);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }


    // ----------------------------------------VIP POOJA PAYMENT METHOD WORKING ON 25/07/2024----------------------------------------------------
    public function vippooja_payment(Request $request)
    {
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet['wallet_balance'] ?? 0;

        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }

        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_vippooja', 0);
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount);
        $totalAmount = $amount + $couponDiscount;

        // Wallet payment if payment_amount == 0
        if ($request->payment_amount == 0) {
            if ($actualWalletBalance < $amount) {
                return redirect()->back()->with('error', 'Insufficient wallet balance.');
            }

            $remainingWalletBalance = $actualWalletBalance - $amount;
            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }

            $orderId = '';
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = 'VPJ' . (100000 + ($orderData['id'] ?? 0) + 1);

            Leads::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'add_product_id' => json_encode($add_product_array),
                'final_amount' => $totalAmount,
                'via_wallet' => $amount,
                'coupon_amount' => $couponDiscount,
                'order_id'  => $orderId,
            ]);
            $additional_data = [
                'leads_id' => $leadsdata->id,
                'package_id' => $leadsdata->package_id,
                'service_id' => $leadsdata->service_id,
                'customer_id' => $request->customer_id,
                'package_price' => $leadsdata->package_price,
                'booking_date' => $leadsdata->booking_date,
                'pandit_assign' => $request->pandit_assign,
                'wallet_balance' => $remainingWalletBalance,
                'final_amount' => $amount,
            ];

            User::where('id', $additional_data['customer_id'])->update(['wallet_balance' => $remainingWalletBalance]);

            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $additional_data['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'vippooja order payment';
            $wallet_transaction->transaction_type = 'vippooja_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();

            $serviceOrderAdd = new Service_order();
            $serviceOrderAdd->customer_id = $additional_data['customer_id'];
            $serviceOrderAdd->service_id = $additional_data['service_id'];
            $serviceOrderAdd->type = 'vip';
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_vippooja');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_vippooja');
            $serviceOrderAdd->leads_id = $additional_data['leads_id'];
            $serviceOrderAdd->package_id = $additional_data['package_id'];
            $serviceOrderAdd->package_price = $additional_data['package_price'];
            $serviceOrderAdd->booking_date = $additional_data['booking_date'];
            $serviceOrderAdd->pandit_assign = $additional_data['pandit_assign'];
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $additional_data['final_amount'] + $couponAmount;
            $serviceOrderAdd->wallet_amount = $additional_data['final_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->payment_status = '1';
            $serviceOrderAdd->payment_id = 'pay_wallet';
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->save();

            PoojaRecords::create([
                'customer_id' => $serviceOrderAdd->customer_id,
                'service_id' => $serviceOrderAdd->service_id,
                'product_id' => json_encode($add_product_array),
                'service_order_id' => $serviceOrderAdd->order_id,
                'package_id' => $serviceOrderAdd->package_id,
                'package_price' => $serviceOrderAdd->package_price ?? 0.00,
                'amount' => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon' => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet' => $serviceOrderAdd->wallet_amount ?? 0.00,
                'booking_date' => $serviceOrderAdd->booking_date,
            ]);

            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'order_id' => $orderId,
            ]);

            Toastr::success(translate('Payment_success'));
            session()->forget('coupon_discount_vippooja');
            session()->forget('coupon_code_vippooja');

            $userInfo = User::find($additional_data['customer_id']);
            $service_name = Vippooja::where('id', $additional_data['service_id'])->where('is_anushthan', 0)->first();
            $bookingDetails = Service_order::where('service_id', $additional_data['service_id'])
                ->where('type', 'vip')
                ->where('booking_date', $additional_data['booking_date'])
                ->where('customer_id', $additional_data['customer_id'])
                ->where('order_id', $orderId)
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
                'booking_date' => date('d-m-Y', strtotime($additional_data['booking_date'])),
                'orderId' => $orderId,
                'puja' => 'VIP Puja',
                'final_amount' => webCurrencyConverter((float)($amount)),
                'customer_id' => $additional_data['customer_id'],
            ];
            Helpers::whatsappMessage('vipanushthan', 'Pooja Confirmed', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of Your VIP Service Purchase';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                Helpers::emailSendMessage($data);
            }

            return redirect()->route('vip.user.detail', [$orderId]);
        }

        // Online Payment Handling
        $user = Helpers::get_customer($request);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        $validator->sometimes('customer_id', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });

        $redirect_link = $this->vippooja_customer_payment_request($request);
        $redirect_link = str_replace(["\r", "\n"], '', $redirect_link);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }


    public function vippooja_customer_payment_request(Request $request)
    {
        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        // dd($add_product_array);
        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_vippooja', 0);
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount - $requestedWalletUse);  
        $totalAmount =  $amount  + $couponDiscount + $requestedWalletUse;
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));
       
        if (!empty($leadsdata->order_id)) {
            $orderId = $leadsdata->order_id;
        } else {
            $orderData = Service_order::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'VPJ' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'VPJ' . (100001);
            }
        }      
        Leads::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' =>json_encode($add_product_array),
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
            'order_id' => $orderId,
        ]);
        //serviceOrder records to store
        $serviceOrderData = Leads::where('id', $leadsdata->id)->first();

        $existingServiceOrder = Service_order::where('order_id', $serviceOrderData->order_id)->first();

        $serviceOrderAdd = [
            'order_id' => $serviceOrderData->order_id,
            'customer_id' => $serviceOrderData->customer_id,
            'service_id' =>  $serviceOrderData->service_id,
            'type' => $serviceOrderData->type,
            'leads_id' => $serviceOrderData->id,
            'package_id' => $serviceOrderData->package_id,
            'coupon_amount' => $couponDiscount,
            'package_price' =>  $serviceOrderData->package_price,
            'booking_date' => $serviceOrderData->booking_date,
            'wallet_amount' => $serviceOrderData->via_wallet ?? 0,
            'transection_amount' => $amount,
            'coupon_code' => session()->get('coupon_code_vippooja'),
            'pay_amount' => $totalAmount,
        ];

        if ($existingServiceOrder) {
            // Update existing record
            $existingServiceOrder->update($serviceOrderAdd);
        } else {
            // Create new record only if not exists
            Service_order::create($serviceOrderAdd);
        }

        session()->forget('coupon_discount_vippooja');
        session()->forget('coupon_code_vippooja');

        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $leadsdata->id,
            'order_id'        => $serviceOrderData->order_id,
            'package_id'      => $leadsdata->package_id,
            'service_id'      => $leadsdata->service_id,
            'customer_id'     => $request->customer_id,
            'package_price'   => $leadsdata->package_price,
            'booking_date'    => $leadsdata->booking_date,
            'pandit_assign'   => $request->pandit_assign,
            'wallet_balance'  => $requestedWalletUse,
            'final_amount'    => $amount,   
        ];
        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'vippuja',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function vippooja_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
          
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);
            
            $leadsData = Leads::where('id', $serviceData->leads_id)->first();
            // service_transaction
            $serviceOrderAdd = Service_order::where('order_id', $leadsData->order_id)->first();
            $wallet = User::select('wallet_balance')->where('id', $leadsData->customer_id)->first();
            $actualWalletBalance = $wallet->wallet_balance ?? 0;

            if ($actualWalletBalance > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $leadsData->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'vip order payment';
                $wallet_transaction->transaction_type = 'vip_order_place';
                $wallet_transaction->balance = 0.00;
                $wallet_transaction->debit = $leadsData->via_wallet;
                $wallet_transaction->save();
                User::where('id', $leadsData->customer_id)->update(['wallet_balance' => 0]);
            } 
            if ($serviceOrderAdd) {
                $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }

            $productlist = ProductLeads::where('leads_id', $serviceData->leads_id)->get();
            $add_product_array = [];
            foreach ($productlist as $product) {
                $add_product_array[] = [
                    'product_id' => $product->product_id,
                    'price' => $product->product_price,
                    'qty' => $product->qty,
                ];
            }

            PoojaRecords::create([
                'customer_id'     => $serviceOrderAdd->customer_id,
                'service_id'      => $serviceOrderAdd->service_id,
                'product_id'      => json_encode($add_product_array),
                'service_order_id'=> $serviceOrderAdd->order_id,
                'package_id'      => $serviceOrderAdd->package_id,
                'package_price'   => $serviceOrderAdd->package_price ?? 0.00,
                'amount'          => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon'          => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet'      => $serviceOrderAdd->wallet_amount ?? 0.00,
                'via_online'      => $serviceOrderAdd->transection_amount ?? 0.00,
                'booking_date'    => $serviceOrderAdd->booking_date
            ]);
            $orderId = $serviceOrderAdd->order_id; 
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_vippooja');
                session()->forget('coupon_code_vippooja');

                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\Vippooja::where('id', ($serviceData->service_id ?? ""))->where('is_anushthan', 0)->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' =>  asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
                    'booking_date' => date('d-m-Y', strtotime($serviceData->booking_date)),
                    'puja' => 'VIP Puja',
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter((float)($additionalData['final_amount'] ?? 0) - (float)($bookingDetails->coupon_amount ?? 0)),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];

                $messages =  Helpers::whatsappMessage('vipanushthan', 'Pooja Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your VIP Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('vip.user.detail', $orderId);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    // offline pooja payment
    public function offlinepooja_payment(Request $request)
    {

        $request->validate([
            'leads_id' => 'required|integer|exists:leads,id',
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        // $findLead = OfflineLead::findOrFail($request->leads_id);
        $authCustomer = auth('customer')->user();
        // Validate lead ownership
        // if ($findLead->customer_id !== $authCustomer->id) {
        //     abort(403, 'Unauthorized lead access.');
        // }
        $wallet = User::select('wallet_balance')->where('id', $authCustomer->id)->first();
        $leadsdata = OfflineLead::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $couponDiscount = session('coupon_discount_offlinepooja', 0);
        if ($leadsdata->payment_type == 'full') {
            $amount = max(0, ($leadsdata->package_main_price ?? 0) - $couponDiscount);
            $remainAmount = 0;
        } else {
            $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount);
            $remainAmount = max(0, ($leadsdata->package_main_price ?? 0) - ($leadsdata->package_price ?? 0));
        }
        if ($amount > $wallet['wallet_balance']) {
            $user = Helpers::get_customer($request);
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ]);
            $validator->sometimes('customer_id', 'required', function ($input) {
                return in_array($input->payment_request_from, ['app', 'react']);
            });

            $redirect_link = $this->offlinepooja_customer_payment_request($request);

            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['redirect_link' => $redirect_link], 200);
            } else {
                return redirect($redirect_link);
            }
        } else {
            // $leadsdata = OfflineLead::find($request->leads_id);
            // if (!$leadsdata) {
            //     return redirect()->back()->with('error', 'Lead not found.');
            // }
            // $couponDiscount = session('coupon_discount_offlinepooja', 0);
            // if ($leadsdata->payment_type == 'full') {
            //     $amount = max(0, ($leadsdata->package_main_price ?? 0) - $couponDiscount);
            //     $remainAmount = 0;
            // } else {
            //     $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount);
            //     $remainAmount = max(0, ($leadsdata->package_main_price ?? 0) - ($leadsdata->package_price ?? 0));
            // }

            $actualWalletBalance = $wallet['wallet_balance'] ?? 0;
            $totalAmount =  $amount  + $couponDiscount;
            $remainingWalletBalance = $actualWalletBalance - $amount;
            
            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }
            OfflineLead::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'final_amount' => $totalAmount,
                'via_wallet' => $amount,
                'coupon_amount' => $couponDiscount,
                'remain_amount' => $remainAmount,
            ]);

            $additional_data = [
                'leads_id' => $leadsdata->id,
                'package_id' => $leadsdata->package_id,
                'service_id' => $leadsdata->pooja_id,
                'customer_id' => $authCustomer->id,
                'package_main_price' => $leadsdata->package_main_price,
                'package_price' => $leadsdata->package_price,
                'remain_amount' => $leadsdata->remain_amount,
                'final_amount'    => $amount,
                'wallet_balance'  => $remainingWalletBalance,
                // 'remain_amount_status' => $request->remain_amount_status,
            ];
            $orderId = '';
            $orderData = OfflinePoojaOrder::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'OP' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'OP' . (100001);
            }
            // Wallet Transection Details
            $serviceData = $additional_data;
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $serviceData['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'offline pooja order payment';
            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();
            User::where('id', $authCustomer->id)->update(['wallet_balance' => $remainingWalletBalance]);
            // Service Transection Details
            $serviceOrderAdd = new OfflinePoojaOrder();
            $serviceOrderAdd->customer_id = $serviceData['customer_id'];
            $serviceOrderAdd->service_id = $serviceData['service_id'];
            $serviceOrderAdd->type = PoojaOffline::where('id', $serviceData['service_id'])->value('type');
            $serviceOrderAdd->leads_id = $serviceData['leads_id'];
            $serviceOrderAdd->package_id = $serviceData['package_id'];
            $serviceOrderAdd->package_main_price = $serviceData['package_main_price'];
            $serviceOrderAdd->package_price = $serviceData['package_price'];
            $serviceOrderAdd->remain_amount = $remainAmount;
            $serviceOrderAdd->remain_amount_status = $remainAmount > 0 ? 0 : 1;
            $serviceOrderAdd->wallet_amount = $serviceData['final_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->payment_status = '1';
            $serviceOrderAdd->order_id = $orderId;
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $totalAmount;
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_offlinepooja');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_offinepooja');
            $serviceOrderAdd->save();

            $order = OfflinePoojaOrder::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'offlinepuja_0', type: 'offlinepuja', order: $order));

            $paymentStatus = ($amount == $leadsdata->package_main_price) ? 'Complete' : 'Half';

            OfflineLead::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => $paymentStatus,
                'order_id' => $orderId,
            ]);

            Toastr::success(translate('Payment_success'));
            session()->forget('coupon_discount_offlinepooja');
            session()->forget('coupon_code_offlinepooja');

            // whatsapp
            $userInfo = \App\Models\User::where('id', ($serviceData['customer_id'] ?? ""))->first();
            $service_name = \App\Models\PoojaOffline::where('id', ($serviceData['service_id'] ?? ""))->first();
            $bookingDetails = \App\Models\OfflinePoojaOrder::where('service_id', ($serviceData['service_id'] ?? ""))
                ->where('customer_id', ($serviceData['customer_id'] ?? ""))
                ->where('order_id', ($orderId ?? ""))
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' =>  asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
                'orderId' => $orderId,
                'final_amount' => webCurrencyConverter(amount: (float)$serviceData['final_amount'] + $couponAmount ?? 0),
                'remain_amount' => webCurrencyConverter(amount: (float)($serviceData['remain_amount'])),
                'customer_id' => ($serviceData['customer_id'] ?? ""),
            ];

            $messages =  Helpers::whatsappMessage('offlinepooja', 'Pooja Confirmed', $message_data);
            // Mail Setup for Pooja Management Send to  User Email Id
            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of Your Service Purchase';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                Helpers::emailSendMessage($data);
            }
            return redirect()->route('offline.pooja.user.detail', $orderId);
        }
    }

    public function offlinepooja_customer_payment_request(Request $request)
    {

        $authCustomer = auth('customer')->user();
        $leadsdata = OfflineLead::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $couponDiscount = session('coupon_discount_offlinepooja', 0);
        $wallet = User::select('wallet_balance')->where('id', $authCustomer->id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;

        if ($leadsdata->payment_type == 'full') {
            $amount = max(0, ($leadsdata->package_main_price ?? 0) - $couponDiscount - $requestedWalletUse);
            $remainAmount = 0;
        } else {
            $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount - $requestedWalletUse);
            $remainAmount = max(0, ($leadsdata->package_main_price ?? 0) - ($leadsdata->package_price ?? 0));
        }
        $totalAmount = $amount + $couponDiscount + $requestedWalletUse;


        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));

        $paymentStatus = ($requestedWalletUse + $amount) >= $leadsdata->package_main_price ? 'Complete' : 'Half';

        OfflineLead::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => $paymentStatus,
            'platform' => 'web',
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
            'remain_amount' => $remainAmount,
        ]);

        $serviceOrderData = OfflineLead::where('id', $leadsdata->id)->first();
        $orderId = "";
        $orderData = OfflinePoojaOrder::select('id')->latest()->first();
        if (!empty($orderData['id'])) {
            $orderId = 'OP' . (100000 + $orderData['id'] + 1);
        } else {
            $orderId = 'OP' . (100001);
        }

        if ($actualWalletBalance > 0) {
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $authCustomer->id;
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'offline pooja order payment';
            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
            $wallet_transaction->balance = 0.00;
            $wallet_transaction->debit = $serviceOrderData->via_wallet;
            $wallet_transaction->save();
            User::where('id', $authCustomer->id)->update(['wallet_balance' => 0]);
        }

        // Service Transection Details
        $serviceOrderAdd = new OfflinePoojaOrder();
        $serviceOrderAdd->order_id = $orderId;
        $serviceOrderAdd->customer_id = $authCustomer->id;
        $serviceOrderAdd->service_id = $serviceOrderData->pooja_id;
        $serviceOrderAdd->type = PoojaOffline::where('id', $serviceOrderData->pooja_id)->value('type');
        $serviceOrderAdd->leads_id = $serviceOrderData->id;
        $serviceOrderAdd->package_id = $serviceOrderData->package_id;
        $serviceOrderAdd->package_main_price = $serviceOrderData->package_main_price;
        $serviceOrderAdd->package_price = $serviceOrderData->package_price;
        $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_offlinepooja');
        $serviceOrderAdd->coupon_code = session()->get('coupon_code_offlinepooja');
        // $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
        $serviceOrderAdd->wallet_amount = $serviceOrderData->via_wallet ?? 0;
        $serviceOrderAdd->transection_amount = $amount;
        $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
        // $walletBalance = $serviceData->wallet_balance ?? 0;
        // $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
        $serviceOrderAdd->pay_amount = $totalAmount;
        $serviceOrderAdd->remain_amount = $serviceOrderData->remain_amount;
        $serviceOrderAdd->remain_amount_status = $serviceOrderData->remain_amount > 0 ? 0 : 1;
        $serviceOrderAdd->save();

        session()->forget('coupon_discount_offlinepooja');
        session()->forget('coupon_code_offlinepooja');

        $additional_data = [
            'business_name' => $companyName,
            'business_logo' => $companyLogo,
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
            'leads_id' => $leadsdata->id,
            'order_id' => $orderId,
            'package_id' => $leadsdata->package_id,
            'service_id' => $leadsdata->pooja_id,
            'customer_id' => $authCustomer->id,
            'package_main_price' => $leadsdata->package_main_price,
            'package_price' => $leadsdata->package_price,
            'wallet_balance' => $requestedWalletUse,
            'booking_date'    => $leadsdata->booking_date,
            'final_amount' => $amount,
            'remain_amount' => $leadsdata->remain_amount,
            // 'remain_amount_status' => $request->remain_amount_status,
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $authCustomer->id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }

        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);
        // dd($customer);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $authCustomer->id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $authCustomer->id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'pandit_booking',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function offlinepooja_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);
            $leadsData = OfflineLead::where('id', $serviceData->leads_id)->first();
            $orderId = $additionalData['order_id'] ?? '';
            $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $orderId)->first();
            if ($serviceOrderAdd) {
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }

            $order = OfflinePoojaOrder::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'offlinepuja_0', type: 'offlinepuja', order: $order));
            $paidAmount = ($serviceData->wallet_balance ?? 0) + ($serviceOrder['payment_amount'] ?? 0);
            $lead = OfflineLead::find($serviceData->leads_id);
            $paymentStatus = $paidAmount >= ($lead->package_main_price ?? 0) ? 'Complete' : 'Half';

            OfflineLead::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => $paymentStatus,
                'order_id' => $orderId,
            ]);

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_offlinepooja');
                session()->forget('coupon_code_offlinepooja');


                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\PoojaOffline::where('id', ($serviceData->service_id ?? ""))->first();
                $bookingDetails = \App\Models\OfflinePoojaOrder::where('service_id', ($serviceData->service_id ?? ""))
                    ->where('customer_id', ($serviceData->customer_id ?? ""))
                    ->where('order_id', ($orderId ?? ""))
                    ->first();

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' => asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter(amount: (float)$additionalData['final_amount'] ?? 0),
                    'remain_amount' => webCurrencyConverter(
                        amount: (float)(
                            ($serviceData->remain_amount ?? 0)
                        )
                    ),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('offlinepooja', 'Pooja Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('offline.pooja.user.detail', $orderId);
            }
        } else {
            // OfflinePoojaOrder::where('order_id', $orderId)->update(['payment_status' => 2]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    // offline pooja pending order payment
    public function offlinepooja_pending_payment(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        $authCustomer = auth('customer')->user();
        $wallet = User::select('wallet_balance')->where('id', $authCustomer->id)->first();
        $orderData = OfflinePoojaOrder::where('order_id',$request->order_id)->first(); 
        $totalAmount=$orderData->transection_amount;
        $actualWalletBalance = $wallet['wallet_balance'] ?? 0;
        // $leadsdata = OfflineLead::find($request->leads_id);
        // if (!$leadsdata) {
        //     return redirect()->back()->with('error', 'Lead not found.');
        // }
        // $couponDiscount = session('coupon_discount_offlinepooja', 0);
        // if ($leadsdata->payment_type == 'full') {
        //     $amount = max(0, ($leadsdata->package_main_price ?? 0) - $couponDiscount);
        //     $remainAmount = 0;
        // } else {
        //     $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount);
        //     $remainAmount = max(0, ($leadsdata->package_main_price ?? 0) - ($leadsdata->package_price ?? 0));
        // }

        
        // if ($totalAmount > $actualWalletBalance) {
        //     // $user = Helpers::get_customer($request);
        //     $validator = Validator::make($request->all(), [
        //         'payment_method' => 'required',
        //         'payment_platform' => 'required',
        //     ]);
        //     $validator->sometimes('customer_id', 'required', function ($input) {
        //         return in_array($input->payment_request_from, ['app', 'react']);
        //     });

        //     $redirect_link = $this->offlinepooja_pending_customer_payment_request($request);

        //     if (in_array($request->payment_request_from, ['app', 'react'])) {
        //         return response()->json(['redirect_link' => $redirect_link], 200);
        //     } else {
        //         return redirect($redirect_link);
        //     }
        // } else {
        //     $remainingWalletBalance = $actualWalletBalance - $amount;
        //     if ($remainingWalletBalance < 0) {
        //         $remainingWalletBalance = 0;
        //     }

        //     $additional_data = [
        //         'leads_id' => $leadsdata->id,
        //         'package_id' => $leadsdata->package_id,
        //         'service_id' => $leadsdata->pooja_id,
        //         'customer_id' => $authCustomer->id,
        //         'package_main_price' => $leadsdata->package_main_price,
        //         'package_price' => $leadsdata->package_price,
        //         'remain_amount' => $leadsdata->remain_amount,
        //         'final_amount'    => $amount,
        //         'wallet_balance'  => $remainingWalletBalance,
        //         // 'remain_amount_status' => $request->remain_amount_status,
        //     ];
        //     $orderId = '';
        //     $orderData = OfflinePoojaOrder::select('id')->latest()->first();
        //     if (!empty($orderData['id'])) {
        //         $orderId = 'OP' . (100000 + $orderData['id'] + 1);
        //     } else {
        //         $orderId = 'OP' . (100001);
        //     }
        //     // Wallet Transection Details
        //     $serviceData = $additional_data;
        //     $wallet_transaction = new WalletTransaction();
        //     $wallet_transaction->user_id = $serviceData['customer_id'];
        //     $wallet_transaction->transaction_id = \Str::uuid();
        //     $wallet_transaction->reference = 'offline pooja order payment';
        //     $wallet_transaction->transaction_type = 'offline_pooja_order_place';
        //     $wallet_transaction->balance = $remainingWalletBalance;
        //     $wallet_transaction->debit = $amount;
        //     $wallet_transaction->save();
        //     User::where('id', $authCustomer->id)->update(['wallet_balance' => $remainingWalletBalance]);
        //     // Service Transection Details
        //     $serviceOrderAdd = new OfflinePoojaOrder();
        //     $serviceOrderAdd->customer_id = $serviceData['customer_id'];
        //     $serviceOrderAdd->service_id = $serviceData['service_id'];
        //     $serviceOrderAdd->type = PoojaOffline::where('id', $serviceData['service_id'])->value('type');
        //     $serviceOrderAdd->leads_id = $serviceData['leads_id'];
        //     $serviceOrderAdd->package_id = $serviceData['package_id'];
        //     $serviceOrderAdd->package_main_price = $serviceData['package_main_price'];
        //     $serviceOrderAdd->package_price = $serviceData['package_price'];
        //     $serviceOrderAdd->remain_amount = $remainAmount;
        //     $serviceOrderAdd->remain_amount_status = $remainAmount > 0 ? 0 : 1;
        //     $serviceOrderAdd->wallet_amount = $serviceData['final_amount'];
        //     $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
        //     $serviceOrderAdd->payment_status = '1';
        //     $serviceOrderAdd->order_id = $orderId;
        //     $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
        //     $serviceOrderAdd->pay_amount = $totalAmount;
        //     $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_offlinepooja');
        //     $serviceOrderAdd->coupon_code = session()->get('coupon_code_offinepooja');
        //     $serviceOrderAdd->save();

        //     $order = OfflinePoojaOrder::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
        //     event(new OrderStatusEvent(key: 'offlinepuja_0', type: 'offlinepuja', order: $order));

        //     $paymentStatus = ($amount == $leadsdata->package_main_price) ? 'Complete' : 'Half';

        //     OfflineLead::where('id', $additional_data['leads_id'])->update([
        //         'status' => 0,
        //         'payment_status' => $paymentStatus,
        //         'order_id' => $orderId,
        //     ]);

        //     Toastr::success(translate('Payment_success'));
        //     session()->forget('coupon_discount_offlinepooja');
        //     session()->forget('coupon_code_offlinepooja');

        //     // whatsapp
        //     $userInfo = \App\Models\User::where('id', ($serviceData['customer_id'] ?? ""))->first();
        //     $service_name = \App\Models\PoojaOffline::where('id', ($serviceData['service_id'] ?? ""))->first();
        //     $bookingDetails = \App\Models\OfflinePoojaOrder::where('service_id', ($serviceData['service_id'] ?? ""))
        //         ->where('customer_id', ($serviceData['customer_id'] ?? ""))
        //         ->where('order_id', ($orderId ?? ""))
        //         ->first();

        //     $message_data = [
        //         'service_name' => $service_name['name'],
        //         'type' => 'text-with-media',
        //         'attachment' =>  asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
        //         'orderId' => $orderId,
        //         'final_amount' => webCurrencyConverter(amount: (float)$serviceData['final_amount'] + $couponAmount ?? 0),
        //         'remain_amount' => webCurrencyConverter(amount: (float)($serviceData['remain_amount'])),
        //         'customer_id' => ($serviceData['customer_id'] ?? ""),
        //     ];

        //     $messages =  Helpers::whatsappMessage('offlinepooja', 'Pooja Confirmed', $message_data);
        //     // Mail Setup for Pooja Management Send to  User Email Id
        //     if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
        //         $data['type'] = 'pooja';
        //         $data['email'] = $userInfo['email'];
        //         $data['subject'] = 'Confirmation of Your Service Purchase';
        //         $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

        //         Helpers::emailSendMessage($data);
        //     }
        //     return redirect()->route('offline.pooja.user.detail', $orderId);
        // }
    }

    public function offlinepooja_pending_customer_payment_request(Request $request)
    {

        $authCustomer = auth('customer')->user();
        $leadsdata = OfflineLead::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $couponDiscount = session('coupon_discount_offlinepooja', 0);
        $wallet = User::select('wallet_balance')->where('id', $authCustomer->id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;

        if ($leadsdata->payment_type == 'full') {
            $amount = max(0, ($leadsdata->package_main_price ?? 0) - $couponDiscount - $requestedWalletUse);
            $remainAmount = 0;
        } else {
            $amount = max(0, ($leadsdata->package_price ?? 0) - $couponDiscount - $requestedWalletUse);
            $remainAmount = max(0, ($leadsdata->package_main_price ?? 0) - ($leadsdata->package_price ?? 0));
        }
        $totalAmount = $amount + $couponDiscount + $requestedWalletUse;


        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));

        $paymentStatus = ($requestedWalletUse + $amount) >= $leadsdata->package_main_price ? 'Complete' : 'Half';

        OfflineLead::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => $paymentStatus,
            'platform' => 'web',
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
            'remain_amount' => $remainAmount,
        ]);

        $serviceOrderData = OfflineLead::where('id', $leadsdata->id)->first();
        $orderId = "";
        $orderData = OfflinePoojaOrder::select('id')->latest()->first();
        if (!empty($orderData['id'])) {
            $orderId = 'OP' . (100000 + $orderData['id'] + 1);
        } else {
            $orderId = 'OP' . (100001);
        }

        if ($actualWalletBalance > 0) {
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $authCustomer->id;
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'offline pooja order payment';
            $wallet_transaction->transaction_type = 'offline_pooja_order_place';
            $wallet_transaction->balance = 0.00;
            $wallet_transaction->debit = $serviceOrderData->via_wallet;
            $wallet_transaction->save();
            User::where('id', $authCustomer->id)->update(['wallet_balance' => 0]);
        }

        // Service Transection Details
        $serviceOrderAdd = new OfflinePoojaOrder();
        $serviceOrderAdd->order_id = $orderId;
        $serviceOrderAdd->customer_id = $authCustomer->id;
        $serviceOrderAdd->service_id = $serviceOrderData->pooja_id;
        $serviceOrderAdd->type = PoojaOffline::where('id', $serviceOrderData->pooja_id)->value('type');
        $serviceOrderAdd->leads_id = $serviceOrderData->id;
        $serviceOrderAdd->package_id = $serviceOrderData->package_id;
        $serviceOrderAdd->package_main_price = $serviceOrderData->package_main_price;
        $serviceOrderAdd->package_price = $serviceOrderData->package_price;
        $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_offlinepooja');
        $serviceOrderAdd->coupon_code = session()->get('coupon_code_offlinepooja');
        // $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
        $serviceOrderAdd->wallet_amount = $serviceOrderData->via_wallet ?? 0;
        $serviceOrderAdd->transection_amount = $amount;
        $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
        // $walletBalance = $serviceData->wallet_balance ?? 0;
        // $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
        $serviceOrderAdd->pay_amount = $totalAmount;
        $serviceOrderAdd->remain_amount = $serviceOrderData->remain_amount;
        $serviceOrderAdd->remain_amount_status = $serviceOrderData->remain_amount > 0 ? 0 : 1;
        $serviceOrderAdd->save();

        session()->forget('coupon_discount_offlinepooja');
        session()->forget('coupon_code_offlinepooja');

        $additional_data = [
            'business_name' => $companyName,
            'business_logo' => $companyLogo,
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
            'leads_id' => $leadsdata->id,
            'order_id' => $orderId,
            'package_id' => $leadsdata->package_id,
            'service_id' => $leadsdata->pooja_id,
            'customer_id' => $authCustomer->id,
            'package_main_price' => $leadsdata->package_main_price,
            'package_price' => $leadsdata->package_price,
            'wallet_balance' => $requestedWalletUse,
            'booking_date'    => $leadsdata->booking_date,
            'final_amount' => $amount,
            'remain_amount' => $leadsdata->remain_amount,
            // 'remain_amount_status' => $request->remain_amount_status,
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $authCustomer->id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }

        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);
        // dd($customer);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $authCustomer->id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $authCustomer->id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'pandit_booking',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function offlinepooja_pending_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);
            $leadsData = OfflineLead::where('id', $serviceData->leads_id)->first();
            $orderId = $additionalData['order_id'] ?? '';
            $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $orderId)->first();
            if ($serviceOrderAdd) {
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }

            $order = OfflinePoojaOrder::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: 'offlinepuja_0', type: 'offlinepuja', order: $order));
            $paidAmount = ($serviceData->wallet_balance ?? 0) + ($serviceOrder['payment_amount'] ?? 0);
            $lead = OfflineLead::find($serviceData->leads_id);
            $paymentStatus = $paidAmount >= ($lead->package_main_price ?? 0) ? 'Complete' : 'Half';

            OfflineLead::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => $paymentStatus,
                'order_id' => $orderId,
            ]);

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_offlinepooja');
                session()->forget('coupon_code_offlinepooja');


                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\PoojaOffline::where('id', ($serviceData->service_id ?? ""))->first();
                $bookingDetails = \App\Models\OfflinePoojaOrder::where('service_id', ($serviceData->service_id ?? ""))
                    ->where('customer_id', ($serviceData->customer_id ?? ""))
                    ->where('order_id', ($orderId ?? ""))
                    ->first();

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' => asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter(amount: (float)$additionalData['final_amount'] ?? 0),
                    'remain_amount' => webCurrencyConverter(
                        amount: (float)(
                            ($serviceData->remain_amount ?? 0)
                        )
                    ),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('offlinepooja', 'Pooja Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('offline.pooja.user.detail', $orderId);
            }
        } else {
            // OfflinePoojaOrder::where('order_id', $orderId)->update(['payment_status' => 2]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    // offline pooja remaining payment 
    public function offlinepooja_remaining_payment(Request $request)
    {
        if ($request->payment_amount == 0) {
            // amount will be deduct completely from wallet
            $packageMainPrice = OfflinePoojaOrder::where('order_id', $request->order_id)->value('package_main_price');
            $alreadyWalletDeducted = OfflinePoojaOrder::where('order_id', $request->order_id)->value('wallet_amount');
            $totalWalletDeduction = $alreadyWalletDeducted + $request->wallet_deduction;

            $order = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
            if (!$order) {
                return redirect()->back()->with('error', 'Lead not found.');
            }

            $walletDeduction = $order->remain_amount;
            $alreadyWalletDeducted = $order->wallet_amount;
            $totalWalletDeduction = $alreadyWalletDeducted + $walletDeduction;

            $remainingPay = OfflinePoojaOrder::where('order_id', $request->order_id)->update([
                'wallet_amount' => $totalWalletDeduction,
                'pay_amount' => $order->package_main_price,
                'remain_amount' => 0,
                'remain_amount_status' => 1,
            ]);

            if ($remainingPay) {
                $prevWalletAmt = User::where('id', $request->customer_id)->value('wallet_balance');
                $newWalletAmt = $prevWalletAmt - $walletDeduction;
                User::where('id', $request->customer_id)->update(['wallet_balance' => $newWalletAmt]);

                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $request->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'offline pooja order payment';
                $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                $wallet_transaction->balance = $newWalletAmt;
                $wallet_transaction->debit = $walletDeduction;
                $wallet_transaction->save();
            }

            // Get existing lead related to this order
            $lead = OfflineLead::where('order_id', $order->order_id)->first();

            if ($lead) {
                $newViaWallet = $lead->via_wallet + $walletDeduction;

                OfflineLead::where('id', $lead->id)->update([
                    'status' => 0,
                    'payment_status' => 'Complete',
                    'platform' => 'web',
                    'final_amount' => $order->package_main_price,
                    'via_wallet' => $newViaWallet,
                    'remain_amount' => 0,
                ]);
            }
            // whatsapp
            $userInfo = \App\Models\User::where('id', $request->customer_id)->first();
            $poojaOrder = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
            $service_name = \App\Models\PoojaOffline::where('id', $poojaOrder->service_id)->first();
            $bookingDetails = $poojaOrder;

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
                'orderId' => $request->order_id,
                'customer_id' => $request->customer_id,
            ];
            $messages =  Helpers::whatsappMessage('offlinepooja', 'Reschedule', $message_data);

            // Mail Setup for Pooja Management Send to  User Email Id
            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of pay remain amount';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offlinepooja-remain', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                Helpers::emailSendMessage($data);
            }

            Toastr::success(translate('reamining_pooja_amount_paid_successfully'));
            return redirect()->back();
        } else {
            $user = Helpers::get_customer($request);
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ]);
            $validator->sometimes('customer_id', 'required', function ($input) {
                return in_array($input->payment_request_from, ['app', 'react']);
            });

            $redirect_link = $this->offlinepooja_remaining_customer_payment_request($request);

            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['redirect_link' => $redirect_link], 200);
            } else {
                return redirect($redirect_link);
            }
        }
    }

    public function offlinepooja_remaining_customer_payment_request(Request $request)
    {
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;

        $order = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
        if (!$order) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $lead = OfflineLead::where('order_id', $order->order_id)->first();
        if ($lead) {
            OfflineLead::where('id', $lead->id)->update([
                'status' => 0,
                'platform' => 'web',
                'final_amount' => $order->package_main_price,
                'via_wallet' => ($lead->via_wallet ?? 0) + $requestedWalletUse,
                'via_online' => ($lead->via_online ?? 0) + max(0, $order->remain_amount - $requestedWalletUse),
                'remain_amount' => 0,
            ]);
        }


        $additional_data = [
            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
            'customer_id' => $request->customer_id,
            'wallet_deduction' => $requestedWalletUse,
            'payment_amount' => $order->remain_amount - $requestedWalletUse,
            'order_id' => $request->order_id,
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: max(0, $order->remain_amount - $requestedWalletUse),
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'order',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function offlinepooja_remaining_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);
            $orderData = OfflinePoojaOrder::where('order_id', $additionalData['order_id'])->first();
            $totalWalletDeduction = $orderData['wallet_amount'] + $additionalData['wallet_deduction'];
            $totalPaymentAmount = $orderData['package_main_price'];
            $totalTransactionAmount = $orderData['transection_amount'] + $additionalData['payment_amount'];

            // service_transaction
            $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $additionalData['order_id'])->first();
            $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
            $serviceOrderAdd->transection_amount = $totalTransactionAmount;
            $serviceOrderAdd->pay_amount = $totalPaymentAmount;
            if ($additionalData['wallet_deduction'] > 0) {
                $serviceOrderAdd->wallet_amount = $totalWalletDeduction;
            }
            $serviceOrderAdd->remain_amount = 0;
            $serviceOrderAdd->remain_amount_status = 1;
            $serviceOrderAdd->save();
            OfflineLead::where('id', $orderData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'payment_type' => 'partial/full'
            ]);

            if ($additionalData['wallet_deduction'] > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $additionalData['customer_id'];
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'offline pooja order payment';
                $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                $wallet_transaction->balance = 0.00;
                $wallet_transaction->debit = $additionalData['wallet_deduction'];
                $wallet_transaction->save();
                User::where('id', $additionalData['customer_id'])->update(['wallet_balance' => 0]);
            }

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_offlinepooja');
                session()->forget('coupon_code_offlinepooja');

                // whatsapp
                $userInfo = \App\Models\User::where('id', ($orderData->customer_id ?? ""))->first();
                $service_name = \App\Models\PoojaOffline::where('id', ($orderData->service_id ?? ""))->first();
                $bookingDetails = $orderData;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' => asset('/storage/app/public/offlinepooja/thumbnail/' . $service_name->thumbnail),
                    'orderId' => $orderData->order_id,
                    'customer_id' => ($orderData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('offlinepooja', 'Remaining Payment', $message_data);
                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of pay remain amount';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offlinepooja-remain', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('account-offlinepooja-order-details', $additionalData['order_id']);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }


    // offline pooja schedule payment 
    public function offlinepooja_schedule_payment(Request $request)
    {
        if ($request->payment_amount == 0) {
            // amount will be deduct completely from wallet
            $schedulePooja = OfflinePoojaOrder::where('order_id', $request->order_id)->update(['booking_date' => $request->booking_date, 'schedule_status' => 1, 'schedule_amount' => $request->wallet_deduction]);
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
            }

            // whatsapp
            $userInfo = \App\Models\User::where('id', $request->customer_id)->first();
            $poojaOrder = OfflinePoojaOrder::where('order_id', $request->order_id)->first();
            $service_name = \App\Models\PoojaOffline::where('id', $poojaOrder->service_id)->first();
            $bookingDetails = $poojaOrder;

            $message_data = [
                'service_name' => $service_name['name'],
                'orderId' => $request->order_id,
                'booking_date' => $request->booking_date,
                'schedule_amount' => webCurrencyConverter(amount: (float)$request->wallet_deduction ?? 0),
                'customer_id' => $request->customer_id,
            ];
            $messages =  Helpers::whatsappMessage('offlinepooja', 'Reschedule', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Reschedule puja';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-reschedule', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                Helpers::emailSendMessage($data);
            }
            Toastr::success(translate('offline_pooja_scheduled_successfully'));
            return redirect()->back();
        } else {
            // amount will be deduct from online and may be from wallet
            $user = Helpers::get_customer($request);
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ]);
            $validator->sometimes('customer_id', 'required', function ($input) {
                return in_array($input->payment_request_from, ['app', 'react']);
            });

            $redirect_link = $this->offlinepooja_schedule_customer_payment_request($request);

            if (in_array($request->payment_request_from, ['app', 'react'])) {
                return response()->json(['redirect_link' => $redirect_link], 200);
            } else {
                return redirect($redirect_link);
            }
        }
    }

    public function offlinepooja_schedule_customer_payment_request(Request $request)
    {
        $additional_data = [
            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
            'booking_date' => $request->booking_date,
            'wallet_deduction' => $request->wallet_deduction,
            'payment_amount' => $request->payment_amount,
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
        ];


        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $request->payment_amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'order',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function offlinepooja_schedule_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            // service_transaction
            $serviceOrderAdd = OfflinePoojaOrder::where('order_id', $additionalData['order_id'])->first();
            $serviceOrderAdd->booking_date = $additionalData['booking_date'];
            $serviceOrderAdd->schedule_status = 1;
            $serviceOrderAdd->schedule_amount = $additionalData['payment_amount'] + $additionalData['wallet_deduction'];
            if ($serviceOrderAdd->save()) {
                if ($additionalData['wallet_deduction'] > 0) {
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $additionalData['customer_id'];
                    $wallet_transaction->transaction_id = \Str::uuid();
                    $wallet_transaction->reference = 'offline pooja order payment';
                    $wallet_transaction->transaction_type = 'offline_pooja_order_place';
                    $wallet_transaction->balance = 0.00;
                    $wallet_transaction->debit = $additionalData['wallet_deduction'];
                    $wallet_transaction->save();

                    $prevWalletAmt = User::where('id', $additionalData['customer_id'])->value('wallet_balance');
                    $newWalletAmt = $prevWalletAmt - $additionalData['wallet_deduction'];
                    User::where('id', $additionalData['customer_id'])->update(['wallet_balance' => $newWalletAmt]);
                }
            }

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceOrderAdd->customer_id ?? ""))->first();
                $service_name = \App\Models\PoojaOffline::where('id', ($serviceOrderAdd->service_id ?? ""))->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'orderId' => $additionalData['order_id'],
                    'booking_date' => $additionalData['booking_date'],
                    'schedule_amount' => webCurrencyConverter(amount: (float)$serviceOrderAdd['schedule_amount'] ?? 0),
                    'customer_id' => ($serviceOrderAdd->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('offlinepooja', 'Reschedule', $message_data);

                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Reschedule puja';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.offline-pooja-reschedule', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                    Helpers::emailSendMessage($data);
                }
                Toastr::success(translate('Payment_success'));
                session()->forget('coupon_discount_offlinepooja');
                session()->forget('coupon_code_offlinepooja');
                return redirect()->route('account-offlinepooja-order-details', $additionalData['order_id']);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    // ----------------------------------------ANUSHTHAN POOJA PAYMENT METHOD WORKING ON 25/07/2024----------------------------------------------------
    public function anushthan_payment(Request $request)
    {
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet['wallet_balance'] ?? 0;

        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }

        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_anushthan', 0);
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount);
        $totalAmount = $amount + $couponDiscount;

        // Wallet payment if payment_amount == 0
        if ($request->payment_amount == 0) {
            if ($actualWalletBalance < $amount) {
                return redirect()->back()->with('error', 'Insufficient wallet balance.');
            }

            $remainingWalletBalance = $actualWalletBalance - $amount;
            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }

            $orderId = '';
            $orderData = Service_order::select('id')->latest()->first();
            $orderId = 'APJ' . (100000 + ($orderData['id'] ?? 0) + 1);

            Leads::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'add_product_id' => json_encode($add_product_array),
                'final_amount' => $totalAmount,
                'via_wallet' => $amount,
                'coupon_amount' => $couponDiscount,
                'order_id'  => $orderId,
            ]);
            $additional_data = [
                'leads_id' => $leadsdata->id,
                'package_id' => $leadsdata->package_id,
                'service_id' => $leadsdata->service_id,
                'customer_id' => $request->customer_id,
                'package_price' => $leadsdata->package_price,
                'booking_date' => $leadsdata->booking_date,
                'pandit_assign' => $request->pandit_assign,
                'wallet_balance' => $remainingWalletBalance,
                'final_amount' => $amount,
            ];


            User::where('id', $additional_data['customer_id'])->update(['wallet_balance' => $remainingWalletBalance]);

            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $additional_data['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'anushthan order payment';
            $wallet_transaction->transaction_type = 'anushthan_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();

            $serviceOrderAdd = new Service_order();
            $serviceOrderAdd->customer_id = $additional_data['customer_id'];
            $serviceOrderAdd->service_id = $additional_data['service_id'];
            $serviceOrderAdd->type = 'anushthan';
            $serviceOrderAdd->coupon_amount = session()->get('coupon_discount_anushthan');
            $serviceOrderAdd->coupon_code = session()->get('coupon_code_anushthan');
            $serviceOrderAdd->leads_id = $additional_data['leads_id'];
            $serviceOrderAdd->package_id = $additional_data['package_id'];
            $serviceOrderAdd->package_price = $additional_data['package_price'];
            $serviceOrderAdd->booking_date = $additional_data['booking_date'];
            $serviceOrderAdd->pandit_assign = $additional_data['pandit_assign'];
            $couponAmount = $serviceOrderAdd->coupon_amount ?? 0;
            $serviceOrderAdd->pay_amount = $additional_data['final_amount'] + $couponAmount;
            $serviceOrderAdd->wallet_amount = $additional_data['final_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->payment_status = '1';
            $serviceOrderAdd->payment_id = 'pay_wallet';
            $serviceOrderAdd->save();

            PoojaRecords::create([
                'customer_id' => $serviceOrderAdd->customer_id,
                'service_id' => $serviceOrderAdd->service_id,
                'product_id' => json_encode($add_product_array),
                'service_order_id' => $serviceOrderAdd->order_id,
                'package_id' => $serviceOrderAdd->package_id,
                'package_price' => $serviceOrderAdd->package_price ?? 0.00,
                'amount' => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon' => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet' => $serviceOrderAdd->wallet_amount ?? 0.00,
                'booking_date' => $serviceOrderAdd->booking_date,
            ]);

            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'order_id' => $orderId,
            ]);

            Toastr::success(translate('Payment_success'));
            session()->forget('coupon_discount_anushthan');
            session()->forget('coupon_code_anushthan');

            $userInfo = User::find($additional_data['customer_id']);
            $service_name = Vippooja::where('id', $additional_data['service_id'])->where('is_anushthan', 1)->first();
            $bookingDetails = Service_order::where('service_id', $additional_data['service_id'])
                ->where('type', 'Anushthan')
                ->where('booking_date', $additional_data['booking_date'])
                ->where('customer_id', $additional_data['customer_id'])
                ->where('order_id', $orderId)
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
                'booking_date' => date('d-m-Y', strtotime($additional_data['booking_date'])),
                'orderId' => $orderId,
                'puja' => 'VIP Puja',
                'final_amount' => webCurrencyConverter((float)($amount)),
                'customer_id' => $additional_data['customer_id'],
            ];
            Helpers::whatsappMessage('vipanushthan', 'Pooja Confirmed', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Confirmation of Your Anushthan Service Purchase';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                Helpers::emailSendMessage($data);
            }

            return redirect()->route('anushthan.user.detail', [$orderId]);
        }

        // Online Payment Handling
        $user = Helpers::get_customer($request);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        $validator->sometimes('customer_id', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });

        $redirect_link = $this->anushthan_customer_payment_request($request);
        $redirect_link = str_replace(["\r", "\n"], '', $redirect_link);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }
    // -------------------------------------------------------------------------------------------------
    public function anushthan_customer_payment_request(Request $request)
    {
        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        // dd($add_product_array);
        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $couponDiscount = session('coupon_discount_anushthan', 0);
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;
        $amount = max(0, ($leadsdata->package_price ?? 0) + $productamount - $couponDiscount - $requestedWalletUse);  
        $totalAmount =  $amount  + $couponDiscount + $requestedWalletUse;
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));
        if (!empty($leadsdata->order_id)) {
            $orderId = $leadsdata->order_id;
        } else {
            $orderData = Service_order::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'APJ' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'APJ' . (100001);
            }
        }   
        Leads::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' =>json_encode($add_product_array),
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'coupon_amount' => $couponDiscount,
            'via_online' => $amount,
            'order_id' => $orderId ,
        ]);
        $serviceOrderData = Leads::where('id', $leadsdata->id)->first();

        $existingServiceOrder = Service_order::where('order_id', $serviceOrderData->order_id)->first();
        
        $serviceOrderAdd = [
            'order_id' => $serviceOrderData->order_id,
            'customer_id' => $serviceOrderData->customer_id,
            'service_id' =>  $serviceOrderData->service_id,
            'type' => $serviceOrderData->type,
            'leads_id' => $serviceOrderData->id,
            'package_id' => $serviceOrderData->package_id,
            'coupon_amount' => $couponDiscount,
            'package_price' =>  $serviceOrderData->package_price,
            'booking_date' => $serviceOrderData->booking_date,
            'wallet_amount' => $serviceOrderData->via_wallet ?? 0,
            'transection_amount' => $amount,
            'coupon_code' => session()->get('coupon_code_anushthan'),
            'pay_amount' => $totalAmount,
        ];
        if ($existingServiceOrder) {
            // Update existing record
            $existingServiceOrder->update($serviceOrderAdd);
        } else {
            // Create new record only if not exists
            Service_order::create($serviceOrderAdd);
        }

        session()->forget('coupon_discount_anushthan');
        session()->forget('coupon_code_anushthan');
        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $leadsdata->id,
            'order_id'        => $serviceOrderData->order_id,
            'package_id'      => $leadsdata->package_id,
            'service_id'      => $leadsdata->service_id,
            'customer_id'     => $request->customer_id,
            'package_price'   => $leadsdata->package_price,
            'booking_date'    => $leadsdata->booking_date,
            'pandit_assign'   => $request->pandit_assign,
            'wallet_balance'  => $requestedWalletUse,
            'final_amount'    => $amount,   
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'anushthan',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function anushthan_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);
            
            $leadsData = Leads::where('id', $serviceData->leads_id)->first();

            $serviceOrderAdd = Service_order::where('order_id', $leadsData->order_id)->first();
            $wallet = User::select('wallet_balance')->where('id', $leadsData->customer_id)->first();
            $actualWalletBalance = $wallet->wallet_balance ?? 0;

            if ($actualWalletBalance > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $leadsData->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'anushthan order payment';
                $wallet_transaction->transaction_type = 'anushthan_order_place';
                $wallet_transaction->balance = 0.00;
                $wallet_transaction->debit = $leadsData->via_wallet;
                $wallet_transaction->save();
                User::where('id', $leadsData->customer_id)->update(['wallet_balance' => 0]);
            }
            if ($serviceOrderAdd) {
                $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }
            $productlist = ProductLeads::where('leads_id', $serviceData->leads_id)->get();
            $add_product_array = [];
            foreach ($productlist as $product) {
                $add_product_array[] = [
                    'product_id' => $product->product_id,
                    'price' => $product->product_price,
                    'qty' => $product->qty,
                ];
            }

            PoojaRecords::create([
                'customer_id'     => $serviceOrderAdd->customer_id,
                'service_id'      => $serviceOrderAdd->service_id,
                'product_id'      => json_encode($add_product_array),
                'service_order_id'=> $serviceOrderAdd->order_id,
                'package_id'      => $serviceOrderAdd->package_id,
                'package_price'   => $serviceOrderAdd->package_price ?? 0.00,
                'amount'          => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon'          => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet'      => $serviceOrderAdd->wallet_amount ?? 0.00,
                'via_online'      => $serviceOrderAdd->transection_amount ?? 0.00,
                'booking_date'    => $serviceOrderAdd->booking_date,
            ]);
            $orderId = $serviceOrderAdd->order_id;
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));

                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\Vippooja::where('id', ($serviceData->service_id ?? ""))->where('is_anushthan', 1)->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' =>  asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
                    'booking_date' => date('d-m-Y', strtotime($serviceData->booking_date)),
                    'puja' => 'Anushthan',
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter((float)$additionalData['final_amount'] - ($bookingDetails->coupon_amount ?? 0)),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('vipanushthan', 'Pooja Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Confirmation of Your Anushthan Service Purchase';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('anushthan.user.detail', $orderId);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }
    // ----------------------------------------CHADHAVA PAYMENT METHOD WORKING ON 25/07/2024----------------------------------------------------
    public function chadhava_payment(Request $request)
    {
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet['wallet_balance'] ?? 0;

        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }

        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $amount = max(0, ($productamount ?? 0));
        $totalAmount = $amount;

        // Wallet payment if payment_amount == 0
        if ($request->payment_amount == 0) {
            if ($actualWalletBalance < $amount) {
                return redirect()->back()->with('error', 'Insufficient wallet balance.');
            }

            $remainingWalletBalance = $actualWalletBalance - $amount;
            if ($remainingWalletBalance < 0) {
                $remainingWalletBalance = 0;
            }
            $orderId = '';
            $orderData = Chadhava_orders::select('id')->latest()->first();
            $orderId = 'CC' . (100000 + ($orderData['id'] ?? 0) + 1);

            Leads::where('id', $leadsdata->id)->update([
                'status' => 0,
                'payment_status' => 'pending',
                'platform' => 'web',
                'add_product_id' => json_encode($add_product_array),
                'final_amount' => $totalAmount,
                'via_wallet' => $amount,
                'order_id'  => $orderId,
            ]);
            $additional_data = [
                'leads_id' => $leadsdata->id,
                'service_id' => $leadsdata->service_id,
                'customer_id' => $request->customer_id,
                'booking_date' => $leadsdata->booking_date,
                'pandit_assign' => $request->pandit_assign,
                'wallet_balance' => $remainingWalletBalance,
                'final_amount' => $amount,
            ];

          
            User::where('id', $additional_data['customer_id'])->update(['wallet_balance' => $remainingWalletBalance]);

            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $additional_data['customer_id'];
            $wallet_transaction->transaction_id = \Str::uuid();
            $wallet_transaction->reference = 'chadhava order payment';
            $wallet_transaction->transaction_type = 'chadhava_order_place';
            $wallet_transaction->balance = $remainingWalletBalance;
            $wallet_transaction->debit = $amount;
            $wallet_transaction->save();

            $serviceOrderAdd = new Chadhava_orders();
            $serviceOrderAdd->customer_id = $additional_data['customer_id'];
            $serviceOrderAdd->service_id = $additional_data['service_id'];
            $serviceOrderAdd->type = 'chadhava';
            $serviceOrderAdd->leads_id = $additional_data['leads_id'];
            $serviceOrderAdd->booking_date = $additional_data['booking_date'];
            $serviceOrderAdd->pandit_assign = $additional_data['pandit_assign'];
            $serviceOrderAdd->pay_amount = $additional_data['final_amount'];
            $serviceOrderAdd->wallet_amount = $additional_data['final_amount'];
            $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id;
            $serviceOrderAdd->order_id = $orderId;
            $serviceOrderAdd->payment_status = '1';
            $serviceOrderAdd->payment_id = 'pay_wallet';
            $serviceOrderAdd->save();

            $order = Chadhava_orders::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $additional_data['leads_id'])->update([
                'status' => 0,
                'payment_status' => 'Complete',
                'order_id' => $orderId,
            ]);

            Toastr::success(translate('Payment_success'));

            $userInfo = User::find($additional_data['customer_id']);
            $service_name = Chadhava::where('id', $additional_data['service_id'])->first();
            $bookingDetails = Chadhava_orders::where('service_id', $additional_data['service_id'])
                ->where('type', 'chadhava')
                ->where('booking_date', $additional_data['booking_date'])
                ->where('customer_id', $additional_data['customer_id'])
                ->where('order_id', $orderId)
                ->first();

            $message_data = [
                'service_name' => $service_name['name'],
                'type' => 'text-with-media',
                'attachment' => asset('/storage/app/public/chadhava/thumbnail/' . $service_name->thumbnail),
                'booking_date' => date('d-m-Y', strtotime($additional_data['booking_date'])),
                'orderId' => $orderId,
                'chadhava_venue' => $service_name['chadhava_venue'],
                'final_amount' => webCurrencyConverter((float)($amount)),
                'customer_id' => $additional_data['customer_id'],
            ];
            Helpers::whatsappMessage('chadhava', 'Chadhava Confirmed', $message_data);

            if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $data['type'] = 'pooja';
                $data['email'] = $userInfo['email'];
                $data['subject'] = 'Your Online Chadhava Booking Confirmation';
                $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                Helpers::emailSendMessage($data);
            }

            return redirect()->route('chadhava.user.detail', [$orderId]);
        }

        // Online Payment Handling
        $user = Helpers::get_customer($request);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'payment_platform' => 'required',
        ]);
        $validator->sometimes('customer_id', 'required', function ($input) {
            return in_array($input->payment_request_from, ['app', 'react']);
        });

        $redirect_link = $this->chadhava_customer_payment_request($request);
        $redirect_link = str_replace(["\r", "\n"], '', $redirect_link);

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            return response()->json(['redirect_link' => $redirect_link], 200);
        } else {
            return redirect($redirect_link);
        }
    }

    public function chadhava_customer_payment_request(Request $request)
    {
        $leadsdata = Leads::find($request->leads_id);
        if (!$leadsdata) {
            return redirect()->back()->with('error', 'Lead not found.');
        }
        $productlist = ProductLeads::where('leads_id', $request->leads_id)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        $productamount = ProductLeads::where('leads_id', $request->leads_id)->sum('final_price') ?? 0;
        $wallet = User::select('wallet_balance')->where('id', $request->customer_id)->first();
        $actualWalletBalance = $wallet->wallet_balance ?? 0;
        $requestedWalletUse = $actualWalletBalance;
        $amount = max(0, ($productamount ?? 0)  -  $requestedWalletUse);
        $totalAmount =  $amount + $requestedWalletUse;
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));
        if (!empty($leadsdata->order_id)) {
            $orderId = $leadsdata->order_id;
        } else {
            $orderData = Chadhava_orders::select('id')->latest()->first();
            if (!empty($orderData['id'])) {
                $orderId = 'CC' . (100000 + $orderData['id'] + 1);
            } else {
                $orderId = 'CC' . (100001);
            }
        }   
        Leads::where('id', $leadsdata->id)->update([
            'status' => 0,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' => json_encode($add_product_array),
            'final_amount' => $totalAmount,
            'via_wallet' => $requestedWalletUse,
            'via_online' => $amount,
            'order_id' => $orderId,
        ]);
        $serviceOrderData = Leads::where('id', $leadsdata->id)->first();

        $existingServiceOrder = Chadhava_orders::where('order_id', $serviceOrderData->order_id)->first();
       
        $serviceOrderAdd = [
            'order_id' => $serviceOrderData->order_id,
            'customer_id' => $serviceOrderData->customer_id,
            'service_id' =>  $serviceOrderData->service_id,
            'type' => $serviceOrderData->type,
            'leads_id' => $serviceOrderData->id,
            'booking_date' => $serviceOrderData->booking_date,
            'wallet_amount' => $serviceOrderData->via_wallet ?? 0,
            'transection_amount' => $amount,
            'pay_amount' => $totalAmount,
        ];
        if ($existingServiceOrder) {
            // Update existing record
            $existingServiceOrder->update($serviceOrderAdd);
        } else {
            // Create new record only if not exists
            Chadhava_orders::create($serviceOrderAdd);
        }

        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $leadsdata->id,
            'order_id' => $serviceOrderData->order_id,
            'service_id'      => $leadsdata->service_id,
            'customer_id'     => $request->customer_id,
            'booking_date'    => $leadsdata->booking_date,
            'pandit_assign'   => $request->pandit_assign,
            'wallet_balance'  => $requestedWalletUse,
            'final_amount'    => $amount,
        ];

        $user = Helpers::get_customer($request);
        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $request->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'chadhava',
            attribute_id: idate("U")
        );

        $receiver_info = new Receiver('receiver_name', 'example.png');

        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        return $redirect_link;
    }

    public function chadhava_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {

            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            $leadsData = Leads::where('id', $serviceData->leads_id)->first();
            // service order table to store
            $serviceOrderAdd = Chadhava_orders::where('order_id', $leadsData->order_id)->first();
            $wallet = User::select('wallet_balance')->where('id', $leadsData->customer_id)->first();
            $actualWalletBalance = $wallet->wallet_balance ?? 0;

            if ($actualWalletBalance > 0) {
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $leadsData->customer_id;
                $wallet_transaction->transaction_id = \Str::uuid();
                $wallet_transaction->reference = 'chadhava order payment';
                $wallet_transaction->transaction_type = 'chadhava_order_place';
                $wallet_transaction->balance = 0.00;
                $wallet_transaction->debit = $leadsData->via_wallet;
                $wallet_transaction->save();
                User::where('id', $leadsData->customer_id)->update(['wallet_balance' => 0]);
            }
            if ($serviceOrderAdd) {
                $serviceOrderAdd->wallet_translation_id = $wallet_transaction->transaction_id ?? null;
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->save();
            }
            $orderId = $serviceOrderAdd->order_id;
            $order = Chadhava_orders::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\Chadhava::where('id', ($serviceData->service_id ?? ""))->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' =>  asset('/storage/app/public/chadhava/thumbnail/' . $service_name->thumbnail),
                    'booking_date' => date('d-m-Y', strtotime($serviceData->booking_date)),
                    'chadhava_venue' => $service_name['chadhava_venue'],
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter(amount: (float)$additionalData['final_amount'] ?? 0),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];

                $messages =  Helpers::whatsappMessage('chadhava', 'Chadhava Confirmed', $message_data);

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'chadhava';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Your Online Chadhava Booking Confirmation';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                    Helpers::emailSendMessage($data);
                }
                return redirect()->route('chadhava.user.detail', [$orderId]);
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }



    public function success_event(Request $request)
    {
        $flag = $request->get('flag');
        $token = $request->get('token');
        $decodedToken = base64_decode($token);
        parse_str($decodedToken, $transactionDetails);
        $paymentMethod = $transactionDetails['payment_method'] ?? null;
        $transactionReference = $transactionDetails['transaction_reference'] ?? '';

        $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
        $payment = $api->payment->fetch($transactionReference);
        $getlist =  $this->payment::where(['transaction_id' => $transactionReference])->first();
        $getadditional = json_decode($getlist->additional_data);

        if (($payment->status === 'captured') && !empty($transactionReference)) {
            $array['payment_requests_id'] = $getlist->id;
            $array['transaction_id'] = $transactionReference;
            $array['status'] = 1;
            EventApproTransaction::where('event_id', $getlist->payer_id)->update($array);
            Events::where('id', $getlist->payer_id)->update(['approve_amount_status' => 1, 'is_approve' => 1]);
            $status = 1;
            $message = 'Event is approve Event booking open';
        } else {
            $array['payment_requests_id'] = $getlist->id;
            $array['transaction_id'] = $transactionReference;
            $array['status'] = 2;
            EventApproTransaction::where('event_id', $getlist->payer_id)->update($array);
            Events::where('id', $getlist->payer_id)->update(['approve_amount_status' => 3, 'is_approve' => 4]);
            $status = 2;
            $message = 'Amount Transaction Failed';
        }
        return view('payment.razor-pay-expires_day', compact('status', 'message'));
    }


    public function eventordersuccess(Request $request)
    {
        $flag = $request->get('flag');
        $token = $request->get('token');
        $decodedToken = base64_decode($token);
        parse_str($decodedToken, $transactionDetails);
        $paymentMethod = $transactionDetails['payment_method'] ?? null;
        $transactionReference = $transactionDetails['transaction_reference'] ?? '';

        $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
        $payment = $api->payment->fetch($transactionReference);
        $getlist =  $this->payment::where(['transaction_id' => $transactionReference])->first();
        $getadditional = json_decode($getlist->additional_data);

        if (($payment->status === 'captured') && !empty($transactionReference)) {

            $getLead = EventLeads::where('id',  $getadditional->leads_id)->first();
            $EventId = Events::where('id',  $getadditional->event_id)->first();
            $listOrganizer =  EventOrganizer::where('id', $EventId['event_organizer_id'])->first();
            $bookingSeats = json_decode($EventId['all_venue_data'], true);
            $foundPackage = [];
            if ($bookingSeats) {
                $pn = 0;
                $amdin_commission = 0;
                $final_amount = 0;
                $govtTax = 0;
                foreach ($bookingSeats as $key => $bo_se) {
                    $foundPackage['all_venue_data'][$key] = $bo_se;
                    if ((($bo_se['id'] ?? "") == $getLead['venue_id']) && !empty($bo_se['package_list'])) {
                        $package = collect($bo_se['package_list'])->firstWhere('package_name', $getLead['package_id']);
                        if (empty($package) && $package['available'] < $getLead['qty']) {
                            $array['transaction_id'] = $transactionReference;
                            $array['transaction_status'] = 1;
                            $array['status'] = 3;
                            $refund['transaction_id'] = $transactionReference;
                            $refund['amount'] = $getLead['total_amount'];
                            $refund['event_id'] = $getadditional->order_id;
                            EventOrder::where('id', $getadditional->order_id)->update($array);
                            // return $this->Event_Order_Refund($refund);


                            ///////// whatsappmessage
                            $eventOrder = EventOrder::where('id', $getadditional->order_id)->with(['orderitem'])->first();
                            User::where('id', $eventOrder['user_id'])->update(['wallet_balance' => DB::raw('wallet_balance + ' . $eventOrder['amount'])]);
                            $userInfo = User::where('id', $eventOrder['user_id'])->first();
                            $wallet_transaction = new WalletTransaction();
                            $wallet_transaction->user_id = $eventOrder['user_id'];
                            $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                            $wallet_transaction->reference = 'Tour refund';
                            $wallet_transaction->transaction_type = 'tour_refund';
                            $wallet_transaction->balance = ($userInfo['wallet_balance'] ?? 0);
                            $wallet_transaction->credit = $eventOrder['amount'];
                            $wallet_transaction->save();

                            $message_data['title_name'] = $EventId['event_name'];
                            $message_data['place_name'] = $bo_se['en_event_cities'];
                            $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date']));
                            $message_data['time'] = ($bo_se['start_time']);
                            $message_data['orderId'] = $eventOrder['order_no'];
                            $message_data['final_amount'] = webCurrencyConverter(amount: (float)$eventOrder['amount'] ?? 0);
                            $message_data['customer_id'] =  $eventOrder['user_id'];
                            $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                            Helpers::whatsappMessage('event', 'Refund', $message_data);

                            return response()->json(['status' => 0, 'message' => $getLead['qty'] . ' seats are not available. ' . $package['available']  . ' seats are available.', 'recode' => '', 'data' => []], 400);
                        } else {
                            foreach ($bo_se['package_list'] as $keys => $val2) {
                                if ($val2['package_name'] == $getLead['package_id']) {
                                    $foundPackage['all_venue_data'][$key]['package_list'][$keys]['available'] = ($val2['available'] - $getLead['qty']);
                                    $foundPackage['all_venue_data'][$key]['package_list'][$keys]['sold'] = ($val2['sold'] + $getLead['qty']);
                                }
                            }

                            $array['transaction_id'] = $transactionReference;
                            $array['transaction_status'] = 1;
                            $eventtax = \App\Models\ServiceTax::find(1);
                            $orderamount = $getLead['total_amount'];
                            if (!empty($EventId) && $EventId['commission_seats']) {
                                $govtTax = (($orderamount * ($eventtax['event_tax'] ?? 0)) / 100);
                                $orderamount = $orderamount - $govtTax;
                                $amdin_commission =  (($orderamount * $EventId['commission_seats']) / 100);
                                $final_amount = $orderamount - $amdin_commission;
                            }
                            $array['admin_commission'] = $amdin_commission;
                            $array['gst_amount'] = $govtTax;
                            $array['final_amount'] = $final_amount;
                            EventOrder::where('id', $getadditional->order_id)->update($array);


                            $eventOrder = EventOrder::where('id', $getadditional->order_id)->with('orderitem')->first();

                            $message_data['title_name'] = $EventId['event_name'];
                            $message_data['place_name'] = $bo_se['en_event_cities'];
                            $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date']));
                            $message_data['time'] = ($bo_se['start_time']);
                            $message_data['orderId'] = $eventOrder['order_no'];
                            $message_data['final_amount'] = webCurrencyConverter(amount: (float)$eventOrder['amount'] ?? 0);
                            $message_data['customer_id'] =  $eventOrder['user_id'];
                            $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                            Helpers::whatsappMessage('event', 'Event booking Confirmed', $message_data);

                            \App\Models\EventLeads::where('id', $getadditional->leads_id)->update(['status' => 1]);
                        }
                    }
                }
                EventOrganizer::where('id', $EventId['event_organizer_id'])->update(
                    [
                        'org_total_tax' => ($listOrganizer['org_total_tax'] + $govtTax),
                        "org_withdrawable_ready" => ($listOrganizer["org_withdrawable_ready"] + $final_amount),
                        "org_total_commission" => ($listOrganizer["org_total_commission"] + $amdin_commission),
                    ]
                );
                Events::where('id',  $getadditional->event_id)->update($foundPackage);
            }
            return response()->json(['message' => 'Payment Success'], 200);
        }
        return response()->json(['message' => 'Payment failed'], 403);
    }

    static function BirthJournalSuccess(Request $request)
    {
        $companyPhone = getWebConfig(name: 'company_phone');
        $companyEmail = getWebConfig(name: 'company_email');
        $companyName = getWebConfig(name: 'company_name');
        if (isset($request->wallet_type) && $request->wallet_type == 1) {
            $findData =  BirthJournalKundali::with('birthJournal')->find($request->insertedId);
            $kundaliPdf = "";
            if ($findData && $findData['birthJournal']['name'] == 'kundali') {
                $apiData = array(
                    'name' => $findData['name'],
                    'gender' => $findData['gender'],
                    'day' => date('d', strtotime($findData['bod'])),
                    'month' => date('m', strtotime($findData['bod'])),
                    'year' => date('Y', strtotime($findData['bod'])),
                    'hour' => date('h', strtotime($findData['time'])),
                    'min' => date('i', strtotime($findData['time'])),
                    'lat' => $findData['lat'],
                    'lon' => $findData['log'],
                    'language' => $findData['language'],
                    'tzone' => $findData['tzone'],
                    'place' => $findData['state'],
                    'chart_style' => $findData['chart_style'],
                    'footer_link' => route('home'),
                    'logo_url' => dynamicStorage(path: "storage/app/public/company/" . getWebConfig(name: 'company_web_logo')),
                    'company_name' => $companyName,
                    'company_info' => 'Description of Mahakal Astrotech (OPC) PVT LTD@2025.',
                    'domain_url' => route('home'),
                    'company_email' => $companyEmail,
                    'company_landline' => $companyPhone,
                    'company_mobile' => $companyPhone
                );

                $kundali_Pdf = '';
                if (($findData['birthJournal']['type'] ?? "basic") == "basic") {
                    $kundali_Pdf = json_decode(ApiHelper::astroApi('https://pdf.astrologyapi.com/v1/basic_horoscope_pdf', $apiData['language'], $apiData), true);
                } else if (($findData['birthJournal']['type'] ?? "basic") == "pro") {
                    $language = in_array($findData['language'], ['hi', 'en']) ? $findData['language'] : 'hi';
                    $kundali_Pdf = json_decode(ApiHelper::astroApi('https://pdf.astrologyapi.com/v1/pro_horoscope_pdf', $language, $apiData), true);
                }
                if (!empty($kundali_Pdf['pdf_url'])) {
                    $fileName = $kundaliPdf = $findData['birthJournal']['pages'] . '_page_' . $apiData['language'] . '_kundali_' . time() . '.pdf';
                    $filePath = storage_path('app/public/birthjournal/kundali/' . $fileName);

                    if (!file_exists(dirname($filePath))) {
                        mkdir(dirname($filePath), 0755, true);
                    }
                    $pdfContent = file_get_contents($kundali_Pdf['pdf_url']);
                    file_put_contents($filePath, $pdfContent);
                    $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                    $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                    $message_data['orderId'] = $findData['order_id'] ?? '';
                    $message_data['booking_date'] = date('d M,Y h:i A', strtotime($findData['created_at'] ?? ''));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
                    $message_data['customer_id'] =  $findData['user_id'];
                    if ($fileName) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('storage/app/public/birthjournal/kundali/' . $fileName);;
                    }
                    Helpers::whatsappMessage('kundali', 'kundali_pdf', $message_data);
                }
                $array['milan_verify'] = 1;
            } else {
                $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                $message_data['kundli_type'] = $findData['birthJournal']['type'] ?? '';
                $message_data['orderId'] = $findData['order_id'] ?? '';
                $message_data['booking_date'] = date('d M,Y h:i A', strtotime($findData['created_at'] ?? ''));
                $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
                $message_data['customer_id'] =  $findData['user_id'];
                Helpers::whatsappMessage('kundali', 'kundali_milan_confirm', $message_data);
            }
            $array['transaction_id'] = 'wallet';
            $array['payment_status'] = 1;
            $array['kundali_pdf'] = $kundaliPdf;
            BirthJournalKundali::where('id', $request->insertedId)->update($array);
            if (isset($request->leads) && !empty($request->leads)) {
                KundaliLeads::where('id', $request->leads)->update(['payment_status' => 1, 'status' => 1]);
            }
            return response()->json(['message' => 'Payment Success'], 200);
        } else {
            $flag = $request->get('flag');
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = $transactionDetails['transaction_reference'] ?? '';
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist = \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $findData =  BirthJournalKundali::with('birthJournal')->find($getlist->payer_id);
                $kundaliPdf = "";
                if ($findData && $findData['birthJournal']['name'] == 'kundali') {
                    $apiData = array(
                        'name' => $findData['name'],
                        'gender' => $findData['gender'],
                        'day' => date('d', strtotime($findData['bod'])),
                        'month' => date('m', strtotime($findData['bod'])),
                        'year' => date('Y', strtotime($findData['bod'])),
                        'hour' => date('h', strtotime($findData['time'])),
                        'min' => date('i', strtotime($findData['time'])),
                        'lat' => $findData['lat'],
                        'lon' => $findData['log'],
                        'language' => $findData['language'],
                        'tzone' => $findData['tzone'],
                        'place' => $findData['state'],
                        'chart_style' => $findData['chart_style'],
                        'footer_link' => route('home'),
                        'logo_url' => dynamicStorage(path: "storage/app/public/company/" . getWebConfig(name: 'company_web_logo')),
                        'company_name' => $companyName,
                        'company_info' => 'Description of Mahakal Astrotech (OPC) PVT LTD@2025.',
                        'domain_url' => route('home'),
                        'company_email' => $companyEmail,
                        'company_landline' => $companyPhone,
                        'company_mobile' => $companyPhone
                    );

                    $kundali_Pdf = '';
                    if (($findData['birthJournal']['type'] ?? "basic") == "basic") {
                        $kundali_Pdf = json_decode(ApiHelper::astroApi('https://pdf.astrologyapi.com/v1/basic_horoscope_pdf', $apiData['language'], $apiData), true);
                    } else if (($findData['birthJournal']['type'] ?? "basic") == "pro") {
                        $language = in_array($findData['language'], ['hi', 'en']) ? $findData['language'] : 'hi';
                        $kundali_Pdf = json_decode(ApiHelper::astroApi('https://pdf.astrologyapi.com/v1/pro_horoscope_pdf', $language, $apiData), true);
                    }
                    // dd($kundali_Pdf['pdf_url']);
                    if (!empty($kundali_Pdf['pdf_url'])) {
                        $fileName = $kundaliPdf = $findData['birthJournal']['pages'] . '_page_' . $apiData['language'] . '_kundali_' . time() . '.pdf';
                        $filePath = storage_path('app/public/birthjournal/kundali/' . $fileName);

                        if (!file_exists(dirname($filePath))) {
                            mkdir(dirname($filePath), 0755, true);
                        }
                        $pdfContent = file_get_contents($kundali_Pdf['pdf_url']);
                        file_put_contents($filePath, $pdfContent);
                        $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                        $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                        $message_data['orderId'] = $findData['order_id'] ?? '';
                        $message_data['booking_date'] = date('d M,Y h:i A', strtotime($findData['created_at'] ?? ''));
                        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
                        $message_data['customer_id'] =  $findData['user_id'];
                        if ($fileName) {
                            $message_data['type'] = 'text-with-media';
                            $message_data['attachment'] = asset('storage/app/public/birthjournal/kundali/' . $fileName);
                        }
                        Helpers::whatsappMessage('kundali', 'kundali_pdf', $message_data);
                    }
                    $array['milan_verify'] = 1;
                } else {
                    $message_data['kundli_page'] = $findData['birthJournal']['pages'] ?? '';
                    $message_data['kundli_type'] = $findData['birthJournal']['type'] ?? '';
                    $message_data['orderId'] = $findData['order_id'] ?? '';
                    $message_data['booking_date'] = date('d M,Y h:i A', strtotime($findData['created_at'] ?? ''));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
                    $message_data['customer_id'] =  $findData['user_id'];
                    Helpers::whatsappMessage('kundali', 'kundali_milan_confirm', $message_data);
                }
                $array['transaction_id'] = $transactionReference;
                $array['payment_status'] = 1;
                $array['kundali_pdf'] = $kundaliPdf;
                BirthJournalKundali::where('id', $getadditional->order_id)->update($array);
                if (isset($getadditional->leads_id) && !empty($getadditional->leads_id)) {
                    KundaliLeads::where('id', $getadditional->leads_id)->update(['payment_status' => 1, 'status' => 1]);
                }
                return response()->json(['message' => 'Payment Success'], 200);
            }
        }
    }

    public function BirthJournalKundliSuccess(Request $request)
    {
        $data =   $this->BirthJournalSuccess($request);

        $token = $request->get('token');
        $decodedToken = base64_decode($token);
        parse_str($decodedToken, $transactionDetails);
        $transactionReference = $transactionDetails['transaction_reference'] ?? '';
        $getlist =  $this->payment::where(['transaction_id' => $transactionReference])->first();
        $findData =  BirthJournalKundali::with('birthJournal')->find($getlist->payer_id);
        // if ($findData && $findData['birthJournal']['name'] == 'kundali') {
        //     $url = 'saved.paid.kundali';
        // } else {
        //     $url = 'saved.paid.kundali.milan';
        // }

        if ($data) {
            // return redirect()->route($url);
            return redirect()->route("kundali-pdf.kundali-payment-success", [$getlist->payer_id]);
        }
    }

    //donate
    public function donate_payment(Request $request)
    {
        $flag = $request->get('flag');
        $token = $request->get('token');
        $decodedToken = base64_decode($token);
        parse_str($decodedToken, $transactionDetails);
        $paymentMethod = $transactionDetails['payment_method'] ?? null;
        $transactionReference = $transactionDetails['transaction_reference'] ?? '';

        $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
        $payment = $api->payment->fetch($transactionReference);
        $getlist =  $this->payment::where(['transaction_id' => $transactionReference])->first();
        $getadditional = json_decode($getlist->additional_data);
        $findData =  DonateAllTransaction::where('payment_requests_id', $getlist->id)->first();
        if (($payment->status === 'captured') && !empty($transactionReference)) {
            $array['transaction_id'] = $transactionReference;
            $array['amount_status'] = 1;
            DonateAllTransaction::where('id', $findData['id'])->update($array);
            $gettrust = DonateTrust::where('id', $findData['trust_id'])->first();
            if ($gettrust) {
                DonateTrust::where('id', $findData['trust_id'])->update(['trust_total_amount' => ($gettrust['trust_total_amount'] + $findData['final_amount']), 'admin_commission' => ($gettrust['admin_commission'] + $findData['admin_commission'])]);
            }
            $adsTrust = DonateAds::where('id', $findData['ads_id'])->first();
            if ($adsTrust) {
                DonateAds::where('id', $findData['ads_id'])->update(['total_amount_ads' => ($adsTrust['total_amount_ads'] + $findData['final_amount']), 'admin_commission_amount' => ($adsTrust['admin_commission_amount'] + $findData['admin_commission'])]);
            }

            if (isset($getadditional->leads_id) && !empty($getadditional->leads_id)) {
                DonateLeads::where('id', $getadditional->leads_id)->update(['status' => 1]);
            }
            $message_data['title_name'] = ((!empty($gettrust) && !empty($gettrust['trust_name'])) ? $gettrust['trust_name'] : 'Mahakal');
            $message_data['ad_name'] = ((!empty($adsTrust) && !empty($adsTrust['name'])) ? $adsTrust['name'] : '');
            $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
            $message_data['customer_id'] =  $getadditional->customer_id;

            $orderData = DonateAllTransaction::where('id', $findData['id'])->where('user_id', $getadditional->customer_id)->with(['users', 'getTrust', 'adsTrust'])->first();
            $message_data['person_phone'] =  $orderData['user_phone'] ?? '';
            $message_data['pan_card'] =  $orderData['pan_card'] ?? '';
            $mpdf_view = \View::make('web-views.donate.invoice', compact('orderData'));
            Helpers::gen_mpdf_Pdf($mpdf_view, 'donate_order', $findData['id']);
            $message_data['attachment'] = asset('storage/app/public/donate/invoice/donate_order' . $findData['id'] . '.pdf');
            $message_data['type'] = 'text-with-media';

            Helpers::whatsappMessage('donate', 'Donation Success', $message_data);
            $orderData = DonateAllTransaction::where('id', $findData['id'])->with(['getTrust', 'adsTrust'])->first();
            $message_data2['trust_name'] =  $orderData['getTrust']['trust_name'] ?? "Mahakal.com";
            $message_data2['ad_name'] =  $orderData['adsTrust']['name'] ?? '';
            $message_data2['booking_date'] =  date('d M,Y H:i A', strtotime($orderData['created_at']));
            $message_data2['order_amount'] =  $orderData['amount'];
            $message_data2['admin_commission'] =  $orderData['admin_commission'];
            $message_data2['final_amount'] =  $orderData['final_amount'];
            $message_data2['vendor_email'] =   $orderData['getTrust']['trust_email'] ?? "Mahakal.com";
            $message_data2['seller_id'] = \App\Models\Seller::where('relation_id', $orderData['trust_id'])->where('type', 'trust')->first()['id'] ?? 0;
            Helpers::whatsappMessage('donate', 'donation_trust_receipt', $message_data2);
            if ($getlist->payment_platform == 'web') {
                return redirect()->route('donate-success', [$findData['id']]);
            } else {
                return response()->json(['message' => 'Payment Success'], 200);
            }
        }
    }


    public function success_adsApprove(Request $request)
    {
        $flag = $request->get('flag');
        $token = $request->get('token');
        $decodedToken = base64_decode($token);
        parse_str($decodedToken, $transactionDetails);
        $paymentMethod = $transactionDetails['payment_method'] ?? null;
        $transactionReference = $transactionDetails['transaction_reference'] ?? '';

        $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
        $payment = $api->payment->fetch($transactionReference);
        $getlist =  $this->payment::where(['transaction_id' => $transactionReference])->first();
        $getadditional = json_decode($getlist->additional_data);
        // dd($getadditional->donate_all_transaction_id);
        if (($payment->status === 'captured') && !empty($transactionReference)) {
            $array['payment_requests_id'] = $getlist->id;
            $array['transaction_id'] = $transactionReference;
            $array['amount_status'] = 1;
            if ($getadditional->type == 'ad_approval') {
                DonateAds::where('id', $getlist->payer_id)->update(['is_approve' => 1, 'req_amount_received' => date('Y-m-d H:i:s')]);
                DonateAllTransaction::where('id', $getadditional->donate_all_transaction_id)->update($array);
            }
            $status = 1;
            $message = 'Trust Ads approve and Ads Donate Active';
        } else {
            $array['payment_requests_id'] = $getlist->id;
            $array['transaction_id'] = $transactionReference;
            $array['status'] = 2;
            if ($getadditional->type == 'ad_approval') {
                DonateAds::where('id', $getlist->payer_id)->update(['is_approve' => 4]);
                DonateAllTransaction::where('id', $getadditional->donate_all_transaction_id)->update($array);
            }
            $status = 2;
            $message = 'Trust Ads Amount Transaction Failed';
        }
        return view('payment.razor-pay-expires_day', compact('status', 'message'));
    }

    public function Event_Order_Refund($refundDetails)
    {
        $apiKey = config('razor_config.api_key');
        $apiSecret = config('razor_config.api_secret');
        $transactionId = $refundDetails['transaction_id'];
        $amount = $refundDetails['amount'];
        $event_id = $refundDetails['event_id'];

        $refundUrl = "https://api.razorpay.com/v1/payments/{$transactionId}/refund";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $refundUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":" . $apiSecret);
        $refundData = json_encode(['amount' => $amount]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $refundData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == 200) {
            $refund_id = json_decode($response);
            EventOrder::where('id', $event_id)->update(['refund_id' => $refund_id->id, 'status' => 3, 'transaction_status' => 1]);
            return response()->json(['message' => 'Refund processed successfully'], 200);
        } else {
            $error = json_decode($response);
            return response()->json(['message' => 'Refund failed: ' . $error->error->description], 200);
        }
    }
    public function Event_customer_payment_request(Request $request, $id)
    {
        try {
            $getLead = EventLeads::where('id', $request['lead'])->where('status', 0)->first();
            DB::beginTransaction();
            $user = User::where('id', $getLead['user_id'])->first();
            $event_booking = new EventOrder();
            $event_booking->event_id = $getLead['event_id'];
            $event_booking->user_id = $getLead['user_id'];
            $event_booking->venue_id = $getLead['venue_id'];
            $event_booking->amount = $getLead['total_amount'];
            $event_booking->coupon_amount = $getLead['coupon_amount'] ?? 0;
            $event_booking->coupon_id = $getLead['coupon_id'];
            $event_booking->transaction_status = 0;
            $event_booking->status = 1;
            $event_booking->save();

            // dd($event_booking);
            $event_book_item = new EventOrderItems();
            $event_book_item->order_id = $event_booking->id;
            $event_book_item->package_id = $getLead['package_id'];
            $event_book_item->no_of_seats = $getLead['qty'] ?? 0;
            $event_book_item->amount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);
            $JsonEncodeMembers = [];
            $eventmemberqty = $getLead['qty'] ?? 0;
            if ($eventmemberqty > 0) {
                for ($qn = 0; $qn < $eventmemberqty; $qn++) {
                    $JsonEncodeMembers[$qn]['id'] = ($qn + 1);
                    $JsonEncodeMembers[$qn]['name'] = $request['member'][$qn]['name'] ?? '';
                    $JsonEncodeMembers[$qn]['phone'] = $request['member'][$qn]['phone'] ?? '';
                    $JsonEncodeMembers[$qn]['aadhar'] = $request['member'][$qn]['aadhar'] ?? '';
                    if (($request->file('member')[$qn]['aadhar_image'] ?? '') && $request->file('member')[$qn]['aadhar_image']) {
                        $JsonEncodeMembers[$qn]['aadhar_image'] =  \App\Utils\ImageManager::file_upload('event/aadhar_image/',  $request->file('member')[$qn]['aadhar_image']->getClientOriginalExtension(), $request->file('member')[$qn]['aadhar_image']);
                    }
                    $JsonEncodeMembers[$qn]['verify'] = 0;
                    $JsonEncodeMembers[$qn]['time'] = '';
                }
            }
            $event_book_item->user_information = json_encode($JsonEncodeMembers);
            $event_book_item->save();

            $additional_data = [
                'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                'payment_mode' => 'web',
                'leads_id' => $getLead['id'],
                'package_id' => $getLead['package_id'],
                'customer_id' => $getLead['user_id'],
                "order_id" => $event_booking->id,
                "event_id" => $getLead['event_id'],
                "amount" => $getLead['total_amount'],
                "user_name" => $user['name'],
                "user_email" => $user['email'],
                "user_phone" => $user['phone'],
            ];

            $currency_model = Helpers::get_business_settings('currency_model');
            if ($currency_model == 'multi_currency') {
                $currency_code = 'USD';
            } else {
                $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
                $currency_code = Currency::find($default)->code;
            }
            $customer = User::where('id', $getLead['user_id'])->first();

            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                DB::rollBack();
                Toastr::error(translate('please_update_your_phone_number'));
                return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
            }

            $payment_info = new PaymentInfo(
                success_hook: 'digital_payment_success_custom',
                failure_hook: 'digital_payment_fail',
                currency_code: $currency_code,
                payment_method: 'razor_pay',
                payment_platform: 'web',
                payer_id: $customer['id'],
                receiver_id: '100',
                additional_data: $additional_data,
                payment_amount: $getLead['total_amount'],
                external_redirect_link: route('event_pay_success', [$id, 'lead' => $request->lead]),
                attribute: 'event_order',
                attribute_id: idate("U")
            );

            $receiver_info = new Receiver('receiver_name', 'example.png');

            DB::commit();
            $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
            $parsed_url = parse_url($redirect_link);
            $query_string = $parsed_url['query'];
            parse_str($query_string, $query_params);
            EventOrder::where('id', $event_booking->id)->update(['payment_requests_id' => $query_params['payment_id']]);
            return $redirect_link;
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('An error occurred: ' . $e->getMessage());
            return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
        }
    }

    // Event Booking Order Payment Getway
    public function Eventpayment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $getLead = EventLeads::where('id', $request['lead'])->where('status', 0)->first();
            if (!$getLead) {
                return redirect()->route('event-details', ['id' => $id]);
            }
            $EventId = Events::where('id', $getLead['event_id'])->first();
            if (empty($EventId) || empty($getLead) || ($getLead['package_id'] ?? 0) <= 0) {
                Toastr::error('Invalid data passed.');
                return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
            }
            $bookingSeats = json_decode($EventId['all_venue_data'], true);

            $foundPackage = false;
            if ($bookingSeats) {
                foreach ($bookingSeats as $bo_se) {
                    if (($bo_se['id'] ?? "") == $getLead['venue_id'] && !empty($bo_se['package_list'])) {
                        foreach ($bo_se['package_list'] as $ch_seat) {
                            if ($ch_seat['package_name'] == $getLead['package_id']) {
                                $foundPackage = true;
                                if ($ch_seat['available'] < $getLead['qty']) {
                                    Toastr::error($getLead['qty'] . ' seats are not available. ' . $ch_seat['available'] . ' seats are available.');
                                    return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
                                }
                                break;
                            }
                        }
                    }
                }
            }
            if (!$foundPackage) {
                $PackagesSeats = json_decode($EventId['package_list'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Toastr::error('Booking seats data is not properly formatted.');
                    return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
                }
            }

            if ($request->wallet_type == 1) {
                $user = User::where('id', $getLead['user_id'])->first();

                $event_booking = new EventOrder();
                $event_booking->event_id = $getLead['event_id'];
                $event_booking->user_id = $getLead['user_id'];
                $event_booking->venue_id = $getLead['venue_id'];
                $event_booking->amount = ($getLead['total_amount']);
                $event_booking->coupon_amount = $getLead['coupon_amount'] ?? 0;
                $event_booking->coupon_id = $getLead['coupon_id'];
                $event_booking->transaction_status = 0;
                $event_booking->status = 1;
                $event_booking->save();

                $event_book_item = new EventOrderItems();
                $event_book_item->order_id = $event_booking->id;
                $event_book_item->package_id = $getLead['package_id'];
                $event_book_item->no_of_seats = $getLead['qty'] ?? 0;
                $event_book_item->amount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);
                $JsonEncodeMembers = [];
                $eventmemberqty = $getLead['qty'] ?? 0;
                if ($eventmemberqty > 0) {
                    for ($qn = 0; $qn < $eventmemberqty; $qn++) {
                        $JsonEncodeMembers[$qn]['id'] = ($qn + 1);
                        $JsonEncodeMembers[$qn]['name'] = $request['member'][$qn]['name'] ?? '';
                        $JsonEncodeMembers[$qn]['phone'] = $request['member'][$qn]['phone'] ?? '';
                        $JsonEncodeMembers[$qn]['aadhar'] = $request['member'][$qn]['aadhar'] ?? '';
                        if (($request->file('member')[$qn]['aadhar_image'] ?? '') && $request->file('member')[$qn]['aadhar_image']) {
                            $JsonEncodeMembers[$qn]['aadhar_image'] =  \App\Utils\ImageManager::file_upload('event/aadhar_image/',  $request->file('member')[$qn]['aadhar_image']->getClientOriginalExtension(), $request->file('member')[$qn]['aadhar_image']);
                        }
                        $JsonEncodeMembers[$qn]['verify'] = 0;
                        $JsonEncodeMembers[$qn]['time'] = '';
                    }
                }
                $event_book_item->user_information = json_encode($JsonEncodeMembers);
                $event_book_item->save();

                if ($user['wallet_balance'] >= $getLead['total_amount']) {
                    // wallet dedication

                    $getLead = EventLeads::where('id',  $request->lead)->first();

                    $EventId = Events::where('id',  $getLead['event_id'])->first();
                    $eventOrder = EventOrder::where('id', $event_booking->id)->with('orderitem')->first();
                    $listOrganizer =  EventOrganizer::where('id', $EventId['event_organizer_id'])->first();
                    $array['transaction_id'] = 'wallet';
                    $array['transaction_status'] = 1;
                    $booking_date_w_message = '';
                    $booking_time_w_message = '';
                    $venue_name_w_message = '';

                    $eventtax = \App\Models\ServiceTax::find(1);
                    $amdin_commission = 0;
                    $final_amount = 0;
                    $govtTax = 0;
                    $orderamount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);
                    if (!empty($EventId) && $EventId['commission_seats']) {
                        $govtTax = (($orderamount * ($eventtax['event_tax'] ?? 0)) / 100);
                        $orderamount = $orderamount - $govtTax;
                        $amdin_commission =  (($orderamount * $EventId['commission_seats']) / 100);
                        $final_amount = $orderamount - $amdin_commission;
                    }
                    $array['admin_commission'] = $amdin_commission;
                    $array['gst_amount'] = $govtTax;
                    $array['final_amount'] = $final_amount;

                    $bookingSeats = json_decode($EventId['all_venue_data'], true);
                    $foundPackage = [];
                    if ($bookingSeats) {
                        $pn = 0;
                        foreach ($bookingSeats as $keys => $bo_se) {
                            $foundPackage[$keys] = $bo_se;
                            if (($bo_se['id'] ?? "") == $getLead['venue_id'] && !empty($bo_se['package_list'])) {
                                foreach ($bo_se['package_list'] as $kp => $ch_seat) {
                                    if ($ch_seat['package_name'] == $getLead['package_id']) {
                                        if ($ch_seat['available'] < $getLead['qty']) {
                                            Toastr::error($getLead['qty'] . ' seats are not available. ' . $ch_seat['available'] . ' seats are available.');
                                            return redirect()->route('event-booking', [$id]);
                                        } else {
                                            $booking_date_w_message = $bo_se['date'];
                                            $booking_time_w_message = $bo_se['start_time'];
                                            $venue_name_w_message = $bo_se['en_event_cities'];

                                            $foundPackage[$keys]['package_list'][$kp]['available'] = ($ch_seat['available'] - $getLead['qty']);
                                            $foundPackage[$keys]['package_list'][$kp]['sold'] = ($ch_seat['sold'] + $getLead['qty']);

                                            $array['transaction_id'] = 'wallet';
                                            $array['transaction_status'] = 1;

                                            $eventtax = \App\Models\ServiceTax::find(1);
                                            $amdin_commission = 0;
                                            $final_amount = 0;
                                            $govtTax = 0;
                                            $orderamount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);

                                            if (!empty($EventId) && $EventId['commission_seats']) {
                                                $govtTax = (($orderamount * ($eventtax['event_tax'] ?? 0)) / 100);
                                                $orderamount = $orderamount - $govtTax;
                                                $amdin_commission =  (($orderamount * $EventId['commission_seats']) / 100);
                                                $final_amount = $orderamount - $amdin_commission;
                                            }
                                            $array['admin_commission'] = $amdin_commission;
                                            $array['gst_amount'] = $govtTax;
                                            $array['final_amount'] = $final_amount;
                                            EventOrder::where('id', $event_booking->id)->update($array);
                                            EventOrderItems::where('id', $event_book_item->id)->update(['sub_amount' => ($getLead['amount'] ?? 0), 'gst' => ($eventtax['event_tax'] ?? 0), 'gst_amount' => $govtTax]);
                                            EventOrganizer::where('id', $EventId['event_organizer_id'])->update(
                                                [
                                                    'org_total_tax' => ($listOrganizer['org_total_tax'] + $govtTax),
                                                    "org_withdrawable_ready" => ($listOrganizer["org_withdrawable_ready"] + $final_amount),
                                                    "org_total_commission" => ($listOrganizer["org_total_commission"] + $amdin_commission),
                                                ]
                                            );
                                        }
                                    }
                                }
                                User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getLead['total_amount'])]);
                                \App\Models\EventLeads::where('id', $request->lead)->update(['status' => 1]);
                            }
                        }
                        Events::where('id',  $getLead['event_id'])->update(['all_venue_data' => $foundPackage]);
                    }
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Event order';
                    $wallet_transaction->transaction_type = 'event_order';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = $getLead['total_amount'];
                    $wallet_transaction->save();
                    DB::commit();

                    ///////// whatsappmessage
                    $message_data['title_name'] = $EventId['event_name'];
                    $message_data['place_name'] = $venue_name_w_message;
                    $message_data['booking_date'] = date('Y-m-d', strtotime($booking_date_w_message));
                    $message_data['time'] = ($booking_time_w_message);
                    $message_data['orderId'] = $eventOrder['order_no'];
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$getLead['total_amount'] ?? 0);
                    $message_data['customer_id'] =  $getLead['user_id'];
                    $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                    $messages =  Helpers::whatsappMessage('event', 'Event booking Confirmed', $message_data);

                    return redirect()->route('event-booking-success', [$id, 'lead' => $request->lead]);
                } else {
                    // wallet dedication
                    $user = User::where('id', $getLead['user_id'])->first();
                    $wallet_amount = ($user['wallet_balance']);
                    $total_amount = $getLead['total_amount'];
                    $onlinepay = ($getLead['total_amount'] - $user['wallet_balance']);
                    $data = [
                        'additional_data' => [
                            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                            'payment_mode' => 'web',
                            'leads_id' => $getLead['id'],
                            'package_id' => $getLead['package_id'],
                            'customer_id' => $getLead['user_id'],
                            "order_id" => $event_booking->id,
                            "event_id" => $getLead['event_id'],
                            "amount" => $getLead['total_amount'],
                            "user_name" => $user['name'],
                            "user_email" => $user['email'],
                            "user_phone" => $user['phone'],
                            'total_amount' => $total_amount,
                            'wallet_amount' => $wallet_amount,
                            "online_pay" => $onlinepay,
                            'page_name' => 'event_order',
                            'success_url' => route('event-booking-success', [$id, 'lead' => $request->lead]),
                        ],
                        'user_id' => $user['id'],
                        'payment_method' => 'razor_pay',
                        'payment_platform' => 'web',
                        'payment_amount' => $onlinepay,
                        'attribute' => "Event Order",
                        'external_redirect_link' => route('all-pay-wallet-payment-success-2', [$id, 'lead' => $request->lead]),
                    ];
                    $url_open = $this->Wallet_amount_add($data);
                    DB::commit();
                    return redirect($url_open);
                }
            } else {
                $redirect_link = $this->Event_customer_payment_request($request, $id);
                DB::commit();
                return redirect($redirect_link);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('An error occurred: ' . $e->getMessage());
            return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
        }
    }

    public function EventpaySuccess(Request $request, $id)
    {
        $data_id = $id; //json_decode(base64_decode($id));

        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $getLead = EventLeads::where('id',  $request['lead'])->first();
                $EventId = Events::where('slug',  $data_id)->first();
                $listOrganizer =  EventOrganizer::where('id', $EventId['event_organizer_id'])->first();
                $eventOrder = EventOrder::where('id', $getadditional->order_id)->with('orderitem')->first();
                $bookingSeats = json_decode($EventId['all_venue_data'], true);
                $booking_date_w_message = '';
                $booking_time_w_message = '';
                $venue_name_w_message = '';
                $foundPackage = [];
                if ($bookingSeats) {
                    $pn = 0;
                    foreach ($bookingSeats as $keys => $bo_se) {
                        $foundPackage[$keys] = $bo_se;
                        if (($bo_se['id'] ?? "") == $getLead['venue_id'] && !empty($bo_se['package_list'] ?? [])) {
                            foreach ($bo_se['package_list'] as $kp => $ch_seat) {
                                if ($ch_seat['package_name'] == $getLead['package_id']) {
                                    if ($ch_seat['available'] < $getLead['qty']) {
                                        Toastr::error($getLead['qty'] . ' seats are not available. ' . $ch_seat['available'] . ' seats are available.');
                                        $array['transaction_id'] = $transactionReference;
                                        $array['transaction_status'] = 3;
                                        $refund['transaction_id'] = $transactionReference;
                                        $refund['amount'] = $getLead['total_amount'];
                                        $refund['event_id'] = $getadditional->order_id;
                                        EventOrder::where('id', $getadditional->order_id)->update($array);
                                        // $this->Event_Order_Refund($refund);


                                        User::where('id', $eventOrder['user_id'])->update(['wallet_balance' => DB::raw('wallet_balance + ' . $eventOrder['amount'])]);
                                        $userInfo = User::where('id', $eventOrder['user_id'])->first();
                                        $wallet_transaction = new WalletTransaction();
                                        $wallet_transaction->user_id = $eventOrder['user_id'];
                                        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                                        $wallet_transaction->reference = 'Tour refund';
                                        $wallet_transaction->transaction_type = 'tour_refund';
                                        $wallet_transaction->balance = ($userInfo['wallet_balance'] ?? 0);
                                        $wallet_transaction->credit = $eventOrder['amount'];
                                        $wallet_transaction->save();

                                        $message_data['title_name'] = $EventId['event_name'];
                                        $message_data['place_name'] = $bo_se['en_event_cities'];
                                        $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date']));
                                        $message_data['time'] = ($bo_se['start_time']);
                                        $message_data['orderId'] = $eventOrder['order_no'];
                                        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$eventOrder['amount'] ?? 0);
                                        $message_data['customer_id'] =  $eventOrder['user_id'];
                                        $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                                        Helpers::whatsappMessage('event', 'Refund', $message_data);
                                        return redirect()->route('event-booking', [$id, $request['lead']]);
                                    } else {

                                        $booking_date_w_message = $bo_se['date'];
                                        $booking_time_w_message = $bo_se['start_time'];
                                        $venue_name_w_message = $bo_se['en_event_cities'];

                                        $foundPackage[$keys]['package_list'][$kp]['available'] = ($ch_seat['available'] - $getLead['qty']);
                                        $foundPackage[$keys]['package_list'][$kp]['sold'] = ($ch_seat['sold'] + $getLead['qty']);

                                        $array['transaction_id'] = $transactionReference;
                                        $array['transaction_status'] = 1;

                                        $eventtax = \App\Models\ServiceTax::find(1);
                                        $amdin_commission = 0;
                                        $final_amount = 0;
                                        $govtTax = 0;
                                        $orderamount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);
                                        if (!empty($EventId) && $EventId['commission_seats']) {
                                            $govtTax = (($orderamount * ($eventtax['event_tax'] ?? 0)) / 100);
                                            $orderamount = $orderamount - $govtTax;
                                            $amdin_commission =  (($orderamount * $EventId['commission_seats']) / 100);
                                            $final_amount = $orderamount - $amdin_commission;
                                        }
                                        $array['admin_commission'] = $amdin_commission;
                                        $array['gst_amount'] = $govtTax;
                                        $array['final_amount'] = $final_amount;
                                        EventOrder::where('id', $getadditional->order_id)->update($array);
                                        EventOrderItems::where('order_id', $getadditional->order_id)->update(['sub_amount' => ($getLead['amount'] ?? 0), 'gst' => ($eventtax['event_tax'] ?? 0), 'gst_amount' => $govtTax]);
                                        EventOrganizer::where('id', $EventId['event_organizer_id'])->update(
                                            [
                                                'org_total_tax' => ($listOrganizer['org_total_tax'] + $govtTax),
                                                "org_withdrawable_ready" => ($listOrganizer["org_withdrawable_ready"] + $final_amount),
                                                "org_total_commission" => ($listOrganizer["org_total_commission"] + $amdin_commission),
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }


                    Events::where('slug',  $data_id)->update(['all_venue_data' => $foundPackage]);
                }
                \App\Models\EventLeads::where('id', $request['lead'])->update(['status' => 1]);
                $userInfo = \App\Models\User::where('phone', ($getLead['user_phone'] ?? ""))->first();



                ///////// whatsappmessage
                $message_data['title_name'] = $EventId['event_name'];
                $message_data['place_name'] = $venue_name_w_message;
                $message_data['booking_date'] = date('Y-m-d', strtotime($booking_date_w_message));
                $message_data['time'] = ($booking_time_w_message);
                $message_data['orderId'] = $eventOrder['order_no'];
                $message_data['final_amount'] = webCurrencyConverter(amount: (float)$getLead['total_amount'] ?? 0);
                $message_data['customer_id'] =  $userInfo['id'];
                $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                $messages =  Helpers::whatsappMessage('event', 'Event booking Confirmed', $message_data);

                return redirect()->route('event-booking-success', [$id, $request['lead']]);
            } else {
                $array['transaction_id'] = $transactionReference;
                $array['transaction_status'] = 2;
                EventOrder::where('id', $getadditional->order_id)->update($array);
            }
        } else {
            \App\Models\EventLeads::where('id', $request['lead'])->update(['test' => 2]);
        }
        return redirect()->route('event-booking', [$id, $request['lead']]);
    }

    public function TourBookingPay(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'tour_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
                            $fail('The selected Tour is invalid or inactive.');
                        }
                    },
                ],
                'leads_id' => 'required',
            ]);

            $tourData = TourVisits::where('id',  $request->tour_id)->first();
            $tourLeads = TourLeads::where('id',  $request->leads_id)->first();

            if ($tourData['use_date'] == 1 && $tourData['is_person_use'] == 0) {
                $getseats = \App\Models\TourOrder::where('tour_id', $request->tour_id)->where('amount_status', 1)->where('status', 1)->where('available_seat_cab_id', ($request->available_seat_cab_id ?? 0))->sum('qty');
                if (($request->totals_seat_cab_id - $getseats) < $tourLeads['qty']) {
                    Toastr::error('Currently ' . ($request->totals_seat_cab_id - $getseats) . ' seats are available');
                    return redirect()->route('tour.tour-visit-id', [$id]);
                }
            }


            $coupon_amount = 0;
            $final_amount = $tourLeads['amount'] ?? 0;
            if (!empty($tourLeads['coupon_id'])) {
                $couponData = \App\Models\Coupon::find($tourLeads['coupon_id']);
                if ($couponData) {
                    $final_Namount = (($tourLeads['part_payment'] == 'full') ? $final_amount : ($final_amount + $final_amount));
                    if ($couponData->discount_type === 'amount') {
                        $coupon_amount = $couponData->discount ?? 0;
                    }
                    if ($couponData->discount_type === 'percentage') {
                        $discount_percent = $couponData->discount ?? 0;
                        $max_discount = $couponData->max_discount ?? 0;
                        $calculated_discount = round(($final_Namount * $discount_percent) / 100, 2);

                        $coupon_amount = ($calculated_discount > $max_discount && $max_discount > 0)
                            ? $max_discount
                            : $calculated_discount;
                    }
                    $coupon_amount = (($tourLeads['part_payment'] == 'full') ? $coupon_amount : ($coupon_amount / 2));
                    $tourLeads['amount'] = max(0, $final_amount - $coupon_amount);
                }
            }
            $user = User::where("id", $tourLeads['user_id'])->first();
            $event_booking = new TourOrder();
            $event_booking->user_id = $user['id'];
            $event_booking->tour_id = $request->tour_id;
            $event_booking->package_id = $tourLeads['package_id'];
            $event_booking->coupon_amount = $coupon_amount ?? 0;
            $event_booking->coupon_id = $tourLeads['coupon_id'] ?? '';
            $event_booking->amount = $tourLeads['amount'];
            $event_booking->qty = $tourLeads['qty'];
            $event_booking->available_seat_cab_id = $request->available_seat_cab_id ?? 0;
            $event_booking->total_seats_cab = $request->totals_seat_cab_id ?? 0;
            $event_booking->pickup_address = $tourLeads['pickup_address'];
            $event_booking->pickup_date = $tourLeads['pickup_date'];
            $event_booking->pickup_time = $tourLeads['pickup_time'];
            $event_booking->pickup_lat = $tourLeads['pickup_lat'];
            $event_booking->pickup_long = $tourLeads['pickup_long'];
            $event_booking->payment_method = 'razor_pay';
            $event_booking->payment_platform = 'web';
            $event_booking->leads_id = $request->leads_id;
            $event_booking->use_date = $tourData['use_date'];
            $event_booking->part_payment = ((!empty($tourLeads['part_payment'])) ? $tourLeads['part_payment'] : 'full');

            $event_booking->traveller_id = ($tourData['created_id'] ?? 0);
            $event_booking->cab_assign = 0;
            $event_booking->booking_package = $tourLeads['booking_package'];

            $event_booking->pickup_otp = mt_rand(1000, 9999);
            $event_booking->drop_opt = mt_rand(1000, 9999);
            $event_booking->amount_status = 0;
            $event_booking->status = 1;
            // dd($event_booking);
            $event_booking->save();
            \App\Models\TourLeads::where('id', $request->leads_id)->update(['order_id' => $event_booking->id]);
            /////////////////////////////////////////// WALLET AND ONLINE /////////////////////////////////////////////////
            if ($request->wallet_type == 1) {
                if ($user['wallet_balance'] >= $tourLeads['amount']) {
                    // wallet dedication
                    User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . $tourLeads['amount'])]);
                    \App\Models\TourLeads::where('id', $request->leads_id)->update(['amount_status' => 1, 'order_id' => $event_booking->id, 'via_wallet' => $tourLeads['amount']]);
                    $getLead = TourLeads::where('id',  $request->leads_id)->first();
                    $tourData = TourVisits::where('id',  $request->tour_id)->first();
                    $gst_amount = 0;
                    $admin_commission = 0;
                    $final_amount = $tourLeads['amount'];
                    $tourstax = \App\Models\ServiceTax::find(1);
                    if ($tourstax['tour_tax']) {
                        $gst_amount = (($final_amount * ($tourstax['tour_tax'] ?? 0)) / 100);
                        $final_amount = $final_amount - $gst_amount;
                    }
                    if ($tourData['tour_commission']) {
                        $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                        $final_amount = ($final_amount - $admin_commission);
                    }
                    TourOrder::where('id', $event_booking->id)->update(['payment_method' => 'wallet', 'amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => 'wallet']);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour order';
                    $wallet_transaction->transaction_type = 'tour_order';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = $tourLeads['amount'];
                    $wallet_transaction->save();

                    $tourOrder = TourOrder::where('id', $event_booking->id)->first();
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                    $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourLeads['amount'] ?? 0);
                    $message_data['customer_id'] = $user['id'];
                    if ($tourData['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                    }
                    $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);

                    DB::commit();
                    return redirect()->route('tour.tour-booking-success', [$id]);
                } else {
                    // wallet dedication
                    $wallet_amount = ($user['wallet_balance'] ?? 0);
                    $total_amount = $tourLeads['amount'];
                    $onlinepay = ($tourLeads['amount'] - $user['wallet_balance']);
                    $data = [
                        'additional_data' => [
                            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                            'payment_mode' => 'web',
                            'leads_id' => $request->leads_id,
                            'package_id' => $tourLeads['package_id'],
                            'customer_id' => $tourLeads['user_id'],
                            "order_id" => $event_booking->id,
                            "tour_id" => $request->tour_id,
                            "amount" => $tourLeads['amount'],
                            "user_name" => $user['name'],
                            "user_email" => $user['email'],
                            "user_phone" => $user['phone'],
                            'total_amount' => $total_amount,
                            'wallet_amount' => $wallet_amount,
                            "online_pay" => $onlinepay,
                            'page_name' => 'tour_order',
                            'success_url' => route('tour.tour-booking-success', [$id]),
                        ],
                        'user_id' => $user['id'],
                        'payment_method' => 'razor_pay',
                        'payment_platform' => 'web',
                        'payment_amount' => $onlinepay,
                        'attribute' => "Tour Order",
                        'external_redirect_link' => route('all-pay-wallet-payment-success-2', [$id, 'lead' => $request->leads_id]),
                    ];

                    $url_open = $this->Wallet_amount_add($data);
                    DB::commit();
                    return redirect($url_open);
                }
                // dd($request['payment_amount']);
            } else {
                // dd($request['payment_amount'] - $user['wallet_balance']);

                $additional_data = [
                    'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                    'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                    'payment_mode' => 'web',
                    'leads_id' => $request->leads_id,
                    'package_id' => $tourLeads['package_id'],
                    'customer_id' => $tourLeads['user_id'],
                    "order_id" => $event_booking->id,
                    "tour_id" => $request->tour_id,
                    "amount" => $tourLeads['amount'],
                    "user_name" => $user['name'],
                    "user_email" => $user['email'],
                    "user_phone" => $user['phone'],
                ];
                $currency_model = Helpers::get_business_settings('currency_model');
                if ($currency_model == 'multi_currency') {
                    $currency_code = 'USD';
                } else {
                    $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
                    $currency_code = Currency::find($default)->code;
                }
                $customer = User::where("id", $tourLeads['user_id'])->first();
                $payer = new Payer(
                    $customer['f_name'] . ' ' . $customer['l_name'],
                    $customer['email'],
                    $customer['phone'],
                    ''
                );
                if (empty($customer['phone'])) {
                    DB::rollBack();
                    Toastr::error(translate('please_update_your_phone_number'));
                    // return redirect()->route('tour.tour-booking', [$id]);
                    return redirect()->route('tour.tour-visit-id', [$id]);
                }

                $payment_info = new PaymentInfo(
                    success_hook: 'digital_payment_success_custom',
                    failure_hook: 'digital_payment_fail',
                    currency_code: $currency_code,
                    payment_method: 'razor_pay',
                    payment_platform: 'web',
                    payer_id: $customer['id'],
                    receiver_id: '100',
                    additional_data: $additional_data,
                    payment_amount: $tourLeads['amount'],
                    external_redirect_link: route('tour.tour-pay-success', [$tourData['slug'], 'lead' => ($tourLeads['id'] ?? '')]),
                    attribute: 'tour_order',
                    attribute_id: idate("U")
                );
                DB::commit();
                $receiver_info = new Receiver('receiver_name', 'example.png');
                $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
                $parsed_url = parse_url($redirect_link);
                $query_string = $parsed_url['query'];
                parse_str($query_string, $query_params);
                return redirect($redirect_link);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('An error occurred: ' . $e->getMessage());
            return redirect()->route('tour.tour-visit-id', [$id]);
        }
    }

    public function TourSuccess(Request $request, $id, $lead)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $getLead = TourLeads::where('id',  $lead)->first();
                $tourData = TourVisits::where('slug',  $id)->first();
                $tourOrder = TourOrder::where('id', ($getadditional->order_id ?? ''))->first();
                \App\Models\TourLeads::where('id', $lead)->update(['amount_status' => 1, 'order_id' => ($getadditional->order_id ?? ''), 'via_online' => $tourOrder['amount']]);
                $userInfo = \App\Models\User::where('id', ($getLead['user_id'] ?? ""))->first();
                // if ($userInfo['email'] != $getLead['user_phone']) {
                //     $data_email = [
                //         'subject' => translate("Tour_booking_successfully"),
                //         'email' => $userInfo['email'],
                //         'message' => "Booking Completed",
                //     ];
                //     $this->eventsRepository->sendMails($data_email);
                // }

                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $tourOrder['amount'];
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($tourData['tour_commission']) {
                    $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }

                TourOrder::where('id', ($getadditional->order_id ?? ''))->update(['amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => $transactionReference]);

                if ($tourOrder['use_date'] == 1 && $tourData['is_person_use'] == 0) {
                    $getseats =   \App\Models\TourOrder::where('tour_id', $tourOrder['tour_id'])->where('id', "!=", ($getadditional->order_id ?? ''))->where('amount_status', 1)->where('status', 1)->where('available_seat_cab_id', ($tourOrder['available_seat_cab_id'] ?? 0))->sum('qty');
                    if (((int)$tourOrder['qty'] + (int)$getseats) > (int)$tourOrder['total_seats_cab']) {
                        Toastr::error('Currently ' . ($tourOrder['total_seats_cab'] - $getseats) . ' seats are available');
                        TourOrder::where('id', ($getadditional->order_id ?? ''))->update(['status' => 2, 'refound_id' => 'wallet', 'refund_status' => 1, 'refund_amount' => $tourOrder['amount'], 'refund_date' => date('Y-m-d H:i:s')]);
                        User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $tourOrder['amount'])]);
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $getadditional->customer_id;
                        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                        $wallet_transaction->reference = 'Tour refund';
                        $wallet_transaction->transaction_type = 'tour_refund';
                        $wallet_transaction->balance = ($userInfo['wallet_balance'] - $tourOrder['amount']);
                        $wallet_transaction->credit = $tourOrder['amount'];
                        $wallet_transaction->save();
                        return redirect()->route('tour.tour-visit-id', [$id]);
                    } else {
                        $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                        $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                        $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                        $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                        $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                        $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                        $message_data['customer_id'] = $getadditional->customer_id;
                        if ($tourData['tour_image']) {
                            $message_data['type'] = 'text-with-media';
                            $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                        }
                        $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                        $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                        Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);
                    }
                } else {
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                    $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                    $message_data['customer_id'] = $getadditional->customer_id;
                    if ($tourData['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                    }
                    $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);
                }

                return redirect()->route('tour.tour-booking-success', [$id]);
            } else {
                $array['transaction_id'] = $transactionReference;
                $array['amount_status'] = 2;
                TourOrder::where('id', $getadditional->order_id)->update($array);
                return redirect()->route('tour.tour-booking-failed', [$id]);
            }
        } else {
            \App\Models\TourLeads::where('id', $lead)->update(['amount_status' => 1]);
            return redirect()->route('tour.tour-booking-failed', [$id]);
        }
    }

    // public function TourBookingApi(Request $request)
    // {
    //     $request->validate([
    //         'wallet_type' => 'required|in:0,1',
    //         'tour_id' =>  ['required', function ($attribute, $value, $fail) {
    //             if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
    //                 $fail('The selected tour is invalid or inactive.');
    //             }
    //         },],
    //         'leads_id' => 'required',
    //         'package_id' => 'required',
    //         'payment_amount' => 'required|numeric|min:1',
    //         'qty' => 'required|numeric|min:1',

    //         'pickup_address' => 'required',
    //         'pickup_date' => 'required',
    //         'pickup_time' => 'required',
    //         'pickup_lat' => 'required',
    //         'pickup_long' => 'required',
    //         'use_date' => 'required|in:0,1',

    //         'user_id' => ['required', function ($attribute, $value, $fail) {
    //             if (!User::where('id', $value)->where('is_active', 1)->exists()) {
    //                 $fail('The selected user is invalid or inactive.');
    //             }
    //         },],
    //     ], [
    //         'tour_id.required' => "tour is required!",
    //         'leads_id.required' => "lead Id is required!",
    //         'user_id.required' => "user Id is required!",
    //     ]);


    //     DB::beginTransaction();
    //     try {
    //         // dd($request['payment_amount']);
    //         $user = User::find($request->user_id);
    //         $event_booking = new TourOrder();
    //         $event_booking->user_id = $request->user_id;
    //         $event_booking->tour_id = $request->tour_id;
    //         $event_booking->package_id = $request->package_id;
    //         $event_booking->coupon_amount = $request->coupon_amount ?? 0;
    //         $event_booking->coupon_id = $request->coupon_id ?? '';
    //         $event_booking->amount = $request->payment_amount;
    //         $event_booking->qty = $request->qty;
    //         $event_booking->pickup_address = $request->pickup_address;
    //         $event_booking->pickup_date = $request->pickup_date;
    //         $event_booking->pickup_time = $request->pickup_time;
    //         $event_booking->pickup_lat = $request->pickup_lat;
    //         $event_booking->pickup_long = $request->pickup_long;
    //         $event_booking->payment_method = 'razor_pay';
    //         $event_booking->payment_platform = 'api';
    //         $event_booking->leads_id = $request->leads_id;
    //         $event_booking->use_date = $request->use_date;
    //         $event_booking->pickup_otp = mt_rand(1000, 9999);
    //         $event_booking->drop_opt = mt_rand(1000, 9999);
    //         $event_booking->amount_status = 0;
    //         $event_booking->status = 0;
    //         $event_booking->save();
    //         /////////////////////////////////////////// WALLET AND ONLINE /////////////////////////////////////////////////
    //         if ($request->wallet_type == 1) {
    //             if ($user['wallet_balance'] >= $request['payment_amount']) {
    //                 // wallet dedication
    //                 User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . $request['payment_amount'])]);
    //                 \App\Models\TourLeads::where('id', $request->leads_id)->update(['amount_status' => 1]);
    //                 $getLead = TourLeads::where('id',  $request->leads_id)->first();
    //                 $tourData = TourVisits::where('id',  $request->tour_id)->first();
    //                 if ($user['email'] != $getLead['user_phone']) {
    //                     $data_email = [
    //                         'subject' => translate("Tour_booking_successfully"),
    //                         'email' => $user['email'],
    //                         'message' => "Booking Completed",
    //                     ];
    //                     $this->eventsRepository->sendMails($data_email);
    //                 }
    //                 $gst_amount = 0;
    //                 $admin_commission = 0;
    //                 $final_amount = $request['payment_amount'];
    //                 if ($tourData['tour_commission']) {
    //                     $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
    //                     $final_amount = ($final_amount - $admin_commission);
    //                 }
    //                 TourOrder::where('id', $event_booking->id)->update(['payment_method' => 'wallet', 'amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => 'wallet']);
    //                 $wallet_transaction = new WalletTransaction();
    //                 $wallet_transaction->user_id = $user['id'];;
    //                 $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
    //                 $wallet_transaction->reference = 'Tour order';
    //                 $wallet_transaction->transaction_type = 'tour_order';
    //                 $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
    //                 $wallet_transaction->debit = $request->payment_amount;
    //                 $wallet_transaction->save();
    //                 DB::commit();
    //                 return response()->json(['status' => 1, 'message' => "booking Successfully", 'data' => []], 200);
    //             } else {
    //                 // wallet dedication
    //                 return response()->json(['status' => 0, 'message' => 'please wallet Amount Check', 'data' => []], 200);
    //             }
    //         } else {
    //             $additional_data = [
    //                 'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
    //                 'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
    //                 'payment_mode' => 'app',
    //                 'leads_id' => $request->leads_id,
    //                 'package_id' => $request->package_id,
    //                 'customer_id' => $request->user_id,
    //                 "order_id" => $event_booking->id,
    //                 "tour_id" => $request->tour_id,
    //                 "amount" => $request->payment_amount,
    //                 "user_name" => $user['name'],
    //                 "user_email" => $user['email'],
    //                 "user_phone" => $user['phone'],
    //             ];
    //             $currency_model = Helpers::get_business_settings('currency_model');
    //             if ($currency_model == 'multi_currency') {
    //                 $currency_code = 'USD';
    //             } else {
    //                 $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
    //                 $currency_code = Currency::find($default)->code;
    //             }
    //             $customer = Helpers::get_customer($request);
    //             if ($customer == 'offline') {
    //                 $address = ShippingAddress::where(['customer_id' => $request->user_id, 'is_guest' => 1])->latest()->first();
    //                 if ($address) {
    //                     $payer = new Payer(
    //                         $address->contact_person_name,
    //                         $address->email,
    //                         $address->phone,
    //                         ''
    //                     );
    //                 } else {
    //                     $payer = new Payer(
    //                         'Contact person name',
    //                         '',
    //                         '',
    //                         ''
    //                     );
    //                 }
    //             } else {
    //                 $payer = new Payer(
    //                     $customer['f_name'] . ' ' . $customer['l_name'],
    //                     $customer['email'],
    //                     $customer['phone'],
    //                     ''
    //                 );
    //                 if (empty($customer['phone'])) {
    //                     DB::rollBack();
    //                     response()->json(['status' => 0, 'message' => 'please update your phone number', 'data' => []], 200);
    //                 }
    //             }

    //             $payment_info = new PaymentInfo(
    //                 success_hook: 'digital_payment_success_custom',
    //                 failure_hook: 'digital_payment_fail',
    //                 currency_code: $currency_code,
    //                 payment_method: 'razor_pay',
    //                 payment_platform: 'app',
    //                 payer_id: $customer == 'offline' ? $request->user_id : $customer['id'],
    //                 receiver_id: '100',
    //                 additional_data: $additional_data,
    //                 payment_amount: $request->payment_amount,
    //                 external_redirect_link: url('api/v1/tour/tour-payamount-success'),
    //                 attribute: 'tour_order',
    //                 attribute_id: idate("U")
    //             );

    //             DB::commit();
    //             $receiver_info = new Receiver('receiver_name', 'example.png');
    //             $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
    //             $parsed_url = parse_url($redirect_link);
    //             $query_string = $parsed_url['query'];
    //             parse_str($query_string, $query_params);
    //             return response()->json(['status' => 1, 'message' => 'pay Now', 'data' => ['url' => $redirect_link]], 200);
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['status' => 0, 'message' => 'An error occurred: ' . $e->getMessage(), 'data' => []], 200);
    //     }
    // }

    public function TourBookingApi(Request $request)
    {
        $request->validate([
            'wallet_type' => 'required|in:0,1',
            'tour_id' =>  ['required', function ($attribute, $value, $fail) {
                if (!TourVisits::where('id', $value)->where('status', 1)->exists()) {
                    $fail('The selected tour is invalid or inactive.');
                }
            },],
            'leads_id' => 'required',
            'package_id' => 'required',
            'payment_amount' => 'required|numeric|min:1',
            'qty' => 'required|numeric|min:1',

            'pickup_address' => 'required',
            'pickup_date' => 'required',
            'pickup_time' => 'required',
            'pickup_lat' => 'required',
            'pickup_long' => 'required',
            'booking_package' => 'required',
            'use_date' => 'required|in:0,1,2,3,4',
            'transaction_id' => 'required',
            'online_pay' => 'required_unless:transaction_id,wallet',
            'user_id' => ['required', function ($attribute, $value, $fail) {
                if (!User::where('id', $value)->where('is_active', 1)->exists()) {
                    $fail('The selected user is invalid or inactive.');
                }
            },],
        ], [
            'tour_id.required' => "tour is required!",
            'leads_id.required' => "lead Id is required!",
            'user_id.required' => "user Id is required!",
        ]);


        if ($request->wallet_type == 1 && ($request['online_pay'] ?? 0) > 0) {
            User::where('id', $request->user_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $request['online_pay'])]);
            $wallet_transaction = new WalletTransaction();
            $wallet_transaction->user_id = $request->user_id;
            $wallet_transaction->transaction_id = (($request->transaction_id) ? $request->transaction_id : \Illuminate\Support\Str::uuid());
            $wallet_transaction->reference = 'add_funds_to_wallet';
            $wallet_transaction->transaction_type = 'add_fund';
            $wallet_transaction->balance = User::where('id', $request->user_id)->first()['wallet_balance'];
            $wallet_transaction->credit = $request['online_pay'];
            $wallet_transaction->save();
        }

        DB::beginTransaction();
        try {
            $user = User::find($request->user_id);
            $event_booking = new TourOrder();
            $event_booking->user_id = $request->user_id;
            $event_booking->tour_id = $request->tour_id;
            $event_booking->package_id = $request->package_id;
            $event_booking->coupon_amount = $request->coupon_amount ?? 0;
            $event_booking->coupon_id = $request->coupon_id ?? '';
            $event_booking->amount = $request->payment_amount;
            $event_booking->qty = $request->qty;
            $event_booking->pickup_address = $request->pickup_address;
            $event_booking->booking_package = json_encode($request->booking_package ?? ['']);
            $event_booking->pickup_date = $request->pickup_date;
            $event_booking->pickup_time = $request->pickup_time;
            $event_booking->pickup_lat = $request->pickup_lat;
            $event_booking->pickup_long = $request->pickup_long;
            $event_booking->payment_method = 'razor_pay';
            $event_booking->payment_platform = 'api';
            $event_booking->leads_id = $request->leads_id;
            $event_booking->use_date = $request->use_date;
            $event_booking->pickup_otp = mt_rand(1000, 9999);
            $event_booking->drop_opt = mt_rand(1000, 9999);
            $event_booking->part_payment = (($request->part_payment) ? $request->part_payment : 'full');
            $cabPackages = collect($request->booking_package)->firstWhere('type', 'cab');
            $event_booking->available_seat_cab_id = $cabPackages ? (int) $cabPackages['id'] : 0;
            $event_booking->total_seats_cab = $cabPackages ? (int) $cabPackages['title'] : 0;
            $event_booking->amount_status = (($request->transaction_id) ? 1 : 0);
            $event_booking->status = (($request->transaction_id) ? 1 : 0);
            $event_booking->save();
            /////////////////////////////////////////// WALLET AND ONLINE /////////////////////////////////////////////////
            $getLead = TourLeads::where('id',  $request->leads_id)->first();
            $tourData = TourVisits::where('id',  $request->tour_id)->first();
            if ($request->wallet_type == 1) {
                if ($user['wallet_balance'] >= $request['payment_amount']) {
                    User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . $request['payment_amount'])]);
                    \App\Models\TourLeads::where('id', $request->leads_id)->update(['amount_status' => 1, 'order_id' => $event_booking->id, 'via_wallet' => $request['payment_amount']]);
                    // if ($user['email'] != $getLead['user_phone']) {
                    //     $data_email = [
                    //         'subject' => translate("Tour_booking_successfully"),
                    //         'email' => $user['email'],
                    //         'message' => "Booking Completed",
                    //     ];
                    //     $this->eventsRepository->sendMails($data_email);
                    // }
                    $gst_amount = 0;
                    $admin_commission = 0;
                    $final_amount = $request['payment_amount'];
                    $eventtax = \App\Models\ServiceTax::find(1);
                    if ($eventtax['tour_tax']) {
                        $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                        $final_amount = $final_amount - $gst_amount;
                    }
                    if ($tourData['tour_commission']) {
                        $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                        $final_amount = ($final_amount - $admin_commission);
                    }
                    TourOrder::where('id', $event_booking->id)->update(['payment_method' => 'wallet', 'amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => 'wallet']);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour order';
                    $wallet_transaction->transaction_type = 'tour_order';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = $request->payment_amount;
                    $wallet_transaction->save();
                    DB::commit();
                    return response()->json(['status' => 1, 'message' => "booking Successfully", 'data' => []], 200);
                } else {
                    return response()->json(['status' => 0, 'message' => 'please wallet Amount Check', 'data' => []], 200);
                }
            } else {
                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $request->payment_amount;
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($tourData['tour_commission']) {
                    $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }
                TourOrder::where('id', ($event_booking->id ?? ''))->update(['amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => $request->transaction_id]);
                $tourOrder = TourOrder::where('id', ($event_booking->id ?? ''))->first();
                if ($request->use_date == 1 && $tourData['is_person_use'] == 0) {
                    $getseats =   \App\Models\TourOrder::where('tour_id', $request->tour_id)->where('id', "!=", ($event_booking->id ?? ''))->where('amount_status', 1)->where('status', 1)->where('available_seat_cab_id', ($tourOrder['available_seat_cab_id'] ?? 0))->sum('qty');
                    if (((int)$request->qty + (int)$getseats) > (int)$tourOrder['total_seats_cab']) {
                        TourOrder::where('id', ($event_booking->id ?? ''))->update(['status' => 2, 'refound_id' => 'wallet', 'refund_status' => 1, 'refund_amount' => $tourOrder['amount'], 'refund_date' => date('Y-m-d H:i:s')]);
                        User::where('id', $request->user_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $tourOrder['amount'])]);
                        $userInfo = \App\Models\User::where('id', ($request->user_id ?? ""))->first();
                        $wallet_transaction = new WalletTransaction();
                        $wallet_transaction->user_id = $request->user_id;
                        $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                        $wallet_transaction->reference = 'Tour refund';
                        $wallet_transaction->transaction_type = 'tour_refund';
                        $wallet_transaction->balance = ($userInfo['wallet_balance']);
                        $wallet_transaction->credit = $tourOrder['amount'];
                        $wallet_transaction->save();
                        DB::commit();
                        return response()->json(['status' => 1, 'message' => 'Currently ' . ($tourOrder['total_seats_cab'] - $getseats) . ' seats are available', 'data' => []], 200);
                        // return redirect()->route('tour.tour-visit-id', [$id]);
                    } else {
                        $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                        $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                        $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
                        $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                        $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                        $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                        $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                        $message_data['customer_id'] = $request->user_id;
                        if ($tourData['tour_image']) {
                            $message_data['type'] = 'text-with-media';
                            $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                        }
                        $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                        $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                        Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);
                    }
                } else {
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                    $message_data['booking_date'] = ($tourOrder['pickup_date'] ?? '');
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$request->payment_amount ?? 0);
                    $message_data['customer_id'] = $request->user_id;
                    if ($tourData['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                    }
                    $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);
                }
                \App\Models\TourLeads::where('id', $request->leads_id)->update(['amount_status' => 1, 'order_id' => $event_booking->id, 'via_online' => $request['payment_amount']]);
                DB::commit();
                return response()->json(['status' => 1, 'message' => 'pay Successfully', 'data' => []], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'An error occurred: ' . $e->getMessage(), 'data' => []], 200);
        }
    }

    public function TourBookingSuccess(Request $request)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $getLead = TourLeads::where('id',  $getadditional->leads_id)->first();
                $tourData = TourVisits::where('id',  $getadditional->tour_id)->first();
                $tourOrder = TourOrder::where('id', ($getadditional->order_id ?? ''))->first();

                \App\Models\TourLeads::where('id', $getadditional->leads_id)->update(['amount_status' => 1]);
                $userInfo = \App\Models\User::where('id', ($getLead['user_id'] ?? ""))->first();
                // if ($userInfo['email'] != $getLead['user_phone']) {
                //     $data_email = [
                //         'subject' => translate("Tour_booking_successfully"),
                //         'email' => $userInfo['email'],
                //         'message' => "Booking Completed",
                //     ];
                //     $this->eventsRepository->sendMails($data_email);
                // }
                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $tourOrder['amount'];
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($tourData['tour_commission']) {
                    $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }
                TourOrder::where('id', ($getadditional->order_id ?? ''))->update(['amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => $transactionReference]);

                ///////////

                // $deviceToken = 'YOUR_DEVICE_TOKEN_HERE';
                // $title = 'Hello World';
                // $message = 'This is a sample notification.';        
                // sendFirebasePushNotification($deviceToken, $title, $message);

                ///////////
                return response()->json(['status' => 1, 'message' => 'Amount Transaction Successfully ', 'data' => []], 200);
            } else {
                $array['transaction_id'] = $transactionReference;
                $array['amount_status'] = 2;
                TourOrder::where('id', $getadditional->order_id)->update($array);
                return response()->json(['status' => 0, 'message' => 'Amount Transaction Failed ', 'data' => []], 200);
            }
        } else {
            return response()->json(['status' => 0, 'message' => 'Amount Transaction Failed ', 'data' => []], 200);
        }
    }

    static function Wallet_amount_add($all_info)
    {
        $additional_data = $all_info['additional_data'];
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer(['customer_id' => $all_info['user_id']]);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $all_info['user_id'], 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
        }

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $all_info['payment_method'],
            payment_platform: $all_info['payment_platform'],
            payer_id: $customer == 'offline' ? $all_info['user_id'] : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $all_info['payment_amount'],
            external_redirect_link: $all_info['payment_platform'] == 'web' ? $all_info['external_redirect_link'] : null,
            attribute: $all_info['attribute'],
            attribute_id: idate("U")
        );
        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        $parsed_url = parse_url($redirect_link);
        $query_string = $parsed_url['query'];
        parse_str($query_string, $query_params);
        return ($redirect_link);
        // return redirect($redirect_link);
    }

    public function AllWalletSuccess(Request $request, $id)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $getadditional->online_pay)]);
                $userInfo = \App\Models\User::where('id', ($getadditional->customer_id))->first();
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $getadditional->customer_id;
                $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                $wallet_transaction->pay_transaction_id =  $transactionReference;
                $wallet_transaction->reference = 'add_funds_to_wallet';
                $wallet_transaction->transaction_type = 'add_fund';
                $wallet_transaction->balance = $userInfo['wallet_balance'];
                $wallet_transaction->credit = $getadditional->online_pay;
                $wallet_transaction->save();
                if ($getadditional->page_name == 'tour_order') {
                    $data_id = json_decode(base64_decode($id));
                    $getLead = TourLeads::where('id',  $request->lead)->first();
                    $tourData = TourVisits::where('slug',  $id)->first();
                    $tourOrder = TourOrder::where('id', ($getadditional->order_id ?? ''))->first();
                    \App\Models\TourLeads::where('id', $request->lead)->update(['amount_status' => 1, 'order_id' => ($getadditional->order_id ?? ''), 'via_wallet' => $getadditional->total_amount]);
                    // if ($userInfo['email'] != $getLead['user_phone']) {
                    //     $data_email = [
                    //         'subject' => translate("Tour_booking_successfully"),
                    //         'email' => $userInfo['email'],
                    //         'message' => "Booking Completed",
                    //     ];
                    //     $this->eventsRepository->sendMails($data_email);
                    // }
                    $eventtax = \App\Models\ServiceTax::find(1);
                    $gst_amount = 0;
                    $admin_commission = 0;
                    $final_amount = $getadditional->total_amount;
                    if ($eventtax['tour_tax']) {
                        $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                        $final_amount = $final_amount - $gst_amount;
                    }
                    if ($tourData['tour_commission']) {
                        $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                        $final_amount = ($final_amount - $admin_commission);
                    }
                    TourOrder::where('id', ($getadditional->order_id ?? ''))->update(['status' => 1, 'amount_status' => 1, 'admin_commission' => $admin_commission, 'gst_amount' => $gst_amount, 'final_amount' => $final_amount, 'transaction_id' => 'wallet']);
                    User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getadditional->total_amount)]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour Order';
                    $wallet_transaction->transaction_type = 'tour_order';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] - $getadditional->total_amount);
                    $wallet_transaction->debit = $getadditional->total_amount;
                    $wallet_transaction->save();

                    if ($tourOrder['use_date'] == 1 && $tourData['is_person_use'] == 0) {
                        $getseats =   \App\Models\TourOrder::where('tour_id', $tourOrder['tour_id'])->where('id', "!=", ($getadditional->order_id ?? ''))->where('amount_status', 1)->where('status', 1)->where('available_seat_cab_id', ($tourOrder['available_seat_cab_id'] ?? 0))->sum('qty');
                        if (((int)$tourOrder['qty'] + (int)$getseats) > (int)$tourOrder['total_seats_cab']) {
                            Toastr::error('Currently ' . ($tourOrder['total_seats_cab'] - $getseats) . ' seats are available');
                            TourOrder::where('id', ($getadditional->order_id ?? ''))->update(['status' => 2, 'refound_id' => 'wallet', 'refund_status' => 1, 'refund_amount' => $tourOrder['amount'], 'refund_date' => date('Y-m-d H:i:s')]);
                            User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $tourOrder['amount'])]);
                            $wallet_transaction = new WalletTransaction();
                            $wallet_transaction->user_id = $getadditional->customer_id;
                            $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                            $wallet_transaction->reference = 'Tour refund';
                            $wallet_transaction->transaction_type = 'tour_refund';
                            $wallet_transaction->balance = ($userInfo['wallet_balance'] - $tourOrder['amount']);
                            $wallet_transaction->credit = $tourOrder['amount'];
                            $wallet_transaction->save();
                            return redirect()->route('tour.tour-visit-id', [$id]);
                        }
                    }

                    $tourOrder = TourOrder::where('id', $getadditional->order_id)->first();
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['title_name'] = ($tourData['tour_name'] ?? '');
                    $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['pickup_date'] ?? ''));
                    $message_data['time'] = ($tourOrder['pickup_time'] ?? '');
                    $message_data['place_name'] = ($tourOrder['pickup_address'] ?? '');
                    $message_data['tour_type'] = ucwords(str_replace('_', ' ', (($tourData['tour_type'] ?? ''))));
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$tourOrder['amount'] ?? 0);
                    $message_data['customer_id'] = $getadditional->customer_id;
                    if ($tourData['tour_image']) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = asset('/storage/app/public/tour_and_travels/tour_visit/' . $tourData['tour_image'] ?? '');
                    }
                    $remain_amount = ((!empty($tourOrder['part_payment']) && $tourOrder['part_payment'] == 'part') ? $tourOrder['amount'] : 0);
                    $message_data['remain_amount'] = webCurrencyConverter(amount: (float)$remain_amount ?? 0);
                    Helpers::whatsappMessage('tour', 'Tour booking Confirmed', $message_data);

                    return redirect()->route('tour.tour-booking-success', [$id]);
                } elseif ($getadditional->page_name == 'event_order') {
                    $data_id = $id;
                    $getLead = EventLeads::where('id',  $request->lead)->first();
                    $EventId = Events::where('slug',  $data_id)->first();
                    $listOrganizer =  EventOrganizer::where('id', $EventId['event_organizer_id'])->first();
                    $bookingSeats = json_decode($EventId['all_venue_data'], true);
                    $foundPackage = [];
                    if ($bookingSeats) {
                        $pn = 0;
                        foreach ($bookingSeats as $keys => $bo_se) {
                            $foundPackage[$keys] = $bo_se;
                            if (($bo_se['id'] ?? "") == $getLead['venue_id'] && !empty($bo_se['package_list'])) {
                                foreach ($bo_se['package_list'] as $kp => $ch_seat) {
                                    if ($ch_seat['package_name'] == $getLead['package_id']) {
                                        if ($ch_seat['available'] < $getLead['qty']) {
                                            Toastr::error($getLead['qty'] . ' seats are not available. ' . $ch_seat['available'] . ' seats are available.');
                                            $array['transaction_id'] = 'wallet';
                                            $array['transaction_status'] = 3;
                                            EventOrder::where('id', $getadditional->order_id)->update($array);

                                            $eventOrder = EventOrder::where('id', $getadditional->order_id)->with('orderitem')->first();

                                            $message_data['title_name'] = $EventId['event_name'];
                                            $message_data['place_name'] = $bo_se['en_event_cities'];
                                            $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date']));
                                            $message_data['time'] = ($bo_se['start_time']);
                                            $message_data['orderId'] = $eventOrder['order_no'];
                                            $message_data['final_amount'] = webCurrencyConverter(amount: (float)$eventOrder['amount'] ?? 0);
                                            $message_data['customer_id'] =  $eventOrder['user_id'];
                                            $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                                            Helpers::whatsappMessage('event', 'Refund', $message_data);

                                            return redirect()->route('event-booking', [$id, 'lead' => $request->lead]);
                                        } else {
                                            User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getadditional->total_amount)]);
                                            $foundPackage[$keys]['package_list'][$kp]['available'] = ($ch_seat['available'] - $getLead['qty']);
                                            $foundPackage[$keys]['package_list'][$kp]['sold'] = ($ch_seat['sold'] + $getLead['qty']);
                                            $array['transaction_id'] = 'wallet';
                                            $array['transaction_status'] = 1;
                                            $eventtax = \App\Models\ServiceTax::find(1);
                                            $amdin_commission = 0;
                                            $final_amount = 0;
                                            $govtTax = 0;
                                            $orderamount = ($getLead['total_amount'] + $getLead['coupon_amount'] ?? 0);
                                            if (!empty($EventId) && $EventId['commission_seats']) {
                                                $govtTax = (($orderamount * ($eventtax['event_tax'] ?? 0)) / 100);
                                                $orderamount = $orderamount - $govtTax;
                                                $amdin_commission =  (($orderamount * $EventId['commission_seats']) / 100);
                                                $final_amount = $orderamount - $amdin_commission;
                                            }
                                            $array['admin_commission'] = $amdin_commission;
                                            $array['gst_amount'] = $govtTax;
                                            $array['final_amount'] = $final_amount;
                                            EventOrder::where('id', $getadditional->order_id)->update($array);
                                            EventOrderItems::where('order_id', $getadditional->order_id)->update(['sub_amount' => ($getLead['amount'] ?? 0), 'gst' => ($eventtax['event_tax'] ?? 0), 'gst_amount' => $govtTax]);
                                            EventOrganizer::where('id', $EventId['event_organizer_id'])->update(
                                                [
                                                    'org_total_tax' => ($listOrganizer['org_total_tax'] + $govtTax),
                                                    "org_withdrawable_ready" => ($listOrganizer["org_withdrawable_ready"] + $final_amount),
                                                    "org_total_commission" => ($listOrganizer["org_total_commission"] + $amdin_commission),
                                                ]
                                            );
                                            $eventOrder = EventOrder::where('id', $getadditional->order_id)->with('orderitem')->first();
                                            $message_data['title_name'] = $EventId['event_name'];
                                            $message_data['place_name'] = $bo_se['en_event_cities'] ?? '';
                                            $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date'] ?? ''));
                                            $message_data['time'] = ($bo_se['start_time'] ?? "");
                                            $message_data['orderId'] = $eventOrder['order_no'];
                                            $message_data['final_amount'] = webCurrencyConverter(amount: (float)$getLead['total_amount'] ?? 0);
                                            $message_data['customer_id'] =  $userInfo['id'];
                                            $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                                            $messages =  Helpers::whatsappMessage('event', 'Event booking Confirmed', $message_data);
                                        }
                                    }
                                }
                            }
                        }
                        Events::where('slug',  $data_id)->update(['all_venue_data' => $foundPackage]);
                    }
                    \App\Models\EventLeads::where('id', $request->lead)->update(['status' => 1]);
                    $userInfo = \App\Models\User::where('phone', ($getLead['user_phone'] ?? ""))->first();

                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Event Order';
                    $wallet_transaction->transaction_type = 'event_order';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] - $getadditional->total_amount);
                    $wallet_transaction->debit = $getadditional->total_amount;
                    $wallet_transaction->save();

                    $eventOrder = EventOrder::where('id', $getadditional->order_id)->with('orderitem')->first();

                    $message_data['title_name'] = $EventId['event_name'];
                    $message_data['place_name'] = $bo_se['en_event_cities'];
                    $message_data['booking_date'] = date('Y-m-d', strtotime($bo_se['date']));
                    $message_data['time'] = ($bo_se['start_time']);
                    $message_data['orderId'] = $eventOrder['order_no'];
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$eventOrder['amount'] ?? 0);
                    $message_data['customer_id'] =  $getadditional->customer_id;
                    $message_data['number'] =  $eventOrder['orderitem'][0]['no_of_seats'] ?? 0;
                    Helpers::whatsappMessage('event', 'Event booking Confirmed', $message_data);
                    return redirect()->route('event-booking-success', [$id]);
                } elseif ($getadditional->page_name == "donate_order") {
                    User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getadditional->total_amount)]);
                    $findData =  DonateAllTransaction::where('id', $getadditional->transaction_id)->first();
                    $array['transaction_id'] = 'wallet';
                    $array['amount_status'] = 1;
                    DonateAllTransaction::where('id', $findData['id'])->update($array);
                    $gettrust = DonateTrust::where('id', $findData['trust_id'])->first();
                    if ($gettrust) {
                        DonateTrust::where('id', $findData['trust_id'])->update(['trust_total_amount' => ($gettrust['trust_total_amount'] + $findData['final_amount']), 'admin_commission' => ($gettrust['admin_commission'] + $findData['admin_commission'])]);
                    }
                    $adsTrust = DonateAds::where('id', $findData['ads_id'])->first();
                    if ($adsTrust) {
                        DonateAds::where('id', $findData['ads_id'])->update(['total_amount_ads' => ($adsTrust['total_amount_ads'] + $findData['final_amount']), 'admin_commission_amount' => ($adsTrust['admin_commission_amount'] + $findData['admin_commission'])]);
                    }

                    if (isset($getadditional->leads_id) && !empty($getadditional->leads_id)) {
                        DonateLeads::where('id', $getadditional->leads_id)->update(['status' => 1]);
                    }
                    $message_data['title_name'] = ((!empty($gettrust) && !empty($gettrust['trust_name'])) ? $gettrust['trust_name'] : 'Mahakal');
                    $message_data['ad_name'] = ((!empty($adsTrust) && !empty($adsTrust['name'])) ? $adsTrust['name'] : '');
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)$findData['amount'] ?? 0);
                    $message_data['customer_id'] =  $getadditional->customer_id;

                    $orderData = DonateAllTransaction::where('id', $findData['id'])->where('user_id', $getadditional->customer_id)->with(['users', 'getTrust', 'adsTrust'])->first();
                    $message_data['person_phone'] =  $orderData['user_phone'];
                    $message_data['pan_card'] =  $orderData['pan_card'];

                    $mpdf_view = \View::make('web-views.donate.invoice', compact('orderData'));
                    Helpers::gen_mpdf_Pdf($mpdf_view, 'donate_order', $findData['id']);
                    $message_data['attachment'] = asset('storage/app/public/donate/invoice/donate_order' . $findData['id'] . '.pdf');
                    $message_data['type'] = 'text-with-media';
                    Helpers::whatsappMessage('donate', 'Donation Success', $message_data);
                    $orderData = DonateAllTransaction::where('id', $findData['id'])->with(['getTrust', 'adsTrust'])->first();
                    $message_data2['trust_name'] =  $orderData['getTrust']['trust_name'] ?? "Mahakal.com";
                    $message_data2['ad_name'] =  $orderData['adsTrust']['name'] ?? '';
                    $message_data2['booking_date'] =  date('d M,Y H:i A', strtotime($orderData['created_at']));
                    $message_data2['order_amount'] =  $orderData['amount'];
                    $message_data2['admin_commission'] =  $orderData['admin_commission'];
                    $message_data2['final_amount'] =  $orderData['final_amount'];
                    $message_data2['vendor_email'] =   $orderData['getTrust']['trust_email'] ?? "Mahakal.com";
                    $message_data2['seller_id'] = \App\Models\Seller::where('relation_id', $orderData['trust_id'])->where('type', 'trust')->first()['id'] ?? 0;
                    Helpers::whatsappMessage('donate', 'donation_trust_receipt', $message_data2);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Donate';
                    $wallet_transaction->transaction_type = 'donate';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] - $getadditional->total_amount);
                    $wallet_transaction->debit = $getadditional->total_amount;
                    $wallet_transaction->save();

                    return redirect()->route('donate-success', [$findData['id']]);
                } elseif ($getadditional->page_name == "kundli_order") {
                    User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getadditional->total_amount)]);
                    $request['wallet_type'] = 1;
                    $request['insertedId'] = $getadditional->order_id;
                    $request['leads'] = $getadditional->leads_id;
                    $data =   $this->BirthJournalSuccess($request);
                    $findData =  BirthJournalKundali::with('birthJournal')->find($getadditional->order_id);
                    // if ($findData && $findData['birthJournal']['name'] == 'kundali') {
                    //     $url = 'saved.paid.kundali';
                    // } else {
                    //     $url = 'saved.paid.kundali.milan';
                    // }
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'kundli_order';
                    $wallet_transaction->transaction_type = 'kundli_order';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] - $getadditional->total_amount);
                    $wallet_transaction->debit = $getadditional->total_amount;
                    $wallet_transaction->save();
                    // return redirect()->route($url);
                    return redirect()->route("kundali-pdf.kundali-payment-success", [$getadditional->order_id]);
                } elseif ($getadditional->page_name == 'vip_darshan_order') {
                    \App\Models\TempleDarshanLead::where('id', $getadditional->leads_id)->update(['status' => 1]);

                    \App\Models\DarshanOrder::where('id', $getadditional->order_id)->update(['payment_method' => 'wallet', 'transaction_id' => 'wallet', 'status' => 1]);
                    User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . $getadditional->total_amount)]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'vip Darshan order';
                    $wallet_transaction->transaction_type = 'vip_darshan_order';
                    $wallet_transaction->balance = User::where('id', $getadditional->customer_id)->first()['wallet_balance'];
                    $tourOrder = \App\Models\DarshanOrder::where('id', $getadditional->order_id)->with(['Temple'])->first();
                    $wallet_transaction->debit = ($tourOrder['price'] ?? 0);
                    $wallet_transaction->save();

                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['temple_name'] = ($tourOrder['Temple']['name'] ?? '');
                    $message_data['title_name'] = ($tourOrder['title'] ?? '');
                    $message_data['service_name'] = ($tourOrder['package_name'] ?? '');
                    $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['date'] ?? ''));
                    $message_data['time'] = ($tourOrder['time'] ?? '');
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)($tourOrder['price'] ?? 0));
                    $message_data['customer_id'] = $tourOrder['user_id'];
                    // if (($tourOrder['Temple']['thumbnail'] ?? '')) {
                    //     $message_data['type'] = 'text-with-media';
                    //     $message_data['attachment'] = getValidImage(path: 'storage/app/public/temple/thumbnail/' . ($tourOrder['Temple']['thumbnail'] ?? ''), type: 'backend-logo');
                    // }
                    Helpers::whatsappMessage('vipdarshan', 'Vip Darshan booking Confirmed', $message_data);
                    DonateTrust::where('id', $tourOrder['Temple']['trust_id'] ?? '')->update([
                        'trust_total_amount' => DB::raw('trust_total_amount + ' . $tourOrder['final_amount']),
                        'admin_commission' => DB::raw('admin_commission + ' . $tourOrder['admin_commission']),
                        'gst_total_amount' => DB::raw('gst_total_amount + ' . $tourOrder['gst_amount'])
                    ]);
                    return redirect()->route('vip-darshan-booking-success', [$tourOrder['Temple']['slug']]);
                }
            } else {
                if ($getadditional->page_name == 'tour_order') {
                    $array['transaction_id'] = 'wallet';
                    $array['amount_status'] = 2;
                    TourOrder::where('id', $getadditional->order_id)->update($array);
                    return redirect()->route('tour.tour-booking-failed', [$id]);
                } elseif ($getadditional->page_name == 'event_order') {
                    $array['transaction_id'] = 'wallet';
                    $array['transaction_status'] = 2;
                    EventOrder::where('id', $getadditional->order_id)->update($array);
                    Toastr::error('Transaction Failed.');
                    return redirect()->route('event-booking', [$id]);
                } elseif ($getadditional->page_name == "kundli_order") {
                    Toastr::error('Transaction Failed.');
                    return url('/');
                }
            }
        } else {
            return back();
        }
    }

    public function addTourRemainingpay(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required',
                'payment_platform' => 'required',
            ]);
            $user = Helpers::get_customer($request);
            $tourDataOrder = TourOrder::where('id', $id)->where('status', 1)->first();
            if (empty($tourDataOrder)) {
                return back();
            }
            $tourData = TourVisits::where('id',  $tourDataOrder['tour_id'])->first();

            /////////////////////////////////////////// WALLET AND ONLINE /////////////////////////////////////////////////
            if ($request->wallet_type == 1) {
                if ($user['wallet_balance'] >= $request['payment_amount']) {
                    // wallet dedication
                    User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . $request['payment_amount'])]);
                    // if ($user['email'] != $user['phone']) {
                    //     $data_email = [
                    //         'subject' => translate("Tour_booking_successfully"),
                    //         'email' => $user['email'],
                    //         'message' => "remaining pay success",
                    //     ];
                    //     $this->eventsRepository->sendMails($data_email);
                    // }
                    $eventtax = \App\Models\ServiceTax::find(1);
                    $gst_amount = 0;
                    $admin_commission = 0;
                    $final_amount = $request['payment_amount'];
                    if ($eventtax['tour_tax']) {
                        $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                        $final_amount = $final_amount - $gst_amount;
                    }
                    if ($tourData['tour_commission']) {
                        $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                        $final_amount = ($final_amount - $admin_commission);
                    }
                    TourOrder::where('id', $id)->update(['part_payment' => 'full', 'admin_commission' => DB::raw('admin_commission + ' . $admin_commission), 'gst_amount' => DB::raw('gst_amount + ' . $gst_amount), 'final_amount' => DB::raw('final_amount + ' . $final_amount), 'amount' => DB::raw('amount + ' . $request['payment_amount'])]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour remaining pay';
                    $wallet_transaction->transaction_type = 'tour_order_remaining_pay';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = $request->payment_amount;
                    $wallet_transaction->save();
                    DB::commit();
                    return redirect($request->external_redirect_link);
                } else {
                    // wallet dedication
                    $wallet_amount = ($user['wallet_balance']);
                    $total_amount = $request['payment_amount'];
                    $onlinepay = ($request['payment_amount'] - $user['wallet_balance']);
                    $data = [
                        'additional_data' => [
                            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                            'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
                            'customer_id' => $request->customer_id,
                            "order_id" => $id,
                            "tour_id" => $tourDataOrder['tour_id'],
                            "amount" => $request->payment_amount,
                            "user_name" => $user['name'],
                            "user_email" => $user['email'],
                            "user_phone" => $user['phone'],
                            'total_amount' => $total_amount,
                            'wallet_amount' => $wallet_amount,
                            "online_pay" => $onlinepay,
                            'page_name' => 'tour_order_wallet',
                            'success_url' => $request->external_redirect_link,
                        ],
                        'user_id' => $user['id'],
                        'payment_method' => $request->payment_method,
                        'payment_platform' => $request->payment_platform,
                        'payment_amount' => $onlinepay,
                        'attribute' => "Tour Order",
                        'external_redirect_link' => route('tour.tour-remaining-payment-success', [$id]),
                    ];

                    $url_open = $this->Wallet_amount_add($data);
                    DB::commit();
                    return redirect($url_open);
                }
                // dd($request['payment_amount']);
            } else {
                // dd($request['payment_amount'] - $user['wallet_balance']);

                $additional_data = [
                    'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                    'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                    'payment_mode' => $request->has('payment_platform') ? $request->payment_platform : 'web',
                    'customer_id' => $request->customer_id,
                    "order_id" => $id,
                    "tour_id" => $tourDataOrder['tour_id'],
                    "amount" => $request->payment_amount,
                    "user_name" => $user['name'],
                    "user_email" => $user['email'],
                    "user_phone" => $user['phone'],
                    'page_name' => 'tour_order_online',
                ];
                $currency_model = Helpers::get_business_settings('currency_model');
                if ($currency_model == 'multi_currency') {
                    $currency_code = 'USD';
                } else {
                    $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
                    $currency_code = Currency::find($default)->code;
                }
                $customer = Helpers::get_customer($request);

                if ($customer == 'offline') {
                    $address = ShippingAddress::where(['customer_id' => $request->customer_id, 'is_guest' => 1])->latest()->first();
                    if ($address) {
                        $payer = new Payer(
                            $address->contact_person_name,
                            $address->email,
                            $address->phone,
                            ''
                        );
                    } else {
                        $payer = new Payer(
                            'Contact person name',
                            '',
                            '',
                            ''
                        );
                    }
                } else {
                    $payer = new Payer(
                        $customer['f_name'] . ' ' . $customer['l_name'],
                        $customer['email'],
                        $customer['phone'],
                        ''
                    );
                    if (empty($customer['phone'])) {
                        DB::rollBack();
                        Toastr::error(translate('please_update_your_phone_number'));
                        return redirect($request->external_redirect_link);
                    }
                }

                $payment_info = new PaymentInfo(
                    success_hook: 'digital_payment_success_custom',
                    failure_hook: 'digital_payment_fail',
                    currency_code: $currency_code,
                    payment_method: $request->payment_method,
                    payment_platform: $request->payment_platform,
                    payer_id: $customer == 'offline' ? $request->customer_id : $customer['id'],
                    receiver_id: '100',
                    additional_data: $additional_data,
                    payment_amount: $request->payment_amount,
                    external_redirect_link: route('tour.tour-remaining-payment-success', [$id]),
                    attribute: 'tour_order',
                    attribute_id: idate("U")
                );
                DB::commit();
                $receiver_info = new Receiver('receiver_name', 'example.png');
                $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
                $parsed_url = parse_url($redirect_link);
                $query_string = $parsed_url['query'];
                parse_str($query_string, $query_params);
                return redirect($redirect_link);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('An error occurred: ' . $e->getMessage());
            return redirect($request->external_redirect_link);
        }
    }

    public function TourRemainingpaysuccess(Request $request, $id)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $tourOrder = TourOrder::where('id', ($id ?? ''))->first();
                $tourData = TourVisits::where('id',  $tourOrder['tour_id'])->first();

                $userInfo = \App\Models\User::where('id', ($tourOrder['user_id'] ?? ""))->first();
                // if ($userInfo['email'] != $userInfo['phone']) {
                //     $data_email = [
                //         'subject' => translate("Tour_booking_successfully"),
                //         'email' => $userInfo['email'],
                //         'message' => "remaining pay success",
                //     ];
                //     $this->eventsRepository->sendMails($data_email);
                // }

                $eventtax = \App\Models\ServiceTax::find(1);
                $gst_amount = 0;
                $admin_commission = 0;
                $final_amount = $tourOrder['amount'];
                if ($eventtax['tour_tax']) {
                    $gst_amount = (($final_amount * ($eventtax['tour_tax'] ?? 0)) / 100);
                    $final_amount = $final_amount - $gst_amount;
                }
                if ($tourData['tour_commission']) {
                    $admin_commission = (($final_amount * $tourData['tour_commission']) / 100);
                    $final_amount = ($final_amount - $admin_commission);
                }
                TourOrder::where('id', $id)->update(['part_payment' => 'full', 'admin_commission' => DB::raw('admin_commission + ' . $admin_commission), 'gst_amount' => DB::raw('gst_amount + ' . $gst_amount), 'final_amount' => DB::raw('final_amount + ' . $final_amount), 'amount' => DB::raw('amount + ' . $tourOrder['amount'])]);
                if ($getadditional->page_name == 'tour_order_wallet') {
                    User::where('id', $getadditional->customer_id)->update(['wallet_balance' => DB::raw('wallet_balance - ' . ($getadditional->total_amount - $getadditional->online_pay))]);
                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'add_funds_to_wallet';
                    $wallet_transaction->transaction_type = 'add_fund';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] + $getadditional->online_pay);
                    $wallet_transaction->credit = $getadditional->online_pay;
                    $wallet_transaction->save();

                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $getadditional->customer_id;
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'Tour remaining pay';
                    $wallet_transaction->transaction_type = 'tour_order_remaining_pay';
                    $wallet_transaction->balance = ($userInfo['wallet_balance'] - $getadditional->total_amount);
                    $wallet_transaction->debit = $getadditional->total_amount;
                    $wallet_transaction->save();
                    return redirect()->route('tour.view-details', [$id]);
                }
            }
            return redirect()->route('tour.view-details', [$id]);
        } else {
            return redirect()->route('tour.view-details', [$id]);
        }
    }

    public function service_order_book_report($type)
    {
        return view('web-views.admin-service-book.order-place', compact('type'));
    }

    public function TempleDarshanBookingPay(Request $request)
    {

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'lead_id' => 'required',
            ]);

            $tourData = \App\Models\TempleDarshanLead::where('id',  $request->lead_id)->with(['Temple', 'userData'])->first();
            $peopleJson = $tourData['people_info'] ?? '';
            $peopleInfo = json_decode($peopleJson, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($peopleInfo) || $tourData['people_qty'] <= 0) {
                return redirect()->route('vip-darshan-booking-pay', ["slug" => $tourData['Temple']['slug'], "lead" => base64_encode($tourData['id'])]);
            }

            $gst_amount = 0;
            $admin_commission = 0;
            $final_amount = ($tourData['price'] ?? 0);
            $darshantax = \App\Models\ServiceTax::find(1);
            $vip_admin_commission = (optional(optional($tourData->Temple)->Trust)->vip_darshan_commission) ?? 0;
            if ($darshantax['vip_darshan_tax']) {
                $gst_amount = (($final_amount * ($darshantax['vip_darshan_tax'] ?? 0)) / 100);
                $final_amount = $final_amount - $gst_amount;
            }
            if (($vip_admin_commission ?? 0)) {
                $admin_commission = (($final_amount * $vip_admin_commission) / 100);
                $final_amount = ($final_amount - $admin_commission);
            }
            $darshan_booking = new \App\Models\DarshanOrder();
            $darshan_booking->user_id = $tourData['user_id'];
            $darshan_booking->temple_id = $tourData['temple_id'];
            $darshan_booking->package_id = $tourData['package_id'];
            $darshan_booking->title = $tourData['title'] ?? '';
            $darshan_booking->package_name = $tourData['package_name'] ?? '';
            $darshan_booking->date = $tourData['date'];
            $darshan_booking->time = $tourData['time'];
            $darshan_booking->price = $tourData['price'] ?? 0;
            $darshan_booking->people_qty = $tourData['people_qty'] ?? 0;
            $darshan_booking->admin_commission = $admin_commission ?? 0;
            $darshan_booking->gst_amount = $gst_amount ?? 0;
            $darshan_booking->final_amount = $final_amount ?? 0;
            $darshan_booking->status = 0;
            $darshan_booking->save();
            for ($iq = 0; $iq < ($tourData['people_qty'] ?? 0); $iq++) {
                $darshan_memberbook = new \App\Models\DarshanOrderMembers();
                $darshan_memberbook->darshan_id = $darshan_booking->id;
                $darshan_memberbook->name = $peopleInfo[$iq]['fullName'] ?? '';
                $darshan_memberbook->phone = $peopleInfo[$iq]['phone'] ?? '';
                $darshan_memberbook->aadhar = $peopleInfo[$iq]['aadhar'] ?? '';
                $darshan_memberbook->aadhar_verify_status = $peopleInfo[$iq]['verify'] ?? '';
                $darshan_memberbook->save();
            }
            /////////////////////////////////////////// WALLET AND ONLINE /////////////////////////////////////////////////
            $user = User::where('id', $tourData['user_id'])->first();

            if ($request->wallet_type == 1) {
                if ($user['wallet_balance'] >= ($tourData['price'] ?? 0)) {
                    User::where('id', $user['id'])->update(['wallet_balance' => DB::raw('wallet_balance - ' . ($tourData['price'] ?? 0))]);
                    \App\Models\TempleDarshanLead::where('id', $request->lead_id)->update(['status' => 1]);

                    \App\Models\DarshanOrder::where('id', $darshan_booking->id)->update(['payment_method' => 'wallet', 'transaction_id' => 'wallet', 'status' => 1]);

                    $wallet_transaction = new WalletTransaction();
                    $wallet_transaction->user_id = $user['id'];
                    $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                    $wallet_transaction->reference = 'vip Darshan order';
                    $wallet_transaction->transaction_type = 'vip_darshan_order';
                    $wallet_transaction->balance = User::where('id', $user['id'])->first()['wallet_balance'];
                    $wallet_transaction->debit = ($tourData['price'] ?? 0);
                    $wallet_transaction->save();

                    $tourOrder = \App\Models\DarshanOrder::where('id', $darshan_booking->id)->with(['Temple'])->first();
                    $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                    $message_data['temple_name'] = ($tourOrder['Temple']['name'] ?? '');
                    $message_data['title_name'] = ($tourOrder['title'] ?? '');
                    $message_data['service_name'] = ($tourOrder['package_name'] ?? '');
                    $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['date'] ?? ''));
                    $message_data['time'] = ($tourOrder['time'] ?? '');
                    $message_data['final_amount'] = webCurrencyConverter(amount: (float)($tourOrder['price'] ?? 0));
                    $message_data['customer_id'] = $user['id'];
                    if (($tourOrder['Temple']['thumbnail'] ?? '')) {
                        $message_data['type'] = 'text-with-media';
                        $message_data['attachment'] = getValidImage(path: 'storage/app/public/temple/thumbnail/' . ($tourOrder['Temple']['thumbnail'] ?? ''), type: 'backend-logo');
                    }
                    Helpers::whatsappMessage('vipdarshan', 'Vip Darshan booking Confirmed', $message_data);
                    DonateTrust::where('id', $tourOrder['Temple']['trust_id'] ?? '')->update([
                        'trust_total_amount' => DB::raw('trust_total_amount + ' . $tourOrder['final_amount']),
                        'admin_commission' => DB::raw('admin_commission + ' . $tourOrder['admin_commission']),
                        'gst_total_amount' => DB::raw('gst_total_amount + ' . $tourOrder['gst_amount'])
                    ]);
                    DB::commit();
                    return redirect()->route('vip-darshan-booking-success', [$tourOrder['Temple']['slug']]);
                } else {
                    // dd('sadasfadf');
                    // wallet dedication
                    $wallet_amount = ($user['wallet_balance']);
                    $total_amount = ($tourData['price'] ?? 0);
                    $onlinepay = (($tourData['price'] ?? 0) - $user['wallet_balance']);
                    $data = [
                        'additional_data' => [
                            'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                            'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                            'payment_mode' => 'web',

                            'leads_id' => $request->lead_id,
                            'package_id' => $tourData['package_id'],
                            'customer_id' => $tourData['user_id'],
                            "order_id" => $darshan_booking->id,
                            "temple_id" => $tourData['temple_id'],
                            "amount" => ($tourData['price'] ?? 0),
                            "user_name" => ($tourData['userData']['f_name'] ?? '') . " " . ($tourData['userData']['l_name'] ?? ''),
                            "user_email" => $tourData['userData']['email'],
                            "user_phone" => $tourData['userData']['phone'],

                            'total_amount' => $total_amount,
                            'wallet_amount' => $wallet_amount,
                            "online_pay" => $onlinepay,
                            'page_name' => 'vip_darshan_order',
                            'success_url' => route('vip-darshan-booking-success', [$tourData['Temple']['slug']]),
                        ],
                        'user_id' => $user['id'],
                        'payment_method' => 'razor_pay',
                        'payment_platform' => 'web',
                        'payment_amount' => $onlinepay,
                        'attribute' => "vip_darshan_order",
                        'external_redirect_link' => route('all-pay-wallet-payment-success-2', [$darshan_booking->id, 'lead' => $request->lead_id]),
                    ];
                    $url_open = $this->Wallet_amount_add($data);
                    DB::commit();
                    return redirect($url_open);
                }
                // dd($request['payment_amount']);
            } else {
                $additional_data = [
                    'business_name' => BusinessSetting::where(['type' => 'company_name'])->first()->value,
                    'business_logo' => asset('storage/app/public/company') . '/' . Helpers::get_business_settings('company_web_logo'),
                    'payment_mode' => 'web',
                    'leads_id' => $request->lead_id,
                    'package_id' => $tourData['package_id'],
                    'customer_id' => $tourData['user_id'],
                    "order_id" => $darshan_booking->id,
                    "temple_id" => $tourData['temple_id'],
                    "amount" => ($tourData['price'] ?? 0),
                    "user_name" => ($tourData['userData']['f_name'] ?? '') . " " . ($tourData['userData']['l_name'] ?? ''),
                    "user_email" => $tourData['userData']['email'],
                    "user_phone" => $tourData['userData']['phone'],
                ];
                $currency_model = Helpers::get_business_settings('currency_model');
                if ($currency_model == 'multi_currency') {
                    $currency_code = 'USD';
                } else {
                    $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
                    $currency_code = Currency::find($default)->code;
                }
                $customer = User::where('id', $tourData['user_id'])->first();
                $payer = new Payer(
                    $customer['f_name'] . ' ' . $customer['l_name'],
                    $customer['email'],
                    $customer['phone'],
                    ''
                );
                if (empty($customer['phone'])) {
                    DB::rollBack();
                    Toastr::error(translate('please_update_your_phone_number'));
                    return redirect()->route('vip-darshan-booking', ["slug" => $tourData['Temple']['slug'], "lead" => base64_encode($tourData['id'])]);
                }

                $payment_info = new PaymentInfo(
                    success_hook: 'digital_payment_success_custom',
                    failure_hook: 'digital_payment_fail',
                    currency_code: $currency_code,
                    payment_method: 'razor_pay',
                    payment_platform: 'web',
                    payer_id: $customer['id'],
                    receiver_id: '100',
                    additional_data: $additional_data,
                    payment_amount: ($tourData['price'] ?? 0),
                    external_redirect_link: route('vip-darshan-booking-pay-received'),
                    attribute: 'vip_darshan_order',
                    attribute_id: idate("U")
                );

                DB::commit();
                $receiver_info = new Receiver('receiver_name', 'example.png');
                $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
                $parsed_url = parse_url($redirect_link);
                $query_string = $parsed_url['query'];
                parse_str($query_string, $query_params);
                return redirect($redirect_link);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('An error occurred: ' . $e->getMessage());
            return redirect()->route('vip-darshan-booking-pay', ["slug" => $tourData['Temple']['slug'], "lead" => base64_encode($tourData['id'])]);
        }
    }

    public function TempleDarshanBookingReceived(Request $request)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data, true);
            $leads = \App\Models\TempleDarshanLead::where('id',  $getadditional['leads_id'])->with(['Temple'])->first();
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $tourOrder = \App\Models\DarshanOrder::where('id', ($getadditional['order_id'] ?? ''))->with(['Temple'])->first();
                $leads->status = 1;
                $leads->save();
                $userInfo = \App\Models\User::where('id', ($tourOrder['user_id'] ?? ""))->first();

                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $getadditional['customer_id'];
                $wallet_transaction->transaction_id = \Illuminate\Support\Str::uuid();
                $wallet_transaction->reference = 'vip Darshan order';
                $wallet_transaction->transaction_type = 'vip_darshan_order';
                $wallet_transaction->balance = ($userInfo['wallet_balance']);
                $wallet_transaction->debit = $getadditional['amount'];
                $wallet_transaction->save();

                $message_data['orderId'] = ($tourOrder['order_id'] ?? '');
                $message_data['temple_name'] = ($tourOrder['Temple']['name'] ?? '');
                $message_data['title_name'] = ($tourOrder['title'] ?? '');
                $message_data['service_name'] = ($tourOrder['package_name'] ?? '');
                $message_data['booking_date'] = date("d M,Y", strtotime($tourOrder['date'] ?? ''));
                $message_data['time'] = ($tourOrder['time'] ?? '');
                $message_data['final_amount'] = webCurrencyConverter(amount: (float)($tourOrder['price'] ?? 0));
                $message_data['customer_id'] = $userInfo['id'];
                if (($tourOrder['Temple']['thumbnail'] ?? '')) {
                    $message_data['type'] = 'text-with-media';
                    $message_data['attachment'] = getValidImage(path: 'storage/app/public/temple/thumbnail/' . ($tourOrder['Temple']['thumbnail'] ?? ''), type: 'backend-logo');
                }
                Helpers::whatsappMessage('vipdarshan', 'Vip Darshan booking Confirmed', $message_data);
                DonateTrust::where('id', $tourOrder['Temple']['trust_id'] ?? '')->update([
                    'trust_total_amount' => DB::raw('trust_total_amount + ' . $tourOrder['final_amount']),
                    'admin_commission' => DB::raw('admin_commission + ' . $tourOrder['admin_commission']),
                    'gst_total_amount' => DB::raw('gst_total_amount + ' . $tourOrder['gst_amount'])
                ]);
                $tourOrder->status = 1;
                $tourOrder->payment_method = $getlist->payment_method;
                $tourOrder->transaction_id = $transactionReference;
                $tourOrder->save();
                return redirect()->route('vip-darshan-booking-success', [$tourOrder['Temple']['slug']]);
            }
            return redirect()->route('vip-darshan-booking-pay', ["slug" => $leads['Temple']['slug'], "lead" => base64_encode($leads['id'])]);
        } else {
            return redirect()->route('darshan');
        }
    }

    public static function TrustPujaBooking($all_info)
    {
        $additional_data = $all_info['additional_data'];
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $payer = new Payer(
            $additional_data['user_name'],
            $additional_data['user_email'],
            $additional_data['user_phone'],
            ''
        );

        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: 'razor_pay',
            payment_platform: 'web',
            payer_id: 0,
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $all_info['payment_amount'],
            external_redirect_link: route('trust-puja-orders', [$additional_data['order_id']['person_phone']]),
            attribute: 'vip_darshan_order',
            attribute_id: idate("U")
        );
        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        $parsed_url = parse_url($redirect_link);
        $query_string = $parsed_url['query'];
        parse_str($query_string, $query_params);
        return ($redirect_link);
    }

    public function TrustPujaOrder(Request $request)
    {
        if ($request->flag == 'success') {
            $token = $request->get('token');
            $decodedToken = base64_decode($token);
            parse_str($decodedToken, $transactionDetails);
            $paymentMethod = $transactionDetails['payment_method'] ?? null;
            $transactionReference = ($transactionDetails['transaction_reference'] ?? '');
            $api = new Api(config('razor_config.api_key'), config('razor_config.api_secret'));
            $payment = $api->payment->fetch($transactionReference);
            $getlist =  \App\Models\PaymentRequest::where(['transaction_id' => $transactionReference])->first();
            $getadditional = json_decode($getlist->additional_data, true);
            if (($payment->status === 'captured') && !empty($transactionReference)) {
                $trustPuja = new \App\Models\TrustPujaOrder();
                $trustPuja->puja_name = $getadditional['order_id']['puja_name'];
                $trustPuja->trust_id = $getadditional['order_id']['trust_id'];
                $trustPuja->user_name = $getadditional['order_id']['user_name'];
                $trustPuja->user_phone = $getadditional['order_id']['user_phone'];
                $trustPuja->rprice = $getadditional['order_id']['rprice'];
                $trustPuja->pprice = $getadditional['order_id']['pprice'];
                $trustPuja->discount = $getadditional['order_id']['discount'];
                $trustPuja->tax = $getadditional['order_id']['tax'];
                $trustPuja->tax_amount = $getadditional['order_id']['tax_amount'];
                $trustPuja->admin_commission = $getadditional['order_id']['admin_commission'];
                $trustPuja->final_amount = $getadditional['order_id']['final_amount'];
                $trustPuja->transaction_id = $transactionReference;
                $trustPuja->paymant_method = $getlist->payment_method;
                $trustPuja->payment_status = 1;
                $trustPuja->save();


                $dataemail['orderId'] = $trustPuja->order_id;
                $dataemail['admin_name'] = $trustPuja['user_name'];
                $dataemail['admin_phone'] = $getadditional['order_id']['user_phone'];
                $dataemail['rprice'] =  $trustPuja->rprice;
                $dataemail['discount'] = $trustPuja->discount;
                $dataemail['tax_amount'] = $trustPuja->tax_amount;
                $dataemail['paymant_model'] = 'Online';
                $dataemail['final_amount'] = ($getlist->payment_amount);
                Helpers::whatsappMessage('donate', 'trust_puja_order_message', $dataemail);
                DonateTrust::where('id', $trustPuja->trust_id ?? '')->update([
                    'trust_total_amount' => DB::raw('trust_total_amount + ' . $trustPuja->final_amount),
                    'admin_commission' => DB::raw('admin_commission + ' . $trustPuja->admin_commission),
                    'gst_total_amount' => DB::raw('gst_total_amount + ' . $trustPuja->tax_amount)
                ]);

                return redirect()->route('trust-puja-booking-success', ['id' => $trustPuja->id]);
            }
            return redirect()->route('trust-puja-booking-success', ['id' => 0]);
        } else {
            return redirect()->route('darshan');
        }
    }
    // puja/anushthan/chadhava/vip pending order payment
    public function admin_pooja_pending_payment_request(Request $request)
    {
        $orderType = Service_order::where('order_id', $request->order_id)->value('type');
        $order = Service_order::with($orderType === 'pooja' ? 'services' : 'vippoojas')
            ->where('order_id', $request->order_id)
            ->first();

        $redirect_link = $this->admin_pooja_pending_customer_payment_request($request, $order);
        $linkid = explode('=', $redirect_link)['1'];
        PaymentRequest::where('id', $linkid)->update(['previous_url' => url('pooja/order/fail')]);
       
        $message_data = [
            'service_name' => $orderType === 'pooja' ? $order->services->name : $order->vippoojas->name,
            // 'type' => 'text-with-media',
            // 'attachment' =>  asset('/storage/app/public/services/thumbnail/' . $order->services->thumbnail),
            'final_amount' => webCurrencyConverter((float)($order->pay_amount)),
            'customer_id' => $order->customer_id,
            'payment_link' => $redirect_link
        ];

        Helpers::whatsappMessage('vipanushthan', 'Pending Order Request', $message_data);
        Toastr::success(translate('message_sent_successfully'));
        return back();
    }

    public function puja_pending_payment_request(Request $request)
    {
       
        $orderType = Service_order::where('order_id', $request->order_id)->value('type');
        $order = Service_order::with($orderType === 'pooja' ? 'services' : 'vippoojas')
            ->where('order_id', $request->order_id)
            ->first();

        $redirect_link = $this->admin_pooja_pending_customer_payment_request($request, $order);
        $linkid = explode('=', $redirect_link)['1'];
        PaymentRequest::where('id', $linkid)->update(['previous_url' => url('pooja/order/fail')]);
        return redirect($redirect_link);
    }

    public function admin_pooja_pending_customer_payment_request(Request $request, $order)
    {
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));

        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $order->leads_id,
            'order_id'        => $order->order_id,
            'package_id'      => $order->package_id,
            'service_id'      => $order->service_id,
            'customer_id'     => $order->customer_id,
            'package_price'   => $order->package_price,
            'final_amount'    => $order->pay_amount,
        ];
        

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $order->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $order->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }
        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $order->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $order->pay_amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'puja',
            attribute_id: idate("U")
        );
        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        return $redirect_link;
    }

    public function admin_pooja_pending_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            // Wallet Maintan

            $leadsData = Leads::where('id', $serviceData->leads_id)->first();
            //serviceordder table main records updates
            $serviceOrderAdd = Service_order::where('order_id', $leadsData->order_id)->first();
            if ($serviceOrderAdd) {
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->transection_amount = $serviceData->final_amount;
                $serviceOrderAdd->wallet_translation_id = null;
                $serviceOrderAdd->wallet_amount = 0;
                $serviceOrderAdd->coupon_code = null;
                $serviceOrderAdd->coupon_amount = null;
                $serviceOrderAdd->save();
            }
            $productlist = ProductLeads::where('leads_id', $serviceData->leads_id)->get();
            $add_product_array = [];
            foreach ($productlist as $product) {
                $add_product_array[] = [
                    'product_id' => $product->product_id,
                    'price' => $product->product_price,
                    'qty' => $product->qty,
                ];
            }

            PoojaRecords::create([
                'customer_id'     => $serviceOrderAdd->customer_id,
                'service_id'      => $serviceOrderAdd->service_id,
                'product_id' => json_encode($add_product_array),
                'service_order_id' => $serviceOrderAdd->order_id,
                'package_id'      => $serviceOrderAdd->package_id,
                'package_price'   => $serviceOrderAdd->package_price ?? 0.00,
                'amount'          => $serviceOrderAdd->pay_amount ?? 0.00,
                'coupon'          => $serviceOrderAdd->coupon_amount ?? 0.00,
                'via_wallet'      => $serviceOrderAdd->wallet_amount ?? 0.00,
                'via_online'      => $serviceOrderAdd->transection_amount ?? 0.00,
                'booking_date'    => $serviceOrderAdd->booking_date,
            ]);
            $orderId = $serviceOrderAdd->order_id;
            $order = Service_order::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                $url = $_SERVER['HTTP_HOST'];

                // whatsapp
                $userInfo = User::where('id', ($serviceData->customer_id ?? ""))->first();

                if ($serviceOrderAdd->type == 'pooja') {
                    $service_name = Service::where('id', ($serviceData->service_id ?? ""))->where('product_type', 'pooja')->first();
                    $bookingDetails = $serviceOrderAdd;

                    $message_data = [
                        'service_name' => $service_name['name'],
                        'attachment' => asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                        'booking_date' => date('d-m-Y', strtotime($serviceOrderAdd->booking_date)),
                        'puja_venue' => $service_name['pooja_venue'],
                        'orderId' => $orderId,
                        'final_amount' => webCurrencyConverter((float)$additionalData['final_amount'] - ($bookingDetails->coupon_amount ?? 0)),
                        'type' => 'text-with-media',
                        'customer_id' => ($serviceData->customer_id ?? ""),
                    ];
                    $messages =  Helpers::whatsappMessage('whatsapp', 'Pooja Confirmed', $message_data);
                } else {
                    $service_name = \App\Models\Vippooja::where('id', ($serviceData->service_id ?? ""))->first();
                    $bookingDetails = $serviceOrderAdd;

                    if ($serviceOrderAdd->type == 'vip') {
                        $vipAnushthan = 'VIP Puja';
                    } else {
                        $vipAnushthan = 'Anushthan';
                    }

                    $message_data = [
                        'service_name' => $service_name['name'],
                        'type' => 'text-with-media',
                        'attachment' =>  asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
                        'booking_date' => date('d-m-Y', strtotime($serviceOrderAdd->booking_date)),
                        'puja' => $vipAnushthan,
                        'orderId' => $orderId,
                        'final_amount' => webCurrencyConverter((float)($additionalData['final_amount'] ?? 0) - (float)($bookingDetails->coupon_amount ?? 0)),
                        'customer_id' => ($serviceData->customer_id ?? ""),
                    ];

                    $messages =  Helpers::whatsappMessage('vipanushthan', 'Pooja Confirmed', $message_data);
                }

                // Mail Setup for Pooja Management Send to  User Email Id
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'pooja';
                    $data['email'] = $userInfo['email'];
                    if ($serviceOrderAdd->type == 'pooja') {
                        $data['subject'] = 'Confirmation of Your Service Purchase';
                    } elseif ($serviceOrderAdd->type == 'vip') {
                        $data['subject'] = 'Confirmation of Your VIP Service Purchase';
                    } else {
                        $data['subject'] = 'Confirmation of Your Anushthan Service Purchase';
                    }
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();

                    Helpers::emailSendMessage($data);
                }
                if (auth()->guard('customer')->check()) {
                    if ($serviceOrderAdd->type == 'pooja') {
                        return redirect()->route('sankalp', [$orderId]);
                    } elseif ($serviceOrderAdd->type == 'vip') {
                        return redirect()->route('vip.user.detail', $orderId);
                    } else {
                        return redirect()->route('anushthan.user.detail', $orderId);
                    }
                } else {
                    return redirect()->route('home');
                }
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    public function admin_pooja_order_fail()
    {
        return view('web-views.epooja.order-fail');
    }

    // chadhava pending order payment
    public function admin_chadhava_pending_payment_request(Request $request)
    {
        $order = Chadhava_orders::with('chadhava')->where('order_id', $request->order_id)->first();

        $redirect_link = $this->admin_chadhava_pending_customer_payment_request($request, $order);
        $linkid = explode('=', $redirect_link)['1'];
        PaymentRequest::where('id', $linkid)->update(['previous_url' => url('chadhava/order/fail')]);
        // dd($order,$redirect_link);

        // whatsapp message
        $message_data = [
            'service_name' => $order->chadhava->name,
            // 'type' => 'text-with-media',
            // 'attachment' =>  asset('/storage/app/public/services/thumbnail/' . $order->services->thumbnail),
            'final_amount' => webCurrencyConverter((float)($order->pay_amount)),
            'customer_id' => $order->customer_id,
            'payment_link' => $redirect_link
        ];

        Helpers::whatsappMessage('chadhava', 'Pending Order Request', $message_data);
        Toastr::success(translate('message_sent_successfully'));
        return back();
    }
    // Chadhava Single PAge
    
    public function chadhava_pending_payment_request(Request $request)
    {
       
        $order = Chadhava_orders::with('chadhava')->where('order_id', $request->order_id)->first();
        $redirect_link = $this->admin_chadhava_pending_customer_payment_request($request, $order);
        $linkid = explode('=', $redirect_link)['1'];
        PaymentRequest::where('id', $linkid)->update(['previous_url' => url('pooja/order/fail')]);
        $message_data = [
            'service_name' => $order->chadhava->name,
            // 'type' => 'text-with-media',
            // 'attachment' =>  asset('/storage/app/public/services/thumbnail/' . $order->services->thumbnail),
            'final_amount' => webCurrencyConverter((float)($order->pay_amount)),
            'customer_id' => $order->customer_id,
            'payment_link' => $redirect_link
        ];
        Helpers::whatsappMessage('chadhava', 'Pending Order Request', $message_data);
        return redirect($redirect_link);
    }

    public function admin_chadhava_pending_customer_payment_request(Request $request, $order)
    {
        $companyName = BusinessSetting::where('type', 'company_name')->value('value') ?? 'Company Name';
        $companyLogo = asset('storage/app/public/company/' . Helpers::get_business_settings('company_web_logo'));

        $additional_data = [
            'business_name'   => $companyName,
            'business_logo'   => $companyLogo,
            'payment_mode'    => $request->payment_platform ?? 'web',
            'leads_id'        => $order->leads_id,
            'order_id'        => $order->order_id,
            'service_id'      => $order->service_id,
            'customer_id'     => $order->customer_id,
            'final_amount'    => $order->pay_amount,
        ];

        if (in_array($request->payment_request_from, ['app', 'react'])) {
            $additional_data['customer_id'] = $order->customer_id;
            $additional_data['is_guest'] = $request->is_guest;
            $additional_data['order_note'] = $request['order_note'];
            $additional_data['payment_request_from'] = $request->payment_request_from;
        }
        $currency_model = Helpers::get_business_settings('currency_model');
        if ($currency_model == 'multi_currency') {
            $currency_code = 'USD';
        } else {
            $default = BusinessSetting::where(['type' => 'system_default_currency'])->first()->value;
            $currency_code = Currency::find($default)->code;
        }
        $customer = Helpers::get_customer($request);

        if ($customer == 'offline') {
            $address = ShippingAddress::where(['customer_id' => $order->customer_id, 'is_guest' => 1])->latest()->first();
            if ($address) {
                $payer = new Payer(
                    $address->contact_person_name,
                    $address->email,
                    $address->phone,
                    ''
                );
            } else {
                $payer = new Payer(
                    'Contact person name',
                    '',
                    '',
                    ''
                );
            }
        } else {
            $payer = new Payer(
                $customer['f_name'] . ' ' . $customer['l_name'],
                $customer['email'],
                $customer['phone'],
                ''
            );
            if (empty($customer['phone'])) {
                Toastr::error(translate('please_update_your_phone_number'));
                return route('checkout-payment');
            }
        }
        $payment_info = new PaymentInfo(
            success_hook: 'digital_payment_success_custom',
            failure_hook: 'digital_payment_fail',
            currency_code: $currency_code,
            payment_method: $request->payment_method,
            payment_platform: $request->payment_platform,
            payer_id: $customer == 'offline' ? $order->customer_id : $customer['id'],
            receiver_id: '100',
            additional_data: $additional_data,
            payment_amount: $order->pay_amount,
            external_redirect_link: $request->payment_platform == 'web' ? $request->external_redirect_link : null,
            attribute: 'chadhava',
            attribute_id: idate("U")
        );
        $receiver_info = new Receiver('receiver_name', 'example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);
        return $redirect_link;
    }

    public function admin_chadhava_pending_web_payment_success(Request $request)
    {
        if ($request->flag == 'success') {
            $servicePaymentData = explode('transaction_reference=', base64_decode($request->token));
            $serviceOrder = PaymentRequest::where('transaction_id', $servicePaymentData['1'])->first();
            $additionalData = json_decode($serviceOrder['additional_data'], true);
            $serviceData = json_decode($serviceOrder->additional_data);

            $leadsData = Leads::where('id', $serviceData->leads_id)->first();
            //serviceordder table main records updates
            $serviceOrderAdd = Chadhava_orders::where('order_id', $leadsData->order_id)->first();
            if ($serviceOrderAdd) {
                $serviceOrderAdd->payment_id = $serviceOrder['transaction_id'];
                $serviceOrderAdd->payment_status = '1';
                $serviceOrderAdd->transection_amount = $serviceData->final_amount;
                $serviceOrderAdd->wallet_translation_id = null;
                $serviceOrderAdd->wallet_amount = 0;
                $serviceOrderAdd->save();
            }

            $orderId = $serviceOrderAdd->order_id;
            $order = Chadhava_orders::where('order_id', $orderId)->where('status', '0')->with(['customer'])->first();
            event(new OrderStatusEvent(key: '0', type: 'puja', order: $order));

            Leads::where('id', $serviceData->leads_id)->update([
                'status' => 0,
                'payment_status' => 'Complete',
            ]);
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment succeeded'], 200);
            } else {
                Toastr::success(translate('Payment_success'));
                // whatsapp
                $userInfo = \App\Models\User::where('id', ($serviceData->customer_id ?? ""))->first();
                $service_name = \App\Models\Chadhava::where('id', ($serviceData->service_id ?? ""))->first();
                $bookingDetails = $serviceOrderAdd;

                $message_data = [
                    'service_name' => $service_name['name'],
                    'type' => 'text-with-media',
                    'attachment' =>  asset('/storage/app/public/chadhava/thumbnail/' . $service_name->thumbnail),
                    'booking_date' => date('d-m-Y', strtotime($serviceOrderAdd->booking_date)),
                    'chadhava_venue' => $service_name['chadhava_venue'],
                    'orderId' => $orderId,
                    'final_amount' => webCurrencyConverter(amount: (float)$additionalData['final_amount'] ?? 0),
                    'customer_id' => ($serviceData->customer_id ?? ""),
                ];

                $messages =  Helpers::whatsappMessage('chadhava', 'Chadhava Confirmed', $message_data);

                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data['type'] = 'chadhava';
                    $data['email'] = $userInfo['email'];
                    $data['subject'] = 'Your Online Chadhava Booking Confirmation';
                    $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-template', compact('userInfo', 'service_name', 'bookingDetails'))->render();
                    Helpers::emailSendMessage($data);
                }


                if (auth()->guard('customer')->check()) {
                    return redirect()->route('chadhava.user.detail', [$orderId]);
                } else {
                    return redirect()->route('home');
                }
            }
        } else {
            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                return response()->json(['message' => 'Payment failed'], 403);
            } else {
                Toastr::error(translate('Payment_failed') . '!');
                return redirect(url('/'));
            }
        }
    }

    public function admin_chadhava_order_fail()
    {
        return view('web-views.chadhava.order-fail');
    }
}
