<?php

namespace App\Enums\ViewPaths\Admin;

enum TourCabPath
{
    const ADDCAB =[
        URI =>"cab",
        VIEW =>"admin-views.tour_and_travels.cab-service.list",
        REDIRECT =>"admin.tour_cab_service.view",
    ];

    const CABUPDATE =[
        URI =>"cab-update",
        VIEW =>"admin-views.tour_and_travels.cab-service.edit",
    ];
    const CABSTATUS =[
        URI =>"status-update",
    ];

    const CABDELETE =[
        URI =>"delete",
    ];
}
?>