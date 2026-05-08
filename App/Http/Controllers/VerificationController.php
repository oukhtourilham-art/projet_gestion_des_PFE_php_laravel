<?php

namespace App\Http\Controllers;

use App\Services\ConstraintChecker;

class VerificationController extends Controller
{

    public function verifier(){
        $checker = new ConstraintChecker();
        $resultats = $checker->verifierTout();

        //on compter le total des erreurs
        $totalErreurs = 0;
        foreach($resultats as $categorie => $erreurs){
            $totalErreurs += count($erreurs);
        }

        return response()->json([
            'status'  => $totalErreurs == 0 ? 'Planning valide' : 'Planning contient des erreurs',
            'total_erreurs' => $totalErreurs,
            'details' => $resultats,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
