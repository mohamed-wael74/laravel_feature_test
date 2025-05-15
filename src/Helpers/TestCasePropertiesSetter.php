<?php

namespace Otas\Testing\Helpers;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Otas\Testing\Traits\CreatesApplication;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCasePropertiesSetter extends BaseTestCase
{
    use CreatesApplication, WithFaker;

    public string $authGuard;
    public Model $authUser;
    public string $modelName;
    public string $routeName;
    public Model $modelObject;
    public string $authModelClass;
    public string $routeNamespace;
    public array $availableLocales;
    public array $responseDataType;
    public array $attributesAliases;
    public bool $isPolymorphicModel;
    public string $modelDbTableName;
    public string $collectionKeyName;
    public int $indexCollectionCount;
    public array $collectionAttributes;
    public array $storeValidationRules;
    public bool $responseExactStructure;
    public array $updateValidationRules;
    public array $destroyValidationRules;
    public array $singleObjectAttributes;
    public array $factoryStoreRequestData;
    public array $factoryUpdateRequestData;
    public array $factoryDestroyRequestData;
    public array $responseMissingAttributes;
    public array $modelRelationsResponseKeys;
    public array $polymorphicRelationClasses;
    public string $modelObjectClassIdentifier;
    public array $responseCollectionStructure;
    public string $polymorphicRelationMethodName;
    public array $responseSingleObjectStructure;
    public string $modelObjectClassWithNamespace;
    public string|null $randomPolymorphicRelationClass;

    public function setUpSetter() : void
    {
        $this->setRouteNamespace()
            ->setAvailableLocales()
            ->setAppRouteListAttribute()
            ->setAuthModelClassWithNamespace()
            ->setModelObjectClassWithNamespace()
            ->setModelDbTableName()
            ->setModelObjectClassIdentifier()
            ->setModelRelationsResponseKeys()
            // ->setAuthGuard() //called form setAuthUser
            // ->setAuthUser() // should be called from a child class when needed. if called in setUp or specific crud method then response will check for auth accordingly
            ->setPolymorphicRelationMethodName()
            ->setIsPolymorphicModel()
            ->setPolymorphicRelationClasses()
            ->setRandomPolymorphicRelationClass()
            ->setModelObject()
            ->setModelName()
            ->setRouteName()
            ->setIndexCollectionCount()
            ->setStoreValidationRules()
            ->setUpdateValidationRules()
            ->setDestroyValidationRules()
            ->setResponseExactStructure()
            ->setResponseMissingAttributes()
            ->setSingleObjectAttributes()
            ->setCollectionAttributes()
            ->setAttributesAliases()
            // ->setFactoryStoreRequestData()
            // ->setFactoryUpdateRequestData()
            // ->setFactoryDestroyRequestData()
            ->setResponseSingleObjectStructure()
            ->setResponseCollectionStructure()
            ->setResponseDataType();
    }

    public function setAvailableLocales(array $locales = []) : self
    {
        $this->availableLocales = $locales != null ? $locales : ['en', 'ar'];
        \shuffle($this->availableLocales);
        return $this;
    }

    public function setAuthGuard(string $guard = '') : self
    {
        $this->authGuard = $guard != null ? $guard : 'api';
        return $this;
    }

    public function setAuthUser(?Model $user = null, array $authUserCustomFactoryData = [], $authGuard = '') : self
    {
        $this->setAuthGuard($authGuard);

        $this->authUser = Passport::actingAs($user != null ? $user : $this->authModelClass::factory()->create($authUserCustomFactoryData), [], $this->authGuard);
        return $this;
    }

    private function setRouteNamespace() : self
    {
        $namespaceParts = \explode('\\', \get_class($this));
        \array_pop($namespaceParts);
        $this->routeNamespace = \array_pop($namespaceParts);
        return $this;
    }

    private function setAppRouteListAttribute() : self
    {
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            $routes[] = $route->getName();
        }
        app()->routeList = \array_filter($routes, fn ($route) => \str_starts_with($route, \strtolower($this->routeNamespace)));
        return $this;
    }

    public function setAuthModelClassWithNamespace(string $modelClass = '') : self
    {
        $this->authModelClass = $modelClass != null ? $modelClass : 'App\Models\User';
        return $this;
    }

    public function setModelObjectClassWithNamespace(string $modelObjectClass = '') : self
    {
        $classNameParts = \explode('\\', \get_class($this));
        $this->modelObjectClassWithNamespace = $modelObjectClass != null ?
            (\str_contains($modelObjectClass, '\\') ? $modelObjectClass : 'App\Models\\') :
            'App\Models\\' . Str::singular(\str_replace('Test', '', \array_pop($classNameParts)));
        return $this;
    }

    public function setModelDbTableName(string $modelDbTableName = '') : self
    {
        $this->modelDbTableName = $modelDbTableName != null ? $modelDbTableName : Str::plural(Str::snake(\str_replace('App\Models\\' , '', $this->modelObjectClassWithNamespace)));
        return $this;
    }

    private function setModelObjectClassIdentifier() : self
    {
        $this->modelObjectClassIdentifier = (new ($this->modelObjectClassWithNamespace)())->getRouteKeyName();
        return $this;
    }

    public function setModelRelationsResponseKeys(array $modelRelationsResponseKeys = []) : self
    {
        $this->modelRelationsResponseKeys = $modelRelationsResponseKeys;
        return $this;
    }

    public function setModelObject(array $modelFactoryCustomAttributes = []) : self
    {
        $this->modelObject = $this->factoryCreate(1, $modelFactoryCustomAttributes);
        return $this;
    }

    private function setModelName() : self
    {
        $modelName = \explode('\_', \strtolower(Str::snake(get_class($this->modelObject))));
        $this->modelName = \array_pop($modelName);
        return $this;
    }

    public function setRouteName(string $routeName = '') : self
    {
        $className = \explode('\\', Str::plural(\get_class($this->modelObject)));
        $this->routeName = $routeName != null ? $routeName : \strtolower($this->routeNamespace . '.' . Str::kebab(\array_pop($className)));
        return $this;
    }

    public function setIndexCollectionCount(int $indexCollectionCount = 10) : self
    {
        $this->indexCollectionCount = $this->modelObject != null ? $indexCollectionCount - 1 : $indexCollectionCount;
        return $this;
    }

    public function setStoreValidationRules(array $validationRules = []) : self
    {
        $this->storeValidationRules = $validationRules;
        return $this;
    }

    public function setUpdateValidationRules(array $validationRules = []) : self
    {
        $this->updateValidationRules = $validationRules;
        return $this;
    }

    public function setDestroyValidationRules(array $validationRules = []) : self
    {
        $this->destroyValidationRules = $validationRules;
        return $this;
    }

    public function setResponseExactStructure(bool $responseExactStructure = true) : self
    {
        $this->responseExactStructure = $responseExactStructure;
        return $this;
    }

    public function setResponseMissingAttributes(array $responseMissingAttributes = []) : self
    {
        $this->responseMissingAttributes = $responseMissingAttributes;
        return $this;
    }

    public function setSingleObjectAttributes(array $attributes = []) : self
    {
        $this->singleObjectAttributes = \array_merge($attributes, ['missing' => $this->responseMissingAttributes]);
        return $this;
    }

    public function setCollectionAttributes(array $attributes = []) : self
    {
        $this->collectionAttributes = $attributes != null ?
            \array_merge($attributes, ['missing' => $this->responseMissingAttributes])
            : \array_merge(
                \array_keys($this->modelObject->getAttributes()),
                ($this->modelObject->translatedAttributes != null ? $this->modelObject->translatedAttributes : []),
                ['missing' => $this->responseMissingAttributes]
            );
        return $this;
    }

    public function setAttributesAliases(array $attributesAliases = []) : self
    {
        $this->attributesAliases = $attributesAliases;
        return $this;
    }

    public function setResponseSingleObjectStructure(string $customModelResponseKeyName = '') : self
    {
        if (request()->method() == 'DELETE') {
            $this->responseSingleObjectStructure = ['message'];
            return $this;
        }
        $modelResponseKeyName = $customModelResponseKeyName != '' ? $customModelResponseKeyName : $this->modelName;
        $this->responseSingleObjectStructure = [
            $modelResponseKeyName => $this->singleObjectAttributes,
            'message'
        ];
        return $this;
    }

    public function setResponseCollectionStructure(bool $isPaginated = true, bool $replaceDataKeyWithModelPluralName = false) : self
    {
        $this->collectionKeyName = $replaceDataKeyWithModelPluralName ? Str::plural($this->modelName) : 'data';
        $this->responseCollectionStructure[$this->collectionKeyName] = $this->collectionAttributes;
        if ($isPaginated) {
            $this->responseCollectionStructure[] = 'links';
            $this->responseCollectionStructure[] = 'meta';
        }
        return $this;
    }

    public function setResponseDataType(array $propertiesTypesMap = []) : self
    {
        if ($propertiesTypesMap != null) {
            foreach ($propertiesTypesMap as $key => $value) {
                if (\is_int($key)) abort(500, 'The method setResponseDataType() parameter must be an associative array or an empty array if no model response keys need to be checked for its types');
            }
        }
        $notExistingResponseKeys = \array_filter(\array_filter(
            \array_keys($propertiesTypesMap), function ($key) {
            return ! \in_array($key, \array_merge($this->singleObjectAttributes, $this->collectionAttributes, $this->modelRelationsResponseKeys, \array_keys($this->attributesAliases)));
        }));
        if ($propertiesTypesMap != null && \count($notExistingResponseKeys) > 0) {
            $stringListOfNotExistingResponseKeys = \implode(', ', $notExistingResponseKeys);
            abort(500, "One or more properties founded in responseDataType array while its not exists in defined response attributes, relations or aliases. a list of it can be found here [{$stringListOfNotExistingResponseKeys}]");
        }
        $this->responseDataType = $propertiesTypesMap;
        return $this;
    }

    public function setFactoryStoreRequestData(array $requestPayload = [], Model|null $factory = null) : self
    {
        $this->factoryStoreRequestData = $this->factoryToDataArray('store', $requestPayload, $factory);
        return $this;
    }

    public function setFactoryUpdateRequestData(array $requestPayload = [], Model|null $factory = null) : self
    {
        $this->factoryUpdateRequestData = $this->factoryToDataArray('update', $requestPayload, $factory);
        return $this;
    }

    public function setFactoryDestroyRequestData(array $requestPayload = [], Model|null $factory = null) : self
    {
        $this->factoryDestroyRequestData = $this->factoryToDataArray('destroy', $requestPayload, $factory);
        return $this;
    }

    public function factoryToDataArray(
        string $requestMethod = 'store'|'update'|'destroy',
        array $requestPayload = [],
        Model|null $factory = null
    ) : array
    {
        $factory = $factory != null ? $factory : $this->modelObjectClassWithNamespace::factory()->make();
        $validationRulesAttributeName = "{$requestMethod}ValidationRules";
        $data = [];
        foreach ($factory->toArray() as $key => $value) {
            switch (true) {
                case \in_array($key, \array_keys($this->$validationRulesAttributeName)) :
                    $data[$key] = $value;
                break;
                case \in_array($key, $this->attributesAliases) :
                    $data[\array_flip($this->attributesAliases)[$key]] = $value;
                break;
                default:
                break;
            }
        }

        return \array_merge(array_filter($data), $requestPayload);
    }

    public function factoryCreate(int $count = 1, array $customFactoryData = [])
    {
        $results = $this->isPolymorphicModel ? $this->polymorphicFactoryCreate($count, $customFactoryData) : $this->modelObjectClassWithNamespace::factory($count)->create($customFactoryData);
        return $results->first();
    }

    public function polymorphicFactoryCreate(int $count = 1, array $customFactoryData = [])
    {
        return $this->modelObjectClassWithNamespace::factory($count)->for($this->randomPolymorphicRelationClass::factory(), $this->polymorphicRelationMethodName)->create($customFactoryData);
    }

    public function setIsPolymorphicModel() : self
    {
        $foreignKeyIdColumnName = Str::snake($this->polymorphicRelationMethodName) . '_id';
        $this->isPolymorphicModel = \in_array($foreignKeyIdColumnName, Schema::getColumnListing((new ($this->modelObjectClassWithNamespace))->getTable()));
        return $this;
    }

    public function setPolymorphicRelationMethodName(string $polymorphicRelationMethodName = 'relatedObject') : self
    {
        $this->polymorphicRelationMethodName = $polymorphicRelationMethodName;
        return $this;
    }

    public function setPolymorphicRelationClasses(array $polymorphicRelationClasses = []) : self
    {
        $this->polymorphicRelationClasses = $polymorphicRelationClasses;
        if ($this->isPolymorphicModel && \count($this->polymorphicRelationClasses) == 0) abort(500, 'the method polymorphicRelationClasses must return an array of related polymorphic model classes instead of an empty array');
        return $this;
    }

    public function setRandomPolymorphicRelationClass(string $randomPolymorphicRelationClass = '') : self
    {
        $this->randomPolymorphicRelationClass = $randomPolymorphicRelationClass != '' ? $randomPolymorphicRelationClass : $this->faker->randomElement($this->polymorphicRelationClasses);
        return $this;
    }
}
