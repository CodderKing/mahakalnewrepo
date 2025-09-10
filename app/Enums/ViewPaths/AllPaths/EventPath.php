<?php

namespace App\Enums\ViewPaths\AllPaths;

enum EventPath
{
    const DASHBOARD = [
        URL => "/",
        VIEW => "all-views.event.dashboard.index",
        REDIRECT => "event-vendor.dashboard.index",
    ];

    const PROFILEUPDATE = [
        URL => 'update',
        URI => 'edit',
        VIEW => "all-views.event.profile.update-profile",
    ];

    const ADDARTIST = [
        URL => 'add-artist',
        URI => 'edit',
        VIEW => "all-views.event.artist.add",
        REDIRECT => "event-vendor.artist.list",
    ];
    const ARTISTLIST = [
        URI => 'artist-list',
        VIEW => "all-views.event.artist.list",
    ];

    const ARTISTUPDATE = [
        URI => "update",
        VIEW => "all-views.event.artist.edit",
    ];

    const EVENTMANAG = [
        URL => "add-event",
        URI => 'store-event',
        VIEW => "all-views.event.events.add",
    ];

    const EVENTMANAGLIST = [
        URI => 'event-list',
        VIEW => "all-views.event.events.list",
        REDIRECT => "event-vendor.event-management.event-list",
    ];

    const EVENTMANAGPENDING = [
        URI => 'event-pending',
        VIEW => "all-views.event.events.pending",
    ];
    const EVENTMANAGUPCOMMING = [
        URI => 'event-upcomming',
        VIEW => "all-views.event.events.upcomming",
    ];
    const EVENTMANAGRUNNING = [
        URI => 'event-running',
        VIEW => "all-views.event.events.running",
    ];
    const EVENTMANAGCOMPLATE = [
        URI => 'event-complate',
        VIEW => "all-views.event.events.complate",
    ];
    const EVENTMANAGCANCEL = [
        URI => 'event-cancel',
        VIEW => "all-views.event.events.cancel",
    ];

    const EVENTMANAGUPDATE = [
        URI => 'event-update',
        URL => 'event-edit',
        VIEW => "all-views.event.events.update",
    ];

    const EVENTOVERVIEW = [
        URI => "event-overview",
        VIEW => "all-views.event.event-details.index",
    ];

    const EVENTORDERRUNING = [
        URI => "running",
        VIEW => "all-views.event.event-order.running",
    ];
    const EVENTORDERCOMPLATE = [
        URI => "complate",
        VIEW => "all-views.event.event-order.complate",
    ];
    const EVENTORDERRUNNING = [
        URI => "refund",
        VIEW => "all-views.event.event-order.refund",
    ];
    const EVENTORDERVIEWS = [
        URI => "order-view",
    ];



    const EVENTINBOX = [
        URL => "inbox",
        VIEW => "all-views.event.support.vendor-index",
    ];
    const EVENTINBOXSTATUS = [
        URL => "inbox-status",
        VIEW => "",
    ];
    const EVENTINBOXVIEW = [
        URL => "inbox-view",
        VIEW => "all-views.event.support.view-chat",
    ];

    const EVENTADMININBOX = [
        URL => "inbox",
        VIEW => "all-views.event.support.admin-index",
    ];

    const WITHDRAW = [
        URL => "list",
        VIEW => "all-views.event.withdraw.list",
    ];

    const WITHDRAWVIEW = [
        VIEW => "all-views.event.withdraw.view",
    ];
    const QRTODAYLIST = [
        URL => "/",
        VIEW => "all-views.event.qr-verify.list",
    ];
    const QRTODAYINFORMATION = [
        URL => "information",
        VIEW => "all-views.event.qr-verify.view",
    ];
    const QRTODAYSUBMIT = [
        URL => "verify-user",
    ];
    const ADDEMPLOYEE = [
        URL => 'add-employee',
        URI => 'edit',
        VIEW => "all-views.event.employee.add",
    ];
    const EMPLOYEELIST = [
        URI => 'employee-list',
        VIEW => "all-views.event.employee.list",
        REDIRECT => "event-vendor.employee.employee-list",
    ];

    const EMPLOYEEUPDATE = [
        URI => "update",
        VIEW => "all-views.event.employee.edit",
    ];
    const EMPLOYEESTATUSUPDATE = [
        URI => "status-update",
        URL => "employee-delete",
    ];
    const CHECHEMAILPHONE = [
        URI => "check-value",
    ];
}