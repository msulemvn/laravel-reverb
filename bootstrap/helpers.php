<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use App\Models\ErrorLog;
use App\Models\ActivityLog;
use Illuminate\Http\Resources\Json\JsonResource;
use App\DTO\ErrorLog\StoreErrorLogDTO;
use App\DTO\ActivityLog\StoreActivityLogDTO;
use Illuminate\Support\Str;

if (!function_exists('apiResponse')) {
    function apiResponse(
        ?string $message = null,
        array|JsonResource $data = [],
        array $errors = [],
        int $statusCode = SymfonyResponse::HTTP_OK,
        ?Request $request = null,
        ?Throwable $exception = null
    ): JsonResponse {
        $result = [];
        if ($statusCode) {
            $result['status'] = SymfonyResponse::$statusTexts[$statusCode];
        }

        if ($message) {
            $result['message'] = $message;
        }

        if (!empty($data)) {
            $result['data'] = $data;
        }

        if (!empty($errors)) {
            $result['errors'] = ['validation' => $errors];
        }

        if ($exception && $request) {
            try {
                ErrorLog::create((new StoreErrorLogDTO($request, $exception))->toArray());
                Log::error('Error Log:', ['exception' => $exception]);
            } catch (Throwable $e) {
                Log::critical('Error logging failed', ['exception' => $e]);
            }

            return response()->json($result, $statusCode);
        }
        return response()->json($result, $statusCode);
    }
}

if (!function_exists('logActivity')) {
    function logActivity(Request $request, string $description, bool $showable = false): void
    {
        try {
            ActivityLog::create((new StoreActivityLogDTO(
                $request["request_log_id"],
                $description,
                $showable,
            ))->toArray());
        } catch (Throwable $e) {
            Log::warning('Activity logging failed', ['exception' => $e]);
        }
    }
}
