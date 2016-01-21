@extends('layouts.app')

@section('content')
<div class="container spark-screen">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Edit Alma Bib record</div>

                <div class="panel-body" id="loading">
                    Authorizing concepts… hold on…
                </div>

                <div class="panel-body" id="main" style="display:none;">

                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @endif

                    <tt>Network zone record ID: {{ $mms_id }}</tt>
                    <h3> {{ $record->title }}</h3>

                    <form class="form-horizontal" role="form" method="POST" action="{{ action('RecordController@update', $id) }}">
                        {!! csrf_field() !!}

                        <p>
                            Emnesystem som dropdown? Men bør isåfall være mulig å redigere flere..
                        </p>

                        <div id="selectedConcept" style="overflow:auto;height:150px;"></div>

                        <div class="control-group">
                            <label for="select-repo">Emner:</label>
                            <select id="select-repo" name="noubomn[]" multiple>
                            </select>
                        </div>

                        <p>
                            <strong>Dewey</strong>: TODO. Må laste inn data i Skosmos.
                        </p>

                        <button type="submit" class="btn btn-primary">Lagre</button>
                    </form>

                    <h3>MARC record</h3>
                    <pre>{{ $record }}</pre>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')

<script>

(function(){
    // Your base, I'm in it!
    var originalAddClassMethod = jQuery.fn.addClass;

    jQuery.fn.addClass = function(){
        // Execute the original method.
        var result = originalAddClassMethod.apply( this, arguments );

        // trigger a custom event
        jQuery(this).trigger('cssClassChanged');

        // return the original result
        return result;
    }
})();

(function() {

    function getType(types) {
        if (types.indexOf('http://data.ub.uio.no/onto#Place') != -1) return 'Place';
        if (types.indexOf('http://data.ub.uio.no/onto#Time') != -1) return 'Time';
        if (types.indexOf('http://data.ub.uio.no/onto#GenreForm') != -1) return 'GenreForm';
        if (types.indexOf('http://data.ub.uio.no/onto#VirtualCompoundConcept') != -1) return 'VirtualCompoundConcept';
        if (types.indexOf('http://data.ub.uio.no/onto#CompoundConcept') != -1) return 'CompoundConcept';
        if (types.indexOf('http://data.ub.uio.no/onto#KnuteTerm') != -1) return 'KnuteTerm';
        if (types.indexOf('Collection') != -1) return 'Facet';
        return '';
    }

    function simplifyResponse(response) {
        var lang = 'nb';
        var simple = _.pick(response, [
            'error',
            'prefLabel',
            'altLabel',
            'type',
            'uri',
            'identifier',
            'narrower',
            'broader',
            'scopeNote',
            'definition',
            'created',
            'modified',
            'related',
            'exactMatch',
            'closeMatch',
            'broadMatch',
            'narrowMatch',
            'relatedMatch',
        ]);

        simple.type = getType(simple.type);
        simple.prefLabel = simple.prefLabel[lang];
        simple.altLabel = simple.altLabel ? simple.altLabel[lang] : [];
        simple.localname = simple.uri.split(':')[1];

        // simple.definition = simple.definition['nb'];
        // simple.scopeNote = simple.scopeNote['nb'];

        return simple;
    }

    function get(args, cb) {
        var url, cacheKey;
        if (args.localname) {
            url = 'https://lsm.biblionaut.net/subjects/show/' + args.vocab + '/' + encodeURIComponent(args.localname);
            cacheKey = args.localname + '@' + args.vocab;
        } else {
            url = 'https://lsm.biblionaut.net/subjects/lookup?vocab=' + args.vocab + '&query=' + encodeURIComponent(args.heading);
            cacheKey = args.heading + '@' + args.vocab;

        }

        var cached = lscache.get(cacheKey);
        if (cached) {
            console.log('> Get from cache');
            return setTimeout(function() {
                cb(null, cached);
            }, 0);
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            error: function() {
                cb('API error');
            },
            success: function(response) {

                var simple = simplifyResponse(response)

                // Cache for 30 minutes
                // lscache.set(cacheKey, simple, 30);

                // Error
                if (simple.error) {
                    return cb(simple.error, null);
                }

                // Success
                cb(null, simple);
            }
        });

    }

    function authorizeHeading(heading, cb) {
        var vocab = 'realfagstermer';
        get({vocab: vocab, heading: heading}, cb);
    }

    var subjects = [
        @foreach ($noubomn as $subject)
            '{{ $subject }}',
            // TODO: Add type
        @endforeach
    ];

    console.log('Gobbl', subjects);

    var options = {
        valueField: 'localname',
        labelField: 'prefLabel',
        searchField: ['prefLabel', 'matchedPrefLabel', 'altLabel'],
        options: [],
        create: false,
        render: {
            item: function(item, escape) {
                // console.log(options.options);
                console.log('Render item: ', item);
                return '<div class="item">' +
                    (item.prefLabel ? '<span class="prefLabel">' + escape(item.prefLabel) + '</span>' : '') +
                    (item.type ? '<span class="conceptType">' + escape(item.type) + '</span>' : '') +
                '</div>';
            },
            option: function(item, escape) {
                // console.log(item);
                return '<div>' +
                    '<span class="title">' +

                        (item.matchedPrefLabel ? '<span class="matchedLabel">' + escape(item.matchedPrefLabel) + '</span>' : '') +

                        (item.matchedPrefLabel && item.lang ? ' <span class="matchedLabel">(' + escape(item.lang) + ')</span>' : '') +

                        (item.altLabel ? '<span class="matchedLabel">' + escape(item.altLabel) + '</span>' : '') +

                        (item.matchedPrefLabel || item.altLabel ? ' → ' : '') +

                        '<span class="prefLabel">' + escape(item.prefLabel) + '</span>' +

                        (item.type ? '<span class="conceptType">' + escape(item.type) + '</span>' : '') +

                    '</span>' +
                    // '<span class="description">' + escape(item.description) + '</span>' +
                    // '<ul class="meta">' +
                    //  (item.language ? '<li class="language">' + escape(item.language) + '</li>' : '') +
                    //  '<li class="watchers"><span>' + escape(item.watchers) + '</span> watchers</li>' +
                    //  '<li class="forks"><span>' + escape(item.forks) + '</span> forks</li>' +
                    // '</ul>' +
                '</div>';
            }
        },
        load: function(query, callback) {
            if (!query.length) return callback();

            var vocab = 'realfagstermer';

            $.ajax({
                url: 'https://lsm.biblionaut.net/subjects/search?labellang=nb&unique=true&vocab=' + encodeURIComponent(vocab) + '&query=' + encodeURIComponent(query + '*'),
                type: 'GET',
                error: function() {
                    callback();
                },
                success: function(response) {

                    // Filter out strings for now
                    var results = response.results.filter(function(result) {
                        result.type = getType(result.type);
                        return result.prefLabel.indexOf(' : ') == -1;
                    });

                    callback(results);
                }
            });
        }
    };

    async.map(subjects, authorizeHeading, function(err, results){
        $('#loading').hide();
        $('#main').show();
        initSelectize(results);
    });

    function rebind() {
        $(".selectize-input .item").off('cssClassChanged').on('cssClassChanged', function(){
            console.log('Select', $(this).data('value'));
            get({vocab:'realfagstermer', localname: $(this).data('value')}, function(err, concept) {
                console.log(concept);
                $('#selectedConcept').html(JSON.stringify(concept));
            });
        });
    }

    function initSelectize(subjects) {

        console.log('>INIT:', subjects);

        options.options = subjects;

        var $select = $('#select-repo').selectize(options);
        console.log($select);
        var selectize = $select[0].selectize;
        console.log(selectize);

        selectize.on('item_add', function(value, $item) {
            rebind();
        });

        selectize.on('item_remove', function(value, $item) {
            rebind();
        });
        selectize.on('change', function(value, $item) {
            console.log('REMOVE', value, $item);
        });

        rebind();

        options.options.forEach(function(value) {
            console.log('Add item', value)
            selectize.addItem(value.localname, true);
        });
    }

})();

</script>

@endsection