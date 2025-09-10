<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Repositories\OrderDetailRepositoryInterface;
use App\Contracts\Repositories\ProductCompareRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ProductTagRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Contracts\Repositories\SellerRepositoryInterface;
use App\Contracts\Repositories\TagRepositoryInterface;
use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Chadhava;
use App\Models\Chadhava_orders;
use App\Models\ProductTag;
use App\Models\Leads;
use App\Models\CounsellingUser;
use App\Models\ProductLeads;
use App\Models\Service;
use App\Models\ServiceTag;
use App\Models\FAQ;
use App\Models\Package;
use App\Models\Review;
use App\Utils\CartManager;
// use App\Models\CartManager;
use App\Models\Seller;
use App\User;
use App\Models\ProductCompare;
use App\Models\Category;
use App\Models\Cities;
use App\Models\States;
use App\Models\Country;
use App\Models\ServiceReview;
use App\Models\Service_order;
use App\Models\PaymentRequest;
use App\Models\Tag;
use App\Models\Devotee;
use App\Models\Vippooja;
use App\Models\Wishlist;
use App\Repositories\DealOfTheDayRepository;
use App\Repositories\WishlistRepository;
use App\Traits\ProductTrait;
use App\Utils\Helpers;
use App\Utils\ProductManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use function App\Utils\payment_gateways;
use Razorpay\Api\Api;
use App\Traits\Whatsapp;
use App\Models\Admin;
use App\Models\OfflineLead;
use App\Models\OfflinepoojaCategory;
use App\Models\OfflinePoojaOrder;
use App\Models\OfflinepoojaRefundPolicy;
use App\Models\OfflinepoojaReview;
use App\Models\OfflinepoojaSchedule;
use App\Models\PoojaForecast;
use App\Models\PoojaOffline;
use App\Models\Prashad_deliverys;
use App\Models\UserFeedback;
use App\Models\WhatsappTemplate;
use App\Models\WConsultancyTemplate;
use App\Models\WChadhavaTemplate;
use App\Models\WEventTemplate;
use App\Models\WDonationTemplate;
use App\Models\WEcomTemplate;
use App\Models\WToursTemplate;
use Illuminate\Support\Carbon;
use DateTime;
use DateTimeZone;

class ProductDetailsController extends Controller
{
    use Whatsapp;
    use ProductTrait;
    public function __construct(
        private Service      $services,
        private Product      $product,
        private Leads         $leads,
        private ProductLeads  $productleads,
        private Package      $package,
        private readonly ProductRepositoryInterface        $productRepo,
        private readonly WishlistRepository                $wishlistRepo,
        private readonly ReviewRepositoryInterface         $reviewRepo,
        private readonly OrderDetailRepositoryInterface    $orderDetailRepo,
        private readonly DealOfTheDayRepository            $dealOfTheDayRepo,
        private readonly ProductCompareRepositoryInterface $compareRepo,
        private readonly ProductTagRepositoryInterface     $productTagRepo,
        private readonly TagRepositoryInterface            $tagRepo,
        private readonly SellerRepositoryInterface         $sellerRepo,
    ) {}

    /**
     * @param string $slug
     * @return View
     */
    public function onlinePuja()
    {
        $category = Category::where('name', 'pooja')->first();
        $subcategory = collect();
        if ($category) {
            $subcategory = Category::where('parent_id', $category->id)
                ->where('id', '!=', 52)
                ->get();
        }
        $PoojaShow = PoojaForecast::with([
        'service' => function ($query) {
                    $query->where('status', 1)
                        ->where('product_type', 'pooja');
                }
            ])
            ->whereIn('type', ['weekly', 'special'])
            ->orderBy('booking_date', 'asc')
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->get()
            ->filter(function ($item) {
                return $item->service; // skip if service is null
            })
            ->unique('service_id') // 1 pooja per service
            ->values();
            $vippooja = Vippooja::where('is_anushthan', 0)->where('status', 1)->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')->get();
            $anushthan = Vippooja::where('is_anushthan', 1)->where('status', 1)->withCount('PoojaOrderReview')
                ->withAvg('review', 'rating')->get();

        return view('web-views.epooja.online-puja', compact('subcategory','PoojaShow','vippooja','anushthan'));
    }

    public function all_puja()
    {
        $category = Category::where('name', 'pooja')->first();
        $subcategory = collect();
        if ($category) {
            $subcategory = Category::where('parent_id', $category->id)
                ->where('id', '!=', 52)
                ->get();
        }
        $PoojaShow = PoojaForecast::with([
        'service' => function ($query) {
                    $query->where('status', 1)
                        ->where('product_type', 'pooja');
                }
            ])
            ->whereIn('type', ['weekly', 'special'])
            ->orderBy('booking_date', 'asc')
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->where('is_expired', 0)
            ->get()
            ->filter(function ($item) {
                return $item->service;
            })
            ->unique('service_id') 
            ->values();
            $vippooja = Vippooja::where('is_anushthan', 0)->where('status', 1)->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')->get();
            $anushthan = Vippooja::where('is_anushthan', 1)->where('status', 1)->withCount('PoojaOrderReview')
                ->withAvg('review', 'rating')->get();

        return view("web-views.epooja.all-puja", compact('subcategory', 'PoojaShow', 'vippooja', 'anushthan'));
    }
    public function all_chadhava()
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(7);

        $chadhavaData = Chadhava::where('status', 1)
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->where('chadhava_type', 1)
                    ->where('end_date', '>=', $today->toDateString());
                })->orWhere('chadhava_type', 0);
            })
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->get();

        $filtered = $chadhavaData->filter(function ($item) use ($cutoff) {
            return $item->getNextAvailableDate($cutoff) !== null;
        });

        $sorted = $filtered->sortBy(function ($item) use ($cutoff) {
            return $item->getNextAvailableDate($cutoff)->timestamp;
        })->values();

        return view('web-views.chadhava.all-chadhava', [
            'chadhavaData' => $sorted,
        ]);
    }


    
    public function pooja(string $slug)
    {
        // Fetch Pooja by Slug with related package, review stats
        $epooja = Service::withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->with('package')
            ->where('slug', $slug)
            ->first();
    
        if (!$epooja) {
            return redirect('/');
        }
    
        // Clone service for list compatibility
        $servicesGet = collect([$epooja]);
    
        // Fetch PoojaForecast for this ep ooja only
        $PoojaShow = PoojaForecast::with([
                'service' => function ($query) {
                    $query->where('status', 1)
                        ->where('product_type', 'pooja');
                }
            ])
            ->whereIn('type', ['weekly', 'special'])
            ->orderBy('booking_date', 'asc')
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->where('is_expired', 0)
            ->get()
            ->filter(function ($item) {
                return $item->service;
            })
            ->unique('service_id')
            ->values();
    
        // Get the next booking date of this specific service
        $nextBooking = PoojaForecast::where('service_id', $epooja->id)
        ->where('is_expired', 0)
        ->whereIn('type', ['weekly', 'special'])
        ->whereHas('service', function ($query) {
            $query->where('status', 1)->where('product_type', 'pooja');
        })
        ->orderBy('booking_date', 'asc')
        ->first();
    
        $date = $nextBooking?->booking_date;
        $forecastServiceId = $nextBooking?->service_id;
    
        // FAQs
        $Faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
                $query->where('name', 'Online Puja');
            })
            ->with('Category')
            ->get();
    
        // Reviews
        $allReviews = ServiceReview::with('userData')->get();
    
        $uniqueUserReviews = $allReviews
            ->groupBy('user_id')
            ->map(function ($userReviews) {
                return $userReviews->sortByDesc(function ($review) {
                    if (!empty($review->youtube_link)) return 3;
                    if (!empty($review->comment)) return 2;
                    return 1;
                })->first();
            })
            ->sortByDesc(function ($review) {
                if (!empty($review->youtube_link)) return 3;
                if (!empty($review->comment)) return 2;
                return 1;
            })
            ->values();
    
        $originalReviews = $uniqueUserReviews->where('is_edited', 0);
        $editedReviews = $uniqueUserReviews;
        $serviceReview = $originalReviews->count();
    
        $reviewCounts = [
            'excellent'     => $originalReviews->where('rating', 5)->count(),
            'good'          => $originalReviews->where('rating', 4)->count(),
            'average'       => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor'          => $originalReviews->filter(fn($review) => $review->rating == 1 && !is_null($review->comment))->count(),
            'averageStar'   => round($originalReviews->avg('rating'), 1),
            'list'          => $editedReviews->take(10),
        ];
    
        $totalReviews = $servicesGet->sum('pooja_order_review_count');
    
        return view("web-views.epooja.index", compact(
            'epooja',
            'Faqs',
            'servicesGet',
            'serviceReview',
            'reviewCounts',
            'totalReviews',
            'PoojaShow',
            'date',
            'forecastServiceId'
        ));
    }


    public function poojastore(string $slug, Request $request)
    {
        $servicedata = Service::where('id', $request->service_id)->where('product_type', 'pooja')->first();
        if (!$servicedata) {
            return redirect()->back()->with('error', 'Puja not found.');
        }
        $servicesGet = collect([$servicedata]);
        $PoojaShow = PoojaForecast::with([
                'service' => function ($query) {
                    $query->where('status', 1)
                        ->where('product_type', 'pooja');
                }
            ])
            ->whereIn('type', ['weekly', 'special'])
            ->orderBy('booking_date', 'asc')
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->where('is_expired', 0)
            ->get()
            ->filter(function ($item) {
                return $item->service;
            })
            ->unique('service_id')
            ->values();

        // Get the next booking date of this specific service
        $nextBooking = PoojaForecast::where('service_id', $servicedata->id)
        ->where('is_expired', 0)
        ->whereIn('type', ['weekly', 'special'])
        ->whereHas('service', function ($query) {
            $query->where('status', 1)->where('product_type', 'pooja');
        })
        ->orderBy('booking_date', 'asc')
        ->first();

        $date = $nextBooking?->booking_date;
        $forecastServiceId = $nextBooking?->service_id;

        $packageList = json_decode($servicedata->packages_id, true);
        $selectedPackage = collect($packageList)->firstWhere('package_id', $request->package_id);
        if (!$selectedPackage) {
            return redirect()->back()->with('error', 'Invalid package selection.');
        }

        $package = \App\Models\Package::find($request->package_id);
        if (!$package) {
            return redirect()->back()->with('error', 'Package not found.');
        }

        $bookingDate = $request->input('booking_date');
        $personName = $request->input('person_name');
        $verifyOTP = $request->input('verify_otp');
        $personPhone = $request->input('person_phone');
        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));

        if ($personPhone == '') {
            return redirect()->to('/');
        }

        $customerId = null;

        $userExists = User::where('phone', $request->person_phone)->exists();
        if (!$userExists) {
            $user = User::create([
                'name'     => $personName,
                'f_name'   => $firstName,
                'l_name'   => $lastName,
                'phone'    => $personPhone,
                'email'    => 'user@mahakal.com',
                'password' => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ]);

            $customerId = $user->id;

            $data = ['customer_id' => $customerId];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        } else {
            $user = User::where('phone', $request->person_phone)->first();
            $customerId = $user->id ?? null;
        }
        if (!auth('customer')->check()) {
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user->id);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }


        $cust_details = [
            'service_id'    => $forecastServiceId,
            'type'          => $servicedata->product_type,
            'package_id'    => $selectedPackage['package_id'],
            'product_id'    => $servicedata->product_id,
            'package_price' => $selectedPackage['package_price'],
            'package_name'  => $package->title,
            'noperson'      => $package->person,
            'person_phone'  => $personPhone,
            'person_name'   => $personName,
            'booking_date'  => $date,
            'customer_id'   => $customerId, 
            'platform'      => 'web', 
        ];

        $leads = Leads::create($cust_details);
        $insertedRowId = $leads->id;

        if (!empty($insertedRowId)) {
            $leadno = 'PJ' . (100 + $insertedRowId + 1);
        } else {
            $leadno = 'PJ' . (101);
        }

        Leads::where('id', $insertedRowId)->update(['leadno' => $leadno]);
        //  Get product_ids from lead
        $lead = Leads::find($insertedRowId);
        $productIds = json_decode($lead->product_id, true); // Example: ["550", "661"]

        if (!empty($productIds)) {
            $selectedProduct = null;
            foreach ($productIds as $pid) {
                $product = Product::where('id', $pid)->where('status', 1)->first();
                if ($product) {
                    $selectedProduct = $product;
                    break; // exit loop when found
                }
            }
            if ($selectedProduct) {
                $qty = 1;
                $price = $selectedProduct->unit_price;

                $productData = [
                    [
                        'product_id' => (string) $selectedProduct->id,
                        'price' => (string) $price,
                        'qty' => '1',
                    ],
                ];
                $lead->add_product_id = json_encode($productData);
                $lead->final_amount = $price;
                $lead->save();
                $product_store = [
                    'leads_id' => $lead->id,
                    'product_id' => $selectedProduct->id,
                    'final_price' => $price * $qty,
                    'qty' => $qty,
                    'product_name' => $selectedProduct->name,
                    'product_price' => $price
                ];
                ProductLeads::create($product_store);
            }
        }

        $encodedId = base64_encode($insertedRowId);
        return redirect()->route('poojacart', ['encoded_id' => $encodedId]);

    }

    public function poojaproductstore(Request $request, $encodedId)
    {
        $checkProduct = ProductLeads::where('leads_id', $encodedId)->where('product_id', $request->input('productid')) ->first();
        if ($checkProduct) {
            return response()->json(['status' => false, 'message' => 'Product Already Exists']);
        }
        $productData = Product::where('status', 1)->where('id', $request->input('productid'))->select('name', 'unit_price')->first();
        $productInsert = [
            'leads_id' => $encodedId,
            'product_id' => $request->input('productid'),
            'product_price' =>  $productData['unit_price'],
            'product_name' => $productData['name'],
            'final_price' =>  $productData['unit_price'],
            'qty' => 1,
        ];
        $productLead = ProductLeads::create($productInsert);
        $totalAmount = ProductLeads::where('leads_id', $encodedId)->sum('final_price');
        $productlist = ProductLeads::where('leads_id', $encodedId)->get();
        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        Leads::where('id', $encodedId)->update([
            'status' => 1,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' => json_encode($add_product_array),
            'final_amount' => $totalAmount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'data' => ['total_amount' => $totalAmount]
        ]);
    }

    public function poojacart(Request $request, $encodedId)
    {
        $bookingDate = Session::get('booking_date');
        $id = base64_decode($encodedId);
        if (empty($id)) {
            return redirect()->route('all-puja');
        }
        $leadsDetails = Leads::where('leads.id', $id)->with('service')->with('product')->first();
        $leadsGet = Leads::where('id', $id)->with('productLeads')->with('service')->first();
        $userId = User::where('phone', $leadsGet['person_phone'])->first();
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        $prashadamList = $this->product->where('category_id', 53)->where('user_id', 14)->with(['reviews'])->active()->orderBy('id', 'desc')->take(8)->get();
        return view("web-views.epooja.add-pooja", compact('leadsGet', 'userId', 'leadsDetails', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment', 'bookingDate', 'prashadamList'));
    }


    public function poojaCheckout($order_id, Request $request)
    {
        // Prepare customer details
        $cust_details = [
            'newPhone'       => $request->input('newPhone'),
            'gotra'          => $request->input('gotra'),
            'pincode'        => $request->input('pincode'),
            'city'           => $request->input('city'),
            'state'          => $request->input('state'),
            'house_no'       => $request->input('house_no'),
            'area'           => $request->input('area'),
            'latitude'       => $request->input('latitude'),
            'longitude'      => $request->input('longitude'),
            'landmark'       => $request->input('landmark'),
            'members'        => json_encode($request->input('members')),
            'is_prashad'     => $request->input('is_prashad'),
        ];

        Service_order::where('order_id', $order_id)->update($cust_details);

        $serviceOrder = Service_order::where('order_id', $order_id)->first();
        if (!$serviceOrder) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        // Get Customer Info
        $customer = User::find($serviceOrder->customer_id);
        $full_name = '';
        if ($customer) {
            $full_name = trim($customer->f_name . ' ' . $customer->l_name);
            $full_name = $full_name ?: $customer->name ?: '';
        }
        // dd($cust_details);
        // Create Devotee Record
        Devotee::create([
            'name'             => $full_name,
            'phone'            => $request->input('newPhone') ?: ($customer->phone ?? ''),
            'gotra'            => $request->input('gotra'),
            'service_order_id' => $order_id,
            'members'          => $request->has('members') ? json_encode($request->input('members')) : '[]',
            'address_city'     => $request->input('city'),
            'address_pincode'  => $request->input('pincode'),
            'address_state'    => $request->input('state'),
            'house_no'         => $request->input('house_no'),
            'area'             => $request->input('area'),
            'latitude'         => $request->input('latitude'),
            'longitude'        => $request->input('longitude'),
            'landmark'         => $request->input('landmark'),
            'is_prashad'       => $request->input('is_prashad'),
        ]);

        // Update User Address if User Exists
        if ($request->filled('user_id')) {
            User::where('id', $request->input('user_id'))->update([
                'zip'            => $request->input('pincode'),
                'city'           => $request->input('city'),
                'house_no'       => $request->input('house_no'),
                'street_address' => $request->input('area'),
            ]);
        }

        // Prashad Delivery
        if ($request->input('is_prashad') == 1) {
            Prashad_deliverys::create([
                'seller_id'     => $request->input('seller_id'),
                'order_id'      => $order_id,
                'warehouse_id'  => $request->input('warehouse_id'),
                'service_id'    => $request->input('service_id'),
                'user_id'       => $request->input('user_id'),
                'product_id'    => $request->input('product_id'),
                'type'          => $request->input('type'),
                'payment_type'  => $request->input('payment_type'),
                'booking_date'  => $request->input('booking_date'),
            ]);
        }

        // Refresh Order Data with Relations
        $sankalpData = Service_order::with(['customers', 'services', 'packages', 'leads'])->where('order_id', $order_id)->first();
        if (!$sankalpData) {
            return redirect()->back()->with('error', 'Order data not found.');
        }

        // Create Service Review
        ServiceReview::create([
            'order_id'     => $order_id,
            'user_id'      => $sankalpData->customer_id,
            'service_id'   => $sankalpData->service_id,
            'service_type' => $sankalpData->type,
            'rating'       => 5,
        ]);

        // User Feedback if not exists
        if (!UserFeedback::where('user_id', $sankalpData->customer_id)->exists()) {
            UserFeedback::create([
                'user_id' => $sankalpData->customer_id,
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status'  => 1,
            ]);
        }

        $membersList = json_decode($sankalpData->members, true);
        $formattedMembers = $membersList ? implode(', ', $membersList) : 'No members specified';

        // WhatsApp Message Setup
        $userInfo = User::find($sankalpData->customer_id);
        $service_name = \App\Models\Service::where('id', $sankalpData->service_id)->where('product_type', 'pooja')
                        ->first();

        $bookingDetails = Service_order::where([
            ['service_id', $sankalpData->service_id],
            ['type', 'pooja'],
            ['booking_date', $sankalpData->booking_date],
            ['customer_id', $sankalpData->customer_id],
            ['order_id', $order_id],
        ])->first();

        if ($service_name) {
            $message_data = [
                'service_name'  => $service_name->name,
                'member_names'  => $formattedMembers,
                'type'          => 'text-with-media',
                'attachment'    => asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
                'booking_date'  => date('d-m-Y', strtotime($sankalpData->booking_date)),
                'puja_venue'    => $service_name->pooja_venue,
                'orderId'       => $order_id,
                'prashad'       => $sankalpData->is_prashad == 1 ? 'Your Prasad is being prepared and will be dispatched to your address within 7-8 days, only if you selected "Yes" for receiving Prasad.' : '',
                'gotra'         => $request->input('gotra'),
                'customer_id'   => $sankalpData->customer_id,
            ];

            Helpers::whatsappMessage('whatsapp', 'Sankalp Information', $message_data);
        }

        // Email Notification
        if ($userInfo && !empty($userInfo->email) && filter_var($userInfo->email, FILTER_VALIDATE_EMAIL)) {
            $data = [
                'type'        => 'pooja',
                'email'       => $userInfo->email,
                'subject'     => 'Information given by you for puja',
                'htmlContent' => view('admin-views.email.email-template.pooja-sankalp-template', compact('userInfo', 'service_name', 'bookingDetails', 'formattedMembers', 'request'))->render(),
            ];

            Helpers::emailSendMessage($data);
        }

        return view('web-views.epooja.PoojaBillInfo', compact('sankalpData'));
    }


    public function sankalp($order_id)
    {
        $sankalpData = Service_order::where('order_id', $order_id)->with(['customers', 'services', 'packages', 'leads'])->first();
        // dd($sankalpData);
        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        return view('web-views.epooja.details-sankalp', compact('sankalpData', 'citiesList', 'stateList'));
    }

    public function updateCartQuantity(Request $request)
    {
        $productData = Product::where('status', 1)->where('id', $request->input('cartId'))->select('name', 'unit_price')
            ->first();
        if (!$productData) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found or inactive.'
            ], 404);
        }
        $leadid = $request->input('leadid');
        $quantity = (int)$request->input('quantity');
        $updateid = $request->input('updateid');
        $price = $productData->unit_price;
        // Calculate final price based on quantity
        $final_price = $price * $quantity;
        // Optional: Validate quantity is positive
        if ($quantity <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Quantity must be greater than 0.'
            ], 400);
        }
        $updateQuery = ProductLeads::where('id', $updateid)
            ->update([
                'final_price' => $final_price,
                'qty' => $quantity
            ]);
      
        $totalAmount = ProductLeads::where('leads_id', $leadid)->sum('final_price');
        $productlist = ProductLeads::where('leads_id', $leadid)->get();

        $add_product_array = [];
        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
        }
        Leads::where('id', $leadid)->update([
            'status' => 1,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' => json_encode($add_product_array),
            'final_amount' => $totalAmount,
        ]);
        if ($updateQuery) {
            $finalPrice = ProductLeads::select('final_price')->where('id', $updateid)->first();
            // Array of session keys to forget
            $sessionKeys = [
                'coupon_code_pooja',
                'coupon_type_pooja',
                'coupon_discount_pooja',
                'coupon_bearer_pooja',
                'coupon_seller_id_pooja',
                'coupon_code_vippooja',
                'coupon_type_vippooja',
                'coupon_discount_vippooja',
                'coupon_bearer_vippooja',
                'coupon_seller_id_vippooja',
                'coupon_code_vipanushthan',
                'coupon_type_vipanushthan',
                'coupon_discount_vipanushthan',
                'coupon_bearer_vipanushthan',
                'coupon_seller_id_vipanushthan',
                'coupon_code_instancevip',
                'coupon_type_instancevip',
                'coupon_discount_instancevip',
                'coupon_bearer_instancevip',
                'coupon_seller_id_instancevip',
                'coupon_code_instanceanushthan',
                'coupon_type_instanceanushthan',
                'coupon_discount_instanceanushthan',
                'coupon_bearer_instanceanushthan',
                'coupon_seller_id_instanceanushthan',
                'coupon_code_chadhava',
                'coupon_type_chadhava',
                'coupon_discount_chadhava',
                'coupon_bearer_chadhava',
                'coupon_seller_id_chadhava',
                'coupon_code_counselling',
                'coupon_type_counselling',
                'coupon_discount_counselling',
                'coupon_bearer_counselling',
                'coupon_seller_id_counselling'
            ];

            // Forget each session key
            foreach ($sessionKeys as $key) {
                session()->forget($key);
            }
            return response()->json(['success' => true, 'message' => 'Quantity updated successfully', 'data' => ['final_price' => $finalPrice, 'total_amount' => $totalAmount]]);
        } else {
            return response()->json(['success' => false, 'message' => 'Quantity updated Unsuccessfully']);
        }
    }
    
    public function deleteQuantity(Request $request)
    {
        
        $updateid = $request->input('updateid');
        $leadid = $request->input('leadid');

        // Delete the specific product lead record
        $deleteQuery = ProductLeads::where('id', $updateid)->delete();

        if (!$deleteQuery) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete record'
            ]);
        }

        // Fetch updated product list after deletion
        $productlist = ProductLeads::where('leads_id', $leadid)->get();

        $add_product_array = [];
        $totalAmount = 0;

        foreach ($productlist as $product) {
            $add_product_array[] = [
                'product_id' => $product->product_id,
                'price' => $product->product_price,
                'qty' => $product->qty,
            ];
            $totalAmount += $product->final_price; // Sum total final price of all products
        }

        // Update Leads table with new product list and final amount
        Leads::where('id', $leadid)->update([
            'status' => 1,
            'payment_status' => 'pending',
            'platform' => 'web',
            'add_product_id' => json_encode($add_product_array),
            'final_amount' => $totalAmount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted and lead updated successfully',
            'totalAmount' => $totalAmount,
            'add_product_array' => $add_product_array
        ]);
    }
    public function index(string $slug): View|RedirectResponse
    {
        $theme_name = theme_root_path();

        return match ($theme_name) {
            'default' => self::getDefaultTheme(slug: $slug),
            'theme_aster' => self::getThemeAster(slug: $slug),
            'theme_fashion' => self::getThemeFashion(slug: $slug),
            'theme_all_purpose' => self::theme_all_purpose($slug),
        };
    }

    public function getDefaultTheme(string $slug): View|RedirectResponse
    {
        $product = $this->productRepo->getFirstWhereActive(params: ['slug' => $slug], relations: ['reviews', 'seller.shop']);
        if ($product) {
            $overallRating = getOverallRating(reviews: $product->reviews);
            $wishlistStatus = $this->wishlistRepo->getListWhereCount(filters: ['product_id' => $product['id'], 'customer_id' => auth('customer')->id()]);
            $productReviews = $this->reviewRepo->getListWhere(
                orderBy: ['id' => 'desc'],
                filters: ['product_id' => $product['id']],
                dataLimit: 2,
                offset: 1
            );

            $rating = getRating(reviews: $product->reviews);
            $decimalPointSettings = getWebConfig('decimal_point_settings');
            $moreProductFromSeller = $this->productRepo->getWebListWithScope(
                orderBy: ['id' => 'desc'],
                scope: 'active',
                filters: ['added_by' => $product['added_by'] == 'admin' ? 'in_house' : $product['added_by'], 'seller_id' => $product['user_id']],
                whereNotIn: ['id' => [$product['id']]],
                dataLimit: 5,
                offset: 1
            );

            if ($product['added_by'] == 'seller') {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => $product['added_by'], 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            } else {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => 'in_house', 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            }

            $totalReviews = 0;
            foreach ($productsForReview as $item) {
                $totalReviews += $item->reviews_count;
            }
            $countOrder = $this->orderDetailRepo->getListWhereCount(filters: ['product_id' => $product['id']]);
            $countWishlist = $this->wishlistRepo->getListWhereCount(filters: ['product_id' => $product['id']]);
            $relatedProducts = $this->productRepo->getWebListWithScope(
                scope: 'active',
                filters: ['category_id' => $product['category_id']],
                whereNotIn: ['id' => [$product['id']]],
                relations: ['reviews'],
                dataLimit: 12,
                offset: 1
            );
            $dealOfTheDay = $this->dealOfTheDayRepo->getFirstWhere(['product_id' => $product['id'], 'status' => 1]);
            $currentDate = date('Y-m-d');
            $sellerVacationStartDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_start_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_start_date)) : null;
            $sellerVacationEndDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_end_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_end_date)) : null;
            $sellerTemporaryClose = ($product['added_by'] == 'seller' && isset($product->seller->shop->temporary_close)) ? $product->seller->shop->temporary_close : false;

            $temporaryClose = getWebConfig('temporary_close');
            $inHouseVacation = getWebConfig('vacation_add');
            $inHouseVacationStartDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_start_date'] : null;
            $inHouseVacationEndDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_end_date'] : null;
            $inHouseVacationStatus = $product['added_by'] == 'admin' ? $inHouseVacation['status'] : false;
            $inHouseTemporaryClose = $product['added_by'] == 'admin' ? $temporaryClose['status'] : false;

            return view(VIEW_FILE_NAMES['products_details'], compact(
                'product',
                'countWishlist',
                'countOrder',
                'relatedProducts',
                'dealOfTheDay',
                'currentDate',
                'sellerVacationStartDate',
                'sellerVacationEndDate',
                'sellerTemporaryClose',
                'inHouseVacationStartDate',
                'inHouseVacationEndDate',
                'inHouseVacationStatus',
                'inHouseTemporaryClose',
                'overallRating',
                'wishlistStatus',
                'productReviews',
                'rating',
                'totalReviews',
                'productsForReview',
                'moreProductFromSeller',
                'decimalPointSettings'
            ));
        }

        Toastr::error(translate('not_found'));
        return back();
    }

    public function getThemeAster(string $slug): View|RedirectResponse
    {
        $product = $this->productRepo->getWebFirstWhereActive(
            params: ['slug' => $slug, 'customer_id' => Auth::guard('customer')->user()->id ?? 0],
            relations: ['reviews' => 'reviews', 'seller.shop' => 'seller.shop', 'wishList' => 'wishList', 'compareList' => 'compareList'],
            withCount: ['orderDetails' => 'orderDetails', 'wishList' => 'wishList']
        );

        if ($product != null) {
            $currentDate = date('Y-m-d H:i:s');

            $countOrder = $product['order_details_count'];
            $countWishlist = $product['wish_list_count'];
            $wishlistStatus = $this->wishlistRepo->getCount(params: ['product_id' => $product->id, 'customer_id' => auth('customer')->id()]);
            $compareList = $this->compareRepo->getCount(params: ['product_id' => $product->id, 'customer_id' => auth('customer')->id()]);

            $relatedProducts = $this->productRepo->getWebListWithScope(
                scope: 'active',
                filters: ['category_ids' => $product['category_ids'], 'customer_id' => Auth::guard('customer')->user()->id ?? 0],
                whereNotIn: ['id' => [$product['id']]],
                relations: ['reviews', 'flashDealProducts.flashDeal', 'wishList', 'compareList'],
                withCount: ['reviews' => 'reviews'],
                dataLimit: 12,
                offset: 1
            );

            $relatedProducts?->map(function ($product) use ($currentDate) {
                $flash_deal_status = 0;
                $flash_deal_end_date = 0;
                if (count($product->flashDealProducts) > 0) {
                    $flash_deal = $product->flashDealProducts[0]->flashDeal;
                    if ($flash_deal) {
                        $start_date = date('Y-m-d H:i:s', strtotime($flash_deal->start_date));
                        $end_date = date('Y-m-d H:i:s', strtotime($flash_deal->end_date));
                        $flash_deal_status = $flash_deal->status == 1 && (($currentDate >= $start_date) && ($currentDate <= $end_date)) ? 1 : 0;
                        $flash_deal_end_date = $flash_deal->end_date;
                    }
                }
                $product['flash_deal_status'] = $flash_deal_status;
                $product['flash_deal_end_date'] = $flash_deal_end_date;
                return $product;
            });

            $dealOfTheDay = $this->dealOfTheDayRepo->getFirstWhere(['product_id' => $product['id'], 'status' => 1]);
            $currentDate = date('Y-m-d');
            $sellerVacationStartDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_start_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_start_date)) : null;
            $sellerVacationEndDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_end_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_end_date)) : null;
            $sellerTemporaryClose = ($product['added_by'] == 'seller' && isset($product->seller->shop->temporary_close)) ? $product->seller->shop->temporary_close : false;

            $temporaryClose = getWebConfig('temporary_close');
            $inHouseVacation = getWebConfig('vacation_add');
            $inHouseVacationStartDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_start_date'] : null;
            $inHouseVacationEndDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_end_date'] : null;
            $inHouseVacationStatus = $product['added_by'] == 'admin' ? $inHouseVacation['status'] : false;
            $inHouseTemporaryClose = $product['added_by'] == 'admin' ? $temporaryClose['status'] : false;

            $overallRating = getOverallRating($product['reviews']);

            $rating = getRating($product->reviews);
            $productReviews = $this->reviewRepo->getListWhere(
                orderBy: ['id' => 'desc'],
                filters: ['product_id' => $product['id']],
                dataLimit: 2,
                offset: 1
            );
            $decimalPointSettings = getWebConfig('decimal_point_settings');
            $moreProductFromSeller = $this->productRepo->getWebListWithScope(
                orderBy: ['id' => 'desc'],
                scope: 'active',
                filters: ['added_by' => $product['added_by'] == 'admin' ? 'in_house' : $product['added_by'], 'seller_id' => $product['user_id']],
                whereNotIn: ['id' => [$product['id']]],
                dataLimit: 5,
                offset: 1
            );

            if ($product['added_by'] == 'seller') {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => $product['added_by'], 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            } else {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => 'in_house', 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            }

            $totalReviews = 0;
            foreach ($productsForReview as $item) {
                $totalReviews += $item->reviews_count;
            }

            $productIds = Product::active()->where(['added_by' => $product['added_by']])
                ->where('user_id', $product['user_id'])->pluck('id')->toArray();
            $vendorReviewData = Review::active()->whereIn('product_id', $productIds);
            $ratingCount = $vendorReviewData->count();
            $avgRating = $vendorReviewData->avg('rating');

            $vendorRattingStatusPositive = 0;
            foreach ($vendorReviewData->pluck('rating') as $singleRating) {
                ($singleRating >= 4 ? ($vendorRattingStatusPositive++) : '');
            }

            $positiveReview = $ratingCount != 0 ? ($vendorRattingStatusPositive * 100) / $ratingCount : 0;

            return view(VIEW_FILE_NAMES['products_details'], compact(
                'product',
                'wishlistStatus',
                'countWishlist',
                'countOrder',
                'relatedProducts',
                'dealOfTheDay',
                'currentDate',
                'sellerVacationStartDate',
                'sellerVacationEndDate',
                'sellerTemporaryClose',
                'inHouseVacationStartDate',
                'inHouseVacationEndDate',
                'inHouseVacationStatus',
                'inHouseTemporaryClose',
                'overallRating',
                'decimalPointSettings',
                'moreProductFromSeller',
                'productsForReview',
                'totalReviews',
                'rating',
                'productReviews',
                'avgRating',
                'compareList',
                'positiveReview'
            ));
        }

        Toastr::error(translate('not_found'));
        return back();
    }

    public function getThemeFashion($slug): View|RedirectResponse
    {
        $product = $this->productRepo->getWebFirstWhereActive(
            params: ['slug' => $slug, 'customer_id' => Auth::guard('customer')->user()->id ?? 0],
            relations: ['reviews' => 'reviews', 'seller.shop' => 'seller.shop', 'wishList' => 'wishList', 'compareList' => 'compareList'],
            withCount: ['orderDetails' => 'orderDetails', 'wishList' => 'wishList']
        );
        if ($product != null) {
            $tags = $this->productTagRepo->getIds(fieldName: 'tag_id', filters: ['product_id' => $product['id']]);
            $this->tagRepo->incrementVisitCount(whereIn: ['id' => $tags]);

            $currentDate = date('Y-m-d H:i:s');
            $countWishlist = $product['wish_list_count'];
            $wishlistStatus = $this->wishlistRepo->getCount(params: ['product_id' => $product->id, 'customer_id' => auth('customer')->id()]);
            $compareList = $this->compareRepo->getCount(params: ['product_id' => $product->id, 'customer_id' => auth('customer')->id()]);
            $relatedProducts = $this->productRepo->getWebListWithScope(
                scope: 'active',
                filters: ['category_id' => $product['category_id']],
                whereNotIn: ['id' => [$product['id']]],
                dataLimit: 'all',
            )->count();

            $sellerVacationStartDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_start_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_start_date)) : null;
            $sellerVacationEndDate = ($product['added_by'] == 'seller' && isset($product->seller->shop->vacation_end_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_end_date)) : null;
            $sellerTemporaryClose = ($product['added_by'] == 'seller' && isset($product->seller->shop->temporary_close)) ? $product->seller->shop->temporary_close : false;

            $temporaryClose = getWebConfig(name: 'temporary_close');
            $inHouseVacation = getWebConfig(name: 'vacation_add');
            $inHouseVacationStartDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_start_date'] : null;
            $inHouseVacationEndDate = $product['added_by'] == 'admin' ? $inHouseVacation['vacation_end_date'] : null;
            $inHouseVacationStatus = $product['added_by'] == 'admin' ? $inHouseVacation['status'] : false;
            $inHouseTemporaryClose = $product['added_by'] == 'admin' ? $temporaryClose['status'] : false;

            $overallRating = getOverallRating($product['reviews']);
            $productReviewsCount = $product->reviews->count();

            $rattingStatusPositive = $productReviewsCount != 0 ? ($product->reviews->where('rating', '>=', 4)->count() * 100) / $productReviewsCount : 0;
            $rattingStatusGood = $productReviewsCount != 0 ? ($product->reviews->where('rating', 3)->count() * 100) / $productReviewsCount : 0;
            $rattingStatusNeutral = $productReviewsCount != 0 ? ($product->reviews->where('rating', 2)->count() * 100) / $productReviewsCount : 0;
            $rattingStatusNegative = $productReviewsCount != 0 ? ($product->reviews->where('rating', '=', 1)->count() * 100) / $productReviewsCount : 0;
            $rattingStatus = [
                'positive' => $rattingStatusPositive,
                'good' => $rattingStatusGood,
                'neutral' => $rattingStatusNeutral,
                'negative' => $rattingStatusNegative,
            ];

            $rating = getRating($product->reviews);
            $productReviews = $this->reviewRepo->getListWhere(
                orderBy: ['id' => 'desc'],
                filters: ['product_id' => $product['id']],
                dataLimit: 2,
                offset: 1
            );
            $decimalPointSettings = getWebConfig('decimal_point_settings');
            $moreProductFromSeller = $this->productRepo->getWebListWithScope(
                orderBy: ['id' => 'desc'],
                scope: 'active',
                filters: ['added_by' => $product['added_by'] == 'admin' ? 'in_house' : $product['added_by'], 'seller_id' => $product['user_id']],
                whereNotIn: ['id' => [$product['id']]],
                dataLimit: 5,
                offset: 1
            );

            if ($product['added_by'] == 'seller') {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => $product['added_by'], 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            } else {
                $productsForReview = $this->productRepo->getWebListWithScope(
                    scope: 'active',
                    filters: ['added_by' => 'in_house', 'seller_id' => $product['user_id']],
                    withCount: ['reviews' => 'reviews']
                );
            }
            $productsCount = $productsForReview->count();
            $totalReviews = 0;
            foreach ($productsForReview as $item) {
                $totalReviews += $item->reviews_count;
            }

            $productIds = Product::active()->where(['added_by' => $product['added_by']])
                ->where('user_id', $product['user_id'])->pluck('id')->toArray();
            $vendorReviewData = Review::active()->whereIn('product_id', $productIds);
            $ratingCount = $vendorReviewData->count();
            $avgRating = $vendorReviewData->avg('rating');

            $vendorRattingStatusPositive = 0;
            foreach ($vendorReviewData->pluck('rating') as $singleRating) {
                ($singleRating >= 4 ? ($vendorRattingStatusPositive++) : '');
            }

            $positiveReview = $ratingCount != 0 ? ($vendorRattingStatusPositive * 100) / $ratingCount : 0;

            $sellerList = $this->sellerRepo->getListWithScope(
                scope: 'active',
                filters: ['category_id' => $product['category_id']],
                relations: ['shop' => 'shop', 'product.reviews' => 'product.reviews'],
                withCount: ['product' => 'product'],
                dataLimit: 'all',
            );
            $sellerList?->map(function ($seller) {
                $rating = 0;
                $count = 0;
                foreach ($seller->product as $item) {
                    foreach ($item->reviews as $review) {
                        $rating += $review->rating;
                        $count++;
                    }
                }
                $avg_rating = $rating / ($count == 0 ? 1 : $count);
                $rating_count = $count;
                $seller['average_rating'] = $avg_rating;
                $seller['rating_count'] = $rating_count;

                $product_count = $seller->product->count();
                $random_product = Arr::random($seller->product->toArray(), $product_count < 3 ? $product_count : 3);
                $seller['product'] = $random_product;
                return $seller;
            });
            $newSellers = $sellerList->sortByDesc('id')->take(12);
            $topRatedShops = $sellerList->where('rating_count', '!=', 0)->sortByDesc('average_rating')->take(12);

            $deliveryInfo = self::getProductDeliveryCharge(product: $product, quantity: $product['minimum_order_qty']);
            $productsThisStoreTopRated = $this->productRepo->getWebListWithScope(
                orderBy: ['reviews_count' => 'DESC'],
                scope: 'active',
                filters: ['added_by' => $product['added_by'] == 'admin' ? 'in_house' : $product['added_by'], 'seller_id' => $product['user_id']],
                whereHas: ['reviews' => 'reviews'],
                relations: ['category', 'rating', 'reviews', 'wishList', 'compare_list'],
                withCount: ['reviews' => 'reviews'],
                withSum: [['relation' => 'orderDetails', 'column' => 'qty', 'whereColumn' => 'delivery_status', 'whereValue' => 'delivered']],
                dataLimit: 12,
                offset: 1
            );

            $productsTopRated = $this->productRepo->getWebListWithScope(
                orderBy: ['reviews_count' => 'DESC'],
                scope: 'active',
                filters: ['category_id' => $product['category_id'], 'customer_id' => Auth::guard('customer')->user()->id ?? 0],
                relations: ['wishList', 'compareList'],
                withCount: ['reviews' => 'reviews'],
                dataLimit: 12,
                offset: 1
            );

            $productsLatest = $this->productRepo->getWebListWithScope(
                orderBy: ['id' => 'DESC'],
                scope: 'active',
                filters: ['category_id' => $product['category_id'], 'customer_id' => Auth::guard('customer')->user()->id ?? 0],
                relations: ['wishList', 'compareList'],
                dataLimit: 12,
                offset: 1
            );

            return view(VIEW_FILE_NAMES['products_details'], compact(
                'product',
                'wishlistStatus',
                'countWishlist',
                'relatedProducts',
                'currentDate',
                'sellerVacationStartDate',
                'sellerVacationEndDate',
                'rattingStatus',
                'productsLatest',
                'sellerTemporaryClose',
                'inHouseVacationStartDate',
                'inHouseVacationEndDate',
                'inHouseVacationStatus',
                'inHouseTemporaryClose',
                'positiveReview',
                'overallRating',
                'decimalPointSettings',
                'moreProductFromSeller',
                'productsForReview',
                'productsCount',
                'totalReviews',
                'rating',
                'productReviews',
                'avgRating',
                'topRatedShops',
                'newSellers',
                'deliveryInfo',
                'productsTopRated',
                'productsThisStoreTopRated'
            ));
        }

        Toastr::error(translate('not_found'));
        return back();
    }

    public function theme_all_purpose($slug): View|RedirectResponse
    {
        $product = Product::active()->with(['reviews', 'seller.shop'])->withCount('reviews')->where('slug', $slug)->first();
        if ($product != null) {

            $tags = ProductTag::where('product_id', $product->id)->pluck('tag_id');
            Tag::whereIn('id', $tags)->increment('visit_count');

            $current_date = date('Y-m-d H:i:s');

            $countWishlist = Wishlist::where('product_id', $product->id)->count();
            $wishlist_status = Wishlist::where(['product_id' => $product->id, 'customer_id' => auth('customer')->id()])->count();

            $relatedProducts = Product::active()->with(['reviews', 'flashDealProducts.flashDeal'])->withCount('reviews')->where('category_ids', $product->category_ids)->where('id', '!=', $product->id)->limit(12)->get();
            $relatedProducts?->map(function ($product) use ($current_date) {
                $flash_deal_status = 0;
                $flash_deal_end_date = 0;
                if (count($product->flashDealProducts) > 0) {
                    $flash_deal = $product->flashDealProducts[0]->flashDeal;
                    if ($flash_deal) {
                        $start_date = date('Y-m-d H:i:s', strtotime($flash_deal->start_date));
                        $end_date = date('Y-m-d H:i:s', strtotime($flash_deal->end_date));
                        $flash_deal_status = $flash_deal->status == 1 && (($current_date >= $start_date) && ($current_date <= $end_date)) ? 1 : 0;
                        $flash_deal_end_date = $flash_deal->end_date;
                    }
                }
                $product['flash_deal_status'] = $flash_deal_status;
                $product['flash_deal_end_date'] = $flash_deal_end_date;
                return $product;
            });

            $seller_vacation_start_date = ($product->added_by == 'seller' && isset($product->seller->shop->vacation_start_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_start_date)) : null;
            $seller_vacation_end_date = ($product->added_by == 'seller' && isset($product->seller->shop->vacation_end_date)) ? date('Y-m-d', strtotime($product->seller->shop->vacation_end_date)) : null;
            $seller_temporary_close = ($product->added_by == 'seller' && isset($product->seller->shop->temporary_close)) ? $product->seller->shop->temporary_close : false;

            $temporary_close = Helpers::get_business_settings('temporary_close');
            $inhouse_vacation = Helpers::get_business_settings('vacation_add');
            $inhouse_vacation_start_date = $product->added_by == 'admin' ? $inhouse_vacation['vacation_start_date'] : null;
            $inhouse_vacation_end_date = $product->added_by == 'admin' ? $inhouse_vacation['vacation_end_date'] : null;
            $inHouseVacationStatus = $product->added_by == 'admin' ? $inhouse_vacation['status'] : false;
            $inhouseTemporaryClose = $product->added_by == 'admin' ? $temporary_close['status'] : false;

            $overall_rating = getOverallRating($product->reviews);
            $product_reviews_count = $product->reviews->count();

            $ratting_status_positive = $product_reviews_count != 0 ? ($product->reviews->where('rating', '>=', 4)->count() * 100) / $product_reviews_count : 0;
            $ratting_status_good = $product_reviews_count != 0 ? ($product->reviews->where('rating', 3)->count() * 100) / $product_reviews_count : 0;
            $ratting_status_neutral = $product_reviews_count != 0 ? ($product->reviews->where('rating', 2)->count() * 100) / $product_reviews_count : 0;
            $ratting_status_negative = $product_reviews_count != 0 ? ($product->reviews->where('rating', '=', 1)->count() * 100) / $product_reviews_count : 0;
            $ratting_status = [
                'positive' => $ratting_status_positive,
                'good' => $ratting_status_good,
                'neutral' => $ratting_status_neutral,
                'negative' => $ratting_status_negative,
            ];

            $rating = getRating($product->reviews);
            $reviews_of_product = Review::where('product_id', $product->id)->latest()->paginate(2);
            $decimal_point_settings = \App\Utils\Helpers::get_business_settings('decimal_point_settings');
            $more_product_from_seller = Product::active()->withCount('reviews')->where('added_by', $product->added_by)->where('id', '!=', $product->id)->where('user_id', $product->user_id)->latest()->take(5)->get();
            $more_product_from_seller_count = Product::active()->where('added_by', $product->added_by)->where('id', '!=', $product->id)->where('user_id', $product->user_id)->count();

            if ($product->added_by == 'seller') {
                $products_for_review = Product::active()->where('added_by', $product->added_by)->where('user_id', $product->user_id)->withCount('reviews')->get();
            } else {
                $products_for_review = Product::where('added_by', 'admin')->where('user_id', $product->user_id)->withCount('reviews')->get();
            }

            $total_reviews = 0;
            foreach ($products_for_review as $item) {
                $total_reviews += $item->reviews_count;
            }

            $product_ids = Product::where(['added_by' => $product->added_by, 'user_id' => $product->user_id])->pluck('id');

            $rating_status = Review::whereIn('product_id', $product_ids);
            $rating_count = $rating_status->count();
            $avg_rating = $rating_count != 0 ? $rating_status->avg('rating') : 0;
            $rating_percentage = round(($avg_rating * 100) / 5);

            // more stores start
            $more_seller = Seller::approved()->with(['shop', 'product.reviews'])
                ->withCount(['product' => function ($query) {
                    $query->active();
                }])
                ->inRandomOrder()
                ->take(7)->get();

            $more_seller = $more_seller->map(function ($seller) {
                $review_count = 0;
                $rating = [];
                foreach ($seller->product as $product) {
                    $review_count += $product->reviews_count;
                    foreach ($product->reviews as $reviews) {
                        $rating[] = $reviews['rating'];
                    }
                }
                $seller['reviews_count'] = $review_count;
                $seller['rating'] = collect($rating)->average() ?? 0;
                return $seller;
            });
            //end more stores

            // new stores
            $new_seller = Seller::approved()->with(['shop', 'product.reviews'])
                ->withCount(['product' => function ($query) {
                    $query->active();
                }])
                ->latest()
                ->take(7)->get();

            $new_seller = $new_seller->map(function ($seller) {
                $review_count = 0;
                $rating = [];
                foreach ($seller->product as $product) {
                    $review_count += $product->reviews_count;
                    foreach ($product->reviews as $reviews) {
                        $rating[] = $reviews['rating'];
                    }
                }
                $seller['reviews_count'] = $review_count;
                $seller['rating'] = collect($rating)->average() ?? 0;
                return $seller;
            });
            //end new stores

            $delivery_info = ProductManager::get_products_delivery_charge($product, $product->minimum_order_qty);

            // top_rated products
            $products_top_rated = Product::with(['rating', 'reviews'])->active()
                ->withCount(['reviews'])->orderBy('reviews_count', 'DESC')
                ->take(12)->get();

            $products_this_store_top_rated = Product::with(['rating', 'reviews'])->active()
                ->where(['added_by' => $product->added_by, 'user_id' => $product->user_id])
                ->withCount(['reviews'])->orderBy('reviews_count', 'DESC')
                ->take(12)->get();

            $products_latest = Product::active()->with(['reviews', 'rating'])->latest()->take(12)->get();

            return view(VIEW_FILE_NAMES['products_details'], compact(
                'product',
                'wishlist_status',
                'countWishlist',
                'relatedProducts',
                'current_date',
                'seller_vacation_start_date',
                'seller_vacation_end_date',
                'ratting_status',
                'products_latest',
                'seller_temporary_close',
                'inhouse_vacation_start_date',
                'inhouse_vacation_end_date',
                'inHouseVacationStatus',
                'inhouseTemporaryClose',
                'overall_rating',
                'decimal_point_settings',
                'more_product_from_seller',
                'products_for_review',
                'total_reviews',
                'rating',
                'reviews_of_product',
                'avg_rating',
                'rating_percentage',
                'more_seller',
                'new_seller',
                'delivery_info',
                'products_top_rated',
                'products_this_store_top_rated',
                'more_product_from_seller_count'
            ));
        }

        Toastr::error(translate('not_found'));
        return back();
    }
    // ------------------------Counsleing Data Section Controller---------------------------------------------
    // public function counselling_index($slug)
    // {
    //     if ($slug == 'consultancy') {
    //         $counselling = Service::where(['category_id' => 39, 'sub_category_id' => 40, 'status' => 1])->withCount('PoojaOrderReview')
    //             ->withAvg('review', 'rating')->get();
    //     } else if ($slug == 'muhurat') {
    //         $counselling = Service::where(['category_id' => 39, 'sub_category_id' => 41, 'status' => 1])->withCount('PoojaOrderReview')
    //             ->withAvg('review', 'rating')->get();
    //     }
    //     return view('web-views.counselling.list', compact('counselling', 'slug'));
    // }

    public function counselling_index(Request $request)
    {
        $type = null;
        if ($request->has('type')) {
            $type = $request->type;
        }
        $consultation = Service::where(['category_id' => 39, 'sub_category_id' => 40, 'status' => 1])->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')->get();
        $muhurat = Service::where(['category_id' => 39, 'sub_category_id' => 41, 'status' => 1])->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')->get();
        return view('web-views.counselling.list', compact('consultation', 'muhurat', 'type'));
    }

    public function counselling_details($slug)
    {
        $counsellingDetails = Service::where('slug', $slug)->with('categories')->first();
        if (!$counsellingDetails) {
            return redirect()->to('/');
        }
        $counsellingData = Service::where('slug', $slug)->with('categories')->withCount('PoojaOrderReview')->withAvg('review', 'rating')->get();
        $allReviews = ServiceReview::with('userData')->get();

        $uniqueUserReviews = $allReviews
            ->groupBy('user_id')
            ->map(function ($userReviews) {
                return $userReviews->sortByDesc(function ($review) {
                    if (!empty($review->youtube_link)) return 3;
                    if (!empty($review->comment)) return 2;
                    return 1;
                })->first(); 
            })
            ->sortByDesc(function ($review) {
                if (!empty($review->youtube_link)) return 3;
                if (!empty($review->comment)) return 2;
                return 1;
            })
            ->values();

        $originalReviews = $uniqueUserReviews->where('is_edited', 0);
        $editedReviews = $uniqueUserReviews;
        $serviceReview = $originalReviews->count();

        $reviewCounts = [
            'excellent'     => $originalReviews->where('rating', 5)->count(),
            'good'          => $originalReviews->where('rating', 4)->count(),
            'average'       => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor'          => $originalReviews->filter(fn($review) => $review->rating == 1 && !is_null($review->comment))->count(),
            'averageStar'   => round($originalReviews->avg('rating'), 1),
            'list'          => $editedReviews->take(10),
        ];

        $totalReviews = $counsellingData->sum('reviews_count');

        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');

        $faq_name = '';
        // dd($counsellingDetails->categories->getRawOriginal('name'));
        if (($counsellingDetails->categories->getRawOriginal('name') ?? "") == 'Auspicious Muhurat Consultation') {
            $faq_name = 'Mahurat Consultation';
        } elseif (($counsellingDetails->categories->getRawOriginal('name') ?? "") == 'Astrology Consultation') {
            $faq_name = 'Yog Consultation';
        }
        $faqs = \App\Models\FAQ::whereHas('Category', function ($query) use ($faq_name) {
            $query->where('name', $faq_name);
        })->with('Category')->get();
        // dd($faqs);
        return view('web-views.counselling.detail', compact('counsellingDetails', 'counsellingData', 'serviceReview', 'reviewCounts', 'totalReviews', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment', 'faqs'));
    }
    public function counselling_lead_store(Request $request)
    {
        //user insert
        $userExists = User::where('phone', $request->person_phone)->exists();
        $verifyOTP = $request->input('verify_otp');
        if (!$userExists) {
            $name = explode(' ', $request->person_name);
            $cust_details = [
                'name' => $request->person_name,
                'f_name' => isset($name[0]) ? $name[0] : '',
                'l_name' => isset($name[1]) ? $name[1] : '',
                'phone' => $request->person_phone,
                'email' => $request->person_phone,
                'password' => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ];
            User::create($cust_details);
        }

        if (!auth('customer')->check()) {
            $user = User::where(['phone' => $request->person_phone])->first();
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user['id']);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    // CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }

        // lead insert
        $lead_details = [
            'service_id' => $request->input('service_id'),
            'type' => 'counselling',
            'package_price' => $request->input('service_price'),
            'person_phone' => $request->input('person_phone'),
            'person_name' => $request->input('person_name'),
        ];
        $leads = Leads::create($lead_details);
        $insertedRowId = $leads->id;
        $encodedId = base64_encode($insertedRowId);
        return redirect()->route('counselling.order.book', ['encoded_id' => $encodedId]);
    }
    public function order_book($encodedId)
    {
        $id = base64_decode($encodedId);
        $leadsDetails = Leads::where('id', $id)->with('service')->first();
        $user = User::where('phone', $leadsDetails['person_phone'])->first();
        // $leadsGet = Leads::where('id', $id)->with('productLeads')->with('service')->first();
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        // $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        // $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        return view("web-views.counselling.order-book", compact('leadsDetails', 'user', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment'));
    }

    public function user_detail($orderId)
    {
        $orderDetail = Service_order::where('order_id', $orderId)->with('customers')->first();
        $country = Country::all();
        return view("web-views.counselling.user-detail", compact('orderDetail', 'country'));
    }

    public function user_store(Request $request)
    {
        $request->validate([
            'dob' => 'date_format:d/m/Y',
            'time' => 'date_format:H:i',
        ], [
            'dob.date_format' => 'The dob format should be DD/MM/YYYY.',
            'time.date_format' => 'The time format should be HH:MM.',
        ]);
        $orderId = $request->order_id;
        $user = new CounsellingUser;
        $user->order_id = $request->order_id;
        $user->name = $request->name;
        $user->gender = $request->gender;
        $user->mobile = $request->person_phone;
        $user->dob = $request->dob;
        $user->time = $request->time;
        $user->country = $request->country;
        $user->city = $request->places;
        $user->save();

        $serviceData = \App\Models\Service_order::where('type', 'counselling')->where('order_id', ($orderId ?? ""))->first();
        $serviceReview = ServiceReview::create([
            'order_id' => $request->order_id,
            'user_id' => $serviceData['customer_id'],
            'service_id' =>  $serviceData['service_id'],
            'service_type' => $serviceData['type'],
            'rating' => '5',
        ]);

        $feedbackExists = UserFeedback::where('user_id', $serviceData['customer_id'])->exists();
        if (!$feedbackExists) {
            UserFeedback::create([
                'user_id' => $serviceData['customer_id'],
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status' => 1,
            ]);
        }
        $dob = \App\Models\CounsellingUser::where('order_id', ($orderId ?? ""))->first();

        $userInfo = \App\Models\User::where('id', ($serviceData['customer_id'] ?? ""))->first();
        $service_name = \App\Models\Service::where('id', ($serviceData['service_id'] ?? ""))->where('product_type', 'counselling')->first();
        $bookingDetails = \App\Models\Service_order::where('service_id', ($serviceData['service_id'] ?? ""))->where('type', 'counselling')
            ->where('customer_id', ($serviceData['customer_id'] ?? ""))
            ->where('order_id', ($orderId ?? ""))
            ->first();

        $message_data = [
            'service_name' => $service_name['name'],
            'name' => $dob->name,
            'gender' => $dob->gender,
            'city' => $dob->city,
            'country' => $dob->country,
            'time' => $dob->time,
            'dob' => $dob->dob,
            'type' => 'text-with-media',
            'attachment' =>  asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
            'customer_id' => ($serviceData['customer_id'] ?? ""),
        ];
        $messages =  Helpers::whatsappMessage('consultancy', 'Information', $message_data);

        // Mail Setup for Pooja Management Send to  User Email Id
        if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $data['type'] = 'counselling';
            $data['email'] = $userInfo['email'];
            $data['subject'] = 'Information given by you for Consultation';
            $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-sankalp-template', compact('userInfo', 'service_name', 'bookingDetails', 'dob', 'request', 'serviceData'))->render();

            Helpers::emailSendMessage($data);
        }

        return view("web-views.counselling.order-placed", compact('orderId'));
    }

    //-------------------------------------------- VIP POOJA AND ANUSHTHAN DATE OF CREATED 22/07/2024--------------------------------------------
    public function vip_details(string $slug)
    {
        $vip = Vippooja::where('slug', $slug)->where('is_anushthan', 0)->first();
        if (!$vip) {
            return redirect()->to('/');
        }
        if ($vip) {
            $vip = Vippooja::select('vippoojas.*', 'vippoojas.name as vip_name')->where('id', $vip->id)->first();
        }
        $vipGet = Vippooja::select('vippoojas.*', 'packages.id as package_id', 'packages.title as package_name')
            ->leftJoin('packages', 'vippoojas.packages_id', '=', 'packages.id')
            ->where('vippoojas.id', $vip->id)
            ->withCount('PoojaOrderReview')
            ->withAvg('review', 'rating')
            ->get();
        $Faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
            $query->where('name', 'VIP Puja');
        })->with('Category')->get();

        $allReviews = ServiceReview::with('userData')->get();

        $uniqueUserReviews = $allReviews
            ->groupBy('user_id')
            ->map(function ($userReviews) {
                return $userReviews->sortByDesc(function ($review) {
                    if (!empty($review->youtube_link)) return 3;
                    if (!empty($review->comment)) return 2;
                    return 1;
                })->first(); 
            })
            ->sortByDesc(function ($review) {
                if (!empty($review->youtube_link)) return 3;
                if (!empty($review->comment)) return 2;
                return 1;
            })
            ->values();

        $originalReviews = $uniqueUserReviews->where('is_edited', 0);
        $editedReviews = $uniqueUserReviews;
        $serviceReview = $originalReviews->count();

        $reviewCounts = [
            'excellent'     => $originalReviews->where('rating', 5)->count(),
            'good'          => $originalReviews->where('rating', 4)->count(),
            'average'       => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor'          => $originalReviews->filter(fn($review) => $review->rating == 1 && !is_null($review->comment))->count(),
            'averageStar'   => round($originalReviews->avg('rating'), 1),
            'list'          => $editedReviews->take(10),
        ];

        $totalReviews = $vipGet->sum('reviews_count');
        return view("web-views.vippooja.details", compact('vip', 'Faqs', 'vipGet', 'serviceReview', 'reviewCounts', 'totalReviews'));
    }

    public function vip_lead_store(Request $request)
    {
        $servicedata  = Vippooja::where('id', $request->service_id)->where('is_anushthan', 0)->first();
        if (!$servicedata) {
            return redirect()->back()->with('error', 'Service not found.');
        }

        $packageList = json_decode($servicedata->packages_id, true);
        $selectedPackage = collect($packageList)->firstWhere('package_id', $request->package_id);
        if (!$selectedPackage) {
            return redirect()->back()->with('error', 'Invalid package selection.');
        }

        $package = \App\Models\Package::find($request->package_id);
        if (!$package) {
            return redirect()->back()->with('error', 'Package not found.');
        }

        // Input values
        $bookingDate  = $request->input('booking_date');
        $personName   = $request->input('person_name');
        $personPhone  = $request->input('person_phone');
        $verifyOTP    = $request->input('verify_otp');

        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0];
        $lastName  = implode(' ', array_slice($nameParts, 1));

        // ✅ Initialize customer_id
        $customerId = null;

        // ✅ Check user existence or create
        $userExists = User::where('phone', $request->person_phone)->exists();
        if (!$userExists) {
            $user = User::create([
                'name'       => $personName,
                'f_name'     => $firstName,
                'l_name'     => $lastName,
                'phone'      => $personPhone,
                'email'      => 'user@mahakal.com',
                'password'   => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ]);

            $customerId = $user->id;

            $data = ['customer_id' => $customerId];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        } else {
            $user = User::where('phone', $request->person_phone)->first();
            $customerId = $user->id ?? null;
        }

        // ✅ Login if not already
        if (!auth('customer')->check()) {
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user->id);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }

        // ✅ Add customer_id to details
        $cust_details = [
            'service_id'    => $servicedata->id,
            'type'          => 'vip',
            'package_id'    => $selectedPackage['package_id'],
            'product_id'    => $servicedata->product_id,
            'package_price' => $selectedPackage['package_price'],
            'package_name'  => $package->title,
            'noperson'      => $package->person,
            'person_phone'  => $personPhone,
            'person_name'   => $personName,
            'booking_date'  => $bookingDate,
            'customer_id'   => $customerId, // ✅ Key line
        ];
        $leads = Leads::create($cust_details);
        $insertedRowId = $leads->id;

        if (!empty($insertedRowId)) {
            $leadno = 'VIP' . (100 + $insertedRowId + 1);
        } else {
            $leadno = 'VIP' . (101);
        }
        Leads::where('id', $insertedRowId)->update(['leadno' => $leadno]);
        //  Get product_ids from lead
        $lead = Leads::find($insertedRowId);
        $productIds = json_decode($lead->product_id, true); // Example: ["550", "661"]

        if (!empty($productIds)) {
            $selectedProduct = null;
            foreach ($productIds as $pid) {
                $product = Product::where('id', $pid)->where('status', 1)->first();
                if ($product) {
                    $selectedProduct = $product;
                    break; // exit loop when found
                }
            }
            if ($selectedProduct) {
                $qty = 1;
                $price = $selectedProduct->unit_price;

                $productData = [
                    [
                        'product_id' => (string) $selectedProduct->id,
                        'price' => (string) $price,
                        'qty' => '1',
                    ],
                ];
                $lead->add_product_id = json_encode($productData);
                $lead->final_amount = $price;
                $lead->save();
                $product_store = [
                    'leads_id' => $lead->id,
                    'product_id' => $selectedProduct->id,
                    'final_price' => $price * $qty,
                    'qty' => $qty,
                    'product_name' => $selectedProduct->name,
                    'product_price' => $price
                ];
                ProductLeads::create($product_store);
            }
        }
        //--------
        $encodedId = base64_encode($insertedRowId);
        return redirect()->route('vip.order.book', ['encoded_id' => $encodedId]);
    }


    public function vip_order_book($encodedId)
    {
        $bookingDate = Session::get('booking_date');
        $id = base64_decode($encodedId);
        // $leadsDetails = Leads::join('vippoojas', 'leads.service_id', '=', 'vippoojas.id')
        //     ->leftJoin('products', 'leads.product_id', '=', 'products.id')
        //     ->select('leads.*', 'vippoojas.*', 'vippoojas.name as service_name', 'products.name as product_name')
        //     ->where('leads.id', $id)
        //     ->first();
        $leadsDetails = Leads::where('leads.id', $id)
            ->with('vippooja')->with('product')
            ->first();
        $leadsGet = Leads::where('id', $id)->with('productLeads')->with('service')->first();
        $userId = User::where('phone', $leadsGet['person_phone'])->first();
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        $prashadamList = $this->product->where('category_id', 53)->where('user_id', 14)->with(['reviews'])->active()->orderBy('id', 'desc')->take(8)->get();
        return view("web-views.vippooja.viporder-book", compact('leadsGet', 'userId', 'leadsDetails', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment', 'bookingDate', 'prashadamList'));
    }

    public function vipuser_detail($orderId)
    {
        $orderDetails = Service_order::where('order_id', $orderId)->where('type', 'vip')->with(['customers', 'vippoojas', 'packages', 'leads'])->first();
        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        $poojaDetail = Leads::join('vippoojas', 'leads.service_id', '=', 'vippoojas.id')
            ->leftJoin('products', 'leads.product_id', '=', 'products.id')
            ->select('leads.*', 'vippoojas.*', 'vippoojas.name as vippooja_name', 'products.name as product_name')
            ->where('leads.order_id', $orderId)
            ->first();
        // dd($poojaDetail);
        return view("web-views.vippooja.vipuser-detail", compact('orderDetails', 'citiesList', 'stateList', 'poojaDetail'));
    }

    public function vipuser_store(Request $request)
    {
        $cust_details = [
            'order_id' => $request->order_id,
            'newPhone' => $request->input('newPhone'),
            'gotra' => $request->input('gotra'),
            'pincode' => $request->input('pincode'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'house_no' => $request->input('house_no'),
            'area' => $request->input('area'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'landmark' => $request->input('landmark'),
            'members' => json_encode($request->input('members')),
            'is_prashad' => $request->input('is_prashad'),
            'type' => 'vip',
        ];
        $orderId = $request->order_id;
        $sankalpData = Service_order::where('order_id', $orderId)->update($cust_details);

        $serviceOrder = Service_order::where('order_id', $orderId)->first();
        if (!$serviceOrder) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        // Get Customer Info
        $customer = User::find($serviceOrder->customer_id);
        $full_name = '';
        if ($customer) {
            $full_name = trim($customer->f_name . ' ' . $customer->l_name);
            $full_name = $full_name ?: $customer->name ?: '';
        }

        // Create Devotee Record
        Devotee::create([
            'name'             => $full_name,
            'phone'            => $request->input('newPhone') ?: ($customer->phone ?? ''),
            'gotra'            => $request->input('gotra'),
            'service_order_id' => $orderId,
            'members'          => $request->has('members') ? json_encode($request->input('members')) : '[]',
            'address_city'     => $request->input('city'),
            'address_state'    => $request->input('state'),
            'house_no'         => $request->input('house_no'),
            'address_pincode'  => $request->input('pincode'),
            'area'             => $request->input('area'),
            'latitude'         => $request->input('latitude'),
            'longitude'        => $request->input('longitude'),
            'landmark'         => $request->input('landmark'),
            'is_prashad'       => $request->input('is_prashad'),
        ]);

        if (!$request->input('user_id')) {
            $userInfo = \App\Models\User::where('id', ($request->input('user_id') ?? ""))->update([
                'zip' => $request->input('pincode'),
                'city' => $request->input('city'),
                'house_no' => $request->input('house_no'),
                'street_address' => $request->input('area'),
            ]);
        }
        if ($request->input('is_prashad') == 1) {
            $prashad_order = [
                'seller_id' => $request->input('seller_id'),
                'order_id' => $request->input('order_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'service_id' => $request->input('service_id'),
                'user_id' =>   $request->input('user_id'),
                'product_id' => $request->input('product_id'),
                'type' => $request->input('type'),
                'payment_type' => $request->input('payment_type'),
                'booking_date' => $request->input('booking_date')
            ];
            $prashadOrder = Prashad_deliverys::create($prashad_order);
        }
        $sankalpData = Service_order::where('order_id', $orderId)->with(['customers', 'services', 'packages', 'leads'])->first();
        $serviceReview = ServiceReview::create([
            'order_id' => $orderId,
            'user_id' => $sankalpData['customer_id'],
            'service_id' => $sankalpData['service_id'],
            'service_type' => $sankalpData['type'],
            'rating' => '5',
        ]);

        $feedbackExists = UserFeedback::where('user_id', $sankalpData['customer_id'])->exists();
        if (!$feedbackExists) {
            UserFeedback::create([
                'user_id' => $sankalpData['customer_id'],
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status' => 1,
            ]);
        }
        $UsersData = Service_order::where('type', 'vip')->with(['customers', 'vippoojas', 'packages', 'leads'])->first();
        $membersList = json_decode($sankalpData['members'], true);
        $formattedMembers = $membersList ? implode(', ', $membersList) : 'No members specified';

        // whatsapp
        $userInfo = \App\Models\User::where('id', ($sankalpData['customer_id'] ?? ""))->first();
        $service_name = \App\Models\Vippooja::where('id', ($sankalpData['service_id'] ?? ""))->where('is_anushthan', 0)->first();
        $bookingDetails = \App\Models\Service_order::where('service_id', ($sankalpData['service_id'] ?? ""))->where('type', 'vip')
            ->where('booking_date', ($sankalpData['booking_date'] ?? ""))
            ->where('customer_id', ($sankalpData['customer_id'] ?? ""))
            ->where('order_id', ($orderId ?? ""))
            ->first();

        $message_data = [
            'service_name' => $sankalpData['vippoojas']['name'],
            'member_names' => $formattedMembers,
            'gotra' => $request->input('gotra'),
            'prashad' => $sankalpData['is_prashad'] == 1 ? 'Your Prasad is being prepared and will be dispatched to your address within 7-8 days, only if you selected "Yes" for receiving Prasad.' : '',
            'type' => 'text-with-media',
            'attachment' =>  asset('/storage/app/public/pooja/thumbnail/' . $service_name->thumbnail),
            'booking_date' => date('d-m-Y', strtotime($sankalpData['booking_date'])),
            'puja_venue' => $service_name['pooja_venue'],
            'puja' => 'VIP Puja',
            'orderId' => $orderId,
            'customer_id' => ($sankalpData['customer_id'] ?? ""),
        ];
        $messages =  Helpers::whatsappMessage('vipanushthan', 'Sankalp Information', $message_data);
        // Mail Setup for Pooja Management Send to  User Email Id
        if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $data['type'] = 'pooja';
            $data['email'] = $userInfo['email'];
            $data['subject'] = 'Information given by you for puja';
            $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-sankalp-template', compact('userInfo', 'service_name', 'bookingDetails', 'formattedMembers', 'request'))->render();

            Helpers::emailSendMessage($data);
        }

        return view('web-views.vippooja.viporder-place', compact('UsersData', 'orderId'));
    }



    //----------------ANUSHTHAN POOJA AND ANUSHTHAN DATE OF CREATED 22/07/2024--------------------------------

    public function anushthan_details(string $slug)
    {
        $anushthan = Vippooja::where('slug', $slug)->where('is_anushthan', 1)->first();
        if (!$anushthan) {
            return redirect()->to('/');
        }
        if ($anushthan) {
            $anushthan = Vippooja::select('vippoojas.*', 'vippoojas.name as anushthan_name')->where('id', $anushthan->id)->first();
        }
        $anushthanGet = Vippooja::select('vippoojas.*', 'packages.id as package_id', 'packages.title as package_name')
            ->leftJoin('packages', 'vippoojas.packages_id', '=', 'packages.id')->withCount('PoojaOrderReview')->withAvg('review', 'rating')
            ->where('vippoojas.id', $anushthan->id)
            ->get();
        $Faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
            $query->where('name', 'Anushthan');
        })->with('Category')->get();

       $allReviews = ServiceReview::with('userData')->get();

        $uniqueUserReviews = $allReviews
            ->groupBy('user_id')
            ->map(function ($userReviews) {
                return $userReviews->sortByDesc(function ($review) {
                    if (!empty($review->youtube_link)) return 3;
                    if (!empty($review->comment)) return 2;
                    return 1;
                })->first(); 
            })
            ->sortByDesc(function ($review) {
                if (!empty($review->youtube_link)) return 3;
                if (!empty($review->comment)) return 2;
                return 1;
            })
            ->values();

        $originalReviews = $uniqueUserReviews->where('is_edited', 0);
        $editedReviews = $uniqueUserReviews;
        $serviceReview = $originalReviews->count();

        $reviewCounts = [
            'excellent'     => $originalReviews->where('rating', 5)->count(),
            'good'          => $originalReviews->where('rating', 4)->count(),
            'average'       => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor'          => $originalReviews->filter(fn($review) => $review->rating == 1 && !is_null($review->comment))->count(),
            'averageStar'   => round($originalReviews->avg('rating'), 1),
            'list'          => $editedReviews->take(10),
        ];

        $totalReviews = $anushthanGet->sum('reviews_count');
        return view("web-views.aushthan.details", compact('anushthan', 'Faqs', 'anushthanGet', 'serviceReview', 'reviewCounts', 'totalReviews'));
    }

    public function anushthan_lead_store(Request $request)
    {
        $servicedata  = Vippooja::where('id',$request->service_id)->where('is_anushthan',1)->first();
         if (!$servicedata) {
            return redirect()->back()->with('error', 'Service not found.');
        }

        $packageList = json_decode($servicedata->packages_id, true);
        $selectedPackage = collect($packageList)->firstWhere('package_id', $request->package_id);
        if (!$selectedPackage) {
            return redirect()->back()->with('error', 'Invalid package selection.');
        }

        $package = \App\Models\Package::find($request->package_id);
        if (!$package) {
            return redirect()->back()->with('error', 'Package not found.');
        }

        // Input values
        $bookingDate  = $request->input('booking_date');
        $personName   = $request->input('person_name');
        $personPhone  = $request->input('person_phone');
        $verifyOTP    = $request->input('verify_otp');
        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0];
        $lastName  = implode(' ', array_slice($nameParts, 1));

        // Initialize customer_id
        $customerId = null;

        // Check user existence or create
        $userExists = User::where('phone', $request->person_phone)->exists();
        if (!$userExists) {
            $user = User::create([
                'name'       => $personName,
                'f_name'     => $firstName,
                'l_name'     => $lastName,
                'phone'      => $personPhone,
                'email'      => 'user@mahakal.com',
                'password'   => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ]);
            $customerId = $user->id;
            $data = ['customer_id' => $customerId];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        } else {
            $user = User::where('phone', $request->person_phone)->first();
            $customerId = $user->id ?? null;
        }

        // Login if not already
        if (!auth('customer')->check()) {
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user->id);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }

        // Add customer_id to details
        $cust_details = [
            'service_id'    => $servicedata->id,
            'type'          => 'anushthan',
            'platform'      => 'web',
            'package_id'    => $selectedPackage['package_id'],
            'product_id'    => $servicedata->product_id,
            'package_price' => $selectedPackage['package_price'],
            'package_name'  => $package->title,
            'noperson'      => $package->person,
            'person_phone'  => $personPhone,
            'person_name'   => $personName,
            'booking_date'  => $bookingDate,
            'customer_id'   => $customerId, 
        ];
        $leads = Leads::create($cust_details);
        $insertedRowId = $leads->id;
        $leadno = !empty($insertedRowId) ? 'APJ' . (100 + $insertedRowId + 1) : 'APJ101';
        Leads::where('id', $leads->id)->update(['leadno' => $leadno]);

        //  Get product_ids from lead
        $lead = Leads::find($insertedRowId);
        $productIds = json_decode($lead->product_id, true); // Example: ["550", "661"]

        if (!empty($productIds)) {
            $selectedProduct = null;
            foreach ($productIds as $pid) {
                $product = Product::where('id', $pid)->where('status', 1)->first();
                if ($product) {
                    $selectedProduct = $product;
                    break; // exit loop when found
                }
            }
            if ($selectedProduct) {
                $qty = 1;
                $price = $selectedProduct->unit_price;

                $productData = [
                    [
                        'product_id' => (string) $selectedProduct->id,
                        'price' => (string) $price,
                        'qty' => '1',
                    ],
                ];
                $lead->add_product_id = json_encode($productData);
                $lead->final_amount = $price;
                $lead->save();
                $product_store = [
                    'leads_id' => $lead->id,
                    'product_id' => $selectedProduct->id,
                    'final_price' => $price * $qty,
                    'qty' => $qty,
                    'product_name' => $selectedProduct->name,
                    'product_price' => $price
                ];
                ProductLeads::create($product_store);
            }
        }
        $encodedId = base64_encode($insertedRowId);
        return redirect()->route('anushthan.order.book', ['encoded_id' => $encodedId]);
    }

    public function anushthan_order_book($encodedId)
    {
        $bookingDate = Session::get('booking_date');
        $id = base64_decode($encodedId);
        // $leadsDetails = Leads::join('vippoojas', 'leads.service_id', '=', 'vippoojas.id')
        //     ->leftJoin('products', 'leads.product_id', '=', 'products.id')
        //     ->select('leads.*', 'vippoojas.*', 'vippoojas.name as service_name', 'products.name as product_name')
        //     ->where('leads.id', $id)
        //     ->first();
        $leadsDetails = Leads::where('leads.id', $id)
            ->with('vippooja')->with('product')
            ->first();
        $leadsGet = Leads::where('id', $id)->with('productLeads')->with('service')->first();
        // dd($leadsGet);
        $userId = User::where('phone', $leadsGet['person_phone'])->first();
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        $prashadamList = $this->product->where('category_id', 53)->where('user_id', 14)->with(['reviews'])->active()->orderBy('id', 'desc')->take(8)->get();
        return view("web-views.aushthan.anushthanorder-book", compact('leadsGet', 'userId', 'leadsDetails', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment', 'bookingDate', 'prashadamList'));
    }
    public function anushthanuser_detail($orderId)
    {
        $orderDetails = Service_order::where('order_id', $orderId)->where('type', 'anushthan')->with(['customers', 'vippoojas', 'packages', 'leads'])->first();
        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        // dd($orderDetails);
        $poojaDetail = Leads::join('vippoojas', 'leads.service_id', '=', 'vippoojas.id')
            ->leftJoin('products', 'leads.product_id', '=', 'products.id')
            ->select('leads.*', 'vippoojas.*', 'vippoojas.name as vippooja_name', 'products.name as product_name')
            ->where('leads.order_id', $orderId)
            ->first();
        return view("web-views.aushthan.anushthanuser-detail", compact('orderDetails', 'citiesList', 'stateList', 'poojaDetail'));
    }
    public function anushthanuser_store(Request $request)
    {
        $cust_details = [
            'newPhone' => $request->input('newPhone'),
            'gotra' => $request->input('gotra'),
            'pincode' => $request->input('pincode'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'house_no' => $request->input('house_no'),
            'area' => $request->input('area'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'landmark' => $request->input('landmark'),
            'members' => json_encode($request->input('members')),
            'is_prashad' => $request->input('is_prashad'),
        ];
        $orderId = $request->order_id;
        $sankalpData = Service_order::where('order_id', $orderId)->update($cust_details);
        if (!$request->input('user_id')) {
            $userInfo = \App\Models\User::where('id', ($request->input('user_id') ?? ""))->update([
                'zip' => $request->input('pincode'),
                'city' => $request->input('city'),
                'house_no' => $request->input('house_no'),
                'street_address' => $request->input('area'),
            ]);
        }
        if ($request->input('is_prashad') == 1) {
            $prashad_order = [
                'seller_id' => $request->input('seller_id'),
                'order_id' => $request->input('order_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'service_id' => $request->input('service_id'),
                'user_id' =>   $request->input('user_id'),
                'product_id' => $request->input('product_id'),
                'type' => $request->input('type'),
                'payment_type' => $request->input('payment_type'),
                'booking_date' => $request->input('booking_date')
            ];
            $prashadOrder = Prashad_deliverys::create($prashad_order);
        }
        $sankalpData = Service_order::where('order_id', $orderId)->with(['customers', 'services', 'packages', 'leads'])->first();
        $serviceReview = ServiceReview::create([
            'order_id' => $orderId,
            'user_id' => $sankalpData['customer_id'],
            'service_id' => $sankalpData['service_id'],
            'service_type' => $sankalpData['type'],
            'rating' => '5',
        ]);

        $feedbackExists = UserFeedback::where('user_id', $sankalpData['customer_id'])->exists();
        if (!$feedbackExists) {
            UserFeedback::create([
                'user_id' => $sankalpData['customer_id'],
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status' => 1,
            ]);
        }
        $UsersData = Service_order::where('type', 'anushthan')->with(['customers', 'vippoojas', 'packages', 'leads'])->first();
        $membersList = json_decode($sankalpData['members'], true);
        $formattedMembers = $membersList ? implode(', ', $membersList) : 'No members specified';

        // whatsapp
        $userInfo = \App\Models\User::where('id', ($sankalpData['customer_id'] ?? ""))->first();
        $service_name = \App\Models\Vippooja::where('id', ($sankalpData['service_id'] ?? ""))->where('is_anushthan', 1)->first();
        $bookingDetails = \App\Models\Service_order::where('service_id', ($sankalpData['service_id'] ?? ""))->where('type', 'anushthan')
            ->where('booking_date', ($sankalpData['booking_date'] ?? ""))
            ->where('customer_id', ($sankalpData['customer_id'] ?? ""))
            ->where('order_id', ($orderId ?? ""))
            ->first();

        $message_data = [
            'service_name' => $sankalpData['vippoojas']['name'],
            'member_names' => $formattedMembers,
            'gotra' => $request->input('gotra'),
            'prashad' => $sankalpData['is_prashad'] == 1 ? 'Your Prasad is being prepared and will be dispatched to your address within 7-8 days, only if you selected "Yes" for receiving Prasad.' : '',
            'type' => 'text-with-media',
            'attachment' =>  asset('/storage/app/public/pooja/vip/thumbnail/' . $service_name->thumbnail),
            'booking_date' => date('d-m-Y', strtotime($sankalpData['booking_date'])),
            'puja' => 'Anushthan',
            'orderId' => $orderId,
            'customer_id' => ($sankalpData['customer_id'] ?? ""),
        ];

        $messages =  Helpers::whatsappMessage('vipanushthan', 'Sankalp Information', $message_data);
        // Mail Setup for Pooja Management Send to  User Email Id
        if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $data['type'] = 'pooja';
            $data['email'] = $userInfo['email'];
            $data['subject'] = 'Information given by you for puja';
            $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-sankalp-template', compact('userInfo', 'service_name', 'bookingDetails', 'formattedMembers', 'request'))->render();

            Helpers::emailSendMessage($data);
        }
        return view('web-views.aushthan.anushthanorder-place', compact('UsersData', 'orderId'));
    }

    // -------------------------------------CHADHAVA CONROLLER FUNCTION 01/08/2024---------------------------------------------------------
    public function chadhava_details($slug)
    {        
        // Step 1: Get today and 7-day cutoff
    $today = Carbon::today();
    $cutoff = $today->copy()->addDays(7);

    // Step 2: Fetch single chadhava detail by slug with filters
    $chadhavaDetails = Chadhava::where('slug', $slug)
        ->where('status', 1)
        ->where(function ($query) use ($today) {
            $query->where(function ($q) use ($today) {
                $q->where('chadhava_type', 1)
                ->where('end_date', '>=', $today->toDateString());
            })->orWhere('chadhava_type', 0);
        })
        ->withCount('PoojaOrderReview')
        ->withAvg('review', 'rating')
        ->first();

    // Step 3: Check if not found
    if (!$chadhavaDetails) {
        abort(404, 'Chadhava not found');
    }

    // Step 4: Get next available date (within 7 days)
    $nextDate = $chadhavaDetails->getNextAvailableDate($cutoff);

    // Optional: Abort if no valid date within next 7 days
    if (!$nextDate) {
        abort(404, 'No upcoming dates available for this Chadhava');
    }

        $chadhavaGet = Chadhava::where('id', $chadhavaDetails->id)->where('status',1)->withCount('PoojaOrderReview')->withAvg('review', 'rating')->get();
        $Faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
            $query->where('name', 'Chadhava');
        })->with('Category')->get();
        $allReviews = ServiceReview::with('userData')->get();
        $uniqueUserReviews = $allReviews
            ->groupBy('user_id')
            ->map(function ($userReviews) {
                return $userReviews->sortByDesc(function ($review) {
                    if (!empty($review->youtube_link)) return 3;
                    if (!empty($review->comment)) return 2;
                    return 1;
                })->first(); 
            })
            ->sortByDesc(function ($review) {
                if (!empty($review->youtube_link)) return 3;
                if (!empty($review->comment)) return 2;
                return 1;
            })
            ->values();

        $originalReviews = $uniqueUserReviews->where('is_edited', 0);
        $editedReviews = $uniqueUserReviews;
        $serviceReview = $originalReviews->count();

        $reviewCounts = [
            'excellent'     => $originalReviews->where('rating', 5)->count(),
            'good'          => $originalReviews->where('rating', 4)->count(),
            'average'       => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor'          => $originalReviews->filter(fn($review) => $review->rating == 1 && !is_null($review->comment))->count(),
            'averageStar'   => round($originalReviews->avg('rating'), 1),
            'list'          => $editedReviews->take(10),
        ];

        $totalReviews = $chadhavaGet->sum('reviews_count');
        return view('web-views.chadhava.details', compact('chadhavaDetails','chadhavaGet','Faqs', 'serviceReview', 'reviewCounts', 'totalReviews', 'nextDate'));
    }
    
    public function addChadhavaProduct(Request $request)
    {
        // Session::put('chadhava_products', null);
        // Session::forget('chadhava_products');
        $products = Session::get('chadhava_products');
        // echo "<pre>"; print_r($products);die;
        if (!empty($products['products']) && $request->chadhavaid == $products['chadhavaid']) {
            $oldProd = [];
            $oldProd['chadhavaid'] = $products['chadhavaid'];
            $i = 0;
            foreach ($products['products'] as $prod) {
                $oldProd['products'][$i] = $prod;
                $i++;
            }
            // print_r($oldProd);
            $oldProd['products'][$i] = [
                'product_id' => $request->productid,
                'name' => $request->name,
                'price' => $request->price,
                'qtymin' => $request->qtymin,
                'event' => $request->event
            ];
            // echo "<pre>"; print_r($oldProd);die;
            Session::put('chadhava_products', $oldProd);
        } else {
            $products['chadhavaid'] = $request->chadhavaid;
            $products['products'][0] = [
                'product_id' => $request->productid,
                'name' => $request->name,
                'price' => $request->price,
                'qtymin' => $request->qtymin,
                'event' => $request->event
            ];
            Session::put('chadhava_products', $products);
        }
        $dataprod = Session::get('chadhava_products');
        $totalPrice = array_sum(array_column($dataprod['products'], 'price'));
        return response()->json([
            'count' => count($dataprod['products']),
            'final_price' => $totalPrice,
            'qty' => $request->qtymin,
            'chadhavaId' => $dataprod['chadhavaid'],
            'product_id' => $request->productid,
            'product_name' => $request->name,
            'product_price' => $request->price,
        ]);
    }
    public function DeleteProductChadhava(Request $request)
    {
        $chadhavaId = $request->input('chadhavaid');
        $productId = $request->input('productid');
        $products = Session::get('chadhava_products');
        dd($products);
        // if (!empty($products['products']) && $chadhavaId == $products['chadhavaid']) {
        //     $newProducts = array_filter($products['products'], function($product) use ($productId) {
        //         return $product['product_id'] != $productId;
        //     });
        //     $products['products'] = array_values($newProducts); 
        //     if (empty($products['products'])) {
        //         Session::forget('chadhava_products');
        //     } else {
        //         Session::put('chadhava_products', $products);
        //     }
        // }
        // $dataprod = Session::get('chadhava_products', []);
        // $totalPrice = !empty($dataprod) ? array_sum(array_column($dataprod['products'], 'price')) : 0;

        return response()->json([
            'count' => !empty($dataprod['products']) ? count($dataprod['products']) : 0,
            'productId' => $productId,
            'totalPrice' => $totalPrice,
            'chadhavaId' => !empty($dataprod) ? $dataprod['chadhavaid'] : null,
        ]);
    }

    public function UpdateProductChadhava(Request $request)
    {
        $chadhavaId = $request->input('chadhavaid');
        $productId = $request->input('productid');
        $name = $request->input('name');
        $price = $request->input('price');
        $event = $request->input('event');
        $quantity = $request->input('quantity');
        $products = Session::get('chadhava_products', []);
        if (isset($products['chadhavaid']) && $products['chadhavaid'] == $chadhavaId) {
            $productFound = false;
            foreach ($products['products'] as &$prod) {
                if ($prod['product_id'] == $productId) {
                    $prod['name'] = $name;
                    $prod['price'] = $price;
                    $prod['event'] = $event;
                    $prod['quantity'] = $quantity;
                    $prod['final_price'] = $price * $quantity;
                    $productFound = true;
                    break;
                }
            }
            if (!$productFound) {
                $products['products'][] = [
                    'product_id' => $productId,
                    'name' => $name,
                    'price' => $price,
                    'event' => $event,
                    'quantity' => $quantity,
                    'final_price' => $price * $quantity
                ];
            }
        } else {
            // Create new chadhava with first product
            $products = [
                'chadhavaid' => $chadhavaId,
                'products' => [[
                    'product_id' => $productId,
                    'name' => $name,
                    'price' => $price,
                    'event' => $event,
                    'quantity' => $quantity,
                    'final_price' => $price * $quantity
                ]]
            ];
        }
        Session::put('chadhava_products', $products);
        $dataprod = Session::get('chadhava_products');
        $totalPrice = array_sum(array_column($dataprod['products'], 'final_price'));

        return response()->json([
            'count' => count($dataprod['products']),
            'qty' => $quantity,
            'totalPrice' => $totalPrice,
            'chadhavaId' => $dataprod['chadhavaid'],
            'productId' => $productId,
        ]);
    }
    public function chadhava_lead_store(Request $request)
    {
        // Fetch Chadhava Service
        $chadhavaName = Chadhava::where('id', $request->input('service_id'))
            ->where('status', 1)
            ->first();
        if (!$chadhavaName) {
            return redirect()->back()->with('error', 'Service not found.');
        }

        $bookingDate  = $request->input('booking_date');
        $personName   = $request->input('person_name');
        $personPhone  = $request->input('person_phone');
        $verifyOTP    = $request->input('verify_otp');

        // Split Name
        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0] ?? '';
        $lastName  = implode(' ', array_slice($nameParts, 1)) ?? '';

        // Initialize user
        $user = User::where('phone', $personPhone)->first();
        if (!$user) {
            $cust_details = [
                'name'       => $personName,
                'f_name'     => $firstName,
                'l_name'     => $lastName,
                'phone'      => $personPhone,
                'email'      => $personPhone, // assuming email = phone
                'password'   => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ];
            $user = User::create($cust_details);

            // Send Welcome Message
            $data = ['customer_id' => $user->id];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }

        // Auto Login if not logged in
        if (!auth('customer')->check()) {
            Auth::guard('customer')->loginUsingId($user->id);

            // Set session data
            $wish_list = Wishlist::whereHas('wishlistProduct')
                ->where('customer_id', $user->id)
                ->pluck('product_id')
                ->toArray();

            $compare_list = ProductCompare::where('user_id', $user->id)
                ->pluck('product_id')
                ->toArray();

            session()->put('wish_list', $wish_list);
            session()->put('compare_list', $compare_list);
            Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
            CartManager::cart_to_db();

            // Reset login block flags
            $user->login_hit_count = 0;
            $user->is_temp_blocked = 0;
            $user->temp_block_time = null;
            $user->save();
        }

        // Lead insert
        $lead_details = [
            'service_id'    => $chadhavaName->id,
            'type'          => 'chadhava',
            'platform'      => 'web',
            'product_id'    => $chadhavaName->product_id,
            'package_name'  => $chadhavaName->name,
            'person_phone'  => $personPhone,
            'person_name'   => $personName,
            'booking_date'  => $bookingDate,
            'customer_id'   => $user->id,
        ];
        $leads = Leads::create($lead_details);
        $insertedRowId = $leads->id;

        // Generate Lead Number
        $leadno = 'CC' . (100 + $insertedRowId + 1);
        Leads::where('id', $insertedRowId)->update(['leadno' => $leadno]);

        // Process Product IDs
        $lead = Leads::find($insertedRowId);
        $productIds = json_decode($lead->product_id, true);
        if (!is_array($productIds)) {
            $productIds = [$lead->product_id];
        }

        if (!empty($productIds)) {
            foreach ($productIds as $pid) {
                $product = Product::where('id', $pid)->where('status', 1)->first();
                if ($product) {
                    $qty = 1;
                    $price = $product->unit_price;
                    $productData = [
                        [
                            'product_id' => (string) $product->id,
                            'price'      => (string) $price,
                            'qty'        => '1',
                        ],
                    ];
                    $lead->add_product_id = json_encode($productData);
                    $lead->final_amount = $price;
                    $lead->save();

                    $product_store = [
                        'leads_id'     => $lead->id,
                        'product_id'   => $product->id,
                        'final_price'  => $price * $qty,
                        'qty'          => $qty,
                        'product_name' => $product->name,
                        'product_price'=> $price
                    ];
                    ProductLeads::create($product_store);
                    break;
                }
            }
        }

        // Redirect
        $encodedId = base64_encode($insertedRowId);
        return redirect()->route('chadhava.order.book', ['encoded_id' => $encodedId]);
    }

    public function chadhavaOrderBook($encodedId)
    {
        $bookingDate = Session::get('booking_date');
        $id = base64_decode($encodedId);
        $chadhavaLeads = Leads::join('chadhava', 'leads.service_id', '=', 'chadhava.id')
            ->leftJoin('products', 'leads.product_id', '=', 'products.id')
            ->select('leads.*', 'chadhava.*', 'chadhava.name as chadhava_name', 'products.name as product_name')
            ->where('leads.id', $id)
            ->first();
        $leadsGet = Leads::where('id', $id)->with('productLeads')->with('service')->first();
        if (!$leadsGet) {
             return back();
        }
        $userId = User::where('phone', $leadsGet['person_phone'])->first();
        if (!$userId) {
            return back();
        }
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        return view("web-views.chadhava.chadhavaorder-book", compact('leadsGet', 'userId', 'chadhavaLeads', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment', 'bookingDate'));
    }
    public function chadhavaUserDetail($orderId)
    {
        $orderDetails = Chadhava_orders::where('order_id', $orderId)->where('type', 'chadhava')->with(['customers', 'chadhava', 'leads'])->first();
        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        $ChadhavaDetail = Leads::join('chadhava', 'leads.service_id', '=', 'chadhava.id')
            ->leftJoin('products', 'leads.product_id', '=', 'products.id')
            ->select('leads.*', 'chadhava.*', 'chadhava.name as chadhava_name', 'products.name as product_name')
            ->where('leads.order_id', $orderId)
            ->first();

        return view("web-views.chadhava.chadhavauser-details", compact('orderDetails', 'citiesList', 'stateList', 'ChadhavaDetail'));
    }
    public function chadhavaUserStore(Request $request)
    {
        $cust_details = [
            'newPhone' => $request->input('newPhone'),
            'gotra' => $request->input('gotra'),
            'members' => $request->input('members'),
            'reason' => $request->input('reason'),
        ];
        $orderId = $request->order_id;


        $sankalpData = Chadhava_orders::where('order_id', $orderId)->update($cust_details);
        $service_name = \App\Models\Chadhava::where('id', ($sankalpData['service_id'] ?? ""))->where('chadhava_type', 0)->first();
        $UsersData = Chadhava_orders::where('type', 'chadhava')->where('order_id', $orderId)->with(['customers', 'chadhava', 'packages', 'leads'])->first();
        $serviceReview = ServiceReview::create([
            'order_id' => $request->order_id,
            'user_id' => $UsersData['customer_id'],
            'service_id' =>  $UsersData['service_id'],
            'service_type' => $UsersData['type'],
            'rating' => '5',
        ]);

        $feedbackExists = UserFeedback::where('user_id', $UsersData['customer_id'])->exists();
        if (!$feedbackExists) {
            UserFeedback::create([
                'user_id' => $UsersData['customer_id'],
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status' => 1,
            ]);
        }
        // whatsapp
        $userInfo = \App\Models\User::where('id', ($UsersData['customer_id'] ?? ""))->first();
        $bookingDetails = \App\Models\Chadhava_orders::where('service_id', ($UsersData['service_id'] ?? ""))->where('type', 'chadhava')
            ->where('booking_date', ($UsersData['booking_date'] ?? ""))
            ->where('customer_id', ($UsersData['customer_id'] ?? ""))
            ->where('order_id', ($orderId ?? ""))
            ->first();

        $message_data = [
            'service_name' => $UsersData['chadhava']['name'],
            'member_names' => $UsersData['members'],
            'gotra' => $request->input('gotra'),
            'type' => 'text-with-media',
            'attachment' =>  asset('/storage/app/public/chadhava/thumbnail/' . $UsersData['chadhava']['thumbnail']),
            'booking_date' => date('d-m-Y', strtotime($UsersData['booking_date'])),
            'orderId' => $orderId,
            'prashad' => $UsersData['is_prashad'] == 1 ? 'Your Prasad is being prepared and will be dispatched to your address within 7-8 days, only if you selected "Yes" for receiving Prasad.' : '',
            'customer_id' => ($UsersData['customers']['id'] ?? ""),
        ];

        $messages =  Helpers::whatsappMessage('chadhava', 'Sankalp Information', $message_data);

        // Mail Setup for Pooja Management Send to  User Email Id
        if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $data['type'] = 'chadhava';
            $data['email'] = $userInfo['email'];
            $data['subject'] = 'Information given by you for chadhava';
            $data['htmlContent'] = \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-sankalp-template', compact('userInfo', 'UsersData', 'service_name', 'bookingDetails', 'request'))->render();

            Helpers::emailSendMessage($data);
        }
        return view('web-views.chadhava.chadhavaorder-place', compact('UsersData', 'orderId'));
    }

    public function counselling_store_customer(Request $request)
    {
        //user insert
        $userExists = User::where('phone', $request->person_phone)->exists();
        if (!$userExists) {
            $name = explode(' ', $request->person_name);
            $cust_details = [
                'name' => $request->person_name,
                'f_name' => isset($name[0]) ? $name[0] : '',
                'l_name' => isset($name[1]) ? $name[1] : '',
                'phone' => $request->person_phone,
                'email' => 'user@mahakal.com',
                'password' => bcrypt('12345678'),
            ];
            $user = User::create($cust_details);
            $data = [
                'customer_id' => ($user->id ?? "")
            ];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }

        if (!auth('customer')->check()) {
            $user = User::where(['phone' => $request->person_phone])->first();
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user['id']);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }
        return redirect()->to(url()->previous());
    }

    // offline pooja functions
    public function offline_pooja_all()
    {
        $allOfflinepooja = PoojaOffline::where('status', 1)->withCount('offlinePoojaOrder')
            ->withAvg('review', 'rating')->get();
        $offlinepoojaCategory = OfflinepoojaCategory::where('status', 1)->get();
        $offlinepooja = [];
        foreach ($offlinepoojaCategory as $opCategory) {
            $offlinepoojas[$opCategory->name] = PoojaOffline::where('type', $opCategory->id)->withCount('offlinePoojaOrder')
                ->withAvg('review', 'rating')->where('status', 1)->get();
        }
        return view("web-views.offlinepooja.all", compact('offlinepoojaCategory', 'allOfflinepooja', 'offlinepoojas'));
    }

    public function offline_pooja_detail($slug)
    {
        $details = PoojaOffline::where('slug', $slug)->first();
        if (!$details) {
            return redirect(url('/'));
        }
        // if ($details) {
        //     $vip = Vippooja::select('vippoojas.*', 'vippoojas.name as vip_name')->where('id', $vip->id)->first();
        // }
        $refundPolicy = OfflinepoojaRefundPolicy::where('status', 1)->get();
        $schedulePolicy = OfflinepoojaSchedule::where('status', 1)->get();
        $detailsGet = PoojaOffline::select('pooja_offlines.*', 'packages.id as package_id', 'packages.title as package_name')
            ->leftJoin('packages', 'pooja_offlines.package_details', '=', 'packages.id')
            ->where('pooja_offlines.id', $details->id)
            ->get();
        $Faqs = \App\Models\FAQ::whereHas('Category', function ($query) {
            $query->where('name', 'Pandit Booking');
        })->with('Category')->get();
        $reviews = OfflinepoojaReview::where('service_id', $details->id)
            ->with(['userData'])
            ->orderByDesc('id')
            ->get();

        // Separate reviews
        $originalReviews = $reviews->where('is_edited', 0);
        $editedReviews = $reviews->where('is_edited', 1);

        // Calculate ratings using only non-edited reviews
        $serviceReview = $originalReviews->count();

        $reviewCounts = [
            'excellent' => $originalReviews->where('rating', 5)->count(),
            'good' => $originalReviews->where('rating', 4)->count(),
            'average' => $originalReviews->where('rating', 3)->count(),
            'below_average' => $originalReviews->where('rating', 2)->count(),
            'poor' => $originalReviews->where('rating', 1)->count(),
            'averageStar' => $originalReviews->avg('rating'),
            'list' => $editedReviews, // only edited reviews for listing
        ];


        // Calculating total reviews across all products
        $totalReviews = 0;
        foreach ($detailsGet as $item) {
            $totalReviews += $item->reviews_count;
        }
        return view("web-views.offlinepooja.details", compact('details', 'refundPolicy', 'schedulePolicy', 'Faqs', 'detailsGet', 'serviceReview', 'reviewCounts', 'totalReviews'));
    }

    public function offline_pooja_lead_store(Request $request)
    {
        $leadLastId = OfflineLead::select('id')->latest()->first();
        if (!empty($leadLastId['id'])) {
            $leadNo = 'OPLN' . (100000 + $leadLastId['id'] + 1);
        } else {
            $leadNo = 'OPLN' . (100001);
        }
        $cust_details = [
            'pooja_id' => $request->input('service_id'),
            'lead_no' => $leadNo,
            'package_id' => $request->input('package_id'),
            'package_name' => $request->input('package_name'),
            'package_main_price' => $request->input('package_main_price'),
            'package_price' => $request->input('package_price'),
            'noperson' => $request->input('noperson'),
            'person_name' => $request->input('person_name'),
            'person_phone' => $request->input('person_phone'),
        ];

        $personName = $request->input('person_name');
        $personPhone = $request->input('person_phone');
        $verifyOTP = $request->input('verify_otp');
        $nameParts = explode(' ', $personName);
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));
        $userExists = User::where('phone', $request->person_phone)->exists();
        if (!$userExists) {
            $user = User::create([
                'name' => $personName,
                'f_name' => $firstName,
                'l_name' => $lastName,
                'phone' => $personPhone,
                'email' => 'user@mahakal.com',
                'password' => bcrypt('12345678'),
                'verify_otp' => $verifyOTP,
            ]);
            $data = [
                'customer_id' => ($user->id ?? "")
            ];
            Helpers::whatsappMessage('whatsapp', 'Welcome Message', $data);
        }
        if (!auth('customer')->check()) {
            $user = User::where(['phone' => $request->person_phone])->first();
            if ($user) {
                $auth = Auth::guard('customer')->loginUsingId($user['id']);
                if ($auth) {
                    $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
                        return $q;
                    })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

                    $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

                    session()->put('wish_list', $wish_list);
                    session()->put('compare_list', $compare_list);
                    Toastr::info(translate('welcome_to') . ' ' . Helpers::get_business_settings('company_name') . '!');
                    CartManager::cart_to_db();

                    $user->login_hit_count = 0;
                    $user->is_temp_blocked = 0;
                    $user->temp_block_time = null;
                    $user->updated_at = now();
                    $user->save();
                }
            }
        }

        $leads = OfflineLead::create($cust_details);
        $insertedRowId = $leads->id;
        $encodedId = base64_encode($insertedRowId);
        // print_r($leads);die;
        return redirect()->route('offline.pooja.order.book', ['encoded_id' => $encodedId]);
    }

    public function offline_pooja_order_book($encodedId)
    {
        $id = base64_decode($encodedId);
        $leadsDetails = OfflineLead::where('id', $id)->with('offlinePooja')->first();
        // $leadsGet = OfflineLead::where('id', $id)->with('productLeads')->with('service')->first();
        $userId = User::where('phone', $leadsDetails['person_phone'])->first();
        $paymentPublishedStatus = config('get_payment_publish_status');
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;
        $payment_gateways_list = payment_gateways();
        $digital_payment = getWebConfig(name: 'digital_payment');
        // $couponDiscount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        // $amount = ProductLeads::where('leads_id', $encodedId)->groupBy('leads_id')->sum('final_price');
        return view("web-views.offlinepooja.order-book", compact('userId', 'leadsDetails', 'payment_gateways_list', 'paymentGatewayPublishedStatus', 'digital_payment'));
    }

    public function offline_pooja_user_detail($orderId)
    {
        $orderDetails = OfflinePoojaOrder::where('order_id', $orderId)->with(['customers', 'offlinePooja', 'leads', 'package'])->first();
        $citiesList = Cities::orderBy('city', 'asc')->get();
        $stateList = States::orderBy('name', 'asc')->get();
        $poojaDetail = Leads::join('pooja_offlines', 'leads.service_id', '=', 'pooja_offlines.id')
            ->select('leads.*', 'pooja_offlines.*', 'pooja_offlines.name as offlinepooja_name')
            ->where('leads.order_id', $orderId)
            ->first();
        // dd($orderDetails);
        return view("web-views.offlinepooja.user-detail", compact('orderDetails', 'citiesList', 'stateList', 'poojaDetail'));
    }


    public function offline_pooja_user_store(Request $request)
    {
        $cust_details = [
            'order_id' => $request->order_id,
            'new_phone' => $request->input('newPhone'),
            'booking_date' => $request->input('booking_date'),
            'state' => $request->input('state'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
            'venue_address' => $request->input('venue_address'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'landmark' => $request->input('landmark'),
            'is_edited' => 1,
        ];
        // dd($cust_details);
        $orderId = $request->order_id;
        $sankalpData = OfflinePoojaOrder::where('order_id', $orderId)->update($cust_details);

        $orderDetail = OfflinePoojaOrder::where('order_id', $orderId)->first();
        OfflinepoojaReview::create([
            'order_id' => $orderId,
            'user_id' => $orderDetail->customer_id,
            'service_id' => $orderDetail->service_id,
            'service_type' => 'pooja',
            'rating' => 5,
        ]);

        $feedbackExists = UserFeedback::where('user_id', $orderDetail->customer_id)->exists();
        if (!$feedbackExists) {
            UserFeedback::create([
                'user_id' => $orderDetail->customer_id,
                'message' => \App\Utils\getRandomFeedbackMessage(),
                'status' => 1,
            ]);
        }
        return view('web-views.offlinepooja.order-place', compact('orderId'));
    }
}