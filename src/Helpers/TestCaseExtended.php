<?php

namespace Solutionplus\FeatureTest\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Testing\TestResponse as Response;

abstract class TestCaseExtended extends TestCaseAssertion
{
    public function index(
        bool $assertAuth = true,
        int $collectionCount = 10,
        array $customFactoryData = [],
        array $parameters = [],
        string $routeName = '',
        array $parentNestedRoutesIdentifiers = [],
        array $incrementableAttributes = []
    ): Response|null {
        $routeName = $routeName != null ? $routeName : "{$this->routeName}.index";
        $this->withoutExceptionHandling();
        $this->assertRouteExists($routeName)
            ->setIndexCollectionCount($collectionCount);

        $this->factoryCreate($this->indexCollectionCount, $customFactoryData, $incrementableAttributes);
        $response = $this->getJson(route($routeName, $parentNestedRoutesIdentifiers), $parameters)->assertOk();
        if ($assertAuth) {
            $this->assertAuthUser($response);
        }

        return $this->assertResponseStructure(response: $response, isCollection: true);
    }

    public function store(
        bool $assertAuth = true,
        FormRequest|null $request = null,
        array $data = [],
        string $routeName = '',
        Model|null $factory = null,
        array $parentNestedRoutesIdentifiers = []
    ): Response|null {
        $routeName = $routeName != null ? $routeName : "{$this->routeName}.store";
        $this->withoutExceptionHandling();
        $this->setFactoryStoreRequestData($data, $factory);

        $this->assertRouteExists($routeName)
            ->assertValidationRulesMatch($this->storeValidationRules, $request);

        $response = $this->postJson(route($routeName, $parentNestedRoutesIdentifiers), $this->factoryStoreRequestData)->assertOk();
        if ($assertAuth) {
            $this->assertAuthUser($response);
        }

        $this->modelObject = isset($response->original[$this->modelName]->resource) && $response->original[$this->modelName]->resource instanceof ($this->modelObjectClassWithNamespace) ? $response->original[$this->modelName]->resource : $this->modelObject;

        $this->assertDatabaseHasModelObject();
        return $this->assertResponseStructure(response: $response, isCollection: false);
    }

    public function show(
        bool $assertAuth = true,
        string $routeName = '',
        array $parentNestedRoutesIdentifiers = [],
        array $parameters = []
    ): Response|null {
        $routeName = $routeName != null ? $routeName : "{$this->routeName}.show";
        $this->withoutExceptionHandling();
        $this->assertRouteExists($routeName);

        $modelIdentifier = $this->modelObjectClassIdentifier;
        $response = $this->getJson(route($routeName, \array_merge($parentNestedRoutesIdentifiers, [$this->modelObject->$modelIdentifier], $parameters)))->assertOk();
        if ($assertAuth) {
            $this->assertAuthUser($response);
        }

        return $this->assertResponseStructure(response: $response, isCollection: false);
    }

    public function update(
        bool $assertAuth = true,
        FormRequest|null $request = null,
        array $data = [],
        string $routeName = '',
        Model|null $factory = null,
        array $parentNestedRoutesIdentifiers = []
    ): Response|null {
        $routeName = $routeName != null ? $routeName : "{$this->routeName}.update";
        $this->withoutExceptionHandling();
        $this->setFactoryUpdateRequestData($data, $factory);

        $this->assertRouteExists($routeName)
            ->assertValidationRulesMatch($this->updateValidationRules, $request);

        $modelIdentifier = $this->modelObjectClassIdentifier;
        $response = $this->putJson(route($routeName, \array_merge($parentNestedRoutesIdentifiers, [$this->modelObject->$modelIdentifier])), $this->factoryUpdateRequestData)->assertOk();
        if ($assertAuth) {
            $this->assertAuthUser($response);
        }

        return $this->assertResponseStructure(response: $response, isCollection: false);
    }

    public function destroy(
        bool $assertAuth = true,
        bool $deleteNewFactoryObject = false,
        int $expectedStatusCode = 200,
        bool $isSoftDelete = false,
        FormRequest|null $request = null,
        array $data = [],
        string $routeName = '',
        Model|null $factory = null,
        array $parentNestedRoutesIdentifiers = [],
        bool $isDatabaseDelete = true
    ): Response|null {
        $routeName = $routeName != null ? $routeName : "{$this->routeName}.destroy";
        $this->withoutExceptionHandling();
        $this->setFactoryDestroyRequestData($data, $factory);

        $this->assertRouteExists($routeName)
            ->assertValidationRulesMatch($this->destroyValidationRules, $request);

        $modelIdentifier = $this->modelObjectClassIdentifier;
        $requestMethod = $data != null ? 'postJson' : 'deleteJson';

        $this->modelObject = $deleteNewFactoryObject ? $this->factoryCreate() : $this->modelObject;

        $response = $this->$requestMethod(route($routeName, \array_merge($parentNestedRoutesIdentifiers, [$this->modelObject->$modelIdentifier])), $this->factoryDestroyRequestData)->assertStatus($expectedStatusCode);
        if ($assertAuth) {
            $this->assertAuthUser($response);
        }

        if ($expectedStatusCode < 300 && $expectedStatusCode > 199 && $isDatabaseDelete) {
            $this->assertDatabaseMissingModelObject($isSoftDelete);
        } else {
            $this->assertDatabaseHasModelObject($isSoftDelete);
        }

        return $response;
    }
}
