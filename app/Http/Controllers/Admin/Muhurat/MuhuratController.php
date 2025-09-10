<?php 
namespace App\Http\Controllers\Admin\Muhurat;

use App\Http\Controllers\Controller;
use App\Models\Muhurat;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MuhuratController extends Controller
{

    public function getList()
    {
        $currentYear = Carbon::now()->year;
        $getMuhurat = Muhurat::where('year', '>=', $currentYear)->orderBy('id', 'asc')->get();
        return view('admin-views.muhurat.list', compact('getMuhurat'));
    }

    public function muhurat_update(Request $request, $id)
    {
        
        $muhurat = Muhurat::findOrFail($id);
        $muhurat->titleLink = $request->muhuratdate;
        $muhurat->muhurat = $request->muhurattime;
        $muhurat->nakshatra = $request->nakshatra;
        $muhurat->tithi = $request->tithi;
        $muhurat->added_by = auth('admin')->user()->role->name ?? '';
        $muhurat->save();

        return response()->json(['success' => true]);
    }
    public function muhurat_store(Request $request)
    {
        $request->validate([
        'year' => 'required|string|max:255',
        'type' => 'required|string|max:255',
        'titleLink' => 'required|date',
        'muhurat' => 'required|string|max:255',
        'nakshatra' => 'required|string|max:255',
        'tithi' => 'required|string|max:255',
        ]);
        Muhurat::create([
            'year' => $request->year,
            'type' => $request->type,
            'titleLink' => $request->titleLink,
            'muhurat' => $request->muhurat,
            'nakshatra' => $request->nakshatra,
            'tithi' => $request->tithi,
            'added_by' => auth('admin')->user()->role->name ?? '', 
        ]);

        return response()->json(['success' => true]);
    }

}
