<?php
/**
 * jtlwawi_connector/adminSession.php
 * AdminSession Verwaltung
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.0 / 16.06.06
*/

class AdminSession {
	// session-lifetime
	var $lifeTime;
	function open($savePath, $sessName) {
	   // get session-lifetime
	   $this->lifeTime = get_cfg_var("session.gc_maxlifetime");
	   // return success
	   if(!$GLOBALS["DB"]->DB_Connection)
	      return false;
	   return true;
	}
	function close() {
	   // mach nichts
	   return true;
	}
	function read($sessID) {
	   // fetch session-data
	   $res = mysql_query("SELECT cSessionData FROM eazysales_adminsession
	                       WHERE cSessionId = '$sessID'
	                       AND nSessionExpires > ".time(),$GLOBALS["DB"]->DB_Connection);
	   // return data or an empty string at failure
	   if($row = mysql_fetch_assoc($res))
	       return $row['cSessionData'];
	   return "";
	}
	function write($sessID,$sessData) {
	   // new session-expire-time
	   $newExp = time() + $this->lifeTime;
	   // is a session with this id in the database?
	   $res = mysql_query("SELECT * FROM eazysales_adminsession
	                       WHERE cSessionId = '$sessID'",$GLOBALS["DB"]->DB_Connection);
	   // if yes,
	   if(mysql_num_rows($res)) {
	       // ...update session-data
	       mysql_query("UPDATE eazysales_adminsession
	                     SET nSessionExpires = '$newExp',
	                     cSessionData = '$sessData'
	                     WHERE cSessionId = '$sessID'",$GLOBALS["DB"]->DB_Connection);
	       // if something happened, return true
	       if(mysql_affected_rows($GLOBALS["DB"]->DB_Connection))
	           return true;
	   }
	   // if no session-data was found,
	   else {
	       // create a new row
	       mysql_query("INSERT INTO eazysales_adminsession (
	                     cSessionId,
	                     nSessionExpires,
	                     cSessionData)
	                     VALUES(
	                     '$sessID',
	                     '$newExp',
	                     '$sessData')",$GLOBALS["DB"]->DB_Connection);
	       // if row was created, return true
	       if(mysql_affected_rows($GLOBALS["DB"]->DB_Connection))
	           return true;
	   }
	   // an unknown error occured
	   return false;
	}
	function destroy($sessID) {
	   // delete session-data
	   mysql_query("DELETE FROM eazysales_adminsession WHERE cSessionId = '$sessID'",$GLOBALS["DB"]->DB_Connection);
	   // if session was deleted, return true,
	   if(mysql_affected_rows($GLOBALS["DB"]->DB_Connection))
	       return true;
	   // ...else return false
	   return false;
	}
	function gc($sessMaxLifeTime) {
	   // delete old sessions
	   mysql_query("DELETE FROM eazysales_adminsession WHERE nSessionExpires < ".time(),$GLOBALS["DB"]->DB_Connection);
	   // return affected rows
	   return mysql_affected_rows($GLOBALS["DB"]->DB_Connection);
	}

	function AdminSession() {
		session_name("eSConnectorAdm");
		session_start();
	}
}