<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{

    private $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {       
          
        // Appelle les 3 fonctions du service
        $tableauCombine        = $this->service->getTableauCombine();
        $soutenancesParFiliere = $this->service->getSoutenancesParFiliere();
        $stats                 = $this->service->getStats();
        $anomalies             = $this->service->getAnomalies();
        
        return view('dashboard', compact(
            'tableauCombine',
            'soutenancesParFiliere',
            'stats',
            'anomalies' 
        ));
    }
}
