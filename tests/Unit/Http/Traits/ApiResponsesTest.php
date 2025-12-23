<?php

namespace Tests\Unit\Http\Traits;

use App\Http\Traits\ApiResponses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponsesTest extends TestCase
{
    use ApiResponses, RefreshDatabase;

    /**
     * @test
     */
    public function success_returns_json_response(): void
    {
        $response = $this->success(['key' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function success_includes_success_flag(): void
    {
        $response = $this->success();
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
    }

    /**
     * @test
     */
    public function success_includes_data_when_provided(): void
    {
        $response = $this->success(['key' => 'value']);
        $data = $response->getData(true);

        $this->assertEquals(['key' => 'value'], $data['data']);
    }

    /**
     * @test
     */
    public function success_includes_message_when_provided(): void
    {
        $response = $this->success(null, 'Success message');
        $data = $response->getData(true);

        $this->assertEquals('Success message', $data['message']);
    }

    /**
     * @test
     */
    public function success_uses_custom_status_code(): void
    {
        $response = $this->success(null, null, 202);

        $this->assertEquals(202, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function error_returns_json_response(): void
    {
        $response = $this->error('Error message');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function error_includes_success_false(): void
    {
        $response = $this->error('Error message');
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
    }

    /**
     * @test
     */
    public function error_includes_message(): void
    {
        $response = $this->error('Error message');
        $data = $response->getData(true);

        $this->assertEquals('Error message', $data['message']);
    }

    /**
     * @test
     */
    public function error_includes_errors_when_provided(): void
    {
        $response = $this->error('Error message', ['field' => 'Invalid']);
        $data = $response->getData(true);

        $this->assertEquals(['field' => 'Invalid'], $data['errors']);
    }

    /**
     * @test
     */
    public function error_uses_custom_status_code(): void
    {
        $response = $this->error('Error message', null, 500);

        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function created_returns_201_status(): void
    {
        $response = $this->created(['id' => 1]);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function created_uses_default_message(): void
    {
        $response = $this->created();
        $data = $response->getData(true);

        $this->assertNotNull($data['message']);
    }

    /**
     * @test
     */
    public function deleted_returns_200_status(): void
    {
        $response = $this->deleted();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function deleted_uses_default_message(): void
    {
        $response = $this->deleted();
        $data = $response->getData(true);

        $this->assertNotNull($data['message']);
    }

    /**
     * @test
     */
    public function not_found_returns_404_status(): void
    {
        $response = $this->notFound();

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function not_found_uses_default_message(): void
    {
        $response = $this->notFound();
        $data = $response->getData(true);

        $this->assertNotNull($data['message']);
    }

    /**
     * @test
     */
    public function not_found_uses_custom_message(): void
    {
        $response = $this->notFound('Custom not found');
        $data = $response->getData(true);

        $this->assertEquals('Custom not found', $data['message']);
    }

    /**
     * @test
     */
    public function unauthorized_returns_401_status(): void
    {
        $response = $this->unauthorized();

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function forbidden_returns_403_status(): void
    {
        $response = $this->forbidden();

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function validation_error_returns_422_status(): void
    {
        $response = $this->validationError(['field' => 'Required']);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function validation_error_includes_errors(): void
    {
        $response = $this->validationError(['field' => 'Required']);
        $data = $response->getData(true);

        $this->assertEquals(['field' => 'Required'], $data['errors']);
    }
}
