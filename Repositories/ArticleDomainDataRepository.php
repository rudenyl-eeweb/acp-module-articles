<?php
namespace Modules\Articles\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Modules\Articles\Entities\ArticleDomainData;
use API\Core\Contracts\EntityRepositoryInterface;

class ArticleDomainDataRepository implements EntityRepositoryInterface
{
    public function create(array $data = null)
    {
        if (is_null($data)) {
            return false;
        }

        // check null values
        $arr = $this->validate($data);

        // get publisher id
        $uid = Request::header('UID', 0);

        $arr = array_merge($arr, [
            'article_id' => $data['id'],
            'created_by' => $uid,
            'modified_by' => $uid
        ]);

        return ArticleDomainData::updateOrCreate(['article_id' => $data['id']], $arr);
    }

    public function perPage() {}

    public function allWithTerm($context = null) {}

    public function getAll() {}

    public function search($context) {}

    public function findById($id) {}

    public function findBy($key, $value, $operator = '=') {}

    public function delete($id) {}

    public function paginate($data) {}

    public static function validate($data)
    {
        if (is_null($data)) {
            return $data;
        }

        $arr = Arr::only($data, ['updated_at', 'access', 'publish_up', 'publish_down', 'published']);

        // check empty
        $fields = [
            'publish_up' => [
                'type' => 'datetime',
                'value' => 'now()'
            ],
            'publish_down' => [
                'type' => 'datetime',
                'value' => null
            ],
            'published' => '0'
        ];
        foreach ($fields as $date_field => $default_value)  {
            $type = null;

            if (is_array($default_value)) {
                // get type
                $type = isset($default_value['type'])
                    ? $default_value['type']
                    : null;

                $default_value = isset($default_value['value'])
                    ? $default_value['value']
                    : null;
            }

            if (empty($arr[$date_field])) {
                $arr[$date_field] = $default_value;
            }
            else {
                //
                // Test for datetime
                //
                // in MySQL, "2016-02-16 05:30 PM" --> "0000-00-00 00:00:00"
                // PostgreSQL seems okay ;)
                //
                if ($type == 'datetime' && (($value = strtotime($arr[$date_field])) !== false)) {
                    $arr[$date_field] = date('Y-m-d H:i:s', $value);
                }
            }
        }

        return $arr;
    }
}
