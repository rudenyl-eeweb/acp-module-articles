<?php
namespace Modules\Articles\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
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
    protected $hidden = ['last_access', 'deleted_at', 'access'];

    /**
     * The fillable property.
     *
     * @var array
     */
    protected $fillable = ['title', 'slug', 'summary', 'description', 'is_featured', 'ordering', 'access', 'published'];

    /**
     * Softdelete attribute.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Scope "published".
     *
     * @param mixed $query
     *
     * @return mixed
     */
    public function scopepublished($query)
    {
        return $query->where('published', 1)
            ->where(function($query) {
                if (!auth()->check()) {
                    $query->where('access', 0);
                }
            });
    }

    /**
     * Scope "most_read".
     *
     * @param mixed $query
     *
     * @return mixed
     */
    public function scopemost_read($query, $limit=5)
    {
        return $query->where('hits', '>', 1)
            ->orderBy('hits', 'desc')
            ->take($limit);
    }

    /**
     * Scope "latest".
     *
     * @param mixed $query
     *
     * @return mixed
     */
    public function scopelatest($query, $limit=5)
    {
        return $query->orderBy('created_at', 'desc')
            ->take($limit);
    }
}
