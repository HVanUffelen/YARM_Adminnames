<?php

namespace Yarm\Adminnames\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Group;
use App\Models\Name;
use App\Models\Ref;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Yarm\Elasticsearch\ElasticsearchController;


class AdminnamesDataCleaningController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
        //$this->middleware('can:admin');
        $this->middleware('can:edit-names');
    }

    public function manual(Request $request)
    {
        $id = null;
        // Check if there is an ID (if came from Data Cleaning List)
        if ($request->id) {
            $id = $request->id;
        }
        return view('dlbt.data_cleaning.names.duplicateNameCleaningManual')->with(self::addDataCleanData($id));
    }

    public function addDataList()
    {
        if (Auth()->user()->can('admin'))
            return view('dlbt.data_cleaning.names.addDataList')->with(self::addUncheckedData(false));
        elseif (Auth()->user()->can('edit-names'))
            return view('dlbt.data_cleaning.names.addDataList')->with(self::addUncheckedData(true));
    }

    static function addDataCleanData($id)
    {
        // Get all names for the dropdown
        $names = Name::all();
        $duplicateNames = [];

        // Get all duplicate names for the selected ID
        if (isset($id)) {
            $selectedName = Name::find($id);
            $duplicateNameObjects = self::makeQuery4SearchDuplicates($selectedName->name)->get();
            $duplicateNames = json_decode(json_encode($duplicateNameObjects), true);

            foreach ($duplicateNames as $index => $duplicate) {
                // Set selected name as first element in array
                if ($duplicate['id'] == $id) {
                    $moveName = $duplicateNames[$index];
                    unset($duplicateNames[$index]);
                    array_unshift($duplicateNames, $moveName);
                }
            }

            $data = [
                'duplicateNames' => $duplicateNames
            ];
        }

        // initialise view data
        $data = [
            'names' => $names,
            'duplicateNames' => $duplicateNames
        ];

        return $data;
    }

    static function addUncheckedData($groups = false)
    {

        $specialCharacters = str_split('([|\/])+-=@?!., ');
        $colNames = ['id', 'WIKIDATA_id', 'name', 'first_name', 'checked'];

        $query = Name::select('name', 'first_name', 'names.id', 'WIKIDATA_id', 'checked');
        $query->leftJoin('name_ref', 'names.id', '=', 'name_ref.name_id')
            ->leftJoin('refs', 'refs.id', '=', 'name_ref.ref_id')
            ->rightJoin('group_ref', 'group_ref.ref_id', '=', 'name_ref.ref_id');

        if ($groups == true) {
            $groupIds = Group::listGroups4user(Auth()->user()->id, true);
            $query->whereIn('group_ref.group_id', $groupIds);
            $query->whereIn('checked', ['false', 'pending']);
            $query->where('name', '!=', '');
            $query->where('first_name', '!=', '');
        } else {
            $query->where(function ($query) use ($specialCharacters, $groups) {
                $query->where(function ($query) use ($specialCharacters, $groups) {
                    //reduce on refs for 'Dutch' and 'Author'
                    $query->where('refs.language_source_id', '=', 19);
                    $query->where('refs.primarytxt', '=', 'true');
                    $query->where('name_ref.role_id', '=', 2);

                    $query->whereIn('checked', ['false', 'pending']);
                    $query->where('name', '!=', '');
                    $query->where('first_name', '!=', '');

                    $query->where(function ($query) use ($specialCharacters) {
                        foreach ($specialCharacters as $char) {
                            $query->where(function ($query) use ($char) {
                                $query->where('name', 'not like', '%' . $char . '%');
                                $query->where('first_name', 'not like', '%' . $char . '%');
                            });
                        }
                    });
                });


                $query->orWhere(function ($query) use ($specialCharacters) {
                    $query->where('checked', '=', 'auto');
                    $query->where('auto_success', '=', 'pending');
                });
            });
        }
        $query->groupBy('names.id');
        if (auth()->user()->id == 87)
            $query->orderBy('name','desc');
        else
            $query->orderBy('name','asc');
        $query->orderBy('first_name');

        $data = [
            'uncheckedData' => $query->take(20)->get(),
            'colNames' => $colNames
        ];

        return $data;
    }

    public function getData(Request $request)
    {
        $data = [
            'names' => json_decode($request->data)
        ];

        return view('Adminnames::addDataListConfirm')->with($data);
    }

    public function confirmData(Request $request)
    {
        // dd($request);
        $changed_ids = [];
        try {
            foreach ($request->id as $index => $id) {
                if (Name::find($id)) {
                    $name = Name::find($id);
                    if (isset($name)) {
                        foreach ($name->getFillable() as $attribute) {
                            if (isset($request->$attribute[$index])) {
                                $name->$attribute = $request->$attribute[$index];
                            }
                        }
                        array_push($changed_ids, $id);
                        $name->update();
                    } else {
                        return redirect('/dlbt/addDataList')->with(self::addUncheckedData())
                            ->with('alert-danger', 'No Name with id = ' . $id . ' in Table');
                    }
                }
            }
            return redirect('/dlbt/addDataList')->with(self::addUncheckedData())
                ->with('alert-success', __('The name(s) with id(s) ' . implode(", ", $changed_ids) . ' have been updated successfully.'));
        } catch (\Throwable $th) {
            return redirect('/dlbt/addDataList')->with(self::addUncheckedData())
                ->with('alert-danger', __('Error. Storing data in database failed.'));
        }
    }


}
