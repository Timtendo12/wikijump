<?php

namespace Wikidot\DB;

use Ozone\Framework\Database\BaseDBObject;

/**
 * Base Class mapped to the database table page_revision.
 */
class PageRevisionBase extends BaseDBObject
{

    protected function internalInit()
    {
        $this->tableName='page_revision';
        $this->peerName = 'Wikidot\\DB\\PageRevisionPeer';
        $this->primaryKeyName = 'revision_id';
        $this->fieldNames = ['revision_id' ,  'page_id' ,  'site_id' ,  'metadata_id' ,  'flags' ,  'flag_text' ,  'flag_title' ,  'flag_file' ,  'flag_rename' ,  'flag_meta' ,  'flag_new' ,   'revision_number' ,  'date_last_edited' ,  'user_id' ,  'user_string' ,  'comments', 'wikitext_hash', 'compiled_hash', 'compiled_generator'];
    }


    public function getRevisionId()
    {
        return $this->getFieldValue('revision_id');
    }

    public function setRevisionId($v1, $raw = false)
    {
        $this->setFieldValue('revision_id', $v1, $raw);
    }


    public function getPageId()
    {
        return $this->getFieldValue('page_id');
    }

    public function setPageId($v1, $raw = false)
    {
        $this->setFieldValue('page_id', $v1, $raw);
    }


    public function getSiteId()
    {
        return $this->getFieldValue('site_id');
    }

    public function setSiteId($v1, $raw = false)
    {
        $this->setFieldValue('site_id', $v1, $raw);
    }


    public function getMetadataId()
    {
        return $this->getFieldValue('metadata_id');
    }

    public function setMetadataId($v1, $raw = false)
    {
        $this->setFieldValue('metadata_id', $v1, $raw);
    }


    public function getFlags()
    {
        return $this->getFieldValue('flags');
    }

    public function setFlags($v1, $raw = false)
    {
        $this->setFieldValue('flags', $v1, $raw);
    }


    public function getFlagText()
    {
        return $this->getFieldValue('flag_text');
    }

    public function setFlagText($v1, $raw = false)
    {
        $this->setFieldValue('flag_text', $v1, $raw);
    }


    public function getFlagTitle()
    {
        return $this->getFieldValue('flag_title');
    }

    public function setFlagTitle($v1, $raw = false)
    {
        $this->setFieldValue('flag_title', $v1, $raw);
    }


    public function getFlagFile()
    {
        return $this->getFieldValue('flag_file');
    }

    public function setFlagFile($v1, $raw = false)
    {
        $this->setFieldValue('flag_file', $v1, $raw);
    }


    public function getFlagRename()
    {
        return $this->getFieldValue('flag_rename');
    }

    public function setFlagRename($v1, $raw = false)
    {
        $this->setFieldValue('flag_rename', $v1, $raw);
    }


    public function getFlagMeta()
    {
        return $this->getFieldValue('flag_meta');
    }

    public function setFlagMeta($v1, $raw = false)
    {
        $this->setFieldValue('flag_meta', $v1, $raw);
    }


    public function getFlagNew()
    {
        return $this->getFieldValue('flag_new');
    }

    public function setFlagNew($v1, $raw = false)
    {
        $this->setFieldValue('flag_new', $v1, $raw);
    }


    public function getRevisionNumber()
    {
        return $this->getFieldValue('revision_number');
    }

    public function setRevisionNumber($v1, $raw = false)
    {
        $this->setFieldValue('revision_number', $v1, $raw);
    }


    public function getDateLastEdited()
    {
        return $this->getFieldValue('date_last_edited');
    }

    public function getDateLastEditedTS()
    {
        $odate = $this->getFieldValue('date_last_edited');
        return $odate->getTimestamp();
    }

    public function setDateLastEdited($v1, $raw = false)
    {
        $this->setFieldValue('date_last_edited', $v1, $raw);
    }


    public function getUserId()
    {
        return $this->getFieldValue('user_id');
    }

    public function setUserId($v1, $raw = false)
    {
        $this->setFieldValue('user_id', $v1, $raw);
    }


    public function getUserString()
    {
        return $this->getFieldValue('user_string');
    }

    public function setUserString($v1, $raw = false)
    {
        $this->setFieldValue('user_string', $v1, $raw);
    }


    public function getComments()
    {
        return $this->getFieldValue('comments');
    }

    public function setComments($v1, $raw = false)
    {
        $this->setFieldValue('comments', $v1, $raw);
    }


    // NOTE: returns hex
    public function getWikitextHash(): string
    {
        return substr($this->getFieldValue('wikitext_hash'), 2);
    }

    // NOTE: expects hex
    public function setWikitextHash($v1, $raw = false): void
    {
        $this->setFieldValue('wikitext_hash', hex2bin($v1), $raw);
    }


    // NOTE: returns hex
    public function getCompiledHash(): string
    {
        return substr($this->getFieldValue('compiled_hash'), 2);
    }

    // NOTE: expects hex
    public function setCompiledHash($v1, $raw = false): void
    {
        $this->setFieldValue('compiled_hash', hex2bin($v1), $raw);
    }


    public function getCompiledGenerator(): string
    {
        return $this->getFieldValue('compiled_generator');
    }

    public function setCompiledGenerator($v1, $raw = false): void
    {
        $this->setFieldValue('compiled_generator', $v1, $raw);
    }
}
