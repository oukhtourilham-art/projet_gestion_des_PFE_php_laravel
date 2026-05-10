<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
     /*
    |--------------------------------------------------------------------------
    | $service = la variable qui stocke l'objet DashboardService
    |--------------------------------------------------------------------------
    | private = seul ce contrôleur peut l'utiliser
    */
    private $service;

    /*
    |--------------------------------------------------------------------------
    | __construct = fonction spéciale appelée automatiquement
    | quand Laravel crée le contrôleur
    |--------------------------------------------------------------------------
    | DashboardService $service = Laravel voit ce type, crée l'objet
    | automatiquement et le donne ici. C'est l'injection de dépendance.
    | Tu n'as jamais besoin d'écrire new DashboardService() toi-même.
    */
    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    /*
    |--------------------------------------------------------------------------
    | index() = fonction appelée quand on visite /dashboard
    |--------------------------------------------------------------------------
    | Elle demande les données au service et les envoie à la vue.
    | Le contrôleur ne fait AUCUN calcul lui-même — il délègue.
    */
    public function index()
    {       
          
        // Appelle les 3 fonctions du service
        $tableauCombine        = $this->service->getTableauCombine();
        $soutenancesParFiliere = $this->service->getSoutenancesParFiliere();
        $stats                 = $this->service->getStats();

        /*
        | compact() = crée un tableau ['tableauCombine' => $tableauCombine, ...]
        | view('dashboard') = charge resources/views/dashboard.blade.php
        | Les variables deviennent accessibles dans la vue par leur nom
        */
        return view('dashboard', compact(
            'tableauCombine',
            'soutenancesParFiliere',
            'stats'
        ));
    }
}
