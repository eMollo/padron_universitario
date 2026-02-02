<?php

namespace App\Imports;

use App\Models\Lista;
use App\Services\Listas\AvalValidationService;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AvalesImport implements ToCollection, WithHeadingRow
{
    protected Lista $lista;
    protected AvalValidationService $validationService;

    public array $resultado = [];

    public function __construct(
        Lista $lista,
        AvalValidationService $validationService
    ) {
        $this->lista = $lista;
        $this->validationService = $validationService;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $avales = $rows->map(fn ($row) => [
            'dni'    => trim((string)($row['dni'] ?? '')),
            'legajo' => $row['legajo'] ?? null,
            #'id_facultad' => $row['facultad'] ?? null,
        ])
        ->filter(fn ($row) => !empty($row['dni']))
        ->values()
        ->toArray();

        
        $this->resultado = $this->validationService->procesarAvales(
            $this->lista,
            $avales
        );
    }
}
