<?php

namespace Modules\Articles\Entities;

use Illuminate\Database\Eloquent\Model;

class ArticleDomainData extends Model
{
    /**
     * Disable timestamps checking
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_domain_data';

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
    protected $hidden = ['domain', 'domain_id'];

    /**
     * The fillable property.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Get article reference
     */
    public function article()
    {
        return $this->hasOne(Article::class, 'id', 'article_id')
            ->withTrashed();
    }

    /**
     * Get domain articles
     */
    public function domain_articles()
    {
        return $this->hasManyThrough(Article::class, $this, 'article_id', 'domain_id');
    }

    /**
     * Get owner domain
     */
    public function domain()
    {
        return $this->belongsTo(\API\Core\Entities\Domain::class);
    }
}
