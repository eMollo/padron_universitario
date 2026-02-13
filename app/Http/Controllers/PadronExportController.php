<?php

namespace App\Http\Controllers;

use App\Models\Padron;
use App\Exports\PadronExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class PadronExportController extends Controller
{
    public function export($id)
    {
        $padron = Padron::findOrFail($id);

        return Excel::download(
            new PadronExport($padron),
            "padron_{$padron->id}.xlsx"
        );
    }
}
