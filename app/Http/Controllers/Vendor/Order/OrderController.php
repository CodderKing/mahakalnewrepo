<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\DeliveryCountryCodeRepositoryInterface;
use App\Contracts\Repositories\DeliveryManRepositoryInterface;
use App\Contracts\Repositories\DeliveryManTransactionRepositoryInterface;
use App\Contracts\Repositories\DeliveryManWalletRepositoryInterface;
use App\Contracts\Repositories\DeliveryZipCodeRepositoryInterface;
use App\Contracts\Repositories\LoyaltyPointTransactionRepositoryInterface;
use App\Contracts\Repositories\OrderDetailRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\OrderStatusHistoryRepositoryInterface;
use App\Contracts\Repositories\OrderTransactionRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\GlobalConstant;
use App\Enums\ViewPaths\Vendor\Order;
use App\Enums\WebConfigKey;
use App\Events\OrderStatusEvent;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDigitalFileAfterSellRequest;
use App\Repositories\WalletTransactionRepository;
use App\Services\DeliveryCountryCodeService;
use App\Services\DeliveryManTransactionService;
use App\Services\DeliveryManWalletService;
use App\Services\OrderStatusHistoryService;
use App\Traits\CustomerTrait;
use App\Traits\FileManagerTrait;
use App\Traits\PdfGenerator;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View as PdfView;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Order as ModelsOrder;
use App\Models\Order_Pickup;
use App\Models\OrderDetail;
use App\Models\SellerWallet;
use App\Utils\Helpers;

class OrderController extends BaseController
{
    use CustomerTrait;
    use PdfGenerator;
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }

    public function __construct(
        private readonly OrderRepositoryInterface                   $orderRepo,
        private readonly CustomerRepositoryInterface                $customerRepo,
        private readonly VendorRepositoryInterface                  $vendorRepo,
        private readonly DeliveryManRepositoryInterface             $deliveryManRepo,
        private readonly DeliveryCountryCodeRepositoryInterface     $deliveryCountryCodeRepo,
        private readonly DeliveryZipCodeRepositoryInterface         $deliveryZipCodeRepo,
        private readonly OrderDetailRepositoryInterface             $orderDetailRepo,
        private readonly WalletTransactionRepository                $walletTransactionRepo,
        private readonly DeliveryManWalletRepositoryInterface       $deliveryManWalletRepo,
        private readonly DeliveryManTransactionRepositoryInterface  $deliveryManTransactionRepo,
        private readonly OrderStatusHistoryRepositoryInterface      $orderStatusHistoryRepo,
        private readonly OrderTransactionRepositoryInterface        $orderTransactionRepo,
        private readonly LoyaltyPointTransactionRepositoryInterface $loyaltyPointTransactionRepo,
    ) {}

    /**
     * @param Request|null $request
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View|Collection|LengthAwarePaginator|callable|RedirectResponse|null
     */
    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getListView(request: $request);
    }

    public function getListView(object $request): View
    {
        $seller = auth('seller')->user();
        $vendorId = $seller['id'];
        $searchValue = $request['searchValue'];
        $filter = $request['filter'];
        $dateType = $request['date_type'];
        $from = $request['from'];
        $to = $request['to'];
        $status = $request['status'];
        $deliveryManId = $request['delivery_man_id'];
        $this->orderRepo->updateWhere(params: ['seller_id' => $vendorId, 'checked' => 0], data: ['checked' => 1]);
        $sellerPos = getWebConfig(name: 'seller_pos');

        $relation = ['customer', 'shipping', 'shippingAddress', 'deliveryMan', 'billingAddress'];
        $filters = [
            'order_status' => $status,
            'order_type' => $request['filter'],
            'date_type' => $dateType,
            'from' => $request['from'],
            'to' => $request['to'],
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'],
            'seller_id' => $vendorId,
            'seller_is' => 'seller',
        ];
        $orders = $this->orderRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $searchValue, filters: $filters, relations: $relation, dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));
        $sellers = $this->vendorRepo->getByStatusExcept(status: 'pending', relations: ['shop']);

        $customer = "all";
        if (isset($request['customer_id']) && $request['customer_id'] != 'all' && !is_null($request->customer_id) && $request->has('customer_id')) {
            $customer = $this->customerRepo->getFirstWhere(params: ['id' => $request['customer_id']]);
        }

        $vendorId = $request['seller_id'];
        $customerId = $request['customer_id'];

        return view(Order::LIST[VIEW], compact(
            'orders',
            'searchValue',
            'from',
            'to',
            'filter',
            'sellers',
            'customer',
            'vendorId',
            'customerId',
            'dateType',
            'searchValue',
            'status',
            'seller',
            'customer',
            'sellerPos',
            'deliveryManId'
        ));
    }

    public function exportList(Request $request, $status): StreamedResponse|string|RedirectResponse
    {
        $vendorId = auth('seller')->id();
        $searchValue = $request['searchValue'];
        $status = $request['status'];
        $relation = ['customer', 'shipping', 'shippingAddress', 'deliveryMan', 'billingAddress'];

        $filters = [
            'order_status' => $status,
            'filter' => $request['filter'] ?? 'all',
            'date_type' => $request['date_type'],
            'from' => $request['from'],
            'to' => $request['to'],
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'],
            'seller_id' => $vendorId,
            'seller_is' => 'seller',
        ];
        $orders = $this->orderRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $searchValue, filters: $filters, relations: $relation, dataLimit: 'all');

        if ($orders->count() == 0) {
            Toastr::warning(translate('order_data_is_not_available'));
            return back();
        }

        $storage = [];
        foreach ($orders as $item) {
            $order_amount = $item->order_amount;
            $discount_amount = $item->discount_amount;
            $shipping_cost = $item->shipping_cost;
            $extra_discount = $item->extra_discount;

            if ($item->order_status == 'processing') {
                $order_status = 'packaging';
            } elseif ($item->order_status == 'failed') {
                $order_status = 'Failed To Deliver';
            } else {
                $order_status = $item->order_status;
            }

            $storage[] = [
                'order_id' => $item->id,
                'Customer Id' => $item->customer_id,
                'Customer Name' => isset($item->customer) ? $item->customer->f_name . ' ' . $item->customer->l_name : 'not found',
                'Order Group Id' => $item->order_group_id,
                'Order Status' => $order_status,
                'Order Amount' => usdToDefaultCurrency(amount: $order_amount),
                'Order Type' => $item->order_type,
                'Coupon Code' => $item->coupon_code,
                'Discount Amount' => usdToDefaultCurrency(amount: $discount_amount),
                'Discount Type' => $item->discount_type,
                'Extra Discount' => usdToDefaultCurrency(amount: $extra_discount),
                'Extra Discount Type' => $item->extra_discount_type,
                'Payment Status' => $item->payment_status,
                'Payment Method' => $item->payment_method,
                'Transaction_ref' => $item->transaction_ref,
                'Verification Code' => $item->verification_code,
                'Billing Address' => isset($item->billingAddress) ? $item->billingAddress->address : 'not found',
                'Billing Address Data' => $item->billing_address_data,
                'Shipping Type' => $item->shipping_type,
                'Shipping Address' => isset($item->shippingAddress) ? $item->shippingAddress->address : 'not found',
                'Shipping Method Id' => $item->shipping_method_id,
                'Shipping Method Name' => isset($item->shipping) ? $item->shipping->title : 'not found',
                'Shipping Cost' => usdToDefaultCurrency(amount: $shipping_cost),
                'Seller Id' => $item->seller_id,
                'Seller Name' => isset($item->seller) ? $item->seller->f_name . ' ' . $item->seller->l_name : 'not found',
                'Seller Email' => isset($item->seller) ? $item->seller->email : 'not found',
                'Seller Phone' => isset($item->seller) ? $item->seller->phone : 'not found',
                'Seller Is' => $item->seller_is,
                'Shipping Address Data' => $item->shipping_address_data,
                'Delivery Type' => $item->delivery_type,
                'Delivery Man Id' => $item->delivery_man_id,
                'Delivery Service Name' => $item->delivery_service_name,
                'Third Party Delivery Tracking Id' => $item->third_party_delivery_tracking_id,
                'Checked' => $item->checked,
            ];
        }

        return (new FastExcel($storage))->download('Order_All_details.xlsx');
    }

    public function getCustomers(Request $request): JsonResponse
    {
        $allCustomer = ['id' => 'all', 'text' => 'All customer'];
        $customers = $this->customerRepo->getCustomerNameList(request: $request)->toArray();
        array_unshift($customers, $allCustomer);

        return response()->json($customers);
    }

    public function generateInvoice(string|int $id): void
    {
        $companyPhone = getWebConfig(name: 'company_phone');
        $companyEmail = getWebConfig(name: 'company_email');
        $companyName = getWebConfig(name: 'company_name');
        $companyWebLogo = getWebConfig(name: 'company_web_logo');
        $vendorId = auth('seller')->id();
        $vendor = $this->vendorRepo->getFirstWhere(params: ['id' => $vendorId])['gst'];

        $params = ['id' => $id, 'seller_id' => $vendorId, 'seller_is' => 'seller'];
        $relations = ['details', 'customer', 'shipping', 'seller'];
        $order = $this->orderRepo->getFirstWhere(params: $params, relations: $relations);

        $mpdf_view = PdfView::make(
            Order::GENERATE_INVOICE[VIEW],
            compact('order', 'vendor', 'companyPhone', 'companyEmail', 'companyName', 'companyWebLogo')
        );
        $this->generatePdf($mpdf_view, 'order_invoice_', $order['id']);
    }

    public function getView(string|int $id, DeliveryCountryCodeService $service): View
    {
        $vendorId = auth('seller')->id();
        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');
        $deliveryCountry = $this->deliveryCountryCodeRepo->getList(dataLimit: 'all');
        $countries = $countryRestrictStatus ? $service->getDeliveryCountryArray(deliveryCountryCodes: $deliveryCountry) : GlobalConstant::COUNTRIES;
        $zipCodes = $zipRestrictStatus ? $this->deliveryZipCodeRepo->getList(dataLimit: 'all') : 0;
        $params = ['id' => $id, 'seller_id' => $vendorId, 'seller_is' => 'seller'];
        $relations = ['deliveryMan', 'verificationImages', 'details', 'customer', 'shipping', 'offlinePayments'];
        $order = $this->orderRepo->getFirstWhere(params: $params, relations: $relations);

        $physicalProduct = false;
        if (isset($order->details)) {
            foreach ($order->details as $product) {
                if (isset($product->product) && $product->product->product_type == 'physical') {
                    $physicalProduct = true;
                }
            }
        }

        $whereNotIn = [
            'order_group_id' => ['def-order-group'],
            'id' => [$order['id']],
        ];
        $linkedOrders = $this->orderRepo->getListWhereNotIn(filters: ['order_group_id' => $order['order_group_id']], whereNotIn: $whereNotIn, dataLimit: 'all');
        $totalDelivered = $this->orderRepo->getListWhere(filters: ['seller_id' => $order['seller_id'], 'order_status' => 'delivered', 'order_type' => 'default_type'], dataLimit: 'all')->count();
        $shippingMethod = getWebConfig(name: 'shipping_method');

        $sellerId = 0;
        if ($shippingMethod == 'sellerwise_shipping') {
            $sellerId = $order['seller_id'];
        }
        $filters = [
            'is_active' => 1,
            'seller_id' => $sellerId,
        ];
        $deliveryMen = $this->deliveryManRepo->getListWhere(filters: $filters, dataLimit: 'all');
        $orderpickup = Order_Pickup::where('order_ids', $id)->first();
        // dd($orderpickup);
        if ($order['order_type'] == 'default_type') {
            $orderCount = $this->orderRepo->getListWhereCount(filters: ['customer_id' => $order['customer_id']]);
            return view(Order::VIEW[VIEW], compact(
                'order',
                'linkedOrders',
                'deliveryMen',
                'totalDelivered',
                'physicalProduct',
                'countryRestrictStatus',
                'zipRestrictStatus',
                'countries',
                'zipCodes',
                'orderCount'
            ));
        } else {
            $orderCount = $this->orderRepo->getListWhereCount(filters: ['customer_id' => $order['customer_id'], 'order_type' => 'POS']);
            return view(Order::VIEW_POS[VIEW], compact('order', 'orderCount', 'orderpickup'));
        }
    }

    public function updateStatus(
        Request                       $request,
        DeliveryManTransactionService $deliveryManTransactionService,
        DeliveryManWalletService      $deliveryManWalletService,
        OrderStatusHistoryService     $orderStatusHistoryService,
    ): JsonResponse {
        // dd($request->all());
        if ($request['order_status'] == 'confirmed') {
            $orderId = \App\Models\Order::latest()->value('id');

            $order = \App\Models\Order::latest()->first();
            $productId = \App\Models\OrderDetail::where('order_id', $orderId)->value('product_id');
            $productName = \App\Models\Product::where('id', $productId)->value('name');
            $userInfo = \App\Models\User::where('id', ($order->customer_id ?? ""))->first();

            $message_data = [
                'product_name' => $productName,
                'orderId' => $orderId,
                'order_amount' => webCurrencyConverter(amount: (float)$order->order_amount ?? 0),
                'customer_id' => ($order->customer_id ?? ""),
            ];
            $messages =  Helpers::whatsappMessage('ecom', 'Confirmed', $message_data);
        }
        if ($request['order_status'] == 'processing') {
            $response = Helpers::ShipwayGetCarrierrates($request->input('fromPincode'), $request->input('toPincode'), $request->input('paymentType'), $request->input('order_weight'), $request->input('box_length'), $request->input('box_breadth'), $request->input('box_height'));
            $rateCards = collect($response['rate_card']);
            $lowest_price = $rateCards->map(function ($item) {
                $item['total_cost'] = $item['delivery_charge'] + $item['rto_charge'];
                return $item;
            })->sortBy('total_cost')->first();
            $deliveryCharge = '';
            if ($request->input('paymentType') == 'cod') {
                $charge1 = $lowest_price['delivery_charge'] + ($lowest_price['delivery_charge'] * (18 / 100));
                $charge2 =  $lowest_price['cod_charges'] + ($lowest_price['cod_charges'] * (18 / 100));
                $deliveryCharge = $charge1 + $charge2;
            } else {
                $deliveryCharge = $lowest_price['delivery_charge'] + ($lowest_price['delivery_charge'] * (18 / 100));
            }

            if ($lowest_price['zone'] == 1) {
                $orderWeight = [
                    'order_weight' => $request->input('order_weight'),
                    'box_length' => $request->input('box_length'),
                    'box_breadth' => $request->input('box_breadth'),
                    'box_height' => $request->input('box_height'),
                    'delivery_type' => 'self_delivery',
                    'delivery_partner' => 'self_delivery',
                    'delivery_order_id' => $request->id,
                    'order_status' => $request->input('order_status'),
                    'delivery_charge' => $deliveryCharge
                ];
                ModelsOrder::where('id', $request->id)->update(array_merge(
                    $orderWeight,
                    ['order_status' => 'pickup']
                ));
                $orderId = \App\Models\Order::latest()->value('id');
                $order = \App\Models\Order::latest()->first();
                $productId = \App\Models\OrderDetail::where('order_id', $orderId)->value('product_id');
                $productName = \App\Models\Product::where('id', $productId)->value('name');
                $userInfo = \App\Models\User::where('id', ($order->customer_id ?? ""))->first();

                $message_data = [
                    'product_name' => $productName,
                    'orderId' => $orderId,
                    'order_amount' => webCurrencyConverter(amount: (float)$order->order_amount ?? 0),
                    'customer_id' => ($order->customer_id ?? ""),
                ];
                $messages =  Helpers::whatsappMessage('ecom', 'Processing', $message_data);
                Toastr::success(translate('Order Successfully Assign.'));
                return response()->json(['order_status' => $request['order_status']]);
            } else {

                $placeOrder = Helpers::ShipWayorderPlace($request->id, $request->input('order_weight'), $request->input('box_length'), $request->input('box_breadth'), $request->input('box_height'));
                if (isset($placeOrder) && $placeOrder['success'] == true) {

                    $orderWeight = [
                        'order_weight' => $request->input('order_weight'),
                        'box_length' => $request->input('box_length'),
                        'box_breadth' => $request->input('box_breadth'),
                        'box_height' => $request->input('box_height'),
                        'delivery_partner' => 'shipway',
                        'delivery_type' => 'shipway',
                        'delivery_order_id' => $request->id,
                        'order_status' => $request->input('order_status'),
                        'delivery_channel_id' => $request->input('warehouse_id'), //warehouse id
                        'delivery_shipment_id' => $lowest_price['carrier_id'], //carrie_id
                        'delivery_service_name' => $lowest_price['courier_name'],
                        'delivery_charge' => $deliveryCharge
                    ];
                    $carrierData = [
                        'courier_name' => $lowest_price['courier_name'],
                        'carrier_id' => $lowest_price['carrier_id'],
                        'delivery_charge' => $deliveryCharge,
                        'payment_type' => $request->input('paymentType') == 'prepaid' ? 'P' : ($request->input('paymentType') == 'cod' ? 'C' : ''),
                        'order_ids' => $request->id,
                        'warehouse_id' => $request->input('warehouse_id'),
                        'return_warehouse_id' => $request->input('warehouse_id'),
                    ];

                    $orderExists = Order_Pickup::where('order_ids', $request->id)->exists();
                    if (!$orderExists) {
                        Order_Pickup::create($carrierData);
                    }
                    ModelsOrder::where('id', $request->id)->update($orderWeight);
                    $responseLabel = Helpers::ShipWayorderLabelGenration($request->id);
                    // dd($responseLabel);
                    if (isset($responseLabel) && $responseLabel['success'] == true) {
                        if (isset($responseLabel['awb_response'])) {
                            $awbresponse = $responseLabel['awb_response'];
                            // dd($awbresponse);
                            if (isset($awbresponse['success']) && $awbresponse['success'] == true) {
                                $pickupData = [
                                    'awb' => $awbresponse['AWB'] ?? null,
                                    'shippingurl' => $awbresponse['shipping_url'] ?? null,
                                    'message' => $awbresponse['message'] ?? null,
                                ];
                                $trakingnumber = [
                                    'third_party_delivery_tracking_id' => $awbresponse['AWB'] ?? null,
                                    'shippingurl' => $awbresponse['shipping_url'] ?? null,
                                    'message' => $awbresponse['message'] ?? null,
                                ];
                                ModelsOrder::where('id', $request->id)->update($trakingnumber);
                                Order_Pickup::where('order_ids', $request->id)->update($pickupData);
                                $orderId = \App\Models\Order::latest()->value('id');
                                $order = \App\Models\Order::latest()->first();
                                $productId = \App\Models\OrderDetail::where('order_id', $orderId)->value('product_id');
                                $productName = \App\Models\Product::where('id', $productId)->value('name');
                                $userInfo = \App\Models\User::where('id', ($order->customer_id ?? ""))->first();
                                $message_data = [
                                    'product_name' => $productName,
                                    'orderId' => $orderId,
                                    'order_amount' => webCurrencyConverter(amount: (float)$order->order_amount ?? 0),
                                    'customer_id' => ($order->customer_id ?? ""),
                                    'tracking' => ($awbresponse['AWB'] ?? ""),
                                ];
                                $messages =  Helpers::whatsappMessage('ecom', 'Pickup', $message_data);
                                Toastr::success(translate($awbresponse['message']));
                                return response()->json(['order_status' => $request['order_status']]);
                            } else if (isset($awbresponse['success']) && $awbresponse['success'] == false) {
                                $errorMessage = isset($awbresponse['error'][0]) ? $awbresponse['error'][0] : 'An error occurred';
                                $pickupData = [
                                    'message' => $errorMessage,
                                ];
                                // dd($errorMessage);
                                ModelsOrder::where('id', $request->id)->update($pickupData);
                                Order_Pickup::where('order_ids', $request->id)->update($pickupData);
                                Toastr::error(translate($errorMessage));
                                return response()->json(['order_status' => $request['order_status']]);
                            }
                        } else {
                            Toastr::error(translate('AWB response missing in ShipWay response.'));
                            return response()->json(['error' => 'AWB response missing in ShipWay response.'], 400);
                        }
                    } else if (isset($response['success']) && $response['success'] == true) {
                        // ModelsOrder::where('id', $request->id)->update(['order_status' => 'processing']);
                        Order_Pickup::where('order_ids', $request->id)->update([
                            'message' => $response['message']
                        ]);
                        Toastr::error(translate('Order_proccessng_not_to_delivery_portal'));
                        return response()->json(['order_status' => $request['order_status']]);
                    }
                } else if (isset($placeOrder) && $placeOrder['success'] == false) {
                    ModelsOrder::where('id', $request->id)->update(['order_status' => 'confirmed']);
                    $errorMessage = $placeOrder['message'];
                    Toastr::error(translate($errorMessage));
                    return response()->json(['order_status' => $request['order_status']]);
                }
            }
            return response()->json($response);
        } else if ($request['order_status'] == 'pickup') {
            $response = Helpers::ShipwayCreatemanifest($request->id);
            if (isset($response['status']) && $response['status'] == true) {
                ModelsOrder::where('id', $request->id)->update([
                    'manifest_id' => $response['manifest_ids'],
                    'order_status' => $request->input('order_status')
                ]);
                Order_Pickup::where('id', $request->id)->update(['manifest_id' => $response['manifest_ids']]);
                Toastr::success(translate($response['message']));
                return response()->json(['order_status' => $request['order_status']]);
            } else if (isset($response['status']) && $response['status'] == false) {
                $errorMessage = $response['message'];
                ModelsOrder::where('id', $request->id)->update(['order_status' => 'processing']);
                Toastr::error(translate($errorMessage));
                return response()->json(['order_status' => $request['order_status']]);
            }
        }
        $order = $this->orderRepo->getFirstWhere(params: ['id' => $request['id']], relations: ['customer', 'seller.shop', 'deliveryMan']);

        if (!$order['is_guest'] && !isset($order['customer'])) {
            return response()->json(['customer_status' => 0], 200);
        }

        if ($order['payment_method'] != 'cash_on_delivery' && $request['order_status'] == 'delivered' && $order['payment_status'] != 'paid') {
            return response()->json(['payment_status' => 0], 200);
        }

        $this->orderRepo->updateStockOnOrderStatusChange($request['id'], $request['order_status']);
        $this->orderRepo->update(id: $request['id'], data: ['order_status' => $request['order_status']]);

        event(new OrderStatusEvent(key: $request['order_status'], type: 'customer', order: $order));
        if ($request->order_status == 'canceled') {
            ModelsOrder::where('id', $request->id)->update(['added_by' => 'vendor']);
            event(new OrderStatusEvent(key: 'canceled', type: 'delivery_man', order: $order));
        }

        $walletStatus = getWebConfig(name: 'wallet_status');
        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');

        if ($walletStatus == 1 && $loyaltyPointStatus == 1 && !$order['is_guest'] && $request['order_status'] == 'delivered' && ($order['payment_method'] == 'cash_on_delivery' ? $order['payment_status'] == 'unpaid' : $order['payment_status'] == 'paid') && $order['seller_id'] != null) {
            $this->loyaltyPointTransactionRepo->addLoyaltyPointTransaction(userId: $order['customer_id'], reference: $order['id'], amount: usdToDefaultCurrency(amount: $order['order_amount'] - $order['shipping_cost']), transactionType: 'order_place');
        }

        $refEarningStatus = getWebConfig(name: 'ref_earning_status') ?? 0;
        $refEarningExchangeRate = getWebConfig(name: 'ref_earning_exchange_rate') ?? 0;

        if (!$order['is_guest'] && $refEarningStatus == 1 && $request['order_status'] == 'delivered' && ($order['payment_method'] == 'cash_on_delivery' ? $order['payment_status'] == 'unpaid' : $order['payment_status'] == 'paid')) {

            $customer = $this->customerRepo->getFirstWhere(params: ['id' => $order['customer_id']]);
            $isFirstOrder = $this->orderRepo->getListWhereCount(filters: ['customer_id' => $order['customer_id'], 'order_status' => 'delivered', 'payment_status' => 'paid']);
            $referredByUser = $this->customerRepo->getFirstWhere(params: ['id' => $order['customer_id']]);

            if ($isFirstOrder == 1 && isset($customer->referred_by) && isset($referredByUser)) {
                $this->walletTransactionRepo->addWalletTransaction(
                    user_id: $referredByUser['id'],
                    amount: floatval($refEarningExchangeRate),
                    transactionType: 'add_fund_by_admin',
                    reference: 'earned_by_referral'
                );
            }
        }

        if ($order['delivery_man_id'] && $request->order_status == 'delivered') {
            $deliverymanWallet = $this->deliveryManWalletRepo->getFirstWhere(params: ['delivery_man_id' => $order['delivery_man_id']]);
            $cashInHand = $order['payment_method'] == 'cash_on_delivery' ? $order['order_amount'] : 0;

            if (empty($deliverymanWallet)) {
                $deliverymanWalletData = $deliveryManWalletService->getDeliveryManData(id: $order['delivery_man_id'], deliverymanCharge: $order['deliveryman_charge'], cashInHand: $cashInHand);
                $this->deliveryManWalletRepo->add(data: $deliverymanWalletData);
            } else {
                $deliverymanWalletData = [
                    'current_balance' => $deliverymanWallet['current_balance'] + currencyConverter($order['deliveryman_charge']) ?? 0,
                    'cash_in_hand' => $deliverymanWallet['cash_in_hand'] + currencyConverter($cashInHand) ?? 0,
                ];

                $this->deliveryManWalletRepo->updateWhere(params: ['delivery_man_id' => $order['delivery_man_id']], data: $deliverymanWalletData);
            }

            if ($order['deliveryman_charge'] && $request['order_status'] == 'delivered') {
                $deliveryManTransactionData = $deliveryManTransactionService->getDeliveryManTransactionData(amount: $order['deliveryman_charge'], addedBy: 'seller', id: $order['delivery_man_id'], transactionType: 'deliveryman_charge');
                $this->deliveryManTransactionRepo->add($deliveryManTransactionData);
            }
        }

        $orderStatusHistoryData = $orderStatusHistoryService->getOrderHistoryData(orderId: $request['id'], userId: auth('seller')->id(), userType: 'seller', status: $request['order_status']);
        $this->orderStatusHistoryRepo->add($orderStatusHistoryData);

        $transaction = $this->orderTransactionRepo->getFirstWhere(params: ['order_id' => $order['id']]);
        if (isset($transaction) && $transaction['status'] == 'disburse') {
            return response()->json($request['order_status']);
        }

        if ($request['order_status'] == 'delivered' && $order['seller_id'] != null) {
            $taxSummary = OrderDetail::where('order_id', $order->id)->get()->groupBy('tax')
                ->map(function ($items) {
                    return $items->sum('tax');
                });
            $taxSum = $taxSummary->sum();
            $finalamount =  $order->delivery_charge + $order->admin_commission;
            $orderData = ModelsOrder::where('id', $order['id'])->update(['seller_adv_amount' => $finalamount]);
            $this->orderRepo->manageWalletOnOrderStatusChange(order: $order, receivedBy: 'seller');
            $advamount = ModelsOrder::where('seller_id', $order['seller_id'])->sum('seller_adv_amount');
            SellerWallet::where('seller_id', $order['seller_id'])->update([
                'seller_adv_amount' => '-' . $advamount,
            ]);

            $this->orderDetailRepo->updateWhere(params: ['order_id' => $order['id']], data: ['delivery_status' => 'delivered']);
        }
        // if( $request['order_status']=='processing'){
        //     return redirect()->back();
        // }
        // dd($request['order_status']);
        return response()->json($request['order_status']);
    }
    public function delivery_shipmentcancel(Request $request)
    {
        $trackingId = $request->input('third_party_delivery_tracking_id');
        $partner = $request->input('delivery_partner');
        $orderId = $request->input('delivery_order_id');
        $warehouseId = $request->input('delivery_channel_id');
        if ($partner == 'shipway') {
            $shipwayResponse = Helpers::ShipwayCancelShipment($trackingId);
            if (isset($shipwayResponse['success']) && $shipwayResponse['success'] == true) {
                Helpers::ShipWayorderChancel($orderId);
                $errorMessage = $shipwayResponse['message'] ?? 'Shipment canceled from Shipway successfully.';
                ModelsOrder::where('id', $orderId)->update([
                    'delivery_order_id'      => null,
                    'delivery_shipment_id'   => null,
                    'order_status'           => 'canceled',
                    'order_weight'           => null,
                    'box_length'             => null,
                    'box_breadth'            => null,
                    'box_height'             => null,
                    'delivery_partner'       => null,
                    'delivery_type'          => null,
                    'delivery_channel_id'    => $warehouseId,
                    'delivery_service_name'  => null,
                    'third_party_delivery_tracking_id'  => null,
                    'delivery_charge'        => null,
                    'message'                => $errorMessage
                ]);
                Order_Pickup::where('order_ids', $orderId)->delete();
                Toastr::success(translate($errorMessage));
                return back();
            }
            if (isset($shipwayResponse['error']) && $shipwayResponse['error'] === true) {
                $errorMessage = $shipwayResponse['message'] ?? 'Unknown error occurred while canceling shipment.';
                Toastr::error(translate('Failed to cancel shipment: ' . $errorMessage));
                return back();
            }
        }
        Toastr::error(translate('Unable to connect to Shipway.'));
        return back();
    }

    public function updateAddress(Request $request): RedirectResponse
    {
        $order = $this->orderRepo->getFirstWhere(params: ['id' => $request['order_id']], relations: ['deliveryMan']);
        $shippingAddressData = json_decode(json_encode($order['shipping_address_data']), true);
        $billingAddressData = json_decode(json_encode($order['billing_address_data']), true);
        $commonAddressData = [
            'contact_person_name' => $request['name'],
            'phone' => $request['phone_number'],
            'country' => $request['country'],
            'city' => $request['city'],
            'zip' => $request['zip'],
            'address' => $request['address'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'updated_at' => now(),
        ];

        if ($request['address_type'] == 'shipping') {
            $shippingAddressData = array_merge($shippingAddressData, $commonAddressData);
        } elseif ($request['address_type'] == 'billing') {
            $billingAddressData = array_merge($billingAddressData, $commonAddressData);
        }

        $updateData = [];
        if ($request['address_type'] == 'shipping') {
            $updateData['shipping_address_data'] = json_encode($shippingAddressData);
        } elseif ($request['address_type'] == 'billing') {
            $updateData['billing_address_data'] = json_encode($billingAddressData);
        }

        if (!empty($updateData)) {
            $this->orderRepo->update(id: $request['order_id'], data: $updateData);
        }

        if ($order->delivery_type == 'self_delivery' && $order->delivery_man_id) {
            OrderStatusEvent::dispatch('order_edit_message', 'delivery_man', $order);
        }

        Toastr::success(translate('successfully_updated'));
        return back();
    }

    public function updatePaymentStatus(Request $request): JsonResponse
    {
        $order = $this->orderRepo->getFirstWhere(params: ['id' => $request['id']]);
        if ($order['payment_status'] == 'paid') {
            return response()->json(['error' => translate('when_payment_status_paid_then_you_can`t_change_payment_status_paid_to_unpaid') . '.']);
        }

        if ($order['is_guest'] == '0' && !isset($order['customer'])) {
            return response()->json(['customer_status' => 0], 200);
        }
        $this->orderRepo->update(id: $request['id'], data: ['payment_status' => $request['payment_status']]);
        return response()->json($request['payment_status']);
    }

    public function updateDeliverInfo(Request $request): RedirectResponse
    {
        $updateData = [
            'delivery_type' => 'third_party_delivery',
            'delivery_service_name' => $request['delivery_service_name'],
            'third_party_delivery_tracking_id' => $request['third_party_delivery_tracking_id'],
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
        ];
        $this->orderRepo->update(id: $request['order_id'], data: $updateData);

        Toastr::success(translate('updated_successfully'));
        return back();
    }

    public function addDeliveryMan(string|int $order_id, string|int $delivery_man_id): JsonResponse
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }

        $order = $this->orderRepo->getFirstWhere(params: ['id' => $order_id]);
        if ($order['order_status'] == 'delivered') {
            return response()->json(['status' => false], 403);
        }
        $orderData = [
            'delivery_man_id' => $delivery_man_id,
            'delivery_type' => 'self_delivery',
            'delivery_service_name' => null,
            'third_party_delivery_tracking_id' => null,
        ];
        $params = ['seller_id' => auth('seller')->id(), 'id' => $order_id];
        $this->orderRepo->updateWhere(params: $params, data: $orderData);

        $order = $this->orderRepo->getFirstWhere(params: ['id' => $order_id], relations: ['deliveryMan']);
        event(new OrderStatusEvent(key: 'new_order_assigned_message', type: 'delivery_man', order: $order));

        return response()->json(['status' => true], 200);
    }

    public function updateAmountDate(Request $request): JsonResponse
    {
        $userId = auth('seller')->id();
        $status = $this->orderRepo->updateAmountDate(request: $request, userId: $userId, userType: 'seller');
        $order = $this->orderRepo->getFirstWhere(params: ['id' => $request['order_id']], relations: ['customer', 'deliveryMan']);

        $fieldName = $request['field_name'];
        $message = '';
        if ($fieldName == 'expected_delivery_date') {
            OrderStatusEvent::dispatch('expected_delivery_date', 'delivery_man', $order);
            $message = translate("expected_delivery_date_added_successfully");
        } elseif ($fieldName == 'deliveryman_charge') {
            OrderStatusEvent::dispatch('delivery_man_charge', 'delivery_man', $order);
            $message = translate("deliveryman_charge_added_successfully");
        }

        return response()->json(['status' => $status, 'message' => $message], $status ? 200 : 403);
    }

    public function uploadDigitalFileAfterSell(UploadDigitalFileAfterSellRequest $request): RedirectResponse
    {
        $orderDetails = $this->orderDetailRepo->getFirstWhere(['id' => $request['order_id']]);
        $digitalFileAfterSell = $this->updateFile(dir: 'product/digital-product/', oldImage: $orderDetails['digital_file_after_sell'], format: $request['digital_file_after_sell']->getClientOriginalExtension(), image: $request->file('digital_file_after_sell'), fileType: 'file');
        if ($this->orderDetailRepo->update(id: $orderDetails['id'], data: ['digital_file_after_sell' => $digitalFileAfterSell])) {
            Toastr::success(translate('digital_file_upload_successfully'));
        } else {
            Toastr::error(translate('digital_file_upload_failed'));
        }
        return back();
    }
}