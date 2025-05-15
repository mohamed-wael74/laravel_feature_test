<?php

namespace Otas\Testing\Helpers;

use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse as Response;

abstract class TestCaseAssertion extends TestCasePropertiesSetter
{
    public function setUp() : void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();
        $this->setUpSetter();
    }

    public function assertRouteExists(string $routeMethodName) : self
    {
        $routeName = Str::contains($routeMethodName, '.') ? $routeMethodName : "{$this->routeName}.{$routeMethodName}";
        $this->assertTrue(\in_array($routeName, app()->routeList), "This route name {$routeName} is not defined");
        return $this;
    }

    public function assertValidationRulesMatch(array $validationRules = [], ?FormRequest $request = null) : self
    {
        if ($request == null) return $this;
        $this->assertEquals($validationRules, $request->rules());
        return $this;
    }

    public function assertAuthUser(?Response $response = null) : self
    {
        $user = auth($this->authGuard)->user();
        if ($user == null) $response == null ? $this->assertTrue($user == null, 'Unauthenticated User') : $response->assertStatus(403);
        return $this;
    }

    public function assertResponseStructure(Response $response, bool $isCollection = false) : Response
    {
        $response->assertJson(function (AssertableJson $json) use ($isCollection) {
            $this->assertStructure($json, $isCollection);
        });
        return $response;
    }

    public function assertStructure(AssertableJson $json, bool $isCollection = false, array $expectedStructure = []) : self
    {
        $this->modelObject = $this->modelObject->refresh();
        $expectedStructure = $expectedStructure != null ? $expectedStructure : ($isCollection ? $this->responseCollectionStructure : $this->responseSingleObjectStructure);
        $this->runAssertOnExpectedStructure($json, $isCollection, $expectedStructure);
        return $this;
    }

    public function assertResponseDataTypes(AssertableJson $json, string $key)
    {
        if (\in_array($key, \array_keys($this->responseDataType))) $json->whereType($key, $this->responseDataType[$key]);
    }

    public function assertDatabaseHasModelObject(bool $isSoftDelete = false)
    {
        $isSoftDelete ? $this->assertNotSoftDeleted($this->modelObject) : $this->assertModelExists($this->modelObject);
    }

    public function assertDatabaseMissingModelObject(bool $isSoftDelete = false)
    {
        $isSoftDelete ? $this->assertSoftDeleted($this->modelObject) : $this->assertModelMissing($this->modelObject);
    }

    private function runAssertOnExpectedStructure(AssertableJson $json, bool $isCollection, array $expectedStructure)
    {
        if ($isCollection) {
            foreach ($expectedStructure as $key => $value) {
                \is_int($key) ? $this->numericCollectionStructureKeysToResponseAssertion($json, $value) : $this->stringCollectionStructureKeysToResponseAssertion($json, $key, $value);
            }
        } else {
            foreach ($expectedStructure as $key => $value) {
                \is_int($key) ? $this->numericSingleObjectStructureKeysToResponseAssertion($json, $value) : $this->stringSingleObjectStructureKeysToResponseAssertion($json, $key, $value);
            }
        }
        if (! $this->responseExactStructure) $json->etc();
    }

    # Private Helpers

    private function numericSingleObjectStructureKeysToResponseAssertion(AssertableJson $json, $value) : void
    {
        if (\is_array($value)) return;
        switch (true) {
            case $value == 'message' && request()->method() == 'GET' :
            break;
            case \in_array($value, $this->modelRelationsResponseKeys) :
                $json->has($value);
                $this->assertResponseDataTypes($json, $value);
            break;
            case ! \in_array($value, \array_merge($this->singleObjectAttributes, \array_keys($this->attributesAliases))) :
                $json->has($value);
                $this->assertResponseDataTypes($json, $value);
            break;
            default :
                $modelAttributeName = \in_array($value, \array_keys($this->attributesAliases)) ? $this->attributesAliases[$value] : $value;
                if (Str::endsWith($modelAttributeName, ['_at', '_date'])) {
                    $json->has($value);
                    return;
                }
                $json->where($value, $this->modelObject->$modelAttributeName);
                $this->assertResponseDataTypes($json, $value);
        }
    }

    private function stringSingleObjectStructureKeysToResponseAssertion(AssertableJson $json, string $key, $value) : void
    {
        switch (true) {
            case $key == $this->modelName && \is_array($value) && $value != null :
                $json->has($key, function (AssertableJson $nestedJson) use ($key, $value) {
                    $this->assertStructure($nestedJson, false, $value);
                });
            break;
            case $key == 'missing' && $value != null :
                $json->missingAll($value);
            break;
            case ! \in_array($key, \array_merge($this->singleObjectAttributes, \array_keys($this->attributesAliases), ['message', 'missing'])):
                $json->has($key);
                $this->assertResponseDataTypes($json, $key);
            break;
        }
    }

    private function numericCollectionStructureKeysToResponseAssertion(AssertableJson $json, $value) : void
    {
        if (\is_array($value)) return;
        switch (true) {
            case \in_array($value, \array_merge($this->modelRelationsResponseKeys, ['meta', 'links'])) :
                $json->has($value);
                $this->assertResponseDataTypes($json, $value);
            break;
            default :
                $modelAttributeName = \in_array($value, \array_keys($this->attributesAliases)) ? $this->attributesAliases[$value] : $value;
                if (Str::endsWith($modelAttributeName, ['_at', '_date'])) {
                    $json->has($value);
                    return;
                }
                $json->where($value, $this->modelObject->$modelAttributeName);
                $this->assertResponseDataTypes($json, $value);
        }
    }

    private function stringCollectionStructureKeysToResponseAssertion(AssertableJson $json, string $key, $value) : void
    {
        switch (true) {
            case $key == $this->collectionKeyName && \is_array($value) && $value != null :
                $actualCollectionCount = $this->modelObject != null ? $this->indexCollectionCount + 1 : $this->indexCollectionCount;
                $json->has($this->collectionKeyName, $actualCollectionCount, function (AssertableJson $nestedJson) use ($key, $value) {
                    $this->assertStructure($nestedJson, true, $value);
                    if (! $this->responseExactStructure) $nestedJson->etc();
                });
                $this->assertResponseDataTypes($json, $key);
            break;
            case ($key == 'missing' || $key == 'missingAll') && $value != null :
                $json->missingAll($value);
            break;
            case ! \in_array($key, \array_merge($this->collectionAttributes, \array_keys($this->attributesAliases), ['missing', 'missingAll'])):
                $json->has($key);
                $this->assertResponseDataTypes($json, $key);
            break;
        }
    }
}
