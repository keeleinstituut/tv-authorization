<?php

namespace Tests\Unit;

use App\Rules\PersonalIdCodeRule;
use RuntimeException;
use Tests\TestCase;

class PersonalIdCodeRuleTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_validation_of_correct_pin(): void
    {
        $this->assertTrue($this->validate('38505268557'));
    }

    public function test_validation_of_incorrect_pin_checksum(): void
    {
        $this->assertFalse($this->validate('38505268556'));
    }

    public function test_validation_of_incorrect_pin(): void
    {
        $this->assertFalse($this->validate('38505268556asd'));
        $this->assertFalse($this->validate('3850526855643243342234'));
        $this->assertFalse($this->validate('38505'));
    }

    private function validate(string $pin): bool
    {
        try {
            (new PersonalIdCodeRule)->validate(
                'pin',
                $pin,
                fn ($message) => throw new RuntimeException($message)
            );

            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }
}
