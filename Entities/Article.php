<?php
namespace Modules\Articles\Entities;

use Carbon\Carbon;
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
    protected $fillable = ['title', 'slug', 'summary', 'description', 'source_id'];

    /**
     * Softdelete attribute.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * @param $value
     */
    public function setIdAttribute($value)
    {
        !is_numeric($value) and $value = Hashedids::decode($value);

        $this->attributes['id'] = $value;
    }

    /**
     * @param void
     */
    public function getIdAttribute()
    {
        $id = $this->attributes['id'];

        !is_numeric($id) and $value = Hashedids::decode($id);

        return $id;
    }

    /**
     * @param void
     */
    public function getDeletedAtAttribute()
    {
        return $this->_getDate( $this->attributes['deleted_at'] );
    }

    /**
     * @param void
     */
    public function getCreatedAtAttribute()
    {
        return $this->_getDate( $this->attributes['created_at'] );
    }

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
        return $query->where('articles.domain_id', '<>', auth_user()->domain->id)
            ->whereNull('source_id');
    }

    /**
     * Hidden scope (has deleted_at).
     */
    public function scopehidden($query)
    {
        return $query->whereNotNull('deleted_at');            
    }

    /**
     * Convert datetime to correct timezone
     *
     * @return \Carbon\Carbon
     */
    private function _getDate($date)
    {
        if (empty($date)) {
            return;
        }

        $tz = config('articles.timezone', 'UTC');
        return (string)(new Carbon($date))->setTimezone($tz);
    }
}
