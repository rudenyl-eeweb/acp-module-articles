<?php

namespace Modules\Articles\Entities;

use API\Core\Entities\ScopedModel as BaseModel;

class ArticleDomainData extends BaseModel
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
}
