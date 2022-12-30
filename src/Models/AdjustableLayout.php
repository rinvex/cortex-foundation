<?php

declare(strict_types=1);

namespace Cortex\Foundation\Models;

use Cortex\Foundation\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Rinvex\Support\Traits\ValidatingTrait;
use Rinvex\Support\Traits\HashidsTrait;

/**
 * Cortex\Foundation\Models\AdjustableLayout.
 *
 * @property int                 $id
 * @property string              $element_id
 * @property string              $position
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AdjustableLayout extends Model
{
    use ValidatingTrait;
    use HashidsTrait;
    use Auditable;

    protected $fillable = [
        'element_id',
        'data',
    ];

    protected $casts = [
        'element_id' => 'string',
        'data' => 'json',
    ];


    /**
     * The default rules that the model will validate against.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('cortex.foundation.tables.adjustable_layouts'));

        $this->mergeRules(['element_id' => 'string', 'data' => 'array']);

        parent::__construct($attributes);
    }
}
