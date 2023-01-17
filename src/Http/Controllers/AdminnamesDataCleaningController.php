<?php

namespace Yarm\Adminnames\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaginationController;
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

    public function setChecked(Request $request)
    {
        // Toggle checked on check/uncheck button
        if (Name::find($request->id)) {
            $name = Name::find($request->id);
            $name->checked === 'pending' ? $name->checked = 'false' : $name->checked = 'pending';
            $name->save();
        }
    }

    public function list()
    {
        $q = '';
        return view('adminnames::duplicateNameCleaningList')->with(self::addDuplicateData($q));
    }

    static function addDuplicateData($q)
    {
        $paginationValue = PaginationController::getPaginationItemCount();
        $colNames = ['id', 'VIAF_id', 'name', 'first_name', 'checked'];

        // Get all names that are double in the DB
        // and set 'colnames'

        $data = [
            'duplicates' => self::makeQuery4SearchDuplicates($q)->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    public function manual(Request $request)
    {
        $id = null;
        // Check if there is an ID (if came from Data Cleaning List)
        if ($request->id) {
            $id = $request->id;
        }
        return view('adminnames::duplicateNameCleaningManual')->with(self::addDataCleanData($id));
    }

    private static function makeQuery4SearchDuplicates($q)
    {

        $query = DB::table('names AS a');
        $query->select('a.name', 'a.first_name', 'a.id', 'a.VIAF_id', 'a.checked');
        $query->join(
            DB::raw('(SELECT id, LEFT(first_name, 1) AS firstChar, name FROM names GROUP BY name, firstChar HAVING COUNT(name) > 1) as b'),
            function ($join) {
                $join->on('a.name', '=', 'b.name');
                $join->on('firstChar', '=', DB::raw('LEFT(a.first_name, 1)'));
            });

        $query->whereIn('checked', ['false', 'auto']);

        // If there is a search string, search in the existing query
        if ($q != '') {
            $query->where('a.name', 'like', '%' . $q . '%');
        }
        $query->union(self::namesWithCommas($q));

        return $query;

    }

    private static function namesWithCommas($q)
    {

        $namesWithCommas = Name::select('name', 'first_name', 'id', 'VIAF_id', 'checked')
            ->where('name', 'like', '%,%');


        // If there is a search string, search in the existing query
        if ($q != '') {
            $namesWithCommas->where('name', 'like', '%' . $q . '%');
        }

        return $namesWithCommas;
    }

    public function addDataList()
    {
        if (Auth()->user()->can('admin'))
            return view('adminnames::addDataList')->with(self::addUncheckedData(false));
        elseif (Auth()->user()->can('edit-names'))
            return view('adminnames::addDataList')->with(self::addUncheckedData(true));
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

    public function clean(Request $request)
    {
        // dd($request);
        try {
            // Get the ID of the selected name
            $selected_id = $request->id[$request->selected_person];
            $changed_ids = [];

            foreach ($request->id as $index => $id) {
                if ($id === "0") {
                    // Create new name
                    $newName = new Name;
                    foreach ($newName->getFillable() as $attribute) {
                        if ($attribute == 'checked') {
                            $newName->$attribute = 'true';
                        } else if (isset($request->$attribute[$index])) {
                            $newName->$attribute = $request->$attribute[$index];
                        }
                    }
                    $newName->save();
                    // If this person was selected, overwrite the $selected_id
                    if ($request->selected_person === $index . '') {
                        $selected_id = $newName->id;
                    } else {
                        array_push($changed_ids, $id);
                    }
                } elseif ($request->selected_person != $index . '') {
                    array_push($changed_ids, $id);
                }
            }

            // Loop across all IDs
            foreach ($request->id as $id) {
                if (Name::find($id)) {
                    $name = Name::find($id);
                    if (isset($name)) {
                        if ($id != $selected_id) {
                            // Change the Name_ref table
                            $name_refs = $name->refs()->get();
                            foreach ($name_refs as $name_ref) {
                                $name->refs()->updateExistingPivot($name_ref->id, array('name_id' => $selected_id), true);
                            }
                            // Delete old record from Name table
                            $name->delete();
                        } // For the selected ID
                        else {
                            // Set name to checked
                            $name->update(['checked' => 'true']);
                        }
                    } else {
                        //TODO Lang
                        return redirect()->back()->withInput()
                            ->with('alert-danger', 'No Name with id = ' . $id . ' in Table');
                    }
                }
            }

            // Return to the right view after data is cleaned
            // TODO Lang

            return redirect('/' . strtolower(config('yarm.sys_name')) . '/dataCleaningManual')
                ->with(self::addDataCleanData($selected_id))
                ->with('alert-success', 'The name(s) with id(s) ' . implode(", ", $changed_ids) . ' have been set to the selected name with id ' . $selected_id . ' successfully');

        } catch (\Throwable $th) {
            return redirect()->back()->withInput()->with('alert-danger', __('Error. Cleaning data failed'));
        }
    }

    static function addUncheckedData($groups = false)
    {

        $specialCharacters = str_split('([|\/])+-=@?!., ');
        $colNames = ['id', 'WIKIDATA_id', 'name', 'first_name', 'checked', 'information', 'address'];

        $query = Name::select('name', 'first_name', 'names.id', 'WIKIDATA_id', 'checked', 'information', 'address');
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
                    if (config('DB_DATABASE') == 'yarm4dlbt') {
                        $query->where('refs.language_source_id', '=', 19);
                        $query->where('refs.primarytxt', '=', 'true');
                        $query->where('name_ref.role_id', '=', 2);
                    }

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
        $query->orderBy('name', 'asc');
        $query->orderBy('first_name', 'asc');

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

        return view('adminnames::addDataListConfirm')->with($data);
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
                        return redirect('/' . strtolower(config('yarm.sys_name')) . '/addDataList')->with(self::addUncheckedData())
                            ->with('alert-danger', 'No Name with id = ' . $id . ' in Table');
                    }
                }
            }
            return redirect('/' . strtolower(config('yarm.sys_name')) . '/addDataList')->with(self::addUncheckedData())
                ->with('alert-success', __('The name(s) with id(s) ' . implode(", ", $changed_ids) . ' have been updated successfully.'));
        } catch (\Throwable $th) {
            return redirect('/' . strtolower(config('yarm.sys_name')) . '/addDataList')->with(self::addUncheckedData())
                ->with('alert-danger', __('Error. Storing data in database failed.'));
        }
    }

    public function confirmOne(Request $request)
    {
        try {
            json_decode($request);
            $name = Name::find($request->id);
            if (isset($name)) {
                foreach ($name->getFillable() as $attribute) {
                    if ($attribute == 'checked') {
                        $name->$attribute = 'true';
                    } else if ($attribute == 'auto_success') {
                        $name->$attribute = null;
                    } else if (isset($request->$attribute)) {
                        $name->$attribute = $request->$attribute;
                    }
                }
                $name->update();
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }


}
