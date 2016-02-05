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
    protected $hidden = ['domain_id', 'data'];

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

    /**
     * Domain data
     */
    public function data()
    {
        return $this->hasOne(ArticleDomainData::class);
    }

    /**
     * Domain
     */
    public function domain()
    {
        return $this->hasOne(\API\Core\Entities\Domain::class, 'id', 'domain_id');
    }

    /**
     * Any domains but this.
     */
    public function scopenot_from_this_domain($query)
    {
        return $query->where('articles.domain_id', '<>', auth_user()->domain->id);
    }

    /**
     * Published scope.
     */
    public function scopepublished($query, $published)
    {
        return $query->where(function($query) use ($published) {
                return $published 
                    ? $query->whereNotNull('article_domain_data.published')
                    : $query->whereNull('article_domain_data.published');
            })
            ->whereNull('deleted_at');
    }

    /**
     * Hidden scope (has deleted_at).
     */
    public function scopehidden($query)
    {
        return $query->whereNotNull('deleted_at');            
    }
}
