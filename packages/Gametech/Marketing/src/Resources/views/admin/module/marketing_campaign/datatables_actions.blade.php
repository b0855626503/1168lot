<button type="button" class="btn-xs btn  btn-info" onclick="editModal({{ $code }})"><i class="fa fa-edit"></i> แก้ไข
</button>
<a type="button" class="btn-xs btn  btn-danger" href="{{ route('admin.marketing_campaign.view',['id' => $code]) }}"><i class="fa fa-eye"></i> ดูข้อมูล
</a>
