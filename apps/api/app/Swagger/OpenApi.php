<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="PalmOutsourcing Task API",
 *     version="1.0.0",
 *     description="Laravel API for tasks (GET /tasks, POST /tasks)."
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Local dev"
 * )
 */
class OpenApi
{
    // This class is only a holder for annotations.
}
