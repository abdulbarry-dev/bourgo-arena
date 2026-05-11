<?php

namespace Tests;

use AllowDynamicProperties;
use App\Models\Member;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

#[AllowDynamicProperties]
abstract class TestCase extends BaseTestCase
{
    /** @var \App\Models\Member|null */
    public $member = null;
    
    /** @var \App\Models\Activity|null */
    public $activity = null;

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
