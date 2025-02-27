<?php

namespace JordanPrice\LaravelFakerAI\Traits;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Trait to add AI context awareness to Laravel factories
 * 
 * When a factory creates multiple models, this trait ensures
 * they all have context of each other (are related/connected)
 */
trait HasAIContext
{
    /**
     * Indicates if AI should maintain context between models
     *
     * @var bool
     */
    protected bool $useAIContext = true;
    
    /**
     * The AI context seed information to use when generating related models
     *
     * @var array
     */
    protected array $aiContextSeed = [];
    
    /**
     * Fields that should be generated with AI
     *
     * @var array
     */
    protected array $aiFields = [];
    
    /**
     * Enable AI context for this factory when creating multiple models
     *
     * @return $this
     */
    public function withAIContext(array $contextSeed = []): self
    {
        $this->useAIContext = true;
        
        if (!empty($contextSeed)) {
            $this->aiContextSeed = array_merge($this->aiContextSeed, $contextSeed);
        }
        
        return $this;
    }
    
    /**
     * Disable AI context for this factory
     *
     * @return $this
     */
    public function withoutAIContext(): self
    {
        $this->useAIContext = false;
        return $this;
    }
    
    /**
     * Specify which fields should be generated with AI context
     *
     * @param array $fields
     * @return $this
     */
    public function aiFields(array $fields): self
    {
        $this->aiFields = $fields;
        return $this;
    }
    
    /**
     * Add context seed information for AI generation
     *
     * @param array $seed
     * @return $this
     */
    public function aiContext(array $seed): self
    {
        $this->aiContextSeed = array_merge($this->aiContextSeed, $seed);
        return $this;
    }
    
    /**
     * Create a collection of models.
     *
     * @param  array<array-key, mixed>|callable|int  $attributes
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model[]
     */
    public function createMany($quantity = null, ?callable $callback = null)
    {
        // If not using AI context, use default method
        if (!$this->useAIContext || empty($this->aiFields)) {
            return parent::createMany($quantity, $callback);
        }
        
        // Get the count of models to create
        $count = is_callable($quantity) || is_array($quantity) ? 1 : $quantity;
        
        // Get the object type from the model
        $objectType = class_basename($this->modelName());
        
        // Create models using AI batch instead of individually
        $aiObjects = fake()->createAIBatch(
            $objectType,
            $count,
            $this->aiContextSeed,
            $this->aiFields
        );
        
        // Convert to collection
        $aiCollection = collect($aiObjects);
        
        // Create models and merge AI-generated fields
        return $this->createManyWithAI($aiCollection, $quantity, $callback);
    }
    
    /**
     * Create multiple models with AI context
     *
     * @param \Illuminate\Support\Collection $aiData
     * @param mixed $quantity
     * @param callable|null $callback
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createManyWithAI(Collection $aiData, $quantity, ?callable $callback = null)
    {
        if (is_callable($quantity)) {
            $callback = $quantity;
            $quantity = 1;
        } elseif (is_array($quantity)) {
            $attributes = $quantity;
            $quantity = 1;
        } else {
            $attributes = [];
        }
        
        $results = [];
        
        // Create each model, applying AI data
        for ($i = 0; $i < $quantity; $i++) {
            // Get AI data for this index
            $aiAttributes = $aiData[$i] ?? [];
            
            // Create the base attributes
            $modelAttributes = $this->getRawAttributes($attributes);
            
            // Merge only the AI fields we specified
            foreach ($this->aiFields as $field) {
                if (isset($aiAttributes[$field])) {
                    $modelAttributes[$field] = $aiAttributes[$field];
                }
            }
            
            // Create the model
            $model = $this->state($modelAttributes)->create([], $callback);
            
            $results[] = $model;
        }
        
        return $this->newModel()->newCollection($results);
    }
}
