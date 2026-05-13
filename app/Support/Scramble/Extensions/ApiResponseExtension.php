<?php

namespace App\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\RouteInfo;

class ApiResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        foreach ($operation->responses as $response) {
            if (! $response instanceof Response) {
                continue;
            }

            if ($response->code >= 200 && $response->code < 300) {
                $this->wrapSuccessResponse($response);
            } elseif ($response->code >= 400) {
                $this->wrapErrorResponse($response);
            }
        }
    }

    private function wrapSuccessResponse(Response $response): void
    {
        $components = $this->openApiTransformer->getComponents();

        foreach ($response->content as $mediaType => $schema) {
            $type = $schema instanceof Schema ? $schema->type : $schema;

            // 1. Detect and normalize existing wrappers
            $isEnvelope = $type instanceof ObjectType && $type->hasProperty('success') && $type->hasProperty('data');
            $isResourceWrapper = $type instanceof ObjectType && count($type->properties) === 1 && $type->hasProperty('data');

            $paginationKeys = [];
            if ($type instanceof ObjectType) {
                if ($type->hasProperty('meta')) {
                    $paginationKeys[] = 'meta';
                }
                if ($type->hasProperty('links')) {
                    $paginationKeys[] = 'links';
                }
            }

            // Extract the core data type
            if ($isEnvelope || $isResourceWrapper) {
                $type = $type->properties['data'];
            }

            // 2. Map specific resources to reusable schemas (handle single and collections)
            $mapType = function ($t) use ($components, &$mapType) {
                $targetSchema = null;
                $nameToMatch = null;

                if ($t instanceof Reference && $t->referenceType === 'schemas') {
                    $nameToMatch = ltrim($t->fullName, '\\');
                } elseif ($t instanceof ObjectType) {
                    $nameToMatch = $t->getAttribute('title');
                }

                if ($nameToMatch) {
                    $targetSchema = match ($nameToMatch) {
                        'App\\Http\\Resources\\Api\\V1\\MemberResource', 'MemberResource' => 'Member',
                        'App\\Http\\Resources\\Api\\V1\\SubscriptionResource', 'SubscriptionResource' => 'Subscription',
                        'App\\Http\\Resources\\Api\\V1\\NotificationResource', 'NotificationResource' => 'Notification',
                        'App\\Http\\Resources\\Api\\V1\\ApiReservationResource', 'ApiReservationResource' => 'Reservation',
                        'App\\Http\\Resources\\Api\\V1\\ActivityResource', 'ActivityResource' => 'Activity',
                        'App\\Http\\Resources\\Api\\V1\\ActivitySlotResource', 'ActivitySlotResource' => 'ActivitySlot',
                        'App\\Http\\Resources\\Api\\V1\\CourseResource', 'CourseResource' => 'Course',
                        'App\\Http\\Resources\\Api\\V1\\SearchResultResource', 'SearchResultResource' => 'SearchResult',
                        'App\\Http\\Resources\\Api\\TerminalCheckInResource', 'TerminalCheckInResource' => 'TerminalCheckIn',
                        'App\\Models\\User', 'User' => 'User',
                        default => null,
                    };
                }

                if ($targetSchema) {
                    return $components->getSchemaReference($targetSchema);
                }

                if ($t instanceof ArrayType) {
                    $t->setItems($mapType($t->items));
                }

                return $t;
            };

            $type = $mapType($type);

            // 3. Detect AuthTokenResponse structure
            if ($type instanceof ObjectType && $type->hasProperty('token') && $type->hasProperty('member')) {
                $type = $components->getSchemaReference('AuthTokenResponse');
            }

            // 4. Detect Pagination
            // In this project, if the resource data is an array, it's almost always a paginated collection
            $isPaginated = ($type instanceof ArrayType) || ! empty($paginationKeys);

            // 5. Build the standardized envelope
            $successReference = $components->getSchemaReference('SuccessResponse');

            $dataObject = (new ObjectType)->addProperty('data', $type);

            if ($isPaginated) {
                $dataObject->addProperty('meta', $components->getSchemaReference('PaginationMeta'));
                $dataObject->addProperty('links', $components->getSchemaReference('PaginationLinks'));
            }

            $envelope = (new AllOf)->setItems([
                $successReference,
                $dataObject,
            ]);

            if ($schema instanceof Schema) {
                $schema->type = $envelope;
            } else {
                $response->setContent($mediaType, Schema::fromType($envelope));
            }
        }
    }

    private function wrapErrorResponse(Response $response): void
    {
        $components = $this->openApiTransformer->getComponents();

        foreach ($response->content as $mediaType => $schema) {
            if ($response->code === 422) {
                $validationRef = $components->getSchemaReference('ValidationErrorResponse');
                if ($schema instanceof Schema) {
                    $schema->type = $validationRef;
                } else {
                    $response->setContent($mediaType, Schema::fromType($validationRef));
                }

                continue;
            }

            $type = $schema instanceof Schema ? $schema->type : $schema;

            // Handle already wrapped responses
            $isEnvelope = $type instanceof ObjectType && $type->hasProperty('success') && $type->hasProperty('errors');
            if ($isEnvelope) {
                $type = $type->properties['errors'];
            }

            $errorReference = $components->getSchemaReference('ErrorResponse');

            $envelope = (new AllOf)->setItems([
                $errorReference,
                (new ObjectType)->addProperty('errors', $type),
            ]);

            if ($schema instanceof Schema) {
                $schema->type = $envelope;
            } else {
                $response->setContent($mediaType, Schema::fromType($envelope));
            }
        }
    }
}
