<?php
class Authentication
{
   /*
	* methoden:
	 Inloggen en uitloggen
	* __construct($database_object,$oWebsite)
	* check($admin,$showform)
	* log_in($user,$pass,$admin)
	* echo_login_form($admin=false)
	* log_out()
	 Gebruikersinformatie
	* get_current_username()
	* get_current_email()
	* get_username($id)
	* get_email($id)
	* get_users_table()
	 Formuliercontrole
	* valid_username($user)
	* valid_name($user)
	* valid_password($pass,$pass2)
	* valid_email($email)
	 Accounts wijzigen en maken
	* add_user($user,$pass,$email,$admin,$name)
	* set_password($newpass,$id=-1)
	* set_email($newemail,$id=-1)
	* 
	* 
	*
	*
	*
	*/
	
	
	protected $database_object;
	protected $oWebsite;
	
	function __construct($oWebsite,$oDB)
	{
		if(!isset($oWebsite->IS_WEBSITE_OBJECT))
		{
			//website object is geen website object, argumenten zijn verkeerd om aangeleverd
			//(voor 5 november 2011 was dat de standaard)
			$this->database_object = $oWebsite;
			$this->website_object = $oDB;
		}
		else
		{
			$this->database_object = $oDB;
			$this->website_object = $oWebsite;
		}
	}
	
	
	function check($admin,$showform)
	{	
		if(
			isset($_SESSION['user'])&&
			isset($_SESSION['pass'])&&
			isset($_SESSION['email'])&&
			isset($_SESSION['admin'])&&
			isset($_SESSION['id'])&&
			$_SESSION['admin']>=$admin)
		{	//ingelogd met voldoende rechten
			return true;
		}
		else
		{	//niet ingelogd met voldoende rechten, kijk of dat inmiddels veranderd is
			if(isset($_POST['user'])&&isset($_POST['pass'])&&$this->log_in($_POST['user'],$_POST['pass'],$admin))
			{	// zojuist ingevoerde gegevens zijn correct..
				return true;
			}
			else
			{	//of niet, laat dan het inlogformulier zien als dat nodig is en geef false door
				if($showform)
				{
					$this->echo_login_form($admin);
				}
				return false;
			}
				
		}
	}
	
	function log_in($user,$pass,$admin)
	{	//geeft terug of gebruiker ingelogd kon worden. Werkt ook de sessies bij. Geeft ook relevante foutmeldingen door.
		$oDB = $this->database_object;
		if(!$oDB) return false;//zinloos om door te gaan
		
		$user = htmlentities(strtolower($oDB->escape_data($user)));//beveilig en in kleine letters
		$pass = md5(sha1($pass));//codeer
		$admin = (int) $admin;//beveilig
		
		$sql = "SELECT gebruiker_id,gebruiker_admin,gebruiker_email FROM `gebruikers` ";
		$sql.= "WHERE gebruiker_login = '$user'";
		$sql.= "AND gebruiker_wachtwoord = '$pass'";
		$sql.= "AND gebruiker_admin >= $admin ";
		
		$result = $oDB->query($sql);
		
		if($result && $oDB->rows($result)!=0)
		{
			$row = $oDB->fetch($result);
			
			$_SESSION['user'] = $user;
			$_SESSION['pass'] = $pass;
			$_SESSION['email'] = $row[2];
			$_SESSION['admin'] = $row[1];
			$_SESSION['id'] = $row[0];
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function echo_login_form($admin=false)
	{	//laat een inlogformulier zien
		
		
		//huidige pagina ophalen
		$oWebsite = $this->website_object;
		$p= urlencode($oWebsite->get_pagevar('file'));
		$logintext = $oWebsite->t("users.please_log_in");
		if($admin) $logintext.=' <strong><em> '.$oWebsite->t("users.as_administrator").'</em></strong>';
		echo <<<EOT
		<form method="post" action="{$oWebsite->get_url_main()}">
			<h3>$logintext</h3>
			<p>
				<label for="user">{$oWebsite->t('users.username')}:</label> <br />
				<input type="text" name="user" id="user" autofocus="autofocus" /> <br />
				<label for="pass">{$oWebsite->t('users.password')}:</label> <br />
				<input type="password" name="pass" id="pass" /> <br />
				
				<input type="submit" value="{$oWebsite->t('main.log_in')}" class="button" />
				
				<input type="hidden" name="p" value="$p" />
			</p>
		</form>	
EOT;
		
	}
	
	function log_out()
	{	//logt de gebruiker uit
		unset($_SESSION['user']);
		unset($_SESSION['pass']);
		unset($_SESSION['email']);
		unset($_SESSION['admin']);	
		unset($_SESSION['id']);	
	}
	
	function get_current_username()
	{	//WAARSCHUWING: geeft de loginnaam terug, niet de echte naam
		return $_SESSION['user'];
	}
	
	function get_current_email()
	{
		return $_SESSION['email'];
	}
	
	function get_current_id()
	{
		return $_SESSION['id'];
	}
	
	function get_username($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$id = (int) $id;
		
		if($id==0)
		{	//heeft geen zin om door te gaan
			$oWebsite->add_error('User not found.');
			return '';
		}
		
		$sql = 'SELECT gebruiker_login FROM `gebruikers` WHERE gebruiker_id = \''.$id.'\' ';
		$result = $oDB->query($sql);
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			$result = $result[0];
			return $result;
		}
		else
		{
			$oWebsite->add_error('User not found.');
			return '';
		}
	}
	
	function get_email($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$id = (int) $id;
		
		if($id==0)
		{	//heeft geen zin om door te gaan
			$oWebsite->add_error('User not found.');
			return '';
		}
		
		$sql = 'SELECT gebruiker_email FROM `gebruikers` WHERE gebruiker_id = \''.$id.'\' ';
		$result = $oDB->query($sql);
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			$result = $result[0];
			return $result;
		}
		else
		{
			$oWebsite->add_error('User not found.');
			return '';
		}
	}
	
	function get_admin($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$id = (int) $id;
		
		if($id==0)
		{	//heeft geen zin om door te gaan
			$oWebsite->add_error('User not found.');
			return '';
		}
		
		$sql = 'SELECT gebruiker_admin FROM `gebruikers` WHERE gebruiker_id = \''.$id.'\' ';
		$result = $oDB->query($sql);
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			$result = $result[0];
			return $result;
				
		}
		else
		{
			$oWebsite->add_error('User not found.');
			return '';
		}
	}
	
	function get_users_table()
	{	//geeft de gebruikers als tabel
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;
		
		$sql = "SELECT gebruiker_id,gebruiker_admin,gebruiker_login,gebruiker_naam,gebruiker_email FROM `gebruikers` ";
		$result = $oDB->query($sql);
		
		$return_value ="<table style=\"width:98%\">\n";
		$return_value.="<tr><th>".$oWebsite->t("users.username")."</th><th>".$oWebsite->t("users.display_name")."</th><th>".$oWebsite->t("users.email")."</th><th>".$oWebsite->t("users.administrator")."</th><th>".$oWebsite->t("main.edit")."</th></tr>\n";//login-naam-email-admin-bewerk
		$return_value.='<tr><td colspan="5"><a class="arrow" href="'.$oWebsite->get_url_page("create_account").'">'.$oWebsite->t("users.create_new")."...</a></td></tr>\n";//maak nieuwe account
		if($oDB->rows($result)>0)
		{
			while(list($id,$admin,$login,$name,$email)=$oDB->fetch($result))
			{
				if($id!=$_SESSION['id'])
				{	//eigen account niet aanpassen
				
					//email als link weergeven
					$emaillink = "<a href=\"mailto:$email\">$email</a>";
					if(empty($email)){ $emaillink = '<em>'.$oWebsite->t("main.not_set").'</em>'; }//niet ingesteld
					
					$return_value.="<tr>";
					$return_value.="<td title=\"$login\">$login</td>";
					$return_value.="<td title=\"$name\">$name</td>";
					$return_value.="<td title=\"$email\">$emaillink</td>";
					if($admin)
					{
						$return_value.="<td>".$oWebsite->t("main.yes")."</td>";
						$return_value.="<td style=\"font-size:80%\"><em>{$oWebsite->t('users.administrator')}!</em></td>\n";//beheerder!
					}
					else
					{
						$return_value.="<td>".$oWebsite->t("main.no")."</td>";
						$return_value.="<td style=\"font-size:80%\">";
						$return_value.='<a href="'.$oWebsite->get_url_page("password_other",$id).'">'.$oWebsite->t("users.password").'</a>|';//wachtwoord
						$return_value.='<a href="'.$oWebsite->get_url_page("email_other",$id).'">'.$oWebsite->t("users.email")."</a></td>\n";//email
					}
				}
			}
		}
		$return_value.="</table>";
		return $return_value;
	}
	
	function valid_username($user)
	{	//controleert of gebruikersnaam geldig is
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		$valid = true;
		
		$user = $oDB->escape_data($user);
		
		if(strlen($user)<4)
		{
			$oWebsite->add_error('Username is too short. Minimum lenght is 4 characters.');
			$valid = false;;
		}
		if(strlen($user)>30)
		{
			$oWebsite->add_error('Username is too long. Maximum lenght is 30 characters.');
			$valid = false;
		}
		if($user!=strip_tags($user))
		{
			$oWebsite->add_error('Invalid username. HTML-tags are not allowed.');
			$valid = false;
		}
		
		if($oDB->rows($oDB->query('SELECT gebruiker_id FROM `gebruikers` WHERE gebruiker_login = \''.$user.'\' LIMIT 0 , 1'))>0)
		{
			$oWebsite->add_error("An user named $user already exists. Please choose a different name.");
			$valid = false;
		}
		return $valid;
	}
	
	function valid_name($name)
	{	//controleert of gebruikersnaam geldig is
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		$valid = true;
		
		$name = $oDB->escape_data($name);
		
		if(strlen($name)<4)
		{
			$oWebsite->add_error('Display name is too short. Minimum lenght is 4 characters.');
			$valid = false;;
		}
		if(strlen($name)>30)
		{
			$oWebsite->add_error('Display name is too long. Maximum lenght is 30 characters.');
			$valid = false;
		}
		if($name!=strip_tags($name))
		{
			$oWebsite->add_error('Invalid display name. HTML-tags are not allowed.');
			$valid = false;
		}
		return $valid;
	}
	
	
	function valid_password($pass,$pass2)
	{	//controleert of wachtwoord geldig is
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$valid = true;
		
		if(strlen($pass)<5)
		{
			$oWebsite->add_error('Password is too short. Minimum lenght is 5 characters.');
			$valid = false;;
		}
		if($pass!=$pass2)
		{
			$oWebsite->add_error('Passwords are not the same.');
			$valid = false;
		}
		return $valid;
	}
	
	function valid_email($email)
	{ //controleert of email in theorie geldig is
		if($email=='') return true;//email is optioneel, sta ook lege emailadressen toe
		
		$oWebsite = $this->website_object;
		
		if(preg_match('/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i',$email))
		{	//ingewikkeld, maar werkt
			return true;
		}
		else
		{
			$oWebsite->add_error('Invalid e-mail address.');
			return false;
		}
	}
	
	function add_user($user,$pass,$email,$admin,$name)
	{	//voegt een gebruiker toe 
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;
	
		//ingevoegde waarden beveiligen
		$user = htmlentities(strtolower($oDB->escape_data($user)));
		$name = htmlentities($oDB->escape_data($name));
		$pass = md5(sha1($pass));
		$email = $oDB->escape_data($email);
		$admin = ($admin==1)? 1 : 0;
	
		$sql = "INSERT INTO `gebruikers` ";
		$sql.= "(`gebruiker_admin`, `gebruiker_login`, `gebruiker_naam`, `gebruiker_wachtwoord`, `gebruiker_email`) ";
		$sql.= "VALUES (";
		$sql.= "'$admin', ";
		$sql.= "'$user', ";
		$sql.= "'$name', ";
		$sql.= "'$pass', ";
		$sql.= "'$email');";
		
		if($oDB->query($sql))
		{
			return true;
		}
		else
		{
			$oWebsite->add_error('Account could not be created.');
		}
	}
	
	function set_password($newpass,$id=-1)
	{
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;
		
		if($id==-1) $id = $_SESSION['id'];//vervang door id van gebruiker
		if(($id==$_SESSION['id'])||($_SESSION['admin']==true&&$this->get_admin($id)==false))
		{
			$newpass = md5(sha1($newpass));
			$sql = "UPDATE `gebruikers` ";
			$sql.= "SET gebruiker_wachtwoord = '$newpass' ";
			$sql.= "WHERE gebruiker_id = '$id';";
			
			$result = $oDB->query($sql);
			if($result)
			{
				if($id == $_SESSION['id'])
				{	//werk sessie bij als het om jouw wachtwoord gaat
					$_SESSION['pass']=$newpass;
				}
				return true;
			}
			else
			{
				return false;//ergens iets misgegaan
			}
			
		}
		else
		{
			$oWebsite->add_error('You cannot change the password address of this account.');
		}
		
		return false;
	}
	
	function set_email($newemail,$id=-1)
	{
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;
		
		if($id==-1) $id = $_SESSION['id'];//vervang door id van gebruiker
		if(($id==$_SESSION['id'])||($_SESSION['admin']==true&&$this->get_admin($id)==false))
		{
			$newemail = $oDB->escape_data($newemail);
			$sql = "UPDATE `gebruikers` ";
			$sql.= "SET gebruiker_email = '$newemail' ";
			$sql.= "WHERE gebruiker_id = '$id';";
			
			$result = $oDB->query($sql);
			if($result)
			{
				if($id == $_SESSION['id'])
				{	//werk sessie bij als het om jouw email gaat
					$_SESSION['email'] = $newemail;
				}
				return true;
			}
			else
			{
				return false;//ergens iets misgegaan
			}
			
		}
		else
		{
			$oWebsite->add_error('You cannot change the email address of this account.');
		}
		
		return false;
	}
	
}

?>