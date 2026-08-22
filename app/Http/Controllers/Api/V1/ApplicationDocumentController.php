<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Application\StoreApplicationDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApplicationDocumentRequest;
use App\Http\Resources\Api\V1\ApplicationDocumentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    public function store(
        StoreApplicationDocumentRequest $request,
        Application $application,
        StoreApplicationDocumentAction $action,
    ): JsonResponse {
        $this->authorize('uploadDocument', $application);

        $document = $action->execute(
            $request->user(),
            $application,
            $request->file('document'),
            $request->input('requirement_code'),
        );

        return ApiResponse::success(
            'Document uploaded successfully',
            new ApplicationDocumentResource($document),
            201,
        );
    }

    public function download(Application $application, ApplicationDocument $document): StreamedResponse|JsonResponse
    {
        $this->authorize('download', $document);

        if (! Storage::disk($document->disk)->exists($document->path)) {
            return ApiResponse::error('Document file is missing', status: 404);
        }

        $disk = Storage::disk($document->disk);

        if (request()->boolean('inline') && in_array($document->mime_type, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true)) {
            return $disk->response($document->path, $document->original_name, [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            ]);
        }

        return $disk->download(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function destroy(Application $application, ApplicationDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return ApiResponse::success('Document deleted successfully');
    }
}
