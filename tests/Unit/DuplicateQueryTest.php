<?php

namespace Spatie\LaravelRay\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Spatie\LaravelRay\Tests\TestCase;
use Spatie\LaravelRay\Tests\TestClasses\User;

class DuplicateQueryTest extends TestCase
{
    /** @test */
    public function it_can_start_logging_duplicate_queries()
    {
        ray()->showDuplicateQueries();

        DB::table('users')->get();

        $this->assertCount(0, $this->client->sentRequests());

        DB::table('users')->get('id');

        $this->assertCount(0, $this->client->sentRequests());

        DB::table('users')->get();

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_ignores_queries_with_different_bindings()
    {
        ray()->showDuplicateQueries();

        DB::table('users')->where('id', 1)->get();
        DB::table('users')->where('id', 2)->get();

        $this->assertCount(0, $this->client->sentRequests());

        DB::table('users')->where('id', 1)->get();

        $this->assertCount(1, $this->client->sentRequests());

        DB::table('users')->where('id', 1)->get();

        $this->assertCount(2, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_stop_logging_duplicate_queries()
    {
        ray()->showDuplicateQueries();

        DB::table('users')->get('id');
        DB::table('users')->get('id');
        $this->assertCount(1, $this->client->sentRequests());

        ray()->stopShowingDuplicateQueries();
        DB::table('users')->get('id');
        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_log_all_duplicate_queries_in_a_callable()
    {
        ray()->showDuplicateQueries(function () {
            // will be logged
            DB::table('users')->where('id', 1)->get();
            DB::table('users')->where('id', 1)->get();
        });
        $this->assertCount(1, $this->client->sentRequests());

        // will not be logged
        DB::table('users')->where('id', 1)->get();
        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function eloquent_duplicate_queries_are_sent_to_ray()
    {
        ray()->showDuplicateQueries();

        User::create(['email' => 'john@example.com']);
        User::create(['email' => 'john@example.com']);

        $this->assertCount(1, $this->client->sentPayloads());
    }

    /**
     * @test
     *
     * ref. issue https://github.com/spatie/laravel-ray/issues/217
     */
    public function it_can_log_duplicated_queries_with_datetime_parameters()
    {
        ray()->showDuplicateQueries();

        DB::table('users')->where('created_at', '<', new \DateTime(now()))->get();
        DB::table('users')->where('created_at', '<', new \DateTime(now()))->get();

        $this->assertCount(1, $this->client->sentRequests());
    }
}
