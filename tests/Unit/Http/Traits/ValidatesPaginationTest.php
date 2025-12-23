<?php

namespace Tests\Unit\Http\Traits;

use App\Http\Traits\ValidatesPagination;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ValidatesPaginationTest extends TestCase
{
    use ValidatesPagination;

    /**
     * @test
     */
    public function get_per_page_returns_default_when_not_specified(): void
    {
        $request = Request::create('/test');

        $this->assertEquals(15, $this->getPerPage($request));
    }

    /**
     * @test
     */
    public function get_per_page_returns_custom_default(): void
    {
        $request = Request::create('/test');

        $this->assertEquals(20, $this->getPerPage($request, 20));
    }

    /**
     * @test
     */
    public function get_per_page_returns_value_from_request(): void
    {
        $request = Request::create('/test', 'GET', ['per_page' => 50]);

        $this->assertEquals(50, $this->getPerPage($request));
    }

    /**
     * @test
     */
    public function get_per_page_enforces_minimum_of_one(): void
    {
        $request = Request::create('/test', 'GET', ['per_page' => 0]);

        $this->assertEquals(1, $this->getPerPage($request));
    }

    /**
     * @test
     */
    public function get_per_page_enforces_negative_to_one(): void
    {
        $request = Request::create('/test', 'GET', ['per_page' => -5]);

        $this->assertEquals(1, $this->getPerPage($request));
    }

    /**
     * @test
     */
    public function get_per_page_enforces_maximum(): void
    {
        $request = Request::create('/test', 'GET', ['per_page' => 200]);

        $this->assertEquals(100, $this->getPerPage($request));
    }

    /**
     * @test
     */
    public function get_per_page_enforces_custom_maximum(): void
    {
        $request = Request::create('/test', 'GET', ['per_page' => 60]);

        $this->assertEquals(50, $this->getPerPage($request, 15, 50));
    }

    /**
     * @test
     */
    public function get_limit_returns_default_when_not_specified(): void
    {
        $request = Request::create('/test');

        $this->assertEquals(10, $this->getLimit($request));
    }

    /**
     * @test
     */
    public function get_limit_returns_value_from_request(): void
    {
        $request = Request::create('/test', 'GET', ['limit' => 25]);

        $this->assertEquals(25, $this->getLimit($request));
    }

    /**
     * @test
     */
    public function get_limit_enforces_minimum(): void
    {
        $request = Request::create('/test', 'GET', ['limit' => 0]);

        $this->assertEquals(1, $this->getLimit($request));
    }

    /**
     * @test
     */
    public function get_limit_enforces_maximum(): void
    {
        $request = Request::create('/test', 'GET', ['limit' => 150]);

        $this->assertEquals(100, $this->getLimit($request));
    }

    /**
     * @test
     */
    public function get_page_returns_default_when_not_specified(): void
    {
        $request = Request::create('/test');

        $this->assertEquals(1, $this->getPage($request));
    }

    /**
     * @test
     */
    public function get_page_returns_value_from_request(): void
    {
        $request = Request::create('/test', 'GET', ['page' => 5]);

        $this->assertEquals(5, $this->getPage($request));
    }

    /**
     * @test
     */
    public function get_page_enforces_minimum_of_one(): void
    {
        $request = Request::create('/test', 'GET', ['page' => 0]);

        $this->assertEquals(1, $this->getPage($request));
    }

    /**
     * @test
     */
    public function get_page_enforces_negative_to_one(): void
    {
        $request = Request::create('/test', 'GET', ['page' => -3]);

        $this->assertEquals(1, $this->getPage($request));
    }
}
