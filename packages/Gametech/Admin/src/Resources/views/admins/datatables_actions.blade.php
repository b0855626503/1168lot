{!! Form::open(['route' => ['wallet.admins.destroy', $code], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="{{ route('wallet.admins.show', $code) }}" class='btn btn-secondary btn-xs'>
        <i class="glyphicon glyphicon-eye-open"></i>
    </a>
    <a href="{{ route('wallet.admins.edit', $code) }}" class='btn btn-secondary btn-xs'>
        <i class="glyphicon glyphicon-edit"></i>
    </a>
    {!! Form::button('<i class="glyphicon glyphicon-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => "return confirm('Are you sure?')"
    ]) !!}
</div>
{!! Form::close() !!}
