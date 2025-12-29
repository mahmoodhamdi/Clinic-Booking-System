<?php

namespace Tests\Unit\Http\Helpers;

use App\Http\Helpers\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    /** @test */
    public function success_returns_success_response(): void
    {
        $response = ApiResponse::success(['key' => 'value'], 'Success message');

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals(['key' => 'value'], $content['data']);
    }

    /** @test */
    public function success_returns_response_without_message(): void
    {
        $response = ApiResponse::success(['key' => 'value']);

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertArrayNotHasKey('message', $content);
        $this->assertEquals(['key' => 'value'], $content['data']);
    }

    /** @test */
    public function created_returns_201_status(): void
    {
        $response = ApiResponse::created(['id' => 1]);

        $this->assertEquals(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
    }

    /** @test */
    public function error_returns_error_response(): void
    {
        $response = ApiResponse::error('Error message', 400);

        $this->assertEquals(400, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Error message', $content['message']);
    }

    /** @test */
    public function error_includes_errors_when_provided(): void
    {
        $errors = ['field' => ['Error 1', 'Error 2']];
        $response = ApiResponse::error('Validation failed', 422, $errors);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertEquals($errors, $content['errors']);
    }

    /** @test */
    public function not_found_returns_404(): void
    {
        $response = ApiResponse::notFound('Resource not found');

        $this->assertEquals(404, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    /** @test */
    public function unauthorized_returns_401(): void
    {
        $response = ApiResponse::unauthorized();

        $this->assertEquals(401, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    /** @test */
    public function forbidden_returns_403(): void
    {
        $response = ApiResponse::forbidden();

        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    /** @test */
    public function validation_error_returns_422(): void
    {
        $errors = ['email' => ['The email field is required.']];
        $response = ApiResponse::validationError($errors);

        $this->assertEquals(422, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals($errors, $content['errors']);
    }

    /** @test */
    public function too_many_requests_returns_429(): void
    {
        $response = ApiResponse::tooManyRequests();

        $this->assertEquals(429, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    /** @test */
    public function server_error_returns_500(): void
    {
        $response = ApiResponse::serverError();

        $this->assertEquals(500, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
    }

    /** @test */
    public function paginated_returns_correct_structure(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);

        $response = ApiResponse::paginated($paginator);

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertArrayHasKey('links', $content);
        $this->assertEquals(1, $content['meta']['current_page']);
        $this->assertEquals(10, $content['meta']['total']);
    }
}
