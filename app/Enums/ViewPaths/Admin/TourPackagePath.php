<?php

namespace App\Enums\ViewPaths\Admin;

enum TourPackagePath
{
    const ADDPACKAGE =[
        URI =>"package",
        VIEW =>"admin-views.tour_and_travels.package.list",
        REDIRECT =>"admin.tour_package.view",
    ];

    const PACKAGEUPDATE =[
        URI =>"package-update",
        VIEW =>"admin-views.tour_and_travels.package.edit",
    ];
    const PACKAGESTATUS =[
        URI =>"status-update",
    ];

    const PACKAGEDELETE =[
        URI =>"delete",
    ];
}
?>