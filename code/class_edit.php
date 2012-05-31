<?php
class Edit
{
	/*
	 *
	 * VARIABELEN
	 * $database_object - (obj) bevat de databaseverbinding
	 * $website_object - (obj) bevat het website-object
	 * $category_object - (obj) bevat het categorie-object
	 * $id - (int) huidig artikel-id. 0=nieuw artikel
	 * $contents - (array) inhoud van artikel, gevuld door __construct() en save(), gelezen door echo_editor
	 *
	 * METHODEN
	 * __construct   - zet alle variabelen goed
	 * echo_editor   - geeft de complete editor terug
	 * check_input   - geeft terug of de ingevoerde gegevens correct zijn
	 * save          - slaat de gegevens op
	 * delete_article- verwijdert het artikel $_REQUEST['id']
	 * noslashes     - roept indien nodig stripslashes aan
	 *
	 */
	
	
	protected $database_object;
	protected $website_object;
	protected $category_object;
	protected $id;
	protected $confirm;
	protected $contents = array();
		
	//constructor, zet gegevens artikel klaar
	function __construct($oDB,$oCats,$oWebsite)
	{
		$this->database_object = $oDB;
		$this->website_object = $oWebsite;
		$this->category_object = $oCats;
		
		//ID GOEDZETTEN
		$id = 0;
		if(isset($_REQUEST['id']))
		{
			$id = (int) $_REQUEST['id'];
		}
		$this->id = $id;
		
		//CONFIRM GOEDZETTEN
		$confirm = 0;
		if(isset($_REQUEST['confirm']))
		{
			$confirm = (int) $_REQUEST['confirm'];
		}
		$this->confirm = $confirm;
		
				
		//ZET GEGEVENS KLAAR IN OBJECT
		$this->contents = array(0,'','','<p>Typ hier het artikel</p>','','','',0,0,'',0);
		if($id>0)
		{
			//haal uit database als id is opgegeven
			$sql = "SELECT `categorie_id`,`artikel_titel`,`artikel_intro`,";
			$sql.= "`artikel_inhoud`,`artikel_gemaakt`,`artikel_bewerkt`,";
			$sql.= "`artikel_afbeelding`,`artikel_gepind`,`artikel_verborgen`,";
			$sql.= "`artikel_verwijsdatum`,`artikel_reacties`";
			$sql.="FROM `artikel` ";
			$sql.="WHERE `artikel_id` = $id";
			$contents = $oDB->query($sql);
			if($contents && $oDB->rows($contents)==1)
			{
				$contents = $oDB->fetch($contents);
				$this->contents = $contents;
			}
			
		}
		
	}
	
	function echo_editor()
	{
		$oWebsite = $this->website_object;
		
		
		$contents = $this->contents;//array met inhoud artikel
		//haal de slashes weg
		$contents[1] = $this->noslashes($contents[1]);
		$contents[2] = $this->noslashes($contents[2]);
		$contents[3] = $this->noslashes($contents[3]);
		
		$contents[9] = trim($contents[9]);
		
		//maak array met categorieen
		$categories = $this->category_object->get_categories();
		
		//maak keuzelijst categorieen
		$cat_list = '<select name="article_category" id="article_category" class="button" style="width:100%">';
		foreach($categories as $cat_id=>$cat_name)
		{
			$cat_list.="<option value=\"$cat_id\" ";
			if($contents[0]==$cat_id)
			{
				$cat_list.="selected=\"selected\" ";
			}
			$cat_list.=">$cat_name</option>\n";
		}
		$cat_list.="</select>";
		
		//maak tekst voor al dan niet gepind,verborgen en met reacties
		$pinned = ($contents[7]==1)? ' checked=\"checked\" ':'';
		$hidden = ($contents[8]==1)? ' checked=\"checked\" ':'';
		$comments = ($contents[10]==1)? ' checked=\"checked\" ':'';
		
		//maak datum en tijd
		if(!empty($contents[9]))
		{	//als er een datum-tijd is gevonden
			$date = explode(" ",$contents[9]);
			$date = $date[0];
			$time = explode(" ",$contents[9]);
			if(isset($time[1]))
			{
				$time = $time[1];
				$time = explode(":",$time);    //secondes
				$time = $time[0].':'.$time[1]; //weghalen
			}
			else
			{
				$time = "";
			}
		}
		if(empty($contents[9])||$date=="0000-00-00")
		{	//als de datum-tijd 0 of leeg is
			$date = "";
			$time = "";
		}
		
		//geef alles weer
		echo <<<EOT
			<script type="text/javascript" src="ckfinder/ckfinder.js"></script>
			<script type="text/javascript">
				var text_select_date;
				text_select_date = "{$oWebsite->translations[51]}";
			</script>
			<script type="text/javascript" src="{$oWebsite->get_url_scripts()}article_editor.js"></script>
			<form action="index.php" method="post">
				<p style="position:absolute;top:.2em;right:2em;">
					<input type="submit" class="button" name="article_submit" id="article_submit" value="{$oWebsite->translations[29]}" />
					<script type="text/javascript">document.write('<input type="submit" class="button" name="article_submit" id="article_submit" value="{$oWebsite->translations[30]}" />'); </script>
					<a href="index.php" class="button" >{$oWebsite->translations[31]}</a>
				</p>
				<p>
					<em>{$oWebsite->translations[21]}</em> <!-- velden met een * zijn verplicht -->
				</p>
				<table class="layout">
					<tr>    <!-- gepind, verborgen en reacties -->
						<td style="width:170px">&nbsp;</td>
						<td>
							<label for="article_hidden" title="{$oWebsite->translations[49]}" style="cursor:help">
								<input type="checkbox" id="article_hidden" name="article_hidden" class="checkbox" $hidden />
								{$oWebsite->translations[48]}
							</label> 
							<label for="article_pinned" title="{$oWebsite->translations[24]}" style="cursor:help">
								<input type="checkbox" id="article_pinned" name="article_pinned" class="checkbox" $pinned />
								{$oWebsite->translations[23]}
							</label> 
							<label for="article_comments" title="{$oWebsite->translations[67]}" style="cursor:help">
								<input type="checkbox" id="article_comments" name="article_comments" class="checkbox" $comments />
								{$oWebsite->translations[66]}
							</label>
						</td>
					</tr>
					<tr>	<!-- titel -->
						<td><label for="article_title">{$oWebsite->translations[22]}<span class="required">*</span></td>
						<td><input type="text" id="article_title" name="article_title" style="width:98%" value="{$contents[1]}" /></td>
					</tr>
					<tr>    <!-- intro -->
						<td><label for="article_intro">{$oWebsite->translations[25]}<span class="required">*</span></label></td>
						<td><textarea id="article_intro" name="article_intro" style="width:98%" rows="3">{$contents[2]}</textarea></td>
					</tr>
					<tr>    <!-- categorie -->
						<td><label for="article_category">{$oWebsite->translations[12]}<span class="required">*</span></label></td>
						<td>$cat_list</td>
					</tr>
					<tr>    <!-- afbeelding --->
						<td><label for="article_featured_image">{$oWebsite->translations[26]}</label></td>
						<td>
							<input id="article_featured_image" name="article_featured_image" type="text" value="{$contents[6]}" onclick="BrowseServer();" style="width:64%"  />
							<input type="button" class="button" id="browseserver" name="browseserver" value="{$oWebsite->translations[27]}" onclick="BrowseServer();" style="width:33%" />
						</td>
					</tr>
					
					<tr>    <!-- datum voor kalender -->
						<td title="{$oWebsite->translations[64]}" style="cursor:help">{$oWebsite->translations[42]}</td>
						<td>
							<label for="article_eventdate">
								{$oWebsite->translations[53]}:<!--datum-->
								<input type="text" id="article_eventdate" name="article_eventdate" value="$date" style="width:8em" />
							</label>
							<label for="article_eventtime">
								{$oWebsite->translations[54]}:<!--tijd-->
								<input type="text" id="article_eventtime" name="article_eventtime" value="$time" style="width:8em" />
							</label>
							<script type="text/javascript">fieldsInit()</script><!-- maak knop voor datumveld -->
						</td>
					</tr>
					<tr>   <!-- inhoud bericht -->
						<td colspan="2">
							<label for="article_body">{$oWebsite->translations[28]}<span class="required">*</span></label><br />	
EOT;
							if(file_exists('./ckeditor/ckeditor_php5.php'))
							{
								// CKEditor insluiten
								include_once('./ckeditor/ckeditor_php5.php');
								// CKFinder insluiten
								include_once('./ckfinder/ckfinder.php');
								
								//Instantie maken en textarea #article_edit maken
								$CKEditor = new CKEditor();
								$CKEditor->basePath = './ckeditor/';
								CKFinder::SetupCKEditor( $CKEditor, './ckfinder/' ) ;
								$CKEditor->editor("article_body", $contents[3]);
							}
							else
							{	//Maar sluit niet in als CKEditor niet gevonden is
								echo '<textarea name="article_body" id="article_body" rows="30" cols="40" style="width:95%">'.$contents[3].'</textarea>';
								echo '<input type="hidden" name="article_no_wysiwyg_editor" value="true" />';
							}
							echo <<<EOT
						</td>	
					</tr>
				</table>
			<!-- page and id -->
			<input type="hidden" name="p" value="edit_article" />
			<input type="hidden" name="id" value="{$this->id}" />
			
			<p>
				
			</p>
			</form>
EOT;
		

	}
	
	function check_input()
	{
		if(isset($_REQUEST['article_title'])) { $title = $_REQUEST['article_title']; $this->contents[1]= $title; }
		if(isset($_REQUEST['article_intro'])) { $intro = $_REQUEST['article_intro']; $this->contents[2]= $intro; }
		if(isset($_REQUEST['article_body'])) { $body = $_REQUEST['article_body']; $this->contents[3]= $body; }
		if(isset($_REQUEST['article_category'])) { $cat = (int) $_REQUEST['article_category']; $this->contents[0]= $cat; }
		if(isset($_REQUEST['article_featured_image'])) { $featured_image = $_REQUEST['article_featured_image']; $this->contents[6]= $featured_image; }
		if(isset($_REQUEST['article_pinned'])) { $pinned = 1; $this->contents[7]= $pinned; }
			elseif( isset($_REQUEST['article_title']) || !isset($this->contents[7]) ) { $pinned = 0; $this->contents[7]= $pinned; }
			//alleen gepind uitzetten als formulier ook daadwerkelijk is verzonden of als de $this->contents[7] leeg is
		if(isset($_REQUEST['article_hidden'])) { $hidden = 1; $this->contents[8]= $hidden; }
			elseif( isset($_REQUEST['article_title']) || !isset($this->contents[8]) ) { $hidden = 0; $this->contents[8]= $hidden; }
			//alleen verborgen status uitzetten als formulier ook daadwerkelijk is verzonden of als de $this->contents[8] leeg is
		if(isset($_REQUEST['article_eventdate'])) { $eventdate = $_REQUEST['article_eventdate']; $this->contents[9]= $eventdate; }
		if(isset($_REQUEST['article_eventtime'])) { $eventtime = $_REQUEST['article_eventtime']; $this->contents[9].= ' '.$eventtime; }
		if(isset($_REQUEST['article_comments'])) { $comments = 1; $this->contents[10]= $comments; }
			elseif( isset($_REQUEST['article_title']) || !isset($this->contents[10]) ) { $comments = 0; $this->contents[10]= $comments; }
			//alleen comments uitzetten als formulier ook daadwerkelijk is verzonden of als de $this->contents[10] leeg is
		
		if(!isset($title)||
		   !isset($intro)||
		   !isset($body)||
		   !isset($cat)||
		   !isset($featured_image)||
		   !isset($eventdate)||
		   !isset($eventtime)
		)
		{
			return false;
		}
		
		$oWebsite = $this->website_object;
		
		//titel
		$title = trim($title);
		if(strlen($title)>100)
		{
			$oWebsite->add_error('Title is too long. Maximum lenght is 100 characters. Current lenght is '.strlen($title).' characters.');
		}
		if(strlen($title)<2)
		{
			$oWebsite->add_error('Please enter a title.');	
		}
		
		//intro
		$intro = trim($intro);
		if(strip_tags($intro)!=$intro)
		{
			$oWebsite->add_error('Intro must not contain (X)HTML-tags.');	
		}
		if(strlen($intro)<2)
		{
			$oWebsite->add_error('Please enter a intro.');
		}
		if(strlen($intro)>325)
		{
			$oWebsite->add_error('Intro is too long. Maximum lenght is 325 characters. Current length is '.strlen($intro).' characters.');
		}
		
		//inhoud
		$body = trim($body);
		if(strlen($body)<9)
		{
			$oWebsite->add_error('Please enter a body.');
		}
		if(strlen($body)>65535)
		{
			$oWebsite->add_error('Body is too long. Maximum length is 65,535 characters.');
		} 
		
		//featured image
		$featured_image = trim($featured_image);
		if(strlen($featured_image)>150)
		{
			$oWebsite->add_error('The link of the featured image is too long. Maximum length is 150 characters.');
		}
		
		return ($oWebsite->error_count()==0);
		
	}
	
	function save()
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		//Gegevens opgehalen
		$title = $_REQUEST['article_title'];
		$intro = $_REQUEST['article_intro'];
		$cat   = (int) $_REQUEST['article_category'];
		$body  = str_replace(array('<h2>','</h2>'),array('<h3>','</h3>'),$_REQUEST['article_body']);//vervang <h2> door <h3>
		$featured_image = $_REQUEST['article_featured_image'];
		$pinned = isset($_REQUEST['article_pinned'])? 1 : 0;
		$hidden = isset($_REQUEST['article_hidden'])? 1 : 0;
		$eventdatetime = $_REQUEST['article_eventdate'].' '.$_REQUEST['article_eventtime'];
		$comments = isset($_REQUEST['article_comments'])? 1 : 0;
		$submit= $_REQUEST['article_submit'];
		
		$authorid = (int) $_SESSION['id'];
		
		//Kijk of artikel nieuw is, namelijk door te kijken of $this->contents al gegevens bevat.
		if($this->id==0)
		{	//nieuw artikel
			$sql = "INSERT INTO `artikel` ";
			$sql.="(`categorie_id`, ";
			$sql.="`artikel_titel`, `artikel_intro`, `artikel_gepind`, `artikel_verborgen`, `artikel_reacties`, ";
			$sql.="`artikel_inhoud`, `artikel_afbeelding`, `artikel_verwijsdatum`, `gebruiker_id`, `artikel_gemaakt`  ) VALUES ";
			$sql.="('$cat', ";
			$sql.="'".$oDB->escape_data($title)."', ";
			$sql.="'".$oDB->escape_data($intro)."', ";
			$sql.="'".$pinned."', ";
			$sql.="'".$hidden."', ";
			$sql.="'".$comments."', ";
			$sql.="'".$oDB->escape_data($body)."', ";
			$sql.="'".$oDB->escape_data($featured_image)."', ";
			$sql.="'".$oDB->escape_data($eventdatetime)."', ";
			$sql.="'".$authorid."', ";
			$sql.=" NOW() );";
			if($oDB->query($sql))
			{
				echo '<p><em>'.$oWebsite->translations[11].' '.$oWebsite->translations[46].'.</em></p>';//bericht gemaakt
				
				//array bijwerken
				$this->contents[1] = $title;
				$this->contents[2] = $intro;
				$this->contents[3] = $body;
				$this->contents[4] = date('Y-m-d H:i:s'); /*gemaakt*/
				$this->contents[6] = $featured_image;
				$this->contents[7] = $pinned;
				$this->contents[8] = $hidden;
				$this->contents[9] = $eventdatetime;
				$this->contents[10] = $comments;
				$this->contents[0] = $cat;
				
				//id bijwerken
				$this->id = $oDB->inserted_id();
			}
		}
		else
		{	//update artikel
			$sql = "UPDATE `artikel` SET ";
			$sql.="`artikel_titel` = '".$oDB->escape_data($title)."', ";
			$sql.="`categorie_id` = '".$cat."', ";
			$sql.="`artikel_intro` = '".$oDB->escape_data($intro)."', ";
			$sql.="`artikel_gepind` = '".$pinned."', ";
			$sql.="`artikel_verborgen` = '".$hidden."', ";
			$sql.="`artikel_reacties` = '".$comments."', ";
			$sql.="`artikel_inhoud` = '".$oDB->escape_data($body)."', ";
			$sql.="`artikel_afbeelding` = '".$oDB->escape_data($featured_image)."', "; 
			$sql.="`artikel_verwijsdatum` = '".$oDB->escape_data($eventdatetime)."', ";
			$sql.="`artikel_bewerkt` = NOW() ";
			$sql.=" WHERE `artikel_id` = {$this->id};";
			if($oDB->query($sql))
			{
				echo '<p><em>'.$oWebsite->translations[11].' '.$oWebsite->translations[45].'.</em></p>';//bericht bijgewerkt
				//array bijwerken
				$this->contents[1] = $title;
				$this->contents[2] = $intro;
				$this->contents[3] = $body;
				$this->contents[5] = date('Y-m-d H:i:s');/*bewerkt*/
				$this->contents[6] = $featured_image;
				$this->contents[7] = $pinned;
				$this->contents[8] = $hidden;
				$this->contents[9] = $eventdatetime;
				$this->contents[10] = $comments;
				$this->contents[0] = $cat;
			}
		}
		
		
		//echo "<p>Saved with id {$this->id}.</p>";
		if($submit==$oWebsite->translations[30])
		{
			echo <<<EOT
			<script type="text/javascript">
			//<![CDATA[
			location.href = "index.php?p=view_article&id={$this->id}";
			//]]>
			</script>
			
			
EOT;
		}
	}
	
	function delete_article()
	{
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;
		if($this->id>0)
		{
			if($this->confirm)
			{	//verwijder categorie
				$sql = 'DELETE FROM `artikel` WHERE artikel_id = '.$this->id;
				if($oDB->query($sql)&&$oDB->affected_rows()==1)
				{
					$return_value = '<p>'.$oWebsite->translations[11].' '.$oWebsite->translations[82].'</p>';//artikel verwijderd
					$return_value.= '<p><a href="index.php">Home</a></p>';
					return $return_value;
				}
				else
				{
					$oWebsite->add_error($oWebsite->translations[11].' '.$oWebsite->translations[83]);//artikel kan niet verwijderd worden
					return '';
				}
			}
			else
			{	//laat bevestigingsvraag zien
				$sql = "SELECT artikel_titel FROM artikel WHERE artikel_id = ".$this->id;
				$result = $oDB->query($sql);
				
				if($result)
				{
					$result = $oDB->fetch($result);
					$result = $result[0];
					
					$return_value = '<p>Are you sure you want to remove the article \''.$result.'\'?';
					$return_value.= ' This action cannot be undone.</p>';
					$return_value.= '<p><a href="index.php?p=delete_article&confirm=1&id='.$this->id.'">Yes</a>|';
					$return_value.= '<a href="index.php">No</a></p>';
					return $return_value;
				}
				else
				{
					$oWebsite->add_error('Article was not found.');
					return '';
				}
			}
		}
	}
	
	function noslashes($string)
	{
		if(ini_get('magic_quotes_gpc'))
		{
			return( stripslashes($string) );
		}
		else
		{
			return $string;
		}
	}
	
}
?>