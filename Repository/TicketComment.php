<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class TicketComment extends Repository
{
    public function findCommentById($id)
    {
        return $this->finder('Kieran\Support:TicketComment')
            ->where('ticket_comment_id', $id)
            ->where('message_state', '!=', 'deleted')
            ->fetchOne();
    }

    public function findLastCommentBy($user_id, $ticket_id)
    {
        return $this->finder('Kieran\Support:TicketComment')
            ->where('ticket_id', $ticket_id)
            ->where('user_id', $user_id)
            ->where('message_state', '!=', ['deleted', 'hidden'])
            ->order('ticket_comment_id', 'desc')
            ->fetchOne();
    }
}