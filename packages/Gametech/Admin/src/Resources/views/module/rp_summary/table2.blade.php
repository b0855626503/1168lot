{!! $wdDataTable->table(['id'=>'wdDataTable', 'width' => '100%', 'class' => 'table table-striped table-sm'],true) !!}
<hr>
<table width="100%" class="table table-bordered" id="wdcustomfooter" style="font-size: medium">
    <tbody></tbody>
</table>


@push('scripts')
{{--    {!! $dataTable->scripts() !!}--}}
    {!! $wdDataTable->scripts() !!}
<script>
    $(function() {

        var table =  window.LaravelDataTables["wdDataTable"];
        window.LaravelDataTables["wdDataTable"].on('draw', function () {
            $("#wdcustomfooter tbody").html('');


            let html = '<tr>';
            html += '<th style="text-align:right;width:80%;color:blue">รวมยอดถอน (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().withdraw + '</th>';
            html += '</tr>';
            html += '<tr>';


            $("#wdcustomfooter tbody").append(html);


        });


    });
</script>
@endpush
