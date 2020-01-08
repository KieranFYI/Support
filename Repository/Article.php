<?php

namespace Kieran\Support\Repository;

use XF;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Article extends Repository
{

    public function findArticles($topic_id, $ignorePerms = false)
    {
        $finder = $this->finder('Kieran\Support:Article')
            ->where('topic_id', $topic_id)
            ->order('display_order', 'ASC')
			->where('display_order', '!=', 0);
            
        if (!$ignorePerms) {
            $finder = $this->filterGroups($finder);
        }
        
        return $finder->fetch();
    }

    public function findFaq()
    {
        $finder = $this->finder('Kieran\Support:Article')
            ->where('is_faq', '=', 1)
            ->order('display_order', 'ASC')
			->where('display_order', '!=', 0);
            
        $finder = $this->filterGroups($finder);
        
        return $finder->fetch();
    }

    private function filterGroups(Finder $finder) {

        $values = array_merge(XF::visitor()->secondary_group_ids, [XF::visitor()->user_group_id]);

        $columnName = $finder->columnSqlName('groups');
        $parts = [];
        foreach ($values AS $part)
        {
            $parts[] = 'FIND_IN_SET(' . $finder->quote($part) . ', '. $columnName . ')';
        }
        if ($parts)
        {
            $finder->whereSql(implode(' OR ', $parts));
        }

        return $finder;
    }
}