<?php

namespace App\Support\Scramble;

use App\Http\Resources\Api\V1\MemberResource;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

class SchemaDefinitions
{
    public static function successResponse(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(true)->setDescription('Indicates if the request was successful.'))
                ->addProperty('message', (new StringType)->nullable(true)->example('Operation completed successfully.')->setDescription('A descriptive message about the result.'))
                ->addProperty('data', (new ObjectType)->nullable(true)->setDescription('The response payload.'))
        );
    }

    public static function errorResponse(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(false)->setDescription('Indicates if the request failed.'))
                ->addProperty('message', (new StringType)->example('An error occurred.')->setDescription('A descriptive error message.'))
                ->addProperty('errors', (new ObjectType)->nullable(true)->setDescription('Additional error details.'))
        );
    }

    public static function validationErrorResponse(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(false))
                ->addProperty('message', (new StringType)->example('The given data was invalid.'))
                ->addProperty('errors', (new ObjectType)
                    ->setDescription('Validation errors by field.')
                    ->additionalProperties(
                        (new ArrayType)->setItems(new StringType)
                    )
                    ->example([
                        'email' => ['The email field is required.'],
                        'password' => ['The password must be at least 8 characters.'],
                    ])
                )
        );
    }

    public static function paginationMeta(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('current_page', (new IntegerType)->example(1)->setDescription('The current page number.'))
                ->addProperty('last_page', (new IntegerType)->example(5)->setDescription('The last available page number.'))
                ->addProperty('per_page', (new IntegerType)->example(15)->setDescription('Number of items per page.'))
                ->addProperty('total', (new IntegerType)->example(75)->setDescription('Total number of items available.'))
        );
    }

    public static function paginationLinks(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('first', (new StringType)->format('uri')->example('https://api.example.com/data?page=1'))
                ->addProperty('last', (new StringType)->format('uri')->example('https://api.example.com/data?page=5'))
                ->addProperty('prev', (new StringType)->format('uri')->nullable(true)->example(null))
                ->addProperty('next', (new StringType)->format('uri')->nullable(true)->example('https://api.example.com/data?page=2'))
        );
    }

    public static function authTokenResponse(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('token', (new StringType)->setDescription('The plain text authentication token.'))
                ->addProperty('token_type', (new StringType)->example('Bearer'))
                /** @see MemberResource */
                ->addProperty('member', (new ObjectType)->setDescription('The authenticated member details.'))
        );
    }

    public static function user(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('name', (new StringType)->example('John Doe'))
                ->addProperty('email', (new StringType)->format('email')->example('john@example.com'))
                ->addProperty('phone', (new StringType)->nullable(true)->example('+21612345678'))
                ->addProperty('role', (new StringType)->example('member')->setDescription('The user role (admin, manager, member).'))
                ->addProperty('email_verified_at', (new StringType)->format('date-time')->nullable(true))
                ->addProperty('created_at', (new StringType)->format('date-time'))
                ->addProperty('updated_at', (new StringType)->format('date-time'))
        );
    }

    public static function member(Components $components): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('name', (new StringType)->example('John Doe'))
                ->addProperty('first_name', (new StringType)->example('John'))
                ->addProperty('last_name', (new StringType)->example('Doe'))
                ->addProperty('email', (new StringType)->format('email')->example('john@example.com'))
                ->addProperty('phone', (new StringType)->nullable(true)->example('+21612345678'))
                ->addProperty('avatar_url', (new StringType)->format('uri')->nullable(true)->example('https://api.bourgo.arena/storage/avatars/1.png'))
                ->addProperty('loyalty_points', (new IntegerType)->example(150))
                ->addProperty('birth_date', (new StringType)->format('date')->nullable(true)->example('1990-01-01'))
                ->addProperty('gender', (new StringType)->nullable(true)->example('male'))
                ->addProperty('status', (new StringType)->example('active'))
                ->addProperty('is_parent_account', (new BooleanType)->example(true))
                ->addProperty('subscription_level', (new StringType)->nullable(true)->example('Premium'))
                ->addProperty('subscription_expiry', (new StringType)->format('date')->nullable(true)->example('2024-12-31'))
                ->addProperty('total_check_ins', (new IntegerType)->example(42))
                ->addProperty('children', (new ArrayType)->setItems($components->getSchemaReference('Member')))
        );
    }

    public static function reservation(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('activity_title', (new StringType)->example('Padel Court 1'))
                ->addProperty('date', (new StringType)->format('date')->example('2024-05-20'))
                ->addProperty('starts_at', (new StringType)->example('14:00'))
                ->addProperty('ends_at', (new StringType)->example('15:00'))
                ->addProperty('price', (new NumberType)->format('float')->example(45.00))
                ->addProperty('status', (new StringType)->example('confirmed'))
                ->addProperty('payment_status', (new StringType)->example('paid'))
                ->addProperty('qr_code', (new StringType)->nullable(true)->setDescription('Base64 encoded QR code or URL.'))
                ->addProperty('cancelled_at', (new StringType)->format('date-time')->nullable(true))
        );
    }

    public static function notification(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('title', (new StringType)->example('Booking Confirmed'))
                ->addProperty('message', (new StringType)->example('Your reservation for Padel has been confirmed.'))
                ->addProperty('type', (new StringType)->example('reservation_update'))
                ->addProperty('is_read', (new BooleanType)->example(false))
                ->addProperty('timestamp', (new StringType)->format('date-time')->example('2024-05-12T14:30:00Z'))
        );
    }

    public static function subscription(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('plan_name', (new StringType)->example('Monthly Premium'))
                ->addProperty('plan_description', (new StringType)->nullable(true)->example('Unlimited access to all courts.'))
                ->addProperty('status', (new StringType)->example('active'))
                ->addProperty('starts_at', (new StringType)->format('date')->nullable(true)->example('2024-01-01'))
                ->addProperty('ends_at', (new StringType)->format('date')->nullable(true)->example('2024-02-01'))
                ->addProperty('days_remaining', (new IntegerType)->example(12))
                ->addProperty('payment_method', (new StringType)->nullable(true)->example('credit_card'))
                ->addProperty('amount_paid', (new NumberType)->format('float')->example(29.99))
        );
    }
}
