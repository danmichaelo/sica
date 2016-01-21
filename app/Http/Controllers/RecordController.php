<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use File_MARC_Subfield;
use File_MARC_Data_Field;
use Scriptotek\Alma\Client as AlmaClient;
use Scriptotek\Alma\Exception\ClientException as AlmaClientException;

class RecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // TODO: we could cache ($id => $nzId) in a fast key/value store
    function getRecord(AlmaClient $alma, $id) {
        $bib = $alma->bibs->fromBarcode($id);
        if (!is_null($bib)) {
            return $bib->getNzRecord();
        }
        try {
            return $alma->nz->bibs[$id];
        } catch (AlmaClientException $e) {}

        return $alma->bibs[$id]->getNzRecord();
    }

    public function edit(AlmaClient $alma, Request $request, $id)
    {
        try {
            $bib = $this->getRecord($alma, $id);
        } catch (AlmaClientException $e) {
            return redirect()->action('RecordController@index')
                ->withErrors(['msg' => 'Barcode or mms id not found']);
        }

        $record = $bib->record;

        $noubomn = [];

        foreach ($record->getSubjects('noubomn') as $subject) {

            // At the moment, just handle single-component subjects
            if (count($subject->parts) == 1) {
                $noubomn[] = $subject->parts[0];
            }
            // foreach ($subject->getSubfields() as $sf) {
            //     echo " - " . $sf->getCode() . ' ' . $sf->getData() . ' - ';
            // }
        }

//        dd($record->subjects);

        return view('records.edit', [
            'id' => $id,
            'mms_id' => $bib->mms_id,
            'noubomn' => $noubomn,
            'record' => $record
        ]);
    }

    public function update(AlmaClient $alma, Request $request, $id)
    {
        $bib = $this->getRecord($alma, $id);
        $record = $bib->record;

        $oldSubjects = [];
        foreach ($record->getSubjects('noubomn') as $subject) {
            // At the moment, just handle single-component subjects
            if (count($subject->parts) != 1) {
                continue;
            }
            $term = strval($subject->parts[0]);
            $oldSubjects[$term] = $subject;
        }

        $newSubjects = $request->get('noubomn');

        $toDelete = [];
        $toAdd = [];

        foreach ($newSubjects as $term) {
            if (!in_array($term, $oldSubjects)) {
                // echo "Add $term\n";
                $toAdd[] = $term;

                // @TODO: MARC-kode basert på oppslag mot autoritetsregisteret
                $field = new File_MARC_Data_Field('650', [
                    new File_MARC_Subfield('a', $term),
                    new File_MARC_Subfield('2', 'noubomn'),
                    // new File_MARC_Subfield('0', 'TODO'),
                ], null, '7');

                // @TODO: Use insertField instead
                $record->appendField($field);
            }
        }

        foreach ($oldSubjects as $term => $subject) {
            if (!in_array($term, $newSubjects)) {
                // echo "Delete $term\n";
                $toDelete[] = $term;
                $subject->delete();
            }
        }

        if ($bib->save()) {
            return redirect()->action('RecordController@edit', $id)
                ->with('status', 'Record saved. Terms added: ' . (count($toAdd) ? implode(', ', $toAdd) : '(ingen)') . '. Terms removed: ' . (count($toDelete) ? implode(', ', $toDelete) : '(ingen)') . '.');
        } else {
            return redirect()->action('RecordController@edit', $id)
                ->with('status', 'Oh noes, failed to save record.');
        }

    }

    public function index()
    {
        return view('records.index');
    }

    public function lookup(Request $request)
    {
        return redirect()->action('RecordController@edit', $request->get('query'));
    }

}
