<?php

namespace Yarm\Adminnames\Http\Controllers;

use App\Models\Name;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NameController;

class AdminnamesController extends Controller
{
    /**
     * NameController constructor.
     */
    public function __construct()
    {
//        $this->middleware('can:create-ref')->only('create', 'store');
//        $this->middleware('can:edit-ref')->only('edit');
//        $this->middleware('can:update-ref')->only('update');
//        $this->middleware('can:delete-ref')->only('destroy');
    }

   public function getName(Request $request)
    {
        return Name::find($request->id);
    }

    public function getRefNames()
    {
        $q = '';
        return view('ydbviews.names.refNames')->with(NameController::addRefNamesData($q, false));
    }

    public function getUncheckedRefNames()
    {
        $q = '';
        return view('ydbviews.names.refNames')->with(NameController::addRefNamesData($q, true));
    }

}
