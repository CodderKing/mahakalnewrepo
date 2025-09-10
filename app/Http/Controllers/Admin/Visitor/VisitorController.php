<?php

namespace App\Http\Controllers\Admin\Visitor;

use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use App\Traits\FileManagerTrait;
use App\Utils\Helpers;
use DateTime;
use DateTimeZone;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitorController
{

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */

    public function visitor()
    {
        $visitor = Visitor::orderBy('created_at', 'desc')->paginate(10);

        return view('admin-views.visitor.visitor-list', compact('visitor'));
    }

}