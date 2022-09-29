<?php

namespace Yarm\Adminnames\Http\Controllers;

use App\Models\Name;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Controllers\;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Name  $name
     * @return \Illuminate\Http\Response
     */
    public function show(Name $name)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Name  $name
     * @return \Illuminate\Http\Response
     */
    public function edit(Name $name)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Name  $name
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Name $name)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Name  $name
     * @return \Illuminate\Http\Response
     */
    public function destroy(Name $name)
    {
        //
    }

    public function getName(Request $request)
    {
        return Name::find($request->id);
    }

    public function getRefNames()
    {
        $q = '';
        return view('dlbt.names.refNames')->with(self::addRefNamesData($q, false));
    }

    public function getUncheckedRefNames()
    {
        $q = '';
        return view('dlbt.names.refNames')->with(self::addRefNamesData($q, true));
    }

    public static function addRefNamesData($q, $checked)
    {
        $paginationValue = PaginationController::getPaginationItemCount();
        $colNames = ['id','name','first_name','VIAF_id','WIKIDATA_id','checked'];

        if (Auth()->user()->can('edit-only-group-ref')){
            $groupIds = Group::listGroups4user(Auth()->user()->id, true);

            $query = Name::select('name', 'first_name', 'names.id as id', 'VIAF_id', 'WIKIDATA_id', 'checked');
            $query->leftJoin('name_ref', 'names.id', '=', 'name_ref.name_id')
                ->leftJoin('group_ref', 'group_ref.ref_id', '=', 'name_ref.ref_id');
            if ($checked) {
                $query->where('names.checked','=','false');
            }
            $query->whereIn('group_ref.group_id', $groupIds);
            $query->groupBy('id')
                ->orderBy('name')
                ->orderBy('first_name');

            if ($q != '') {
                $query->where('name', 'like', '%' . $q . '%');
            }
        }

        $data = [
            'names' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }
}
