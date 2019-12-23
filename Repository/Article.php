<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Article extends Repository
{
    public function findArticle($slug)
    {
        return $this->finder('Kieran\Support:Article')
            ->where('slug', $slug)
			->where('display_order', '!=', 0)
            ->fetchOne();
    }

    public function findArticles($topic_id, $viewpower = 0)
    {
        return $this->finder('Kieran\Support:Article')
            ->where('topic_id', $topic_id)
            ->order('display_order', 'ASC')
            ->where('view_power_required', '<=', $viewpower)
			->where('display_order', '!=', 0)
            ->fetch();
    }

    public function findFaq($viewpower = 0)
    {
        return $this->finder('Kieran\Support:Article')
            ->where('is_faq', '=', 1)
            ->order('display_order', 'ASC')
            ->where('view_power_required', '<=', $viewpower)
			->where('display_order', '!=', 0)
            ->fetch();
    }
}