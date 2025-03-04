<?php

namespace Wikidot\Utils;

use Ozone\Framework\Database\Criteria;
use Ozone\Framework\Database\Database;
use Ozone\Framework\ODate;
use Wikidot\DB\AdminPeer;
use Wikidot\DB\Admin;
use Wikidot\DB\Member;
use Wikidot\DB\ThemePeer;
use Wikidot\DB\ForumGroupPeer;
use Wikidot\DB\ForumCategoryPeer;
use Wikidot\DB\FilePeer;
use Wikidot\DB\PageRevision;
use Wikidot\DB\Page;
use Wikijump\Services\Deepwell\Models\Category;

class Duplicator
{

    private $owner;
    private $excludedCategories = array();

    private $pageMap;

    public function cloneSite($site, $siteProperties, $attrs = array())
    {

        $db = Database::connection();
        $db->begin();
        /*
         * Hopefully attrs contains a set of parameters that determine
         * the behoviour of the duplicatior.
         */
        $nsite = clone ($site);
        $nsite->setNew(true);
        $nsite->setSiteId(null);

        $nsite->setSlug($siteProperties['unixname']);
        if (isset($siteProperties['name'])) {
            $nsite->setName($siteProperties['name']);
        }
        if (isset($siteProperties['subtitle'])) {
            $nsite->setSubtitle($siteProperties['subtitle']);
        }
        if (isset($siteProperties['description'])) {
            $nsite->setDescription($siteProperties['description']);
        }
        if (array_key_exists('private', $siteProperties)) {
            if ($siteProperties['private']) {
                $nsite->setPrivate(true);
            } else {
                $nsite->setPrivate(false);
            }
        }
        $nsite->setCustomDomain(null);
        $nsite->save();

        /* Site settings. */
        $settings = $site->getSettings();
        $settings->setNew(true);
        $settings->setSiteId($nsite->getSiteId());
        $settings->save();

        /* Now handle site owner. */
        $c = new Criteria();
        $c->add('site_id', $site->getSiteId());
        $c->add('founder', true);
        $owner = AdminPeer::instance()->selectOne($c);

        $this->owner = $owner;

        $admin = new Admin();
        $admin->setSiteId($nsite->getSiteId());
        $admin->setUserId($owner->getUserId());
        $admin->setFounder(true); // will be nonremovable
        $admin->save();
        $member = new Member();
        $member->setSiteId($nsite->getSiteId());
        $member->setUserId($owner->getUserId());
        $member->setDateJoined(new ODate());
        $member->save();

        /* Theme(s). */
        $c = new Criteria();
        $c->add('site_id', $site->getSiteId());
        $themes = ThemePeer::instance()->select($c);
        $themeMap = array();
        $nthemes = array();
        foreach ($themes as $theme) {
            $ntheme = clone($theme);
            $ntheme->setNew(true);
            $ntheme->setSiteId($nsite->getSiteId());
            $ntheme->setThemeId(null);
            $ntheme->save();
            $themeMap[$theme->getThemeId()] = $ntheme->getThemeId();
            $nthemes[] = $ntheme;
        }
        foreach ($nthemes as $ntheme) {
            if ($ntheme->getExtendsThemeId() && isset($themeMap[$ntheme->getExtendsThemeId()])) {
                $ntheme->setExtendsThemeId($themeMap[$ntheme->getExtendsThemeId()]);
                $ntheme->save();
            }
        }


        // get all categories from the site
        $categories = Category::getAll($site->getSiteId());
        foreach ($categories as $cat) {
            if (!in_array($cat->getName(), $this->excludedCategories)) {
                $ncategory = $this->duplicateCategory($cat, $nsite);
                /* Check if is using a custom theme. */
                if ($ncategory->getThemeId() && isset($themeMap[$ncategory->getThemeId()])) {
                    $ncategory->setThemeId($themeMap[$ncategory->getThemeId()]);
                    $ncategory->save();
                }
                if ($ncategory->getTemplateId()) {
                    $ncategory->setTemplateId($this->pageMap[$ncategory->getTemplateId()]);
                    $ncategory->save();
                }
            }
        }

        /* Recompile WHOLE site. */
        $od = new Outdater();
        $od->recompileWholeSite($nsite);

        /* Handle forum too. */

        $fs = $site->getForumSettings();
        if ($fs) {
            $fs->setNew(true);
            $fs->setSiteId($nsite->getSiteId());
            $fs->save();

            /* Copy existing structure. */
            $c = new Criteria();
            $c->add('site_id', $site->getSiteId());
            $groups = ForumGroupPeer::instance()->select($c);

            foreach ($groups as $group) {
                $ngroup = clone($group);
                $ngroup->setNew(true);
                $ngroup->setGroupId(null);
                $ngroup->setSiteId($nsite->getSiteId());
                $ngroup->save();

                $c = new Criteria();
                $c->add('group_id', $group->getGroupId());
                $categories = ForumCategoryPeer::instance()->select($c);
                foreach ($categories as $category) {
                    $ncategory = clone($category);
                    $ncategory->setNew(true);
                    $ncategory->setCategoryId(null);
                    $ncategory->setNumberPosts(0);
                    $ncategory->setNumberThreads(0);
                    $ncategory->setLastPostId(null);
                    $ncategory->setSiteId($nsite->getSiteId());
                    $ncategory->setGroupId($ngroup->getGroupId());
                    $ncategory->save();
                }
            }
        }

        /* Copy ALL files from the filesystem. */
        $srcDir = WIKIJUMP_ROOT."/web/files--sites/".$site->getSlug();
        $destDir = WIKIJUMP_ROOT."/web/files--sites/".$nsite->getSlug();

        $cmd = 'cp -r '. escapeshellarg($srcDir) . ' ' . escapeshellarg($destDir);
        exec($cmd);

        /* Copy file objects. */

        $c = new Criteria();
        $c->add('site_id', $site->getSiteId());
        $files = FilePeer::instance()->select($c);
        foreach ($files as $file) {
            $nfile = clone($file);
            $nfile->setSiteId($nsite->getSiteId());
            $nfile->setNew(true);
            $nfile->setFileId(null);
            $nfile->setSiteId($nsite->getSiteId());
            /* Map to a new page objects. */
            $pageId = $this->pageMap[$file->getPageId()];
            $nfile->setPageId($pageId);
            $nfile->save();
        }

        $db->commit();
        return $nsite;
    }

    /**
     * Duplicates the site by copying all the pages & categories & settings.
     */
    public function duplicateSite($site, $nsite)
    {
        $owner = $this->owner;
        // first copy settings

        // site_settings
        $settings = $site->getSettings();
        $settings->setNew(true);
        $settings->setSiteId($nsite->getSiteId());
        $settings->save();

        // add user as admin
        if ($owner) {
            $admin = new Admin();
            $admin->setSiteId($nsite->getSiteId());
            $admin->setUserId($owner->getUserId());
            $admin->setFounder(true); // will be nonremovable
            $admin->save();
            $member = new Member();
            $member->setSiteId($nsite->getSiteId());
            $member->setUserId($owner->getUserId());
            $member->setDateJoined(new ODate());
            $member->save();
        }

        // get all categories from the site
        $categories = Category::findAll($site->getSiteId());
        foreach ($categories as $cat) {
            if (!in_array($cat->getName(), $this->excludedCategories)) {
                $this->duplicateCategory($cat, $nsite);
            }
        }

        // recompile WHOLE site!!!
        $od = new Outdater();
        $od->recompileWholeSite($nsite);
    }

    public function setOwner($user)
    {
        $this->owner = $user;
    }

    public function duplicateCategory($category, $nsite)
    {
        $cat = clone ($category);
        $cat->setNew(true);
        $cat->setCategoryId(null);
        $cat->setSiteId($nsite->getSiteId());
        $cat->save();
        // copy pages
        $c = new Criteria();
        $c->add("category_id", $category->getCategoryId());
        $pages = [null]; // TODO run query
        foreach ($pages as $page) {
            $this->duplicatePage($page, $nsite, $cat);
        }
        return $cat;
    }

    public function duplicatePage($page, $nsite, $ncategory, $newUnixName = null)
    {

        if ($newUnixName == null) {
            $newUnixName = $page->slug;
        }

        // check if page exists - if so, forcibly delete!!!
        // Wait, why exactly are we deleting this?
        // I'm just going to comment this out for now, and eventually
        // this will go the way of the dodo when it's moved to DEEPWELL.

        /*
        $p = PagePeer::instance()->selectByName($nsite->getSiteId(), $newUnixName);
        if ($p) {
            PagePeer::instance()->deleteByPrimaryKey($p->getPageId());
        }
        */

        $owner = $this->owner;
        $now = new ODate();

        $rev = $page->getCurrentRevision();
        $nrev = new PageRevision();
        $nrev->setSiteId($nsite->getSiteId());
        $nrev->setMetadataId($nmeta->getMetadataId());
        $nrev->setFlagNew(true);
        $nrev->setDateLastEdited($now);
        $nrev->setUserId($owner->id);
        $nrev->setWikitextHash($rev->getWikitextHash());
        $nrev->setCompiledHash($rev->getCompiledHash());
        $nrev->setCompiledGenerator($rev->getCompiledGenerator());
        $nrev->obtainPK();

        $npage = new Page();
        $npage->setSiteId($nsite->getSiteId());
        $npage->setCategoryId($ncategory->getCategoryId());
        $npage->setRevisionId($nrev->getRevisionId());
        $npage->setMetadataId($nmeta->getMetadataId());
        $npage->setTitle($page->title);
        $npage->setUnixName($newUnixName);
        $npage->setDateLastEdited($now);
        $npage->setDateCreated($now);
        $npage->setLastEditUserId($owner->id);
        $npage->setOwnerUserId($owner->id);

        $tags = new Set(); // PagePeer::getTags($page->getPageId());
        $npage->setTagsArray($tags->toArray());

        $npage->save();
        $nrev->setPageId($npage->getPageId());
        $nrev->save();

        $this->pageMap[$page->getPageId()] = $npage->getPageId();
    }

    public function addExcludedCategory($categoryName)
    {
        $this->excludedCategories[] = $categoryName;
    }

    /**
     * Dumps everything.
     */
    public function dumpSite($site)
    {

        $dump = array();
        $settings = $site->getSettings();
        $fs = $site->getForumSettings();

        $dump['settings'] = $settings;
        $dump['forumSettings'] = $fs;
        $categories = Category::findAll($site->getSiteId());
        $dump['categories'] = $categories;
        $dump['pages'] = [];

        foreach ($categories as $cat) {
            $c = new Criteria();
            $c->add("category_id", $cat->getCategoryId());
            $pages = [null]; // TODO run query
            foreach ($pages as &$p) {
                $p->setTemp("source", $p->getSource());
                $p->setTemp("meta", $p->getMetadata());
            }
            $dump['pages'][$cat->getCategoryId()] = $pages;
        }

        return $dump;
    }

    public function restoreSite($nsite, $dump)
    {

        $settings = $dump['settings'];

        // site_settings
        $settings->setNew(true);
        $settings->setSiteId($nsite->getSiteId());
        $settings->save();

        $forumSettings = $dump['forumSettings'];
        $forumSettings->setNew(true);
        $forumSettings->setSiteId($nsite->getSiteId());
        $forumSettings->save();

        // add user as admin
        $owner = $this->owner;
        if ($owner) {
            $admin = new Admin();
            $admin->setSiteId($nsite->getSiteId());
            $admin->setUserId($owner->getUserId());
            $admin->setFounder(true); // will be nonremovable
            $admin->save();
            $member = new Member();
            $member->setSiteId($nsite->getSiteId());
            $member->setUserId($owner->getUserId());
            $member->setDateJoined(new ODate());
            $member->save();
        }

        $categories = $dump['categories'];

        foreach ($categories as $category) {
            $cat = clone ($category);
            $cat->setNew(true);
            $cat->setCategoryId(null);
            $cat->setSiteId($nsite->getSiteId());
            $cat->save();

            // get pages
            $pages = $dump['pages'][$category->getCategoryId()];

            foreach ($pages as $page) {
                $newUnixName = $page->slug;

                $now = new ODate();

                $rev = null; // TODO get latest revision for $page->getPageId()
                $nrev = new PageRevision();
                $nrev->setSiteId($nsite->getSiteId());
                $nrev->setMetadataId($nmeta->getMetadataId());
                $nrev->setFlagNew(true);
                $nrev->setDateLastEdited($now);
                $nrev->setUserId($owner->getUserId());
                $nrev->setWikitextHash($rev->getWikitextHash());
                $nrev->setCompiledHash($rev->getCompiledHash());
                $nrev->setCompiledGenerator($rev->getCompiledGenerator());
                $nrev->obtainPK();

                $npage = new Page();
                $npage->setSiteId($nsite->getSiteId());
                $npage->setCategoryId($cat->getCategoryId());
                $npage->setRevisionId($nrev->getRevisionId());
                $npage->setMetadataId($nmeta->getMetadataId());
                $npage->setTitle($page->title);
                $npage->setUnixName($newUnixName);
                $npage->setDateLastEdited($now);
                $npage->setLastEditUserId($owner->getUserId());
                $npage->setOwnerUserId($owner->getUserId());

                $npage->save();
                $nrev->setPageId($npage->getPageId());
                $nrev->save();
            }
        }

        $od = new Outdater();
        $od->recompileWholeSite($nsite);
    }
}
