<?php

namespace App\Http\Controllers\Manage\KpOwl;

use App\Http\Controllers\Controller;
use App\Services\Manage\KpOwl\KpOwlDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpOwlDetailController extends Controller
{
    protected KpOwlDetailService $detailService;

    public function __construct(KpOwlDetailService $detailService)
    {
        $this->detailService = $detailService;
    }

    /**
     * Return detailed learning record JSON for modal view (17 fields).
     */
    public function detail(Request $request, string $sourceType, $id): JsonResponse
    {
        $validTypes = ['content', 'playlist'];
        $cleanType = strtolower(trim($sourceType));

        if (!in_array($cleanType, $validTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid KP OWL learning record source type.',
            ], 404);
        }

        $detail = $this->detailService->getDetail($cleanType, $id);

        if (!($detail['success'] ?? true)) {
            return response()->json($detail, 404);
        }

        return response()->json($detail);
    }
}
