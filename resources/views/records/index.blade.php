@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Lookup Alma Bib record</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ action('RecordController@lookup') }}">
                        {!! csrf_field() !!}

                        @if($errors->any())
                            <div class="alert alert-danger" role="alert">{{$errors->first()}}</div>
                        @endif

                        <div class="form-group{{ $errors->has('query') ? ' has-error' : '' }}">
                            <label class="col-md-4 control-label">Record ID or barcode</label>

                            <div class="col-md-6">
                                <input type="text" class="form-control" name="query" value="{{ old('query') }}">

                                @if ($errors->has('query'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('query') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-search"></i>Lookup
                                </button>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
