<?php

namespace Modules\Articles\Entities;

use Carbon\Carbon;
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
    protected $hidden = [];

    /**
     * The fillable property.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * @param void
     */
    public function getAccessedAtAttribute()
    {
        return $this->_getDate( $this->attributes['accessed_at'] );
    }

    /**
     * @param void
     */
    public function getUpdatedAtAttribute()
    {
        return $this->_getDate( $this->attributes['updated_at'] );
    }

    /**
     * @param void
     */
    public function getPublishUpAttribute()
    {
        return $this->_getDate( $this->attributes['publish_up'] );
    }

    /**
     * @param void
     */
    public function getPublishDownAttribute()
    {
        return $this->_getDate( $this->attributes['publish_down'] );
    }

    /**
     * Convert datetime to correct timezone
     *
     * @return \Carbon\Carbon
     */
    private function _getDate($date)
    {
        if (empty($date) || strtotime($date) === false) {
            return;
        }

        $tz = config('articles.timezone', 'UTC');
        $datetime = (new Carbon($date))->setTimezone($tz);

        // no negative timestamps
        // or 0000-00-00 00:00:00
        if ($datetime->timestamp <= 0) {
            return;
        }

        return (string)$datetime;
    }
}
