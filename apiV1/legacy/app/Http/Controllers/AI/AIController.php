<?php
namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected $deepSeek;

    public function __construct(DeepSeekService $deepSeek)
    {
        $this->deepSeek = $deepSeek;
    }

    public function askAI(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);
    
        // Call the service and capture the response
        $response = $this->deepSeek->chatCompletion($request->message);
    
        // Return the response directly, which will now include the warning message
        return response()->json($response);
    }
}