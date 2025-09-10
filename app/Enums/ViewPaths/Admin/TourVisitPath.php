<?php

namespace App\Enums\ViewPaths\Admin;

enum TourVisitPath
{
    const ADDTRAVEL =[
        URI=>"add-tour",
        VIEW=>"admin-views.tour_and_travels.tour_visit.add-tour",
    ];

    const TRAVELLIST = [
        URI => "tour-list",
        VIEW => "admin-views.tour_and_travels.tour_visit.tour-list",
        REDIRECT => "admin.tour_visits.tour-list",
    ];
    const TRAVELSTATUS = [
        URI => "traveller-status-change",
        URL =>"traveller-company-status",
    ];
    const TRAVELUPDATE = [
        URI=>"edit-tour",
        VIEW=>"admin-views.tour_and_travels.tour_visit.update-tour",
    ];
    const TRAVELDELETE = [
        URI => "tour-delete",
    ];

    const TRAVELVIEW =[
        URI=>"overview",
        VIEW=>"admin-views.tour_and_travels.tour_visit.information",
    ];
    
    const IMAGEREMOVE =[
        URI =>"delete-image",
    ];
    const VISIT =[
        URI =>"add-visit",
        VIEW=>"admin-views.tour_and_travels.tour_visit.visit-add",
        REDIRECT =>"admin.tour_visits.add-visit",
    ];
    const VISITSTATUS =[
        URI =>'place-visit-status',
    ];

    const VISITDELETE = [
        URI =>"delete-place",
    ];

    const LEADS =[
        URL=>"leads",
        VIEW =>"admin-views.tour_and_travels.leads",
    ];
    const LEADSDELETE =[
        URI => 'leads-delete',
    ];
    const LEADSGET =[
        URI =>"tour-follow-up",
    ];
}
?>