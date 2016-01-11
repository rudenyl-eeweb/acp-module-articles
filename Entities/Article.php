<?php
namespace Modules\Articles\Entities;

use API\Core\Entities\ScopedModel as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends BaseModel
{
    use SoftDeletes;

    /**
     * Guarded attributes.
     *
     * @var array
     */
    protected $guarded  = ['id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The fillable property.
     *
     * @var array
     */
    protected $fillable = ['title', 'slug', 'summary', 'description', 'access', 'published'];

    /**
     * Softdelete attribute.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
