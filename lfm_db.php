<?php
/************************************************************
*===========================================================*
*        - cmik.fm -                                        *
*===========================================================*
*************************************************************
*
* Copyright 2012, Tihomir Kit (kittihomir@gmail.com)
* spilp is distributed under the terms of GNU General Public License v3
* A copy of GNU GPL v3 license can be found in LICENSE.txt or
* at http://www.gnu.org/licenses/gpl-3.0.html
*
************************************************************/

class LfmDb {
	private $db_connection;


	// set the db connection up
	function __construct() {
		require 'credentials.php';
		
		$this->db_connection = mysql_connect("localhost", $db_username, $db_password);
		if (!$this->db_connection) {
			die('Could not connect: ' . mysql_error());
		}
		
		mysql_select_db($db_name, $this->db_connection);
		//createTable();
		//dropTable();
	}



	// send email notification whenever someone uses cmik.fm
	private function SendEmail($hof_message) {
		$to = "kittihomir@gmail.com";
		$subject = ""
			. $_SESSION['username'] . " ("
			. $_SESSION['amount'] . " - "
			. $_SESSION['rank'] . "/"
			. $_SESSION['score_avg'] . "/"
			. $hof_message . ") used cmik.fm";
		$message = "Link: http://www.last.fm/user/" . $_SESSION['username'];
		$from = "pootzko@cmikavac.net";
		$headers = "From:" . $from;
		
		mail($to, $subject, $message, $headers);
	}



	// drop old table and create a new empty one
	private function DropTable() {
		$sql = "DROP TABLE hof";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. Table not dropped.";
		else
			$this->CreateTable();
	}



	// create a new empty hof table
	private function CreateTable() {
		$sql = "
			CREATE TABLE hof (
				id int(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				username varchar(20) NOT NULL,
				amount int(4) NOT NULL,
				coefficient float(8) NOT NULL,
				timestamp varchar(12) NOT NULL
			)
		";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. Table not created.";
	}



	// insert new user data into the database
	private function InsertData($score_avg) {
		$username = mysql_real_escape_string($_SESSION['username']);
		$amount = mysql_real_escape_string($_SESSION['safeamount']);
		$score_avg = mysql_real_escape_string($score_avg);
		
		$sql = "
			INSERT INTO hof (username, amount, coefficient, timestamp)
			VALUES ('" . $username . "', '" . $amount . "', '" . $score_avg . "', '" . time() . "')
		";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. Data not inserted.";
	}



	// update old user database data 
	private function UpdateData($id, $score_avg) {
		$amount = mysql_real_escape_string($_SESSION['safeamount']);
		$score_avg = mysql_real_escape_string($score_avg);
		
		$sql = "
			UPDATE hof SET
				amount = '" . $amount . "',
				coefficient = '" . $score_avg . "',
				timestamp = '" . time() . "'
			WHERE id = '" . $id . "'
		";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. Data not updated.";
	}



	// check if user is already in database
	private function FindUser($data, $condition, $column) {
		$data = mysql_real_escape_string($data);
		$condition = mysql_real_escape_string($condition);
		$column = mysql_real_escape_string($column);
		
		$sql = "SELECT " . $data . " FROM hof WHERE " . $condition . " = '" . $column . "'";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. No data found.";
		else
			return $query;
	}



	// select users data for HoF tables
	private function SelectData($method) {
		/*if ($method == "getTop")
			$sql = "SELECT * FROM hof WHERE amount=50 ORDER BY coefficient DESC LIMIT 50";
		if ($method == "getLowest")
			$sql = "SELECT * FROM (SELECT * FROM hof WHERE amount=50 ORDER BY coefficient ASC LIMIT 50) AS temptable ORDER BY coefficient DESC";*/
		
		// data for Top 50 table
		if ($method == "GetTop")
			$sql = "SELECT * FROM hof ORDER BY coefficient DESC LIMIT 50";
		// data for Lowest 50 table
		if ($method == "GetLowest")
			$sql = "SELECT * FROM (SELECT * FROM hof ORDER BY coefficient ASC LIMIT 50) AS temptable ORDER BY coefficient DESC";
		$query = mysql_query($sql, $this->db_connection);
		
		if (!$query)
			echo "Error. No data selected.";
		else
			return $query;
	}



	// determine whether to store new user data, update an old one or do nothing
	public function StoreData($score_avg) {
		// ignore users with score 0
		if ($score_avg != 0) {
			$_SESSION['score_avg'] = $score_avg;
			
			$id_result = $this->FindUser("id", "username", $_SESSION['username']);
			
			// if user is not in the database
			if (!mysql_fetch_assoc($id_result))
				$this->InsertData($score_avg);
			// if user is in the database
			else {
				$id = mysql_result($id_result, 0);
				$amount_result = $this->FindUser("amount", "id", $id);
				$amount = mysql_result($amount_result, 0);
				
				// since higher artist amount means more precise results, ignore
				// resubmitting results for amounts lower than already submitted
				if ($amount <= $_SESSION['safeamount'])
					$this->UpdateData($id, $score_avg);
			}
		}
	}



	// get users current HoF rank
	public function GetUserHofRank() {
		$user = mysql_real_escape_string($_SESSION['username']);
		
		// gets current rank (in case user is not in top/low 50, he can see his rank)
		$sql = "SELECT count(*) FROM hof WHERE coefficient > (
			SELECT coefficient FROM hof WHERE username='" . $user . "'
		)";
		$query = mysql_query($sql, $this->db_connection);
		$row_count = mysql_fetch_row($query);
		$_SESSION['rank'] = $row_count[0] + 1;
		
		
		// gets users db_amount
		$sql = "SELECT amount FROM hof WHERE username='" . $user . "'";
		$query = mysql_query($sql, $this->db_connection);
		$row_amount = mysql_fetch_row($query);
		
		
		if (!$query)
			return "error";
		// if recorded amount (the one from the database) is higher than the current one
		elseif ($_SESSION['amount'] < $row_amount[0]) {
			$hof_score_msg = $_SESSION['username'] . " (old rank <b>" . $_SESSION['rank'] . "</b>) did not get it into HoF this time. (explanation below HoF tables)</br><a href='index.php'>Try again</a> with different amount or a different username?";
			
			// send an email notification (N for Negative - user did not get it into HoF)
			$this->SendEmail("N");
			
			return $hof_score_msg;
		}
		// output if users score got into HoF
		else {
			$hof_score_msg = $_SESSION['username'] . "'s rank in HoF (with amount " . $_SESSION['amount'] . ") is <b>" . $_SESSION['rank'] . ".</b></br><a href='index.php'>Try again</a> with a different amount or a different username?";
			
			// send an email notification (P for Positive - users score recorded in HoF)
			$this->SendEmail("P");
			
			return $hof_score_msg;
		}
	}



	// generate HoF table output data
	public function PrepareTables($method) {
		$data = $this->SelectData($method, $this->db_connection);
		
		// determine needed data range
		if ($method == "GetTop")
			$i = 1;
		if ($method == "GetLowest")
			$i = mysql_num_rows(mysql_query("SELECT * FROM hof", $this->db_connection)) - 49;
		
		$table_data = "";
		
		// generate table 
		while ($row = mysql_fetch_assoc($data)) {
			$user = $row["username"];
			$user_link = "<a href='http://www.last.fm/user/" . $user . "'  target='_blank'>" . $user . "</a>";
			$table_data .= "
				<tr>
					<td class='column1'>" . $i . ". " . $user_link . "</td>
					<td class='column2'>" . $row["coefficient"] . "</td>
					<td class='column3'>" . $row["amount"] . "</td>
				</tr>
			";
			$i++;
		}
		
		return $table_data;
	}
}
?>