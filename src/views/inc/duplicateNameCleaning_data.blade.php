<div class="adminContent">
    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($colNames as $colName)
                    <th>{{strtoupper(__($colName))}}</th>
                @endforeach
                <td colspan="2">@lang('Action')</td>
            </tr>
        </thead>
        <tbody>
            @foreach($duplicates as $duplicate)
                <tr id="tblData-id_{{ $duplicate->id }}">
                    @foreach ($colNames as $colName)
                        <td class="C-{{$colName}}">{{$duplicate->$colName}}</td>
                    @endforeach

                    <td>
                        {{-- // TODO Lang GER \\ --}}
                        <a id="edit-duplicate" class="fa fa-pencil mr-1 text-primary" href="{{ route('dataCleaning_manual', '&id=' . $duplicate->id) }}"
                            data-id={{$duplicate->id}} data-placement="top" data-toggle="popover" data-trigger="hover"
                            data-content="@lang('Go to the Data Cleaning tool to resolve this issue')."></a>
                        @if ($duplicate->checked == 'false')
                            <a id="confirm-checked" class="fa fa-check ml-1 text-success"
                                data-id={{$duplicate->id}} data-placement="top" data-toggle="popover" data-trigger="hover"
                                data-content="@lang('Set this name as pending. (No duplicate)')"></a>
                            <a id="confirm-unchecked" class="fa fa-times ml-1 text-danger" hidden
                                data-id={{$duplicate->id}} data-placement="top" data-toggle="popover" data-trigger="hover"
                                data-content="@lang('Set this name as unchecked. (Duplicate)')"></a>
                        @else
                            <a id="confirm-checked" class="fa fa-check ml-1 text-success" hidden
                                data-id={{$duplicate->id}} data-placement="top" data-toggle="popover" data-trigger="hover"
                                data-content="@lang('Set this name as pending. (No duplicate)')"></a>
                            <a id="confirm-unchecked" class="fa fa-times ml-1 text-danger"
                                data-id={{$duplicate->id}} data-placement="top" data-toggle="popover" data-trigger="hover"
                                data-content="@lang('Set this name as unchecked. (Duplicate)')"></a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $duplicates->links() }}
</div>
