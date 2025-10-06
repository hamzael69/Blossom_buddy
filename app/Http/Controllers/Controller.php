<?php

namespace App\Http\Controllers;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Blossom Buddy API",
 *     version="1.0.0",
 *     description="API pour la gestion des plantes et utilisateurs"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */


abstract class Controller
{
    //
}
