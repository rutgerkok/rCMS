<?php

class Comments {
    /*
     *
     * OPMERKING
     * kijk in de klasse articles voor het weergeven van reacties bij een artikel
     * VARIABELEN
     * $database_object - (obj) bevat de databaseverbinding
     * $website_object - (obj) bevat het website-object
     *
     *
     * METHODEN
     * __construct   - zet alle variabelen goed
     * check_input   - geeft terug of de ingevoerde gegevens correct zijn
     * save          - slaat de gegevens op
     * delete_comment- verwijdert de reactie arg0
     * echo_editor   - geeft de complete editor terug
     * echo_editor_normal - voor niet-ingelogde gebruikers
     * echo_editor_logged_in - voor ingelogde gebruikers
     * get_comment   - geeft de reactie weer met de opgegeven id
     *
     */

    /* @var $website_object Website */
    protected $website_object;
    /* @var $database_object Database */
    protected $database_object;
    /* @var $authentication_object Authentication */
    protected $authentication_object;

    function __construct(Website $oWebsite, Authentication $oAuth) {
        $this->database_object = $oWebsite->get_database();
        $this->website_object = $oWebsite;
        $this->authentication_object = $oAuth;
    }

    function check_input() { //controleert of de ingevulde gegevens correct zijn
        $oWebsite = $this->website_object;
        $oAuth = $this->authentication_object;

        if (!isset($_REQUEST['submit']))
            return false;

        if (!isset($_REQUEST['id']) || ((int) $_REQUEST['id']) == 0) {
            $oWebsite->add_error($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_found")); //Artikel niet gevonden
        }
        if (!$oWebsite->logged_in()) {
            if (!isset($_REQUEST['name']) || trim($_REQUEST['name']) == '') {
                $oWebsite->add_error($oWebsite->t("users.name") . ' ' . $oWebsite->t("errors.not_entered")); //Naam niet ingevuld
            }
            if (isset($_REQUEST['name']) && strlen(trim($_REQUEST['name'])) > 20) {
                $oWebsite->add_error($oWebsite->t("users.name") . ' ' . str_replace("#", 20, $oWebsite->t("errors.too_long"))); //Naam te lang
            }
            if (isset($_REQUEST['email']) && !empty($_REQUEST['email']) && !$oAuth->valid_email($_REQUEST['email'])) { //wel verzonden, wel ingevuld maar niet geldig
                $oWebsite->add_error($oWebsite->t("users.email") . ' ' . $oWebsite->t("errors.not_correct")); //email niet correct
            }
            if (isset($_REQUEST['email']) && strlen(trim($_REQUEST['email'])) > 100) { //wel 
                $oWebsite->add_error($oWebsite->t("users.email") . ' ' . str_replace("#", 100, $oWebsite->t("errors.too_long"))); //email te lang
            }
        }
        if (!isset($_REQUEST['comment']) || trim($_REQUEST['comment']) == '') {
            $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_entered")); //Reactie niet ingevuld
        }
        if (isset($_REQUEST['comment']) && strip_tags($_REQUEST['comment']) != $_REQUEST['comment']) {
            $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_correct") . ' ' . $oWebsite->t("errors.contains_html")); //Reactie niet correct: bevat HTML!
        }
        return ($oWebsite->error_count() == 0);
    }

    function save() { //sla alles op
        //verdeeld over twee dingen: de procedure voor ingelogde en de procedure voor niet-ingelogde gebruikers
        //niet-ingelogde gebruikers krijgen naam en eventueel email opgeslagen
        //ingelogde gebruikers krijgen hun id opgeslagen
        $oWebsite = $this->website_object;
        $oAuth = $this->authentication_object;
        $oDB = $this->database_object;

        $article_id = (int) $_REQUEST['id'];
        $comment = $oDB->escape_data($_REQUEST['comment']);

        if ($oWebsite->logged_in()) {
            $user_id = $oAuth->get_current_user()->get_id();

            $sql = "INSERT INTO `reacties` ( ";
            $sql.= "`artikel_id`, `gebruiker_id`, `reactie_gemaakt`, `reactie_inhoud`";
            $sql.= ") VALUES (";
            $sql.= " $article_id, $user_id, NOW(), \"$comment\" )";
        } else {
            $name = $oDB->escape_data($_REQUEST['name']);
            $mail = $oDB->escape_data($_REQUEST['email']);

            $sql = "INSERT INTO `reacties` ( ";
            $sql.= "`artikel_id`, `reactie_naam`, `reactie_email`,  `reactie_gemaakt`,  `reactie_inhoud`";
            $sql.= ") VALUES (";
            $sql.= " $article_id, '$name', '$mail', NOW(), \"$comment\" )";
        }

        if ($oDB->query($sql)) {
            return true;
        } else {
            $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_saved")); //reactie is niet opgeslagen
            return false;
        }
    }

    function delete_comment($id) {
        $oWebsite = $this->website_object;
        $oDB = $this->database_object;

        $id = (int) $id;
        if ($id > 0) {
            $sql = "DELETE FROM `reacties` WHERE `reactie_id` = $id";
            if ($oDB->query($sql) && $oDB->affected_rows() > 0) {
                return true;
            } else {
                $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
                return false; //meldt dat het mislukt is
            }
        } else {
            $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
            return false; //heeft geen zin om met id=0 of iets anders query uit te voeren
        }
    }

    function echo_editor() {
        $oWebsite = $this->website_object;
        if (!isset($_REQUEST['id']) || ((int) $_REQUEST['id']) == 0) {
            $oWebsite->add_error($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_found")); //Artikel niet gevonden
            return false;
        }

        if ($oWebsite->logged_in()) {
            $this->echo_editor_logged_in();
        } else {
            $this->echo_editor_normal();
        }
        return true;
    }

    function echo_editor_logged_in() {
        $oWebsite = $this->website_object;
        $id = (int) $_REQUEST['id'];

        echo <<<EOT
		<form action="{$oWebsite->get_url_main()}" method="post">
			<p>
				<em>{$oWebsite->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
			</p>
			<p>	
				<!-- reactie -->
				{$oWebsite->t("articles.comment")}<span class="required">*</span>:<br />
				<textarea name="comment" id="comment" rows="10" cols="60" style="width:98%"></textarea>
			</p>
			<p>
				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="add_comment" />
				<input type="submit" name="submit" value="{$oWebsite->t('articles.comment.add')}" class="button" />
			</p>
		</form>
EOT;
    }

    function echo_editor_normal() {
        $oWebsite = $this->website_object;
        $id = (int) $_REQUEST['id'];

        echo <<<EOT
		<form action="{$oWebsite->get_url_main()}" method="post">
			<p>
					<em>{$oWebsite->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
			</p>
			<p>
				<!-- naam -->
				{$oWebsite->t("users.name")}<span class="required">*</span>:<br />
				<input type="text" name="name" id="name" maxlength="20" style="width:98%" /><br />
			</p>
			<p>
				<!-- email -->
				{$oWebsite->t("users.email")}:<br />
				<input type="email" name="email" id="email" style="width:98%" /><br />
				<em>{$oWebsite->t("articles.comments.email_explained")}</em><br />
			</p>
			<p>	
				<!-- reactie -->
				{$oWebsite->t("articles.comment")}<span class="required">*</span>:<br />
				<textarea name="comment" id="comment" rows="10" cols="60" style="width:98%"></textarea>
			</p>
			<p>
				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="add_comment" />
				<input type="submit" name="submit" value="{$oWebsite->t('articles.comment.add')}" class="button" />
			</p>
		</form>
EOT;
    }

    function get_comment($id) { //geeft reactie kant-en-klaar terug
        $oWebsite = $this->website_object;
        $oDB = $this->database_object;

        $id = (int) $id;

        $sql = "SELECT reactie_id, reactie_naam, reactie_gemaakt, reactie_inhoud, gebruiker_naam FROM `reacties` LEFT JOIN `gebruikers` USING ( gebruiker_id ) WHERE reactie_id = $id";
        $result = $oDB->query($sql);
        if ($oDB->rows($result) > 0) { //geef reactie terug
            list($comment_id, $comment_name, $comment_date, $comment, $account_name) = $oDB->fetch($result);

            $comment_date = str_replace(' 0', ' ', strftime("%A %d %B %Y %X", strtotime($comment_date)));
            if (empty($comment_name))
                $comment_name = $account_name; //ingelogde gebruikers correct weergeven
            $return_value = "<h3>$comment_name ($comment_date)</h3>"; //naam en datum
            $return_value.= "<p>" . nl2br($comment) . "</p>"; //reactie
            return $return_value;
        }
        else {
            $oWebsite->add_error($oWebsite->t("articles.comment") . ' ' . $oWebsite->t("errors.not_found"));
            return '';
        }
    }

}

?>