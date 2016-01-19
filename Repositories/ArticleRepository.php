<?php
namespace Modules\Articles\Repositories;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;
use Modules\Articles\Entities\Article;
use Modules\Articles\Repositories\ArticleDomainDataRepository;
use Modules\Articles\Exceptions\FormValidationException;
use API\Core\Contracts\EntityRepositoryInterface;

class ArticleRepository implements EntityRepositoryInterface
{
    public function getModel()
    {
        return Article::withTrashed();
    }

    public function create(array $data = null)
    {
        if (is_null($data)) {
            $data = Request::except('id');
        }

        // slug is slug
        $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );

        // validate
        $this->validate($data);

        return Article::create($data);
    }
    
    public function update($id, &$context)
    {
        $article = $this->findById($id);
        if (Request::has('restore') && (int)Request::get('restore')) {
            $article->restore();

            $context = 'restored';
        }
        else {
            // validate
            $data = $this->validate(Request::all(), $id);

            // slug is slug
            $data['slug'] = Str::slug( Arr::get($data, 'slug') ?: $data['title'] );

            $article->update($data);
            $article->touch();
        }

        return $article;
    }
    
    public function delete($id)
    {
        $article = $this->findById($id);

        if (Request::has('force') && (int)Request::get('force')) {
            $article->forceDelete();

            return 'deleted';
        }
        else {
            $article->delete();

            return 'marked_deleted';
        }
    }

    public function perPage()
    {
        return Request::get('limit', config('articles.pagination.limit', 10));
    }

    public function allWithTerm($context = null)
    {
    }

    public function getAll()
    {
        $offset = Request::get('offset', 0);
        $sort = Request::get('sort', 'created_at');
        $sort_dir = Request::get('order', 'asc');

        $repository = $this->getModel();

        $total = $repository->count();
        $articles = $repository
            ->orderBy($sort, $sort_dir)
            ->take($this->perPage())
            ->skip($offset)
            ->get();

        $list = new Collection($articles, function(Article $item) {
            return [
                'id' => (int)$item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'created_at' => (string)$item->created_at,
                'modified_at' => (string)$item->modified_at,
                'deleted_at' => (string)$item->deleted_at,
                'published' => (int)$item->published,
            ];
        });

        $manager = new Manager();
        $rows = $manager->createData($list)->toArray();

        // get listing count
        $count = count($list->getData());

        return compact('total', 'count') + $rows;
    }

    public function search($context)
    {
    }

    public function findById($id)
    {
        $article = $this->getModel()->find($id);
        if (is_null($article)) {
            throw new \Exception('article not found', 404);
        }

        return $article;
    }

    public function findBy($key, $value, $operator = '=')
    {
        $article = $this->getModel()->where($key, $operator, $value)->first();
        if (is_null($article)) {
            throw new \Exception('article_not_found', 404);
        }

        return $article;
    }

    public function paginate($data) {}

    protected function validate($data = null, $id = null)
    {
        // create slug
        if (Arr::has($data, 'slug')) {
            $data = array_merge($data, [
                'slug' => Str::slug($data['slug'])
            ]);
        }

        $validator = \Validator::make($data, [
            'title' => 'required|min:5',
            'slug' => 'unique:articles,slug' . ($id ? ','.$id : ''),
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            throw new FormValidationException($validator);
        }

        return $data;
    }
}
