<?php

namespace Wikidot\Modules\Forum\Sub;


use Wikidot\DB\ForumPostRevisionPeer;

use Ozone\Framework\SmartyModule;
use Wikidot\Utils\ProcessException;
use Wikidot\Wikitext\WikiTransformation;

class ForumPostRevisionModule extends SmartyModule
{

    public function build($runData)
    {
        $pl = $runData->getParameterList();
        $revisionId = $pl->getParameterValue("revisionId");

        $site = $runData->getTemp("site");

        if ($revisionId == null || !is_numeric($revisionId)) {
            throw new ProcessException(_("No revision specified."), "no_post");
        }

        $revision = ForumPostRevisionPeer::instance()->selectByPrimaryKey($revisionId);
        if ($revision == null) {
            throw new ProcessException(_("No revision specified."), "no_post");
        }

        $runData->ajaxResponseAdd("title", $revision->getTitle());

        $source = $revision->getText();
        $wt = new WikiTransformation();
        $body = $wt->processSource($source);

        $runData->ajaxResponseAdd("content", $body);
        $runData->ajaxResponseAdd("postId", $revision->getPostId());
    }
}
