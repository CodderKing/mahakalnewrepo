<?php

namespace App\Enums\ViewPaths\AllPaths;

enum TrusteesPath
{
    const DASHBOARD = [
        URL => "/",
        VIEW => "all-views.trustees.dashboard.index",
        REDIRECT => "partials-trustees.dashboard.index",
    ];

    const DASHWITHDRAW = [
        URL => "withdraw-request-add",
    ];

    const ADSADD = [
        URI => 'add',
        URL => "status-update",
        VIEW => "all-views.trustees.ads.add",
    ];

    const ADSLIST = [
        URI => 'list',
        VIEW => "all-views.trustees.ads.list",
        REDIRECT => "trustees-vendor.ads-management.list",
    ];

    const ADSUPDATE = [
        URI => 'update',
        VIEW => "all-views.trustees.ads.update",
    ];
    const ADSDELETE = [
        URI => 'delete',
    ];
    const ADSDETAILS = [
        URI => 'ads-details',
        VIEW => "all-views.trustees.ads.details",
    ];

    const PROFILEUPDATE = [
        URL => 'update',
        URI => 'edit',
        VIEW => "all-views.trustees.profile.update-profile",
    ];

    const TRUSTWITHDRAW = [
        URL => "/",
        VIEW => "all-views.trustees.withdraw.index",
    ];

    const TRUSTINBOX = [
        URL => "inbox",
        VIEW => "all-views.trustees.message.inbox",
    ];
    const TRUSTINBOXSTATUS = [
        URL => "inbox-status",
    ];
    const TRUSTINBOXVIEW = [
        URL => "view",
        URI => "replay",
        VIEW => "all-views.trustees.message.single-view",
    ];

    //amdin
    const TRUSTADMININBOX = [
        URL => "inbox",
        VIEW => "all-views.trustees.message.admin-inbox",
    ];
    const ADDEMPLOYEE = [
        URL => 'add-employee',
        URI => 'edit',
        VIEW => "all-views.trustees.employee.add",
    ];
    const EMPLOYEELIST = [
        URI => 'employee-list',
        VIEW => "all-views.trustees.employee.list",
        REDIRECT => "trustees-vendor.employee.employee-list",
    ];

    const EMPLOYEEUPDATE = [
        URI => "update",
        VIEW => "all-views.trustees.employee.edit",
    ];
    const EMPLOYEESTATUSUPDATE = [
        URI => "status-update",
        URL => "employee-delete",
    ];

    const CHECHEMAILPHONE = [
        URI => "check-value",
    ];
}