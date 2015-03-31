<?php
	session_start();
	if( isset($_REQUEST["logout"]) && $_REQUEST["logout"]){
		unset($_SESSION["user"]);
		unset($_SESSION["permitted"]);
		unset($_SESSION["password"]);
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>PrintStats</title>
	<link rel="stylesheet" type="text/css" href="printStat.css" />
<!-- 	<link rel="stylesheet" type="text/css" href="jquery.dataTables.css" />
	<link rel="stylesheet" type="text/css" href="jquery.dataTables_themeroller.css" /> -->
</head>
<body>
	<div id="main">
		<div id="head">

		</div>
		<div id="body">

		</div>
	</div>

	<script type="text/template" id="tpl-costcenter">
		<table>
			<thead>
				<tr>
					<th>Tubit Username</th>
					<th>Name lt. LDAP</th>
					<th>Zugeordnete Kostenstelle</th>
					<th>Prints</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</script>

	<script type="text/template" id="tpl-costcenterRow">
		<tr <% if(_.isNull(costcenter)){%> class="noCostcenter" <%} %>>
			<td><%= username %></td>
			<td><%= displayname %></td>
			<td><%= costcenterSelect({
				costcenterData: statisticsApp.costcenterData,
				activeCC: costcenter,
				username: username
			}) %></td>
			<!--<td>
				<%= groupSelect({
					groups: groups
					})
					%>
			</td>-->
			<td>
				<%= prints %>
			</td>
		</tr>
	</script>

	<script type="text/template" id="tpl-groupSelect">
		<select name="groups" size="1">
			<% _.each(groups, function(group){ %>

				<option><%= group %></option>

			<% }) %>
		</select>
	</script>

	<script type="text/template" id="tpl-costcenterSelect">
		<select name="costcenter" id="<%= username %>" size="1">
			<% costcenterData.each(function(cc){%>

			<option value="<%= cc.get("costcenter") %>" <% if(cc.get("costcenter") == activeCC){%> selected <% }%> ><%= cc.get("cc_name") %></option>

			<%}) %>
		</select>
	</script>

	<script type="text/template" id="tpl-filterbar">
		<div class="main">
			<h3>Optionen</h3>
			<a href="?logout=true">LogOut</a>
			<a href="#">Druckstatistiken</a>
			<a href="#costcenter">Kostenstellenverwaltung</a>
		</div>

		<div class="filterBar">
			<h3>Filter</h3>
			<div class="filterElement">
				<label for="printer">Drucker</label>
				<select size="1" name="printer">

						<option>Alle</option>

					<% _.each(printers, function(printer){ %>

						<option><%= printer %></option>

					<% }); %>

				</select>
			</div>


			<!-- 			<select size="1" name="costcenter">

				<option>Alle</option>

				<% _.each(costcenter.models, function(cc){ %>

					<option value="<%= cc.get("costcenter") %>"><%= cc.get("cc_short") %></option>

				<% }); %>


			</select> -->

			<div class="filterElement">
				<label for="year">Jahr</label>
				<select name="year" size="1">

					<% _.each(years, function(year){ %>

						<option><%= year %></option>

					<% }); %>

				</select>
			</div>


			<div class="filterElement">
				<label for="month">Monat</label>
				<select name="month" size="1">
					<option>Alle</option>

					<% _.each([1,2,3,4,5,6,7,8,9,10,11,12], function(i){ %>

						<option><%= i %></option>

					<% }) %>
				</select>
			</div>

			<div class="filterElement">
				<label for="filterMode">Gesamt</label>
				<input type="radio" name="filterMode" value="global">
			</div>

			<div class="filterElement">
				<label for="filterMode">pro Kostenstelle</label>
				<input type="radio" name="filterMode" value="perUnit" checked="checked">
			</div>


			<!-- <input type="checkbox" name="perCC" checked="checked" /> Pro Kostenstelle -->
		</div>


	</script>

<?php

// Wenn Session ok ist

// $_SESSION["foo"] = "bar";
// print_r($_SESSION);

// session_cache_limiter('private');
// $cache_limiter = session_cache_limiter();

// session_cache_expire(1);
// print_r(session_cache_expire());







function checkLdapLogin($user, $password){

	require_once("ldapConfig.php");



	$ldap = ldap_connect($ldapserver, $ldapport) or die("No connection");
	if($ldap){
		$ldapbind = ldap_bind($ldap, $user."@".$ldapserver, $password) or die("Wrong Wrong!!!");
	}
	if(!$ldapbind){
		?>
			Wrong User credentials
		<?php
	}

	$userdata = getRawUserData($user, $ldap);
	$groups = getGroups($userdata);

	if(in_array("CN=soz_printerstatistics,OU=Teams,OU=Groups,DC=win,DC=tu-berlin,DC=de", $groups)){
		return true;
	}else{
		return false;
	}

}

if(isset($_SESSION["permitted"]) && $_SESSION["permitted"] ){

?>

	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.load('visualization', '1', {packages: ['corechart', 'table']});
	</script>
	<script type="application/javascript" src="//code.jquery.com/jquery-2.0.3.min.js"></script>
	<script type="application/javascript" src="underscore-min.js"></script>
	<!-- <script type="application/javascript" src="jquery.dataTables.min.js"></script> -->
	<script type="application/javascript" src="backbone.js"></script>
	<script type="application/javascript" src="printStat.js"></script>

<?php

	}else{
		// Gucke ob Logindaten da sind
		if(isset($_REQUEST["user"]) && isset($_REQUEST["password"])){
			// print_r("Heree I Am");
			$_SESSION["user"] = $_REQUEST["user"];
			$_SESSION["password"] = $_REQUEST["password"];

			if(checkLdapLogin($_SESSION["user"], $_SESSION["password"])){
				$_SESSION["permitted"] = true;
				?>
					<script type="text/javascript">
						location.reload();
					</script>
				<?php
			}else{
				$_SESSION["permitted"] = false;
			}

		}

		// Wenn session nicht ok
?>
	<h2>Druckerstatistiken des Instituts</h2>
	<div>
		Bitte mit TUBIT-Logindaten anmelden!
	</div>
	<div>
		<form method="POST" action=".">
			<input type="text" name="user" placeholder="Username">
			<input type="password" name="password" placeholder="Password">
			<input type="submit" value="Login">
		</form>
	</div>

<?php
		// End if
	}
?>

</body>
</html>
