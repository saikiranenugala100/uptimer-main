<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Website;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UptimeController extends Controller
{
    public function index(): Response
    {
        $clients = Client::where('is_active', true)
            ->with(['websites' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return Inertia::render('Dashboard', [
            'clients' => $clients
        ]);
    }

    public function getClientWebsites(Client $client)
    {
        $websites = $client->websites()
            ->where('is_active', true)
            ->get();

        return response()->json([
            'websites' => $websites
        ]);
    }
}
