<?php

Class DB{

	function DB(){
		require("config.php");
		$this->dateFormat = "Y-m-d H:i:s";
		$this->db = new PDO($dsn, $user, $password);
		$this->checkStmt = $this->db->prepare("SELECT pages FROM data WHERE jobid=:jobid AND printer=:printer");
		$this->insertStmt = $this->db->prepare("INSERT INTO data(jobid, printer, date, user, pages, costcenter) VALUES(:jobid, :printer, :date, :user, :pages, :costcenter)");
		$this->getStmt = $this->db->prepare("SELECT * FROM data WHERE date >= :startDate AND date <= :endDate");
		$this->user2cc = $this->db->prepare("SELECT * FROM user2cc");
		$this->user2ccByUser = $this->db->prepare(" SELECT costcenter FROM user2cc WHERE username = :user ");
		$this->costcenter = $this->db->prepare("SELECT * FROM costcenter");
		$this->getUserPrintJobsWithoutCCString = $this->db->prepare("SELECT uid FROM data WHERE (costcenter IS NULL OR costcenter = 0 ) AND user = :user");
	}

	function getUserPrintJobsWithoutCC($user = null){
		if(is_null($user)){
			return array();
		}

		$this->getUserPrintJobsWithoutCCString->execute(array(
			":user" => $user
		));

		$uidList = array();

		foreach($this->getUserPrintJobsWithoutCCString->fetchAll(PDO::FETCH_ASSOC) as $uid){
				$uidList[] = $uid["uid"];
		}

		return $uidList;
	}

	function pdo_sql_debug($string,$data) {
	    $indexed=$data==array_values($data);
	    foreach($data as $k=>$v) {
	        if(is_string($v)) $v="'$v'";
	        if($indexed) $string=preg_replace('/\?/',$v,$string,1);
	        else $string=str_replace(":$k",$v,$string);
	    }
	    return $string;
	}

	function getAll($timeStart = 1, $timeEnd = null){
		if(!$timeEnd)
			$timeEnd = time();

		$dateStart = new DateTime();
		$dateStart->setTimestamp($timeStart);

		$dateEnd = new DateTime();
		$dateEnd->setTimestamp($timeEnd);

		$data = array(
			'startDate' => $dateStart->format($this->dateFormat),
			'endDate' => $dateEnd->format($this->dateFormat)
		);

		$this->getStmt->execute($data);

		$rows = $this->getStmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;

	}

	function insert($jobid = null, $printer = null, $date = null, $user = null, $pages = null, $costcenter = null){
		if(is_null($jobid) || is_null($printer) || is_null($date) || is_null($user) || is_null($pages))
			echo "Wrong Parameters";
		
		$this->checkStmt->execute(array(
			':jobid' => $jobid,
			':printer' => $printer
		));

		$rows = $this->checkStmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($rows) == 0){
			$this->insertStmt->execute(array(
				':jobid' => $jobid, 
				':printer' => $printer,
				':date' => $date,
				':user' => $user,
				':pages' => $pages,
				':costcenter' => $costcenter
			));	
		}else{
			print_r($rows);
		}
	}

	function getCostcenter(){
		$this->costcenter->execute();
		$rows = $this->costcenter->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}

	function getUser2CCbyUser($user = null){
		if(is_null($user)){
			return null; 
		}

		$this->user2ccByUser->execute(array(
			':user' => $user
		));

		$rows = $this->user2ccByUser->fetchAll(PDO::FETCH_ASSOC);
		if(count($rows) > 1){
			return null;
		}

		return $rows[0]["costcenter"];
	}

	function insertUser2CC($user, $costcenter){
		
		$costcenterDB = $this->getUser2CCbyUser($user);

		$data = array(
			":user" => $user,
			":costcenter" => $costcenter
		);

		if( is_null($costcenterDB) ){
			// neuen Eintrag anlegen
			$stmt = $this->db->prepare("INSERT INTO user2cc(username, costcenter) VALUES(:user, :costcenter)");
			return $stmt->execute($data);
		}else if( $costcenter != $costcenterDB ){
			// Eintrag ändern
			$stmt = $this->db->prepare("UPDATE user2cc SET costcenter = :costcenter WHERE username = :user;");
			return $stmt->execute($data);
		}

		return false;
	}

	function updatePrintLog($uidList, $costcenter){
		
		$data = array(
			":costcenter" => $costcenter,
			// ":uidList" => $uidList
		);

		// var_dump($data);

		$stmt = $this->db->prepare("UPDATE data SET costcenter = :costcenter WHERE uid IN(".$uidList.")");
		return $stmt->execute($data);
	}

	function getUser2CC(){
		$this->user2cc->execute();
		$rows = $this->user2cc->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
}

