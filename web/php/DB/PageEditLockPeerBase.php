<?php

namespace Wikidot\DB;




use Ozone\Framework\Database\BaseDBPeer;

/**
 * Base peer Class mapped to the database table page_edit_lock.
 */
class PageEditLockPeerBase extends BaseDBPeer
{
    public static $peerInstance;

    protected function internalInit()
    {
        $this->tableName='page_edit_lock';
        $this->objectName='Wikidot\\DB\\PageEditLock';
        $this->primaryKeyName = 'lock_id';
        $this->fieldNames = array( 'lock_id' ,  'page_id' ,  'page_unix_name' ,  'site_id' ,  'user_id' ,  'user_string' ,  'session_id' ,  'date_started' ,  'date_last_accessed' ,  'secret' );
        $this->fieldTypes = array( 'lock_id' => 'serial',  'page_id' => 'int',   'page_unix_name' => 'varchar(100)',  'site_id' => 'int',  'user_id' => 'int',  'user_string' => 'varchar(80)',  'session_id' => 'varchar(60)',  'date_started' => 'timestamp',  'date_last_accessed' => 'timestamp',  'secret' => 'varchar(100)');
    }

    public static function instance()
    {
        if (self::$peerInstance == null) {
            $className = 'Wikidot\\DB\\PageEditLockPeer';
            self::$peerInstance = new $className();
        }
        return self::$peerInstance;
    }
}
