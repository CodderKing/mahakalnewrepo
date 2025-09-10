<?php

use App\Enums\ViewPaths\AllPaths\EventPath;
use App\Enums\ViewPaths\AllPaths\LoginPath;
use App\Enums\ViewPaths\AllPaths\TourPath;
use App\Enums\ViewPaths\AllPaths\TrusteesPath;
use App\Http\Controllers\Admin\DonateTrustController;
use App\Http\Controllers\AllController\CommanController;
use App\Http\Controllers\AllController\EventOrgController;
use App\Http\Controllers\AllController\TourController;
use App\Http\Controllers\AllController\TrusteesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'event-vendor', 'as' => 'event-vendor.'], function () {

    Route::group(['middleware' => [\App\Http\Middleware\EventOrgMiddleware::class]], function () {
        Route::controller(EventOrgController::class)->group(function () {
            Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
                Route::get(EventPath::DASHBOARD[URL], 'dashboard')->name('index');
            });

            Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['employeemodules:Profile']], function () {
                Route::get(EventPath::PROFILEUPDATE[URL] . '/{id}', 'profileUpdate')->name('update');
                Route::post('profile-updates', 'profileUpdate2')->name('update2');
                Route::patch(EventPath::PROFILEUPDATE[URI] . '/{id}', 'profileEdit')->name('profile-edit');
            });

            Route::group(['prefix' => 'artist', 'as' => 'artist.', 'middleware' => ['employeemodules:Artist Management']], function () {
                Route::get(EventPath::ADDARTIST[URL], 'AddArtist')->name('add-artist');
                Route::post(EventPath::ADDARTIST[URI], 'StoreArtist')->name('store-artist');
                Route::get(EventPath::ARTISTLIST[URI], 'ArtistList')->name('list');
                Route::get(EventPath::ARTISTUPDATE[URI] . '/{id}', 'ArtistEdit')->name('artist_update');
                Route::post(EventPath::ARTISTUPDATE[URI] . '/{id}', 'ArtistUpdate')->name('update-artist');
            });

            Route::group(['prefix' => 'event-management', 'as' => 'event-management.', 'middleware' => ['employeemodules:Event Management']], function () {
                Route::get(EventPath::EVENTMANAG[URL], 'EventAdd')->name('add-event');
                Route::post(EventPath::EVENTMANAG[URI], 'EventStore')->name('store-event');

                Route::get(EventPath::EVENTMANAGLIST[URI], 'EventList')->name('event-list');
                Route::get(EventPath::EVENTMANAGPENDING[URI], 'EventPending')->name('event-pending');
                Route::get(EventPath::EVENTMANAGUPCOMMING[URI], 'EventUpcomming')->name('event-upcomming');
                Route::get(EventPath::EVENTMANAGRUNNING[URI], 'EventRunning')->name('event-running');
                Route::get(EventPath::EVENTMANAGCOMPLATE[URI], 'EventComplate')->name('event-complate');
                Route::get(EventPath::EVENTMANAGCANCEL[URI], 'EventCancel')->name('event-cancel');

                Route::get(EventPath::EVENTMANAGUPDATE[URL] . '/{id}', 'EventUpdate')->name('event-update');
                Route::post(EventPath::EVENTMANAGUPDATE[URI] . '/{id}', 'EventEdits')->name('event-edit');

                Route::get(EventPath::EVENTOVERVIEW[URI] . '/{id}', 'EventDetailsOverview')->name('event-detail-overview');
            });
            Route::group(['prefix' => 'event-order', 'as' => 'event-order.', 'middleware' => ['employeemodules:Order Management']], function () {
                Route::get(EventPath::EVENTORDERRUNING[URI], 'EventOrderRunning')->name('running');
                Route::get(EventPath::EVENTORDERCOMPLATE[URI], 'EventOrderComplate')->name('complate');
                Route::get(EventPath::EVENTORDERRUNNING[URI], 'EventOrderRefund')->name('refund');
                Route::post(EventPath::EVENTORDERVIEWS[URI], 'EventOrderView')->name('event-order-view');
            });

            Route::group(['prefix' => 'messages', 'as' => 'messages.', 'middleware' => ['employeemodules:Support Management']], function () {
                Route::get(EventPath::EVENTINBOX[URL], "EventSupportTicket")->name('index');
                Route::post(EventPath::EVENTINBOX[URL], "EventSupportTicketStore")->name('store-inbox');
                Route::post(EventPath::EVENTINBOXSTATUS[URL], "EventSupportTicketStatus")->name('status');
                Route::get(EventPath::EVENTINBOXVIEW[URL] . '/{id}', "EventSupportTicketView")->name('singleTicket');
                Route::post(EventPath::EVENTINBOXVIEW[URL] . "/{id}", "EventSupportTicketReplay")->name('replay');
            });

            // from admin
            Route::group(['prefix' => 'message', 'as' => 'message.', 'middleware' => ['employeemodules:Support Management']], function () {
                Route::get(EventPath::EVENTADMININBOX[URL], "AdminSupportTicket")->name('index');
                Route::post(EventPath::EVENTADMININBOX[URL], "EventSupportTicketStore")->name('store-inbox');

                Route::get(EventPath::EVENTINBOXVIEW[URL] . '/{id}', "EventSupportTicketView")->name('singleTicket');
                Route::post(EventPath::EVENTINBOXVIEW[URL] . "/{id}", "EventSupportTicketReplay")->name('replay');
            });

            Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.', 'middleware' => ['employeemodules:Transaction Management']], function () {
                Route::get(EventPath::WITHDRAW[URL], "withdrawRequests")->name('index');
                Route::post("get-vendor-info", 'GetVendorInfo')->name('get-vendor-data');
                Route::post("payment-request-send", 'AddWithdrawalRequest')->name('add-request-admin-send');
                Route::get("withdraw-request-view/{id}", 'WithdrawalRequestView')->name('withdraw-request-view');
            });
            Route::group(['prefix' => 'transaction', 'as' => 'transaction.', 'middleware' => ['employeemodules:Transaction Management']], function () {
                Route::get('/', "transactionHistory")->name('index');
            });


            Route::group(['prefix' => 'fcm-update', 'as' => 'fcm-update.'], function () {
                Route::post('owner', "FCMUpdates")->name('owners');
                Route::get('delete', "FCMUpdatesdelete")->name('delete');
            });

            Route::group(['prefix' => 'qr-code-verify', 'as' => 'qr-code-verify.', 'middleware' => ['employeemodules:Qr Management']], function () {
                Route::get(EventPath::QRTODAYLIST[URL], "TodayEventList")->name('index');
                Route::get(EventPath::QRTODAYINFORMATION[URL] . "/{id}/{venue}", "EventQRVerify")->name('view');
                Route::post(EventPath::QRTODAYSUBMIT[URL] . "/{id}/{num}", "EventQRVerifySubmit")->name('verify');
                Route::post(EventPath::QRTODAYSUBMIT[URL] . "/{id}", "EventQRVerifySubmit");
            });
            Route::group(['prefix' => 'employee', 'as' => 'employee.', 'middleware' => ['employeemodules:Employee']], function () {
                Route::get(EventPath::ADDEMPLOYEE[URL], 'AddEmployee')->name('add-employee');
                Route::post(EventPath::ADDEMPLOYEE[URI], 'StoreEmployee')->name('store-employee');
                Route::get(EventPath::EMPLOYEELIST[URI], 'EmployeeList')->name('employee-list');
                Route::get(EventPath::EMPLOYEEUPDATE[URI] . '/{id}', 'EmployeeEdit')->name('employee-edit');
                Route::post(EventPath::EMPLOYEEUPDATE[URI] . '/{id}', 'EmployeeUpdate')->name('employee-update');

                Route::post(EventPath::EMPLOYEESTATUSUPDATE[URI], 'EmployeeStatusUpdate')->name('employee-status-update');
                Route::post(EventPath::EMPLOYEESTATUSUPDATE[URL], 'Employeedelete')->name('employee_delete');

                Route::post(EventPath::CHECHEMAILPHONE[URI], 'CheckEmailPhone')->name('check-value');
            });
        });
    });
});

Route::group(['prefix' => 'tour-vendor', 'as' => 'tour-vendor.'], function () {

    Route::group(['middleware' => [\App\Http\Middleware\TourMiddleware::class]], function () {
        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::DASHBOARD[URL], 'index')->name('index');

                Route::post(TourPath::DASHBOARD[URL], 'withdrawRequestadd')->name('withdraw-request');
                Route::get('order-statistics', 'orderStatistics')->name('order-statistics');
            });
        });

        Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::PROFILEUPDATE[URL] . '/{id}', 'profileUpdate')->name('update');
                Route::post(TourPath::PROFILEUPDATE[URI] . '/{id}', 'profileEdit')->name('profile-edit');
                Route::post("update-password" . '/{id}', 'PasswordChange')->name('password-update');
            });
        });

        Route::group(['prefix' => 'tour_cab_management', 'as' => 'tour_cab_management.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::CABLIST[URL], 'CabList')->name('cab-list');
                Route::post(TourPath::CABLIST[URI], 'CabStore')->name('cab-store');
                Route::post(TourPath::CABSTATUSUPDATE[URL], 'CabStatusUpdate')->name('cab_status-update');
                Route::get(TourPath::CABUPDATE[URL] . "/{id}", 'CabUpdate')->name('cab-update');
                Route::post(TourPath::CABUPDATE[URL], 'CabEdit')->name('cab-edit');
                Route::post(TourPath::CABTRAVELLERDELETE[URL], 'CabTravellerDelete')->name('traveller-cab-delete');

                Route::get(TourPath::DRIVERLIST[URL], 'CabDriverList')->name('cab-driver-list');
                Route::post(TourPath::DRIVERLIST[URI], 'DriverStore')->name('driver-store');
                Route::post(TourPath::DRIVERSTATUSUPDATE[URL], 'DriverStatusUpdate')->name('driver_status-update');
                Route::post(TourPath::DRIVERDETELE[URL], 'DriverDetele')->name('traveller-driver-delete');
                Route::get(TourPath::DRIVERUPDATE[URL] . "/{id}", 'DriverUpdate')->name('driver-update');
                Route::post(TourPath::DRIVERUPDATE[URL], 'DriverEdit')->name('driver-edit');
            });
        });

        Route::group(['prefix' => 'order', 'as' => 'order.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::ORDERPENDING[URL], "orderPending")->name('pending');
                Route::get('cancel-order/{id}', "orderCancel")->name('cancel-order');
                Route::get(TourPath::ORDERCONFIRM[URL], "orderConfirm")->name('confirm');
                Route::get(TourPath::ORDERPICKUP[URL], "orderPickUp")->name('pickup');
                Route::get(TourPath::ORDERCOMPLETE[URL], "orderComplete")->name('complete');
                Route::get(TourPath::ORDERCANCEL[URL], "UserCancelOrder")->name('user-cancel');

                Route::get(TourPath::ORDERDETAILS[URL] . '/{id}', "orderDetails")->name('details');
                Route::post(TourPath::ORDERDETAILS[URL], "orderAssignAccept")->name('assign-accept');
                Route::post(TourPath::ORDERCDASSIGN[URL], "ordercabdriverAssign")->name('cab-driver-assign');

                Route::get(TourPath::ORDERREMINDERMESSAGE[URL], "orderReminderMessage")->name('tour-order-reminder-message');
            });
        });

        Route::group(['prefix' => 'tour_visits', 'as' => 'tour_visits.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::ADDTOUR[URL], "addTour")->name('add-tour');
                Route::post(TourPath::ADDTOUR[URL], "tourSave")->name('insert-tour');
                Route::get(TourPath::TOURLIST[URL], "tourList")->name('tour-list');
                Route::get(TourPath::TOURUPDATE[URL] . "/{id}", "tourUpdate")->name('update');
                Route::get(TourPath::TOURVIEW[URL] . "/{id}", "tourView")->name('view');
                Route::post(TourPath::TOURUPDATE[URL], "tourEdit")->name('tour-edit');
                Route::get(TourPath::TOURIMGDELETE[URL] . '/{id}/{name}', "tourImgDelete")->name('tour-delete-image');
                Route::delete(TourPath::TOURDELETE[URL] . "/{id}", "tourDelete")->name('tour-delete');

                Route::get(TourPath::TOUROVERVIEW[URL] . '/{id}', "tourDetails")->name('overview');

                //////add visit                
                Route::get(TourPath::TOURVISITLIST[URL] . "/{id}", "tourVisit")->name('add-visit');
                Route::post(TourPath::TOURVISITLIST[URL], "tourVisitStore")->name('visit_place_store');
                Route::post(TourPath::TOURVISITDELETE[URL], "tourVisitDelete")->name('delete-place');
                Route::get(TourPath::TOURVISITIMGDELETE[URL] . "/{id}/{name}", "tourVisitImgDelete")->name('visit-delete-image');
                Route::get(TourPath::TOURVISITUPDATE[URL] . "/{id}", "tourVisitUpdate")->name('tour-visit-update');
                Route::post(TourPath::TOURVISITUPDATE[URL], "tourVisitEdit")->name('visit_place_edit');
                Route::post(TourPath::TOURACCEPT[URL], 'TourAccept')->name('accept-tour');
            });
        });
        // from vendor
        Route::group(['prefix' => 'messages', 'as' => 'messages.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::INBOX[URL], "TourSupportTicket")->name('index');
                Route::post(TourPath::INBOX[URL], "TourSupportTicketStore")->name('store-inbox');
                Route::post(TourPath::INBOXSTATUS[URL], "TourSupportTicketStatus")->name('status');
                Route::get(TourPath::INBOXVIEW[URL] . '/{id}', "TourSupportTicketView")->name('singleTicket');
                Route::post(TourPath::INBOXVIEW[URL] . "/{id}", "TourSupportTicketReplay")->name('replay');
            });
        });
        // from admin
        Route::group(['prefix' => 'message', 'as' => 'message.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::ADMININBOX[URL], "AdminSupportTicket")->name('index');
                Route::post(TourPath::ADMININBOX[URL], "AdminSupportTicketStore")->name('store-inbox');

                // Route::post(TourPath::INBOXSTATUS[URL], "AdminSupportTicketStatus")->name('status');
                Route::get(TourPath::INBOXVIEW[URL] . '/{id}', "TourSupportTicketView")->name('singleTicket');
                Route::post(TourPath::INBOXVIEW[URL] . "/{id}", "TourSupportTicketReplay")->name('replay');
            });
        });


        Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::get(TourPath::WITHDRAW[URL], "withdrawRequests")->name('index');
                Route::post("get-vendor-info", 'GetVendorInfo')->name('get-vendor-data');
                Route::post("payment-request-send", 'AddWithdrawalRequest')->name('add-request-admin-send');
                Route::get("withdraw-request-view/{id}", 'WithdrawalRequestView')->name('withdraw-request-view');
            });
        });
        Route::group(['prefix' => 'fcm-update', 'as' => 'fcm-update.'], function () {
            Route::controller(TourController::class)->group(function () {
                Route::post('owner', "FCMUpdates")->name('owners');
                Route::get('delete', "FCMUpdatesdelete")->name('delete');
            });
        });
    });
});

Route::group(['prefix' => 'trustees-vendor', 'as' => 'trustees-vendor.'], function () {
    Route::group(['middleware' => [\App\Http\Middleware\TrusteesMiddleware::class]], function () {
        Route::controller(TrusteesController::class)->group(function () {
            Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
                Route::get('/', 'dashboard')->name('index');
                Route::get('order-statistics', 'orderStatistics')->name('order-statistics');
            });

            Route::group(['prefix' => 'ads-management', 'as' => 'ads-management.', 'middleware' => ['employeemodules:Ads Management']], function () {
                Route::get(TrusteesPath::ADSADD[URI], 'AdsAdd')->name('add');
                Route::post(TrusteesPath::ADSADD[URI], 'AdsStore')->name('ad-store');
                Route::post(TrusteesPath::ADSADD[URL], 'AdsStatusUpdate')->name('status-update');
                Route::get(TrusteesPath::ADSUPDATE[URI] . '/{id}', 'AdsUpdate')->name('ads-update');
                Route::post(TrusteesPath::ADSUPDATE[URI], 'AdsUpdateSave')->name('ads-updatesave');
                Route::post(TrusteesPath::ADSDELETE[URI], 'AdsDelete')->name('ad-trust-delete');
                Route::get(TrusteesPath::ADSDETAILS[URI] . '/{id}', 'AdsDetails')->name('ads-details');
                Route::get(TrusteesPath::ADSLIST[URI], 'AdsList')->name('list');
            });

            Route::group(['prefix' => 'messages', 'as' => 'messages.', 'middleware' => ['employeemodules:Support Management']], function () {
                Route::get(TrusteesPath::TRUSTINBOX[URL], "TrustSupportTicket")->name('index');
                Route::post(TrusteesPath::TRUSTINBOX[URL], "TrustSupportTicketStore")->name('store-inbox');
                Route::post(TrusteesPath::TRUSTINBOXSTATUS[URL], "TrustSupportTicketStatus")->name('status');
                Route::get(TrusteesPath::TRUSTINBOXVIEW[URL] . '/{id}', "TrustSupportTicketView")->name('singleTicket');
                Route::post(TrusteesPath::TRUSTINBOXVIEW[URL] . "/{id}", "TrustSupportTicketReplay")->name('replay');
            });

            // from admin
            Route::group(['prefix' => 'message', 'as' => 'message.', 'middleware' => ['employeemodules:Support Management']], function () {
                Route::get(TrusteesPath::TRUSTADMININBOX[URL], "AdminSupportTicket")->name('index');
                Route::post(TrusteesPath::TRUSTADMININBOX[URL], "TrustSupportTicketStore")->name('store-inbox');

                Route::get(TrusteesPath::TRUSTINBOXVIEW[URL] . '/{id}', "TrustSupportTicketView")->name('singleTicket');
                Route::post(TrusteesPath::TRUSTINBOXVIEW[URL] . "/{id}", "TrustSupportTicketReplay")->name('replay');
            });
            Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.', 'middleware' => ['employeemodules:Withdrawal Management']], function () {
                Route::get(TrusteesPath::TRUSTWITHDRAW[URL], "withdrawRequests")->name('index');
                Route::post("get-vendor-info", 'GetVendorInfo')->name('get-vendor-data');
                Route::post("payment-request-send", 'AddWithdrawalRequest')->name('add-request-admin-send');
                Route::get("withdraw-request-view/{id}", 'WithdrawalRequestView')->name('withdraw-request-view');
            });
            Route::group(['prefix' => 'donation-history', 'as' => 'donation-history.', 'middleware' => ['employeemodules:Donation Management']], function () {
                Route::get("list", 'DonationHistory')->name('list');
            });
            Route::group(['prefix' => 'fcm-update', 'as' => 'fcm-update.'], function () {
                Route::post('owner', "FCMUpdates")->name('owners');
            });
            //pending
            Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['employeemodules:Profile']], function () {
                Route::get(TrusteesPath::PROFILEUPDATE[URL] . '/{id}', 'profileUpdate')->name('update');
                Route::post(TrusteesPath::PROFILEUPDATE[URL], 'profileUpdate2')->name('update2');
                Route::patch(TrusteesPath::PROFILEUPDATE[URI] . '/{id}', 'profileEdit')->name('profile-edit');
                Route::get('delete-image/{id}/{photo}', 'DeleteImage')->name('delete-image');
            });

            Route::group(['prefix' => 'trustees-withdrawal', 'as' => 'trustees-withdrawal.', 'middleware' => ['employeemodules:Withdrawal Management']], function () {
                Route::controller(DonateTrustController::class)->group(function () {
                    Route::get('/', 'WithdrawalList')->name('index');
                    Route::get('view/{id}', 'WithdrawalReqView')->name('withdraw-request-view');
                    Route::get('request-reject/{id}', 'WithdrawalReqReject')->name('rejects');
                    Route::get('create-contact/{id}/{type}', 'RazorpaycreateContact')->name('payment-req-approval-admin');
                });
            });
            Route::group(['prefix' => 'employee', 'as' => 'employee.', 'middleware' => ['employeemodules:Employee']], function () {
                Route::get(TrusteesPath::ADDEMPLOYEE[URL], 'AddEmployee')->name('add-employee');
                Route::post(TrusteesPath::ADDEMPLOYEE[URI], 'StoreEmployee')->name('store-employee');
                Route::get(TrusteesPath::EMPLOYEELIST[URI], 'EmployeeList')->name('employee-list');
                Route::get(TrusteesPath::EMPLOYEEUPDATE[URI] . '/{id}', 'EmployeeEdit')->name('employee-edit');
                Route::post(TrusteesPath::EMPLOYEEUPDATE[URI] . '/{id}', 'EmployeeUpdate')->name('employee-update');

                Route::post(TrusteesPath::EMPLOYEESTATUSUPDATE[URI], 'EmployeeStatusUpdate')->name('employee-status-update');
                Route::post(TrusteesPath::EMPLOYEESTATUSUPDATE[URL], 'Employeedelete')->name('employee_delete');
                Route::post(TrusteesPath::CHECHEMAILPHONE[URI], 'CheckEmailPhone')->name('check-value');
            });
        });
    });
});