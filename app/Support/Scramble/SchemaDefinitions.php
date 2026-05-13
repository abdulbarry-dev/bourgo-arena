<?php

namespace App\Support\Scramble;

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
                ->addProperty('success', (new BooleanType)->example(true)->setDescription('Indicates if the operation was executed successfully.'))
                ->addProperty('message', (new StringType)
                    ->nullable(true)
                    ->example('Operation completed successfully.')
                    ->setDescription('A human-readable message describing the result of the operation.'))
        );
    }

    public static function errorResponse(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(false)->setDescription('Indicates that the operation failed.'))
                ->addProperty('message', (new StringType)
                    ->example('An unexpected error occurred.')
                    ->setDescription('A high-level error message for display to the end user.'))
                ->addProperty('code', (new StringType)
                    ->example('ERR_INTERNAL_SERVER')
                    ->setDescription('A machine-readable error code for programmatic handling.'))
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

    public static function authTokenResponse(Components $components): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('token', (new StringType)
                    ->setDescription('The plain-text authentication token to be used in the Authorization header.')
                    ->example('1|abc123def456'))
                ->addProperty('token_type', (new StringType)
                    ->example('Bearer')
                    ->setDescription('The type of the token (always Bearer).'))
                ->addProperty('expires_at', (new StringType)
                    ->format('date-time')
                    ->nullable(true)
                    ->setDescription('The timestamp when the token will expire, if applicable.'))
                ->addProperty('member', $components->getSchemaReference('Member'))
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
                ->addProperty('id', (new IntegerType)->example(1)->setDescription('Unique identifier for the reservation.'))
                ->addProperty('activity_title', (new StringType)
                    ->example('Padel Court 1')
                    ->setDescription('The name of the reserved activity or facility.'))
                ->addProperty('date', (new StringType)
                    ->format('date')
                    ->example('2024-05-20')
                    ->setDescription('The date of the reservation (ISO 8601).'))
                ->addProperty('starts_at', (new StringType)
                    ->example('14:00')
                    ->setDescription('The start time of the reservation (24h format HH:mm).'))
                ->addProperty('ends_at', (new StringType)
                    ->example('15:00')
                    ->setDescription('The end time of the reservation (24h format HH:mm).'))
                ->addProperty('price', (new NumberType)
                    ->format('float')
                    ->example(45.00)
                    ->setDescription('The total price of the reservation.'))
                ->addProperty('status', (new StringType)
                    ->enum(['pending', 'confirmed', 'cancelled', 'completed'])
                    ->example('confirmed')
                    ->setDescription('The current lifecycle status of the reservation.'))
                ->addProperty('payment_status', (new StringType)
                    ->enum(['unpaid', 'partially_paid', 'paid', 'refunded'])
                    ->example('paid')
                    ->setDescription('The financial status of the reservation.'))
                ->addProperty('qr_code', (new StringType)
                    ->nullable(true)
                    ->setDescription('QR code representation for check-in (URL or base64).'))
                ->addProperty('cancelled_at', (new StringType)
                    ->format('date-time')
                    ->nullable(true)
                    ->setDescription('Timestamp when the reservation was cancelled.'))
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
                ->addProperty('id', (new IntegerType)->example(1)->setDescription('Unique identifier for the subscription.'))
                ->addProperty('plan_name', (new StringType)
                    ->example('Monthly Premium')
                    ->setDescription('The name of the subscription plan.'))
                ->addProperty('plan_description', (new StringType)
                    ->nullable(true)
                    ->example('Unlimited access to all courts.')
                    ->setDescription('A detailed description of what the plan includes.'))
                ->addProperty('status', (new StringType)
                    ->enum(['active', 'expired', 'cancelled', 'pending'])
                    ->example('active')
                    ->setDescription('The current status of the subscription.'))
                ->addProperty('starts_at', (new StringType)
                    ->format('date')
                    ->nullable(true)
                    ->example('2024-01-01')
                    ->setDescription('The activation date of the subscription.'))
                ->addProperty('ends_at', (new StringType)
                    ->format('date')
                    ->nullable(true)
                    ->example('2024-02-01')
                    ->setDescription('The expiration date of the subscription.'))
                ->addProperty('days_remaining', (new IntegerType)
                    ->example(12)
                    ->setDescription('Calculated number of days until the subscription expires.'))
                ->addProperty('payment_method', (new StringType)
                    ->nullable(true)
                    ->example('credit_card')
                    ->setDescription('The method used for the last payment.'))
                ->addProperty('amount_paid', (new NumberType)
                    ->format('float')
                    ->example(29.99)
                    ->setDescription('The amount paid for the current billing cycle.'))
        );
    }

    public static function activity(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new StringType)->example('1')->setDescription('The unique identifier of the activity.'))
                ->addProperty('title', (new StringType)->example('Padel')->setDescription('The title of the activity.'))
                ->addProperty('name', (new StringType)->example('Padel')->setDescription('Display name of the activity.'))
                ->addProperty('category', (new StringType)->example('Sports')->setDescription('The category classification.'))
                ->addProperty('base_price', (new NumberType)->format('float')->example(25.0)->setDescription('The minimum starting price.'))
                ->addProperty('currency', (new StringType)->example('TND')->setDescription('The currency for pricing.'))
                ->addProperty('image_url', (new StringType)->format('uri')->nullable(true)->example('https://api.bourgo.arena/images/activities/padel.jpg')->setDescription('The URL to the featured image.'))
                ->addProperty('icon', (new StringType)->nullable(true)->example('padel-icon')->setDescription('The icon identifier.'))
                ->addProperty('description', (new StringType)->nullable(true)->example('Fast-paced racket sport.')->setDescription('Detailed description of the activity.'))
                ->addProperty('features', (new ArrayType)->setItems(new StringType)->nullable(true)->setDescription('Key features or highlights.'))
                ->addProperty('rating', (new NumberType)->format('float')->example(4.8)->setDescription('Average user rating (0-5).'))
                ->addProperty('review_count', (new IntegerType)->example(120)->setDescription('Total number of reviews received.'))
        );
    }

    public static function activitySlot(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new IntegerType)->example(1))
                ->addProperty('date', (new StringType)->format('date')->example('2024-05-20'))
                ->addProperty('time', (new StringType)->example('14:00')->setDescription('The start time (HH:mm).'))
                ->addProperty('available', (new BooleanType)->example(true))
                ->addProperty('start_time', (new StringType)->example('14:00:00'))
                ->addProperty('end_time', (new StringType)->example('15:00:00'))
                ->addProperty('capacity', (new IntegerType)->example(4))
                ->addProperty('booked_count', (new IntegerType)->example(1))
                ->addProperty('is_available', (new BooleanType)->example(true))
                ->addProperty('is_fully_booked', (new BooleanType)->example(false))
        );
    }

    public static function course(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new StringType)->example('1'))
                ->addProperty('title', (new StringType)->example('Padel Basics'))
                ->addProperty('instructor', (new StringType)->nullable(true)->example('Sarah Jones'))
                ->addProperty('start_time', (new StringType)->example('10:00'))
                ->addProperty('end_time', (new StringType)->example('11:30'))
                ->addProperty('day_of_week', (new IntegerType)->example(1)->setDescription('1 (Monday) to 7 (Sunday).'))
                ->addProperty('category', (new StringType)->example('Fitness'))
                ->addProperty('capacity', (new IntegerType)->example(20))
                ->addProperty('enrolled', (new IntegerType)->example(12))
                ->addProperty('icon', (new StringType)->nullable(true)->example('fitness-icon'))
        );
    }

    public static function searchResult(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('id', (new StringType)->example('1'))
                ->addProperty('type', (new StringType)->example('activity')->setDescription('The type of the result (activity, course, etc.).'))
                ->addProperty('title', (new StringType)->example('Padel Court 1'))
                ->addProperty('subtitle', (new StringType)->nullable(true)->example('Premium sports facility'))
                ->addProperty('icon', (new StringType)->nullable(true)->example('padel-icon'))
        );
    }

    public static function terminalCheckIn(): Schema
    {
        return Schema::fromType(
            (new ObjectType)
                ->addProperty('event_id', (new IntegerType)->example(123))
                ->addProperty('member_id', (new IntegerType)->nullable(true)->example(1))
                ->addProperty('card_uid', (new StringType)->example('A1B2C3D4'))
                ->addProperty('terminal_id', (new IntegerType)->example(5))
                ->addProperty('result', (new StringType)->example('granted'))
                ->addProperty('is_suspicious', (new BooleanType)->example(false))
                ->addProperty('checked_in_at', (new StringType)->format('date-time')->example('2024-05-12T14:30:00Z'))
        );
    }
}
