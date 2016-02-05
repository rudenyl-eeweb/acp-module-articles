<?php
namespace Modules\Articles\Repositories;

use Illuminate\Support\Arr;
use Modules\Articles\Entities\ArticleDomainData;
use API\Core\Contracts\EntityRepositoryInterface;

class ArticleDomainDataRepository implements EntityRepositoryInterface
{
    public function create(array $data = null)
    {
        if (is_null($data)) {
            return false;
        }

        $arr = $this->validate($data);
        $arr['article_id'] = $data['id'];

        if ($user = auth_user()) {
            $arr = array_merge($arr, [
                'created_by' => $user->id,
                'modified_by' => $user->id
            ]);
        }

        return ArticleDomainData::create($arr);
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
        foreach (['publish_up', 'publish_down'] as $date_field)  {
            empty($arr[$date_field]) and $arr[$date_field] = null;
        }

        return $arr;
    }
}
