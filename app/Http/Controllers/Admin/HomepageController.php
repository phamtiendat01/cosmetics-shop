<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class HomepageController extends Controller
{
    public function index()
    {
        return view('admin.homepage.index');
    }
}
