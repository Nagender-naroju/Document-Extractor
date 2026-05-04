<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // public function upload(Request $request)
    // {

    // //  dd(config('services.openai.key') );

    //     $request->validate([
    //         'documents.*' => 'required|file'
    //     ]);

    //     $docs = [];

    //     foreach ($request->file('documents') as $file) {
    //         $path = $file->store('documents');

    //         $doc = Document::create([
    //             'file_path' => $path,
    //             'status' => 'processing'
    //         ]);

    //         ProcessDocument::dispatch($doc);

    //         $docs[] = $doc;
    //     }

    //     return response()->json($docs);
    // }

    public function upload(Request $request)
    {
        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|max:20480', // 20MB max, any type
        ]);

        $docs = [];

        foreach ($request->file('documents') as $file) {
            $path = $file->store('documents');

            $doc = Document::create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'status' => 'processing'
            ]);

            ProcessDocument::dispatch($doc);

            $docs[] = $doc;
        }

        return response()->json($docs);
    }

    public function list()
    {
        return Document::latest()->get();
    }
}
