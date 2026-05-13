<?php
<<<<<<< Updated upstream
 namespace App\Http\Controllers;
 use Illuminate\Http\Request; 
 use Maatwebsite\Excel\Facades\Excel;
  use App\Imports\StudentsImport; 
  use App\Imports\ProfessorsImport; 
  class ImportController extends Controller{
     public function showForm() { return view('import'); }
   public function importStudents(Request $request)
  {
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,csv',
        'filiere'    => 'required|string',
    ]);

    Excel::import(new StudentsImport($request->filiere), $request->file('excel_file'));

    return back()->with('success', 'Students imported successfully!');
}
public function importProfessors(Request $request)
=======

namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EtudiantsImport;
use Illuminate\Http\Request;

class ImportController extends Controller
{
public function import(Request $request)
>>>>>>> Stashed changes
{
    Excel::import(new EtudiantsImport, $request->file('excel_file'));
    return redirect()->back()->with('success', 'Données importées avec succès !');
}
}
