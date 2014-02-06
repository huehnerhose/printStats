<?php

Class DB{

	function DB(){
		require("config.php");
		$this->dateFormat = "Y-m-d H:i:s";
		$this->db = new PDO($dsn, $user, $password);
		$this->checkStmt = $this->db->prepare("SELECT pages FROM data WHERE jobid=:jobid AND printer=:printer");
		$this->insertStmt = $this->db->prepare("INSERT INTO data(jobid, printer, date, user, pages) VALUES(:jobid, :printer, :date, :user, :pages)");
		$this->getStmt = $this->db->prepare("SELECT * FROM data WHERE date >= :startDate AND date <= :endDate");
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

		$rows = $this->getStmt->fetchAll();
		return $rows;

	}

	function insert($jobid = null, $printer = null, $date = null, $user = null, $pages = null){
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
				':pages' => $pages
			));	
		}else{
			print_r($rows);
		}
	}
}

