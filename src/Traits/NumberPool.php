<?php

namespace linushstge\NumberPool\Traits;

use Illuminate\Support\Facades\DB;
use linushstge\NumberPool\Exceptions\NumberPoolException;

trait NumberPool
{
    /**
     * The number pool key for selecting the next number
     */
    private string $numberPoolKey;

    /**
     * The attribute which should be used for writing the number pool number
     */
    private string $numberPoolAttribute;

    /**
     * The Step size which number pool gets incremented
     */
    private int $numberPoolStepSize;

    /**
     * Initialize model events
     */
    public static function bootNumberPool()
    {
        static::creating(function ($model) {
            $model->beforeCreated();
        });
    }

    protected function beforeCreated()
    {
        if ($this->numberPoolStepSize() < 0) {
            throw new NumberPoolException("The numberPoolStepSize size can't be a negative increment.");
        }

        $this->numberPoolKey = $this->numberPoolKey();
        $this->numberPoolAttribute = $this->numberPoolAttribute();
        $this->numberPoolStepSize = $this->numberPoolStepSize();

        DB::transaction(function () {

            $q = \linushstge\NumberPool\Models\NumberPool::query();
            $q->where('key', '=', $this->numberPoolKey);
            $q->lockForUpdate();
            $numberPool = $q->firstOr('*', function () {
                throw new NumberPoolException("The Number Pool with key {$this->numberPoolKey} does not exists. The number could not be written. The original save operation has been committed successfully.");
            });

            $numberPool->increment('number', $this->numberPoolStepSize);
            $numberPool->save();

            // Save original model attribute
            $this->setAttribute($this->numberPoolAttribute, $numberPool->number);
        });
    }

    /**
     * The number pool key defines the unique identifier of the number pool which to be used to claim a new unique number
     *
     * @return string
     */
    abstract public function numberPoolKey(): string;

    /**
     * The number pool attributes defines the local model attribute where the unique number has to be saved
     *
     * @return string
     */
    abstract public function numberPoolAttribute(): string;

    /**
     * The number pool step size allows to set an increment integer for each new generated unique number
     *
     * @return int
     */
    public function numberPoolStepSize(): int
    {
        return 1;
    }
}
