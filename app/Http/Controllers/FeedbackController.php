<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Turbo124\Beacon\Facades\LightLogs;
use App\DataMapper\Analytics\FeedbackCreated;

class FeedbackController extends Controller
{
    public function __invoke(Request $request)
    {
        
        $user = auth()->user();
        $company = $user->company();

        $rating = $request->input('rating', 0);
        $notes = $request->input('notes', '');
        
        LightLogs::create(new FeedbackCreated($rating, $notes, $company->company_key, $company->account->key, $user->present()->name()));

        return response()->noContent();

    }
}
