<?php

namespace Tests\Feature;

use App\Models\Contact;
use Database\Seeders\ContactSeeder;
use Database\Seeders\SearchSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class ContactTest extends TestCase
{
    public function testCreateSuccess() {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name'    => 'Romzi',
            'last_name'     => 'Farhan',
            'email'         => 'romzi@gmail.com',
            'phone'         => '123451235'
        ], [
            'Authorization' => 'test'
        ])->assertStatus(201)
        ->assertJson([
            'data'  => [
                'first_name'    => 'Romzi',
                'last_name'     => 'Farhan',
                'email'         => 'romzi@gmail.com',
                'phone'         => '123451235'
            ]
        ]);
    }

    public function testCreateFailed() {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name'    => '',
            'last_name'     => 'Farhan',
            'email'         => 'romzi',
            'phone'         => '123451235'
        ], [
            'Authorization' => 'test'
        ])->assertStatus(400)
        ->assertJson([
            'errors'  => [

                'first_name'    => [
                    'The first name field is required.'
                ],
                'email'         => [
                    'The email field must be a valid email address.'
                ]
            ]
        ]);
    }

    public function testCreateUnauthorized() {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name'    => 'Romzi',
            'last_name'     => 'Farhan',
            'email'         => 'romzi@gmail.com',
            'phone'         => '123451235'
        ])->assertStatus(401)
        ->assertJson([
            'errors'  => [
                'message'   => [
                    'Unauthorized'
                ]
            ]
        ]);
    }

    public function testGetContactSuccess() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();
        $this->get('/api/contacts/'.$contact->id, [
            'Authorization' => 'test'
        ])->assertStatus(200)
        ->assertJson(([
            'data'  => [
                'first_name'    => 'test',
                'last_name'     => 'test',
                'email'         => 'test@mail.com',
                'phone'         => '11111',
            ]
        ]));
    }

    public function testGetContactNotFound() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();
        $this->get('/api/contacts/'.$contact->id + 1, [
            'Authorization' => 'test'
        ])->assertStatus(404)
        ->assertJson(([
            'errors'  => [
                'message'   => [
                    'not found'
                ]
            ]
        ]));
    }

    public function testGetContactOtherUserContact() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();
        $this->get('/api/contacts/'.$contact->id, [
            'Authorization' => 'test2'
        ])->assertStatus(404)
        ->assertJson(([
            'errors'  => [
                'message'   => [
                    'not found'
                ]
            ]
        ]));
    }

    public function testUpdateContactSuccess() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();
        $this->put('/api/contacts/'.$contact->id, [
                'first_name'    => 'test2',
                'last_name'     => 'test2',
                'email'         => 'test2@mail.com',
                'phone'         => '2222',
        ],[
            'Authorization' => 'test'
        ])->assertStatus(200)
        ->assertJson(([
            'data'  => [
                'first_name'    => 'test2',
                'last_name'     => 'test2',
                'email'         => 'test2@mail.com',
                'phone'         => '2222',
            ]
        ]));
    }

    public function testUpdateContactFailed() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();
        $this->put('/api/contacts/'.$contact->id, [
                'first_name'    => '',
                'last_name'     => 'test2',
                'email'         => 'test2@mail.com',
                'phone'         => '2222',
        ],[
            'Authorization' => 'test'
        ])->assertStatus(400)
        ->assertJson(([
            'errors'  => [
                'first_name'    => [
                    'The first name field is required.'
                ],
                
            ]
        ]));
    }

    public function testRemoveContactSuccess() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();

        $this->delete('/api/contacts/'.$contact->id, [], [
            'Authorization' => 'test'
        ])->assertStatus(200)
        ->assertJson(([
            'data'  => true
        ]));
    }

    public function testRemoveContactNotFound() {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();

        $this->delete('/api/contacts/'.$contact->id + 1, [], [
            'Authorization' => 'test'
        ])->assertStatus(404)
        ->assertJson(([
            'errors'    => [
                'message'   => [
                    'not found'
                ]
            ]
        ]));
    }

    public function testSearchByFirstName() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?first_name=first',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchByLastName() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?last_name=last',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchByEmail() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?email=test',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchByPhone() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?phone=1111',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchNotFound() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?name=tidakada',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(0, count($response['data']));
        self::assertEquals(0, $response['meta']['total']);
    }

    public function testSearchWithPage() {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?size=5&page=2',  [
            'Authorization' => 'test'
        ])->assertStatus(200)->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));
        self::assertEquals(5, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
        self::assertEquals(2, $response['meta']['current_page']);
    }
}
