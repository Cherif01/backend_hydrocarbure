<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\CiterneDocument;
use App\Modules\Transport\Requests\CiterneDocumentRequest;
use App\Modules\Transport\Resources\CiterneDocumentResource;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Support\Facades\Auth;

class CiterneDocumentController extends Controller
{
    use ApiResponses, CloudflareUpload;

    private array $relations = [
        'citerne',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $documents = CiterneDocument::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            CiterneDocumentResource::collection($documents),
            "Liste des documents de citerne chargee avec succes."
        );
    }

    public function show(CiterneDocument $citerne_document)
    {
        $citerne_document->load($this->relations);

        return $this->successResponse(
            new CiterneDocumentResource($citerne_document),
            "Document de citerne charge avec succes."
        );
    }

    public function store(CiterneDocumentRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($request->hasFile('fichier_scan')) {
            $data['fichier_scan'] = $this->uploadFile($request->file('fichier_scan'), 'citerne_documents');
        }

        $document = CiterneDocument::create($data)->load($this->relations);

        logActivity("Creation d'un document de citerne", $document->toArray(), $document);

        return $this->successResponse(
            new CiterneDocumentResource($document),
            "Document de citerne cree avec succes."
        );
    }

    public function update(CiterneDocumentRequest $request, CiterneDocument $citerne_document)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('fichier_scan')) {
            $this->deleteFile($citerne_document->fichier_scan, 'citerne_documents');
            $data['fichier_scan'] = $this->uploadFile($request->file('fichier_scan'), 'citerne_documents');
        }

        $oldDocument = $citerne_document->replicate()->fill($citerne_document->getAttributes());

        $citerne_document->update($data);
        $citerne_document->load($this->relations);

        logActivity("Mise a jour d'un document de citerne", [
            'oldDocument' => $oldDocument->toArray(),
            'newDocument' => $citerne_document->toArray(),
        ], $citerne_document);

        return $this->successResponse(
            new CiterneDocumentResource($citerne_document),
            "Document de citerne mis a jour avec succes."
        );
    }

    public function destroy(CiterneDocument $citerne_document)
    {
        $citerne_document->load($this->relations);

        logActivity("Suppression d'un document de citerne", $citerne_document->toArray(), $citerne_document);

        $this->deleteFile($citerne_document->fichier_scan, 'citerne_documents');
        $citerne_document->delete();

        return $this->noContentSuccessResponse("Document de citerne supprime avec succes.");
    }
}
