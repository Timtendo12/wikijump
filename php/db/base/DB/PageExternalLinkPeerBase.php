<?php
/**
 * Wikidot - free wiki collaboration software
 * Copyright (c) 2008, Wikidot Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * For more information about licensing visit:
 * http://www.wikidot.org/license
 *
 * @category Wikidot
 * @package Wikidot
 * @version \$Id\$
 * @copyright Copyright (c) 2008, Wikidot Inc.
 * @license http://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License
 */

namespace DB;

use BaseDBPeer;




/**
 * Base peer class mapped to the database table page_external_link.
 */
class PageExternalLinkPeerBase extends BaseDBPeer {
	public static $peerInstance;

	protected function internalInit(){
		$this->tableName='page_external_link';
		$this->objectName='DB\\PageExternalLink';
		$this->primaryKeyName = 'link_id';
		$this->fieldNames = array( 'link_id' ,  'site_id' ,  'page_id' ,  'to_url' ,  'pinged' ,  'ping_status' ,  'date' );
		$this->fieldTypes = array( 'link_id' => 'serial',  'site_id' => 'int',  'page_id' => 'int',  'to_url' => 'varchar(512)',  'pinged' => 'boolean',  'ping_status' => 'varchar(256)',  'date' => 'timestamp');
		$this->defaultValues = array( 'pinged' => 'false');
	}

	public static function instance(){
		if(self::$peerInstance == null){
			$className = "DB\\PageExternalLinkPeer";
			self::$peerInstance = new $className();
		}
		return self::$peerInstance;
	}

}