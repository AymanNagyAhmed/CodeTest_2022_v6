<?php

namespace tests\app\Helpers;

use Tests\TestCase;

class TeHelperTest extends TestCase
{
    public function test_will_expire_at_Difference_is_less_than_or_equal_to_90_hours()
    {
        // Test case 1: Difference is less than or equal to 90 hours
        $dueTime = '2022-01-01 12:00:00';
        $createdAt = '2022-01-01 10:00:00';
        $expectedResult = '2022-01-01 12:00:00';
        $this->assertEquals($expectedResult, TeHelper::willExpireAt($dueTime, $createdAt));
    }
    public function test_will_expire_at_Difference_is_less_than_or_equal_to_24_hours()
    {
        // Test case 2: Difference is less than or equal to 24 hours
        $dueTime = '2022-01-02 12:00:00';
        $createdAt = '2022-01-01 10:00:00';
        $expectedResult = '2022-01-01 11:30:00'; // 90 minutes added to $createdAt
        $this->assertEquals($expectedResult, TeHelper::willExpireAt($dueTime, $createdAt));
    }

    public function test_will_expire_at_Difference_is_greater_than_24_and_less_than_or_equal_to_72_hours()
    {

        // Test case 3: Difference is greater than 24 and less than or equal to 72 hours
        $dueTime = '2022-01-03 12:00:00';
        $createdAt = '2022-01-01 10:00:00';
        $expectedResult = '2022-01-01 18:00:00'; // 16 hours added to $createdAt
        $this->assertEquals($expectedResult, TeHelper::willExpireAt($dueTime, $createdAt));
    }

    public function test_will_expire_at_Difference_is_less_than_or_equal_to_24_hours()
    {
        // Test case 4: Difference is greater than 72 hours
        $dueTime = '2022-01-05 12:00:00';
        $createdAt = '2022-01-01 10:00:00';
        $expectedResult = '2022-01-03 12:00:00'; // 48 hours subtracted from $dueTime
        $this->assertEquals($expectedResult, TeHelper::willExpireAt($dueTime, $createdAt));
    }
}
