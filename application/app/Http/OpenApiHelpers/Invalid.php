<?php

namespace App\Http\OpenApiHelpers;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Invalid extends OA\Response
{
    public function __construct()
    {
        parent::__construct(
            response: Response::HTTP_UNPROCESSABLE_ENTITY,
            description: 'Request input had validation errors',
            content: new OA\JsonContent(
                required: ['errors'],
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'errors', type: 'object'),
                ],
                type: 'object',
                example: [
                    'message' => 'Date is later than one year from the current calendar date in Estonia.',
                    'errors' => [
                        'deactivation_date' => ['Date is later than one year from the current calendar date in Estonia.'],
                    ],
                ]
            ),
        );
    }
}
