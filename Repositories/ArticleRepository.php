<?php

namespace Modules\Articles\Repositories;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Support\Facades\Request;
use Modules\Articles\Entities\Article;
use Modules\Articles\Repositories\ArticleDomainDataRepository;
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

        $article = Article::create($data);

        return $article;
    }
    
    public function update($id, &$context)
    {
        $article = $this->findById($id);
        if (Request::has('restore') && (int)Request::get('restore')) {
            $article->restore();

            $context = 'restored';
        }
        else {
            $article->update( Request::all() );
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
        return $this->getModel()->findOrFail($id);
    }

    public function findBy($key, $value, $operator = '=')
    {
        return $this->getModel()->where($key, $operator, $value)->firstOrFail();
    }

    public function paginate($data) {}
}
