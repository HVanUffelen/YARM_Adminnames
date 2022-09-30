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
    private static $searchController;

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

    public function list()
    {
        $q = '';
        return view('dlbt.data_cleaning.names.duplicateNameCleaningList')->with(self::addDuplicateData($q));
    }

    public function addDataList()
    {
        if (Auth()->user()->can('admin'))
            return view('dlbt.data_cleaning.names.addDataList')->with(self::addUncheckedData(false));
        elseif (Auth()->user()->can('edit-names'))
            return view('dlbt.data_cleaning.names.addDataList')->with(self::addUncheckedData(true));
    }

    public function fileNotFoundList()
    {
        $q = '';
        return view('dlbt.data_cleaning.files.fileNotFoundlist')->with(self::addFileNotFoundData($q));
    }

    public function commentsOnIllustrationsList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.commentsOnIllustrationsList')->with(self::addCommentsOnIllustrations($q));
    }

    public function commentsOnTranslationList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.commentsOnTranslationList')->with(self::addCommentsOnTranslations($q));
    }

    public function commentsOnPrefacePostfaceList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.commentsOnPrefacePostfaceList')->with(self::addCommentsOnPrefacePostface($q));
    }

    public function commentsOnPublicationList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.commentsOnPublicationList')->with(self::addCommentsOnPublication($q));
    }

    public function originalTitleList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.originalTitleList')->with(self::addOriginalTitles($q));
    }

    public function publisherList()
    {
        $q = '';
        return view('dlbt.data_cleaning.refs.publisherList')->with(self::addPublishers($q));
    }

    static function addPublishers($q)
    {
        $colNames = ['publisher', 'quantity'];
        $paginationValue = PaginationController::getPaginationItemCount();

        $query = Ref::select('id as ref_id', 'publisher', DB::raw('COUNT(publisher) as quantity'))
            ->where('publisher', '!=', '')
            ->groupBy('publisher')
            ->havingRaw('count(publisher) > 1');

        if ($q != '') {
            $query->where('publisher', 'like', '%' . $q . '%');
        }

        $data = [
            'publishers' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    public function confirmPublisher(Request $request)
    {
        try {
            $changed_ids = [];
            foreach ($request->select as $selected_id) {
                $ref = Ref::find($selected_id);
                if ($ref->publisher != $request->edit) {
                    foreach (Ref::where('publisher', '=', $ref->publisher)->get() as $refSamePublisher) {
                        array_push($changed_ids, $refSamePublisher->id);
                        $refSamePublisher->publisher = $request->edit;
                        $refSamePublisher->update();
                    }
                }
            }
            return redirect('/dlbt/publishers/?page=' . $request->pageUrl)
                ->with('alert-success', 'Succesfully changed publisher for Ref(s) with id(s) ' . implode(", ", $changed_ids) . ' to "' . $request->edit . '".');
        } catch (\Throwable $th) {
            return redirect('/dlbt/publishers/?page=' . $request->pageUrl)
                ->with('alert-danger', $th);
        }
    }

    static function addCommentsOnPublication($q)
    {
        $colNames = ['ref_id','title','subtitle','comments_on_publication'];
        $paginationValue = PaginationController::getPaginationItemCount();

        $query = Ref::select('id as ref_id', 'title', 'subtitle', 'comments_on_publication')
            ->whereNotNull('comments_on_publication')
            ->where('comments_on_publication', '!=', '');

        // TODO : Exceptions for comments on publication
        // if (Storage::exists('/' . 'exeptionsCOOriTitle.txt')) {
        //     $exeptions = explode(',', Storage::get('/' . 'exeptionsCOOriTitle.txt'));
        //     foreach ($exeptions as $exeption){
        //         $query->where('orig_title', 'not like', '%' . $exeption . '%');
        //     }
        // }

        if ($q != '') {
            $query->where('comments_on_publication', 'like', '%' . $q . '%');
        }

        $data = [
            'comments_on_publication' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    public function editCommentsOnPublication(Request $request)
    {
        try {
            $ref = Ref::find($request->id);
            $ref->title = $request->title;
            $ref->subtitle = $request->subtitle;
            $ref->comments_on_publication = $request->comments_on_publication;
            $ref->update();
            return Ref::select('id as ref_id', 'title', 'subtitle', 'comments_on_publication')->find($request->id);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    static function addOriginalTitles($q)
    {
        $colNames = ['ref_id','original_title'];
        $paginationValue = PaginationController::getPaginationItemCount();

        $query = Ref::select('id as ref_id', 'orig_title as original_title');

        $query->where(function ($query) {
            $query->whereNotNull('orig_title');
            $query->where('orig_title', '!=', '');
        });

        $query->where(function ($query) {
            $query->orWhere('orig_title', 'regexp', '[( ][12][0-9]{3}[)]');
            $query->orWhere('orig_title', 'regexp', '[,][ ][12][0-9]{3}(?!-)(?!\/)');
            $query->orWhere('orig_title', 'regexp', '[ ][(]*[^(]+[:,].*?[)]');
        });

        if (Storage::exists('/' . 'exeptionsEditCOOriTitle.txt')) {
            $exeptions = explode(',', Storage::get('/' . 'exeptionsCOOriTitle.txt'));
            foreach ($exeptions as $exeption){
                $query->where('orig_title', 'not like', '%' . $exeption . '%');
            }
        }

        if ($q != '') {
            $query->where('orig_title', 'like', '%' . $q . '%');
        }

        $data = [
            'original_titles' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    static function addCommentsOnTranslations($q)
    {
        $colNames = ['ref_id','comments_on_translation'];
        $paginationValue = PaginationController::getPaginationItemCount();

        $query = Ref::select('id as ref_id', 'comments_on_translation')
            ->whereNotNull('comments_on_translation');

        if (Storage::exists('/' . 'exeptionsCOTr.txt')) {
            $exeptions = explode('#',Storage::get('/' . 'exeptionsCOTr.txt'));

            $query->where(function ($query) use ($exeptions){
                $query->where(function ($query) {
                    $query->where('comments_on_translation','!=','');
                });
            })->where(function ($query) use ($exeptions){
                foreach ($exeptions as $exeption){
                    $query->Where('comments_on_translation', '!=', $exeption);
                };
            });
        }

        if ($q != '') {
            $query->where('comments_on_translation', 'like', '%' . $q . '%');
        }

        $data = [
            'comments_on_translation' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    static function addCommentsOnIllustrations($q)
    {
        $colNames = ['ref_id','comments_on_illustrations'];
        $paginationValue = PaginationController::getPaginationItemCount();

        // $commentsOnTranslations = array_unique(Ref::where('comments_on_translations','!=', '')->pluck('commments_on_translations'),asc);

        $query = Ref::select('id as ref_id', 'comments_on_illustrations')
            ->whereNotNull('comments_on_illustrations');

        if (Storage::exists('/' . 'exeptionsCOIll.txt')) {
            $exeptions = explode(',',Storage::get('/' . 'exeptionsCOIll.txt'));

            $query->where(function ($query) use ($exeptions){
                $query->where(function ($query) {
                    $query->whereNotNull('comments_on_illustrations');
                    $query->where('comments_on_illustrations','!=','');
                });
            })->Where(function ($query) use ($exeptions){
                foreach ($exeptions as $exeption){
                    $query->where('comments_on_illustrations', '!=', $exeption);
                }
            });
        }

        if ($q != '') {
            $query->where('comments_on_illustrations', 'like', '%' . $q . '%');
        }

        $data = [
            'comments_on_illustrations' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    static function addCommentsOnPrefacePostface($q)
    {
        $colNames = ['ref_id','comments_on_preface_postface'];
        $paginationValue = PaginationController::getPaginationItemCount();

        $query = Ref::select('id as ref_id', 'comments_on_preface_postface')
            ->whereNotNull('comments_on_preface_postface');

        if (Storage::exists('/' . 'exeptionsCOIll.txt')) {
            $exeptions = explode('#',Storage::get('/' . 'exeptionsCOPrPo.txt'));

            $query->where(function ($query) use ($exeptions){
                $query->where(function ($query) {
                    $query->whereNotNull('comments_on_preface_postface');
                    $query->where('comments_on_preface_postface','!=','');
                });
            })->Where(function ($query) use ($exeptions){
                foreach ($exeptions as $exeption){
                    $query->where('comments_on_preface_postface', '!=', $exeption);
                }
            });
        }

        if ($q != '') {
            $query->where('comments_on_preface_postface', 'like', '%' . $q . '%');
        }

        $data = [
            'comments_on_preface_postface' => $query->paginate($paginationValue),
            'colNames' => $colNames
        ];

        return $data;
    }

    static function addFileNotFoundData($q)
    {
        $colNames = ['name', 'local_name'];
        $paginationValue = PaginationController::getPaginationItemCount();
        $localFiles = [];

        $files = File::select('id', 'name')
            ->where('esearch', 'like', '%not f%');

        if ($q != '') {
            $files->where('name', 'like', '%' . $q . '%');
        }

        foreach ($files->get() as $file) {
            $localFile = self::findLocalFile($file);
            $dataclean_file['id'] = $file->id;
            $dataclean_file['local_name'] = $localFile;
            array_push($localFiles, $dataclean_file);
        }

        $data = [
            'files' => $files->paginate($paginationValue),
            'localFiles' => $localFiles,
            'colNames' => $colNames
        ];

        return $data;
    }

    public static function findLocalFile($file)
    {
        $allLocalFiles = Storage::allFiles('DLBTUploads');
        $validChars = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz123456789_,. ';

        try {
            // Get all words from the DB file
            $searchLike = preg_split('/[\s,_-]+/', pathinfo($file->name, PATHINFO_FILENAME));
            $searchLikeCount = count($searchLike);

            // Remove words with special characters
            foreach ($searchLike as $i => $term) {
                foreach (str_split($term) as $char) {
                    if (!in_array($char, str_split($validChars))) {
                        unset($searchLike[$i]);
                        break;
                    }
                }
            }
            $searchLikeCountNoSpecial = count($searchLike);

            foreach ($allLocalFiles as $localFile) {
                // Get all words in the local file
                $localFile = ltrim(substr($localFile, strpos($localFile, '/', 1)), '/');
                $localFileWords = preg_split('/[\s,_-]+/', pathinfo($localFile, PATHINFO_FILENAME));

                // Check if the localFile contains non-special-character words from our DB file
                $intersect = array_intersect($localFileWords, $searchLike);

                // If the amount of words without special characters is the same as the amount of intersected words
                // && the total amount of words in the DB file is the same as in the local file
                // THEN add the local file name to the file object
                if ($searchLikeCountNoSpecial == count($intersect)
                    && $searchLikeCount == count($localFileWords)) {
                    return $localFile;
                }
            }
        } catch (\Throwable $th) {
            return $th;
        }
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

    public function changeFileNames()
    {
        $files = File::where('esearch', 'like', '%not f%')->get();
        $successIds = [];
        $failedIds = [];
        foreach ($files as $file) {
            try {
                $localName = $file->name;
                $localName = str_replace('ö', 'Ф', $localName);
                $localName = str_replace('ü', 'Б', $localName);
                $localName = str_replace('ä', 'Д', $localName);
                $localName = str_replace('ß', 'c', $localName);
                Storage::move('DLBTUploads/' . $localName, 'DLBTUploads/' . $file->name);
                array_push($successIds, $file->id);
            } catch (\Throwable $th) {
                array_push($failedIds, $file->id);
            }
        }

        //try to store to Elasticsearch (if package = present)
        if (config('elasticsearch.elasticsearch_present') == 'True'){
            ElasticsearchController::upload2ElasticSearch(true, null);
        }


        return redirect()->back()
            ->with('alert-danger', 'Files with id(s) = ' . implode(", ", $failedIds) . ' could not be cleaned automatically.')
            ->with('alert-success', 'Files with id(s) = ' . implode(", ", $successIds) . ' are cleaned automatically.');
    }

    public function changeOneFileName(Request $request)
    {
        try {
            $file = File::find($request->id);
            $file->name = $request->name;
            $file->update();
            if ($request->local_name != $request->new_local_name) {
                Storage::move('DLBTUploads/' . $request->local_name, 'DLBTUploads/' . $request->new_local_name);
            }

            //upload to Elasticsearch (if package is present)
            if (config('elasticsearch.elasticsearch_present') == 'True') {
                ElasticsearchController::upload2ElasticSearch(false, $request->id);
            }

            return [$file, DataCleaningController::findLocalFile($file), Storage::has('DLBTUploads/' . $request->new_local_name)];
        } catch (\Throwable $th) {
            return $th;
        }
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

            return redirect('/dlbt/dataCleaningManual')
                ->with(self::addDataCleanData($selected_id))
                ->with('alert-success', 'The name(s) with id(s) ' . implode(", ", $changed_ids) . ' have been set to the selected name with id ' . $selected_id . ' successfully');

        } catch (\Throwable $th) {
            return redirect()->back()->withInput()->with('alert-danger', __('Error. Cleaning data failed'));
        }
    }

    public function getData(Request $request)
    {
        $data = [
            'names' => json_decode($request->data)
        ];

        return view('dlbt.data_cleaning.names.addDataListConfirm')->with($data);
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

    public function updateViaf()
    {
        // Get all names with a VIAF_id that have not already been updated this month
        $viafNames = Name::where('VIAF_id', '!=', '')
            ->whereNotNull('VIAF_id')
            ->where('updated_at', '<', date('Y-m-1 00:00:00'))
            ->get();

        $count = 0;
        $total = count($viafNames);

        if ($total <= 0) {
            return redirect()->back()->with('alert-warning', __('All VIAF data is up to date'));
        }

        foreach ($viafNames as $name) {
            $response = Http::get('https://www.viaf.org/viaf/' . trim($name->VIAF_id) . '/viaf.json')->json();

            // Get all duplicate names

            // Get all subfields
            $subfields = array();
            if (isset($response['x400s'])) {
                if (!isset($response['x400s']['x400']['datafield']['subfield'])) {
                    foreach ($response['x400s']['x400'] as $x400) {
                        array_push($subfields, $x400['datafield']['subfield']);
                    }
                } else {
                    array_push($subfields, $response['x400s']['x400']['datafield']['subfield']);
                }
            }

            // Get names from subfields
            $names = array();
            if (count($subfields) > 0) {
                foreach ($subfields as $subfield) {
                    if (!isset($subfield['#text'])) {
                        $viafName = '';
                        foreach ($subfield as $subItem) {
                            $viafName .= (' ' . $subItem['#text']);
                        }
                        $viafName = trim($viafName);
                        array_push($names, $viafName);
                    } else {
                        array_push($names, $subfield['#text']);
                    }
                }
            }

            // Remove duplicate names
            $uniqueNames = array();
            if (count($names) > 0) {
                foreach ($names as $unfilteredName) {
                    $nameNoSpecialChars = preg_replace('/[^\x00-\x7F]/', '', $unfilteredName);
                    if (!in_array($nameNoSpecialChars, $uniqueNames)) array_push($uniqueNames, $unfilteredName);
                }
            }

            // Get xlinks
            $xLinks = array();
            if (isset($response['xLinks'])) {
                if (is_array($response['xLinks']['xLink'])) {
                    foreach ($response['xLinks']['xLink'] as $xLink) {
                        if (is_string($xLink)) {
                            array_push($xLinks, $xLink);
                        } elseif (isset($xLink['#text'])) {
                            array_push($xLinks, $xLink['#text']);
                        } else {
                            $response['xLinks']['xLink']['#text'];
                        }
                    }
                } else {
                    array_push($xLinks, $response['xLinks']['xLink']);
                }
            }

            // Get ISNI ID
            $ISNI_id = NULL;
            if (isset($response['sources'])) {
                if (!isset($response['sources']['source']['#text'])) {
                    foreach ($response['sources']['source'] as $source) {
                        if (str_starts_with($source['#text'], 'ISNI')) {
                            $ISNI_id = $source['@nsid'];
                        }
                    }
                } else {
                    if (str_starts_with($response['sources']['source']['#text'], 'ISNI')) {
                        $ISNI_id = $response['sources']['source']['@nsid'];
                    }
                }
            }

            // Get Nationality
            $nationality = NULL;
            if (isset($response['nationalityOfEntity'])) {
                if (!isset($response['nationalityOfEntity']['data']['text'])) {
                    $nationality = $response['nationalityOfEntity']['data'][0]['text'];
                } else {
                    $nationality = $response['nationalityOfEntity']['data']['text'];
                }
            }

            // Update name
            $name->alternative_names = (count($uniqueNames) == 1) ? $uniqueNames[0] : implode(';', $uniqueNames);
            $name->xLink = (count($xLinks) == 1) ? $xLinks[0] : implode(';', $xLinks);
            if (!isset($name->ISNI_id)) $name->ISNI_id = $ISNI_id;
            if (!isset($name->nationality)) $name->nationality = $nationality;
            if (!isset($name->gender)) $name->gender = $response['fixed']['gender'];
            if (!isset($name->birth_year)) $name->birth_year = $response['birthDate'] === '0' ? null : intval($response['birthDate']);
            if (!isset($name->death_year)) $name->death_year = $response['deathDate'] === '0' ? null : intval($response['deathDate']);

            $name->updated_at = date('Y-m-d H:m:s');
            $name->update();

            // Show progress in terminal
            $count++;
            //error_log('Completed name: ' . $count . '/' . $total . ' (ID: ' . $name->id . ')');
        }

        return redirect()->back()->with('alert-success',  __('VIAF data updated') . ' (' . $count . ' names)');
    }
}
