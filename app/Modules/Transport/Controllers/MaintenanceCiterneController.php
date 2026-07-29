<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\MaintenanceCiterne;
use App\Modules\Transport\Requests\MaintenanceCiterneRequest;
use App\Modules\Transport\Resources\MaintenanceCiterneResource;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Support\Facades\Auth;

class MaintenanceCiterneController extends Controller
{
    use ApiResponses, CloudflareUpload;

    private array $relations = [
        'citerne',
        'createdBy',
        'updatedBy',
    ];

    public function index()
    {
        $maintenances = MaintenanceCiterne::with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            MaintenanceCiterneResource::collection($maintenances),
            "Liste des maintenances de citerne chargee avec succes."
        );
    }

    public function show(MaintenanceCiterne $maintenance_citerne)
    {
        $maintenance_citerne->load($this->relations);

        return $this->successResponse(
            new MaintenanceCiterneResource($maintenance_citerne),
            "Maintenance de citerne chargee avec succes."
        );
    }

    public function store(MaintenanceCiterneRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if ($request->hasFile('fichier_scan')) {
            $data['fichier_scan'] = $this->uploadFile($request->file('fichier_scan'), 'maintenance_citernes');
        }

        $maintenance = MaintenanceCiterne::create($data)->load($this->relations);

        logActivity("Creation d'une maintenance de citerne", $maintenance->toArray(), $maintenance);

        return $this->successResponse(
            new MaintenanceCiterneResource($maintenance),
            "Maintenance de citerne creee avec succes."
        );
    }

    public function update(MaintenanceCiterneRequest $request, MaintenanceCiterne $maintenance_citerne)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $oldMaintenance = $maintenance_citerne->replicate()->fill($maintenance_citerne->getAttributes());

        if ($request->hasFile('fichier_scan')) {
            $this->deleteFile($maintenance_citerne->fichier_scan, 'maintenance_citernes');
            $data['fichier_scan'] = $this->uploadFile($request->file('fichier_scan'), 'maintenance_citernes');
        }

        $maintenance_citerne->update($data);
        $maintenance_citerne->load($this->relations);

        logActivity("Mise a jour d'une maintenance de citerne", [
            'oldMaintenance' => $oldMaintenance->toArray(),
            'newMaintenance' => $maintenance_citerne->toArray(),
        ], $maintenance_citerne);

        return $this->successResponse(
            new MaintenanceCiterneResource($maintenance_citerne),
            "Maintenance de citerne mise a jour avec succes."
        );
    }

    public function destroy(MaintenanceCiterne $maintenance_citerne)
    {
        $maintenance_citerne->load($this->relations);

        logActivity("Suppression d'une maintenance de citerne", $maintenance_citerne->toArray(), $maintenance_citerne);

        $this->deleteFile($maintenance_citerne->fichier_scan, 'maintenance_citernes');
        $maintenance_citerne->delete();

        return $this->noContentSuccessResponse("Maintenance de citerne supprimee avec succes.");
    }
}
