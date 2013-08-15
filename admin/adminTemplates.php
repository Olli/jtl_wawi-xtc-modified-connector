<?php
/**
 * jtlwawi_connector/adminTemplates.php
 * AdminLogin fÃÂ¼r JTL-Wawi Connector
 * 
 * Es gelten die Nutzungs- und Lizenzhinweise unter http://www.jtl-software.de/jtlwawi.php
 * 
 * @author JTL-Software <thomas@jtl-software.de>
 * @copyright 2006, JTL-Software
 * @link http://jtl-software.de/jtlwawi.php
 * @version v1.01 / 09.11.06
*/

function zeigeKopf()
{
	echo('
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=iso-8559-1">
		<meta http-equiv="language" content="deutsch, de">
		<meta name="author" content="JTL-Software, www.jtl-software.de">
		<META NAME="ROBOTS" CONTENT="NOARCHIVE">
		<META NAME="ROBOTS" CONTENT="NOFOLLOW">
		<META NAME="ROBOTS" CONTENT="NOINDEX">
		<title>JTL-Wawi Connector Konfiguration</title>
		<link rel="stylesheet" type="text/css" href="jtlwawiConnectorAdmin.css">
	</head>
	<body>
	<center>
	<table cellspacing="0" cellpadding="0" width="770">
		<tr>
			<td><img src="../gfx/jtlwawi_Connector_head_XTC.jpg"></td>
		</tr>
		<tr>
			<td valign="top">
				<table cellspacing="0" cellpadding="0" width="100%">
					<tr>

	');
}

function zeigeFuss()
{
	echo('
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td bgcolor="#542A11" height="48" align="center"><a href="http://www.jtl-software.de"><span class="small" style="color:#ffffff">&copy; 2004-2006 JTL-Software</span></a></td>
		</tr>
	</table>
	</center>
	</body>
</html>
	');
}

function zeigeLogin()
{
	echo('
						<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px; border-left-width:0px;" valign="top" align="center"><br>
							<table cellspacing="0" cellpadding="0" width="96%">
								<tr><td class="content_header" align="center"><h3>Admin-Login</h3></td></tr>
								<tr><td class="content" align="center"><br>
									Bitte loggen Sie sich als Admin ein. Es gelten die Zugangsdaten fÃÂ¼r den bestehenden Administrationsbereich des Shops.
									<form name="login" method="post" action="index.php">
									<input type="hidden" name="adminlogin" value="1">
									<table cellspacing="0" cellpadding="10" width="300" style="border-width:1px;border-color:#222222;border-style:solid;">
										<tr>
											<td><b>e-Mailadresse</b></td><td><input type="text" name="benutzer" size="40" class="login"></td>
										</tr>
										<tr>

											<td><b>Passwort</b></td><td><input type="password" name="passwort" size="40" class="login"></td>
										</tr>
									</table><br><br>
										<input type="submit" value="JTL-Wawi Connector Login">
										<br><br><br>
									</form>
								</td></tr>
							</table><br>
						</td>
	');
}

function zeigeLoginBereich()
{
	echo('
						<td bgcolor="#ffffff" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px; border-left-width:0px;" valign="top" align="center" height="400"><br>
							<table cellspacing="0" cellpadding="0" width="96%">
								<tr><td class="content_header" align="center"><h3>Willkommen im Konfigurationsbereich vom JTL-Wawi Connector</h3></td></tr>
								<tr><td class="content" align="center"><br>
										Sie haben sich erfolgreich eingeloggt.<br>
										Bitte benutzen Sie das MenÃÂ¼ links zur Navigation.<br><br>
								</td></tr>
							</table><br>
						</td>
	');
}

function zeigeLinks($loggedIn)
{
	if ($loggedIn==1)
	{
		echo('
							<td width="140" bgcolor="#FAFAFA" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px;" valign="top"><br>
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr><td class="oberlink_gewaehlt" style="padding-left:5px;">JTL-Wawi Connector<br></td></tr>
										<tr><td class="unterlink"><a class="innen_menu" href="konfiguration.php?'.SID.'">Konfiguration</a><br></td></tr>
										<tr><td class="unterlink"><a class="innen_menu" href="bildexport.php?'.SID.'">Bildexport</a><br></td></tr>
		');

		switch(date(w))
		{
			case 0:$tag="Sonntag";break;
			case 1:$tag="Montag";break;
			case 2:$tag="Dienstag";break;
			case 3:$tag="Mittwoch";break;
			case 4:$tag="Donnerstag";break;
			case 5:$tag="Freitag";break;
			case 6:$tag="Samstag";break;

		}

		echo('
										<tr><td><br><br><br></td></tr>
										<tr><td class="user"><span class="small">&nbsp;'.$tag.', '.date("d.m.y H:i").'</span></td></tr>
		');
		echo('
								</table>
							</td>
		');
	}
	else
	{
		echo('
							<td width="140" bgcolor="#FAFAFA" style="border-color:#222222; border-width:1px; border-style:solid; border-top-width:0px; border-bottom-width:0px;" valign="top"><br>
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr><td class="oberlink_gewaehlt"><a class="menu" href="">Login</a><br></td></tr>
								</table>
							</td>
		');
	}
}
?>