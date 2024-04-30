<?php

namespace tests\app\Repository;

use Tests\TestCase;
use DTApi\Models\User;
use DTApi\Models\Company;
use DTApi\Models\Type;
use DTApi\Models\UsersBlacklist;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use DTApi\Models\Town;
use DTApi\Models\UserMeta;
use DTApi\Models\UserTowns;
use DTApi\Events\JobWasCreated;
use DTApi\Models\UserLanguages;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\FirePHPHandler;
use DTApi\Models\Department;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_new_user()
    {
        $userData = [
            'role' => 'customer',
            'name' => 'John Doe',
            'company_id' => '',
            'department_id' => '',
            'email' => 'john.doe@example.com',
            'dob_or_orgid' => '1234567890',
            'phone' => '1234567890',
            'mobile' => '1234567890',
            'password' => 'secret',
            'consumer_type' => 'paid',
            'customer_type' => 'individual',
            'username' => 'johndoe',
            'post_code' => '12345',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'town' => '',
            'country' => 'US',
            'reference' => 'no',
            'additional_info' => 'Some additional information',
            // ... other fields as needed
        ];

        $user = $this->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertTrue(app('hash')->check($userData['password'], $user->password));
        $this->assertEquals($userData['consumer_type'], $user->meta->consumer_type);
        $this->assertEquals($userData['customer_type'], $user->meta->customer_type);
        $this->assertEquals($userData['username'], $user->meta->username);
        $this->assertEquals($userData['post_code'], $user->meta->post_code);
        $this->assertEquals($userData['address'], $user->meta->address);
        $this->assertEquals($userData['city'], $user->meta->city);
        $this->assertEquals($userData['country'], $user->meta->country);
        $this->assertEquals($userData['reference'], $user->meta->reference);
        $this->assertEquals($userData['additional_info'], $user->meta->additional_info);

        if ($userData['consumer_type'] === 'paid' && empty($userData['company_id'])) {
            $this->assertDatabaseHas('companies', [
                'name' => $userData['name'],
            ]);
            $this->assertDatabaseHas('departments', [
                'name' => $userData['name'],
            ]);
        }
    }

    public function test_update_existing_user()
    {
        $user = User::factory()->create();
        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'newpassword',
            'consumer_type' => 'free',

        ];

        $this->createUser($updateData, $user->id);

        $user->refresh();

        $this->assertEquals($updateData['name'], $user->name);
        $this->assertEquals($updateData['email'], $user->email);
        $this->assertTrue(app('hash')->check($updateData['password'], $user->password));
        $this->assertEquals($updateData['consumer_type'], $user->meta->consumer_type);
    }

    public function test_update_user_status()
    {
        $user = User::factory()->create();
        $updateData = [
            'status' => '1',
        ];

        $this->createUser($updateData, $user->id);

        $user->refresh();

        $this->assertEquals('1', $user->status);
    }

    public function test_update_user_blacklist()
    {
        $user = User::factory()->create();
        $initialBlacklist = [1, 2];

        // Add initial blacklist entries
        foreach ($initialBlacklist as $translatorId) {
            UsersBlacklist::create([
                'user_id' => $user->id,
                'translator_id' => $translatorId,
            ]);
        }

        $updateData = [
            'role' => env('CUSTOMER_ROLE_ID'),
            'translator_ex' => [2, 3],
        ];

        $this->createUser($updateData, $user->id);

        $userBlacklist = UsersBlacklist::where('user_id', $user->id)->get()->pluck('translator_id')->toArray();

        $this->assertCount(2, $userBlacklist);
        $this->assertContains(2, $userBlacklist);
        $this->assertContains(3, $userBlacklist);
    }
}
