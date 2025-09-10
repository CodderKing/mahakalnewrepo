<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Chadhava;
use App\Models\Order;
use App\Models\Service;
use App\Models\Service_order;
use App\Models\Chadhava_orders;
use App\Models\Prashad_deliverys;
use App\Models\SellerWallet;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Utils\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;

class ShipwayCroneController extends Controller
{

    public function GetOrderDetails()
    {
        $orders = Order::where('delivery_partner', 'shipway')
            ->whereIn('order_status', ['processing', 'pickup', 'out_for_delivery', 'in-transit'])
            ->get();
        $prashadRecords = Prashad_deliverys::where('delivery_partner', 'shipway')
            ->whereIn('order_status', ['processing', 'in-transit', 'out_for_delivery'])
            ->get();
        foreach ($orders as $order) {
            $this->updateOrderDetails($order);
        }
        foreach ($prashadRecords as $prashadOrder) {
            $this->updatePrashadDetails($prashadOrder);
        }
    }
    private function updateOrderDetails($order)
    {
        $response = Helpers::ShipwayGetOrder($order->id);
        $orderDetails = $response['message'][0];
        $shipmentScan = isset($orderDetails['shipment_status_scan']) ? json_encode($orderDetails['shipment_status_scan']) : null;
        $orderStatus = isset($orderDetails['shipment_status_name']) ? $orderDetails['shipment_status_name'] : null;
        $orderId = isset($orderDetails['order_id']) ? $orderDetails['order_id'] : null;
        if ($orderStatus !== null) {
            // Convert to lowercase and replace spaces with underscores
            $orderStatus = strtolower(str_replace(' ', '_', $orderStatus));
            if ($orderStatus == 'delivered') {
                $order = Order::where('id', $order->id)->first();
                $finalamount = $order->shipping_cost - $order->delivery_charge;
                $orderData = Order::where('id', $order->id)->update(['seller_adv_amount' => $finalamount]);
                $advamount = Order::where('seller_id', $order['seller_id'])->sum('seller_adv_amount');
                SellerWallet::where('seller_id', $order['seller_id'])->update([
                    'seller_adv_amount' => $advamount,
                ]);
            }
        }
        if ($orderId) {
            Order::where('id', $orderId)->update([
                'order_status' => $orderStatus,
                'shipment_status_scan' => $shipmentScan,
                'updated_at' => now(),
            ]);
        }
    }

    // Method to handle order details update for 'prashadRecords'
    private function updatePrashadDetails($prashadOrder)
    {
        $response = Helpers::ShipwayGetOrder($prashadOrder->order_id);
        $orderDetails = $response['message'][0];
        $shipmentScan = isset($orderDetails['shipment_status_scan']) ? json_encode($orderDetails['shipment_status_scan']) : null;
        $orderStatus = isset($orderDetails['shipment_status_name']) ? $orderDetails['shipment_status_name'] : null;
        $orderId = isset($orderDetails['order_id']) ? $orderDetails['order_id'] : null;
        if ($orderStatus !== null) {
            // Convert to lowercase and replace spaces with underscores
            $orderStatus = strtolower(str_replace(' ', '_', $orderStatus));
        }
        if ($orderId) {
            Prashad_deliverys::where('order_id', $orderId)->update([
                'order_status' => $orderStatus,
                'shipment_status_scan' => $shipmentScan,
                'updated_at' => now(),
            ]);
        }
    }

    //banner start date end date 
    public function unpublishExpiredBanners()
    {
        $today = Carbon::now()->format('Y-m-d');
        Banner::where('published', 1)
            ->whereDate('end_date', '<', $today)
            ->update(['published' => 0]);
    }

    public function SpecialPoojaDate()
    {
        try {
            $now = Carbon::now();

            //  Step 1: Service check (special pooja schedules)
            Service::where('pooja_type', 1)->chunk(100, function ($services) use ($now) {
                foreach ($services as $service) {
                    $schedules = json_decode($service->schedule, true);

                    if (!is_array($schedules) || empty($schedules)) {
                        continue;
                    }

                    // Find latest schedule datetime
                    $latestDateTime = null;

                    foreach ($schedules as $entry) {
                        if (!isset($entry['schedule']) || !isset($entry['schedule_time'])) {
                            continue;
                        }

                        $dateTime = Carbon::parse($entry['schedule'] . ' ' . $entry['schedule_time']);

                        if (is_null($latestDateTime) || $dateTime->gt($latestDateTime)) {
                            $latestDateTime = $dateTime;
                        }
                    }

                    // If latest schedule datetime has passed, update status
                    if ($latestDateTime && $latestDateTime->lt($now)) {
                        $service->status = 0;
                        $service->save();
                    }
                }
            });

            //  Step 2: Expire old Chadhava
            Chadhava::where('chadhava_type', 1)->whereDate('end_date', '<', $now)->where('status', 1)->update(['status' => 0]);
            return response()->json([
                'success' => true,
                'message' => 'Special Pooja Date check completed successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in SpecialPoojaDate function.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // for rejected pooja, vip, anushthan, chadhava
    public function rejectedPoojaDate()
    {
        $threeDaysBeforeYesterday = \Carbon\Carbon::now()->subDays(3)->format('Y-m-d');

        $commonUpdateFields = [
            'order_status' => 6,
            'status' => 6,
            'schedule_time' => null,
            'schedule_created' => null,
            'live_stream' => null,
            'live_created_stream' => null,
            'pooja_video' => null,
            'video_created_sharing' => null,
            'pandit_assign' => null,
            'pooja_certificate' => null,
        ];

        // Get all pooja service IDs
        $serviceIds = Service_order::where('type', 'pooja')
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->toArray();

        // Update pooja orders
        Service_order::where('type', 'pooja')
            ->where('status', 0)
            ->where('order_status', 0)
            ->whereIn('service_id', $serviceIds)
            ->whereDate('booking_date', '<', $threeDaysBeforeYesterday)
            ->update($commonUpdateFields);

        // Update vip orders (package_id = 5)
        Service_order::where('type', 'vip')
            ->where('package_id', 5)
            ->where('status', 0)
            ->where('order_status', 0)
            ->whereDate('booking_date', '<', $threeDaysBeforeYesterday)
            ->update($commonUpdateFields);

        // Update anushthan orders (package_id = 7)
        Service_order::where('type', 'anushthan')
            ->where('package_id', 7)
            ->where('status', 0)
            ->where('order_status', 0)
            ->whereDate('booking_date', '<', $threeDaysBeforeYesterday)
            ->update($commonUpdateFields);

        // Update chadhava orders
        $chadhavaUpdateFields = $commonUpdateFields;
        $chadhavaUpdateFields['is_completed'] = 0;

        Chadhava_orders::where('type', 'chadhava')
            ->where('status', 0)
            ->where('order_status', 0)
            ->whereDate('booking_date', '<', $threeDaysBeforeYesterday)
            ->update($chadhavaUpdateFields);
    }


}