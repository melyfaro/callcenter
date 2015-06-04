<div class="row">
    <div class="col-lg-12">
        <div class="form-group">
            {!! Form::label('name',Lang::get('user.name')) !!}
            {!!Form::text('name',null,["class"=>"form-control"])!!}
        </div>
        <div class="form-group">
            {!! Form::label('text',Lang::get('user.email')) !!}
            {!! Form::text('email',null,["class"=>"form-control"]) !!}
        </div>
        <div class="form-group">
            {!! Form::label('text',Lang::get('user.send_email')) !!}
            {!! Form::text('send_email',null,["class"=>"form-control"]) !!}
        </div>
        <div class="form-group">
            {!!Form::label('phone',Lang::get('user.phone'))!!}
            {!!Form::text('phone',null,["class"=>"form-control"])!!}
        </div>
        <div class="form-group">
            {!! Form::label('role',Lang::get('user.role')) !!}
            @if(Auth::user()->role_id=="1")
                {!! Form::select('role_id',\App\Role::orderBy('sort','asc')->get(['id','title'])->lists('title','id'),null,['class'=>'form-control']) !!}
            @else
                {!! Form::select('role_id',\App\Role::orderBy('sort','asc')->where('visible',true)->get(['id','title'])->lists('title','id'),null,['class'=>'form-control']) !!}
            @endif
        </div>
        <div class="form-group">
            {!!Form::label('password',Lang::get('user.password'))!!}
            {!!Form::password('password',["class"=>"form-control"])!!}
        </div>
        <div class="form-group">
            {!!Form::label('password_confirmation',Lang::get('user.confirm_password'))!!}
            {!!Form::password('password_confirmation',["class"=>"form-control"])!!}
        </div>
        <div class="form-group">
            {!! Form::submit($submit,["class"=>"btn btn-primary"]) !!}
        </div>
    </div>
</div>