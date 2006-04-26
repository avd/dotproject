<?php /* ADMIN $Id$ */
/**
* User Class
*/
class CUser extends CDpObject {
	var $user_id = NULL;
	var $user_username = NULL;
	var $user_password = NULL;
	var $user_parent = NULL;
	var $user_type = NULL;
	var $user_contact = NULL;
	var $user_signature = NULL;

	function CUser() {
		$this->CDpObject( 'users', 'user_id' );
		$this->_tbl_name = 'user_username';
		$this->search_fields = array ('user_username', 'user_signature');
	}

	function check() {
		if ($this->user_id === NULL) {
			return 'user id is NULL';
		}
		if ($this->user_password !== NULL) {
			$this->user_password = db_escape( trim( $this->user_password ) );
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
		$msg = $this->check();
		if( $msg ) {
			return get_class( $this )."::store-check failed";
		}
		$q  = new DBQuery;
		if( $this->user_id ) {
		// save the old password
			$perm_func = 'updateLogin';
			$q->addTable('users');
			$q->addQuery('user_password');
			$q->addWhere('user_id = ' . $this->user_id);
			$pwd = $q->loadResult();
			if ($pwd != $this->user_password) {
				$this->user_password = md5($this->user_password);
			} else {
				$this->user_password = null;
			}

			$ret = db_updateObject( 'users', $this, 'user_id', false );
		} else {
			$perm_func = 'addLogin';
			$this->user_password = md5($this->user_password);
			$ret = db_insertObject( 'users', $this, 'user_id' );
		}
		if( !$ret ) {
			return get_class( $this ).'::store failed <br />' . db_error();
		} else {
			$acl =& $GLOBALS['AppUI']->acl();
			$acl->$perm_func($this->user_id, $this->user_username);
			//Insert Default Preferences
			//Lets check if the user has already default users preferences set, if not insert the default ones
			$q->addTable('user_preferences', 'upr');
			$q->addWhere('upr.pref_user = ' . $this->user_id);
			$uprefs = $q->loadList();
			$q->clear();
			if (!count($uprefs) && $this->user_id > 0) {
				//Lets get the default users preferences
				$q->addTable('user_preferences', 'dup');
				$q->addWhere('dup.pref_user = 0');
				$dprefs = $q->loadList();
				$q->clear();
				
				foreach ($dprefs as $dprefskey => $dprefsvalue) {
					$q->addTable('user_preferences', 'up');
					$q->addInsert('pref_user', $this->user_id);
					$q->addInsert('pref_name', $dprefsvalue['pref_name']);
					$q->addInsert('pref_value', $dprefsvalue['pref_value']);
					$q->exec();
					$q->clear();
				}
			}

			return NULL;
		}
	}

	function delete( $oid = NULL ) {
		$id = $this->user_id;
		$result = parent::delete($oid);
		if (! $result) {
			$acl =& $GLOBALS['AppUI']->acl();
			$acl->deleteLogin($id);
			$q  = new DBQuery;
			$q->setDelete('user_preferences');
			$q->addWhere('pref_user = '.$this->user_id);
			$q->exec();
			$q->clear();
		}
		return $result;
 	}
}
?>
