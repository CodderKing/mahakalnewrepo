<?php
namespace App\Enums\ViewPaths\Admin;

enum TemplePath{
    const ADD =[
        URI=>"add_temple",
        VIEW=>"admin-views.temple.index",
    ];
    const LIST =[
        URI => "list",
        VIEW => "admin-views.temple.list",
    ];
    const UPDATE =[
        URI=>"temple_update",
        VIEW =>'admin-views.temple.update',
    ];
    const DELETE = [
        URI =>'',
    ];
    const DELETE_IMAGE= [
        URI=>"",
    ];
    const STATUS = [
        URI=>'status-update',

    ];
   
    const GET_CITIES=[
        URI =>"get-cities",
    ];

    const REVIEW =[
        URI =>'review',
        VIEW=>"admin-views.temple.review",
        URL =>"delete-review",
        SAVE =>'review-status',
        REDIRECT =>"admin.temple.review",
    ];
}

?>