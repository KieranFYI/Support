<?php

namespace Kieran\Support\Service\Ticket;

use Kieran\Support\Entity\TicketComment;

class CommentPreparer extends \XF\Service\AbstractService
{
	/**
	 * @var TicketComment
	 */
	protected $comment;
	
	protected $mentionedUsers = [];

	public function __construct(\XF\App $app, TicketComment $comment)
	{
		parent::__construct($app);
		$this->setComment($comment);
	}

	public function setComment(TicketComment $comment)
	{
		$this->comment = $comment;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setUser(\XF\Entity\User $user)
	{
		$this->comment->user_id = $user->user_id;
	}

	public function getMentionedUsers($limitPermissions = true)
	{
		if ($limitPermissions && $this->comment)
		{
			/** @var \XF\Entity\User $user */
			$user = $this->comment->User ?: $this->repository('XF:User')->getGuestUser();
			return $user->getAllowedUserMentions($this->mentionedUsers);
		}
		else
		{
			return $this->mentionedUsers;
		}
	}

	public function getMentionedUserIds($limitPermissions = true)
	{
		return array_keys($this->getMentionedUsers($limitPermissions));
	}

	public function setAttachmentHash($hash)
	{
		$this->comment->attachmentHash = $hash;
	}

    public function setMessage($message, $format = true)
    {
        $preparer = $this->getMessagePreparer($format);
        $this->comment->message = $preparer->prepare($message);

        $this->mentionedUsers = $preparer->getMentionedUsers();

        return $preparer->pushEntityErrorIfInvalid($this->comment);
    }

    public function setPriority($priority)
    {
        $this->comment->priority_change = $priority;
    }

    /**
     * @param bool $format
     *
     * @return \XF\Service\Message\Preparer
     */
    protected function getMessagePreparer($format = true)
	{
		/** @var \XF\Service\Message\Preparer $preparer */
		$preparer = $this->service('XF:Message\Preparer', 'ticket_comment');
		if (!$format)
		{
			$preparer->disableAllFilters();
		}
		$preparer->setConstraint('allowEmpty', true);

		return $preparer;
	}
}