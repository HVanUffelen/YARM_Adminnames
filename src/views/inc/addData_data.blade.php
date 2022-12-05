@if (count($uncheckedData) > 0)
    <div id="addDataList" class="adminContent">
        <style>
            .T-information,
            .T-address,
            .C-information,
            .C-address{
                display: none;
            }
        </style>
        <table class="table table-bordered">
            <thead>
            <tr>
                @foreach ($colNames as $colName)
                    <th class="T-{{$colName}}">{{strtoupper(__($colName))}}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($uncheckedData as $ud)
                <tr id="tblData-id_{{ $ud->id }}">
                    @foreach ($colNames as $colName)
                        <td class="C-{{$colName}}">{{$ud->$colName}}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div id="wikiSearchLoader" class="loader" hidden>Loading...</div>
@else
    <div class="text-center">
        <p>@lang('There are no names that have not been checked for VIAF -and WIKI data').</p>
    </div>
@endif
