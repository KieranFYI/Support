<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Topic extends Repository
{
    public function findTopic($slug)
    {
        return $this->finder('Kieran\Support:Topic')
            ->where('slug', $slug)
			->where('display_order', '!=', 0)
            ->fetchOne();
    }

    public function findTopics($parent = 0, $viewpower = 0)
    {
        return $this->finder('Kieran\Support:Topic')
            ->order('display_order', 'ASC')
            ->where('parent_topic_id', $parent)
            ->where('view_power_required', '<=', $viewpower)
			->where('display_order', '!=', 0)
            ->fetch();
    }
}