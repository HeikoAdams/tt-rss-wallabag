<?php
class Wallabag extends Plugin {
	private $host;

	function init($host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
		$host->add_hook($host::HOOK_PREFS_TAB, $this);
	}

	function about() {
		return array(1.0,
			"Save articles on wallabag (requires wallabag 1.9.1 or newer)",
			"Heiko Adams");
	}

	function save() {
		$wallabag_url = db_escape_string($_POST["wallabag_url"]);
		$this->host->set($this, "wallabag_url", $wallabag_url);
		echo "Value Wallabag URL set to $wallabag_url<br/>";
	}

	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/wallabag.js");
	}

	function hook_article_button($line) {
		$article_id = $line["id"];

		$rv = "<img src=\"plugins.local/wallabag/wallabag.png\"
			class='tagsPic' style=\"cursor : pointer\"
			onclick=\"shareArticleToWallabag($article_id)\"
			title='".__('Save on wallabag')."'>";

		return $rv;
	}

	function getInfo() {
		$id = db_escape_string($_REQUEST['id']);

		$result = db_query("SELECT title, link
				FROM ttrss_entries, ttrss_user_entries
				WHERE id = '$id' AND ref_id = id AND owner_uid = " .$_SESSION['uid']);

		if (db_num_rows($result) != 0) {
			$title = truncate_string(strip_tags(db_fetch_result($result, 0, 'title')),
				100, '...');
			$article_link = db_fetch_result($result, 0, 'link');
		}

		$wallabag_url = $this->host->get($this, "wallabag_url");

		print json_encode(array("title" => $title, "link" => $article_link,
				"id" => $id, "wallabag_url" => $wallabag_url));
	}
	
	function hook_prefs_tab($args) {
		if ($args != "prefPrefs") return;

		print "<div dojoType=\"dijit.layout.AccordionPane\" title=\"".__("Wallabag settings")."\">";

		print "<br/>";

		$wallabag_url = $this->host->get($this, "wallabag_url");
		print "<form dojoType=\"dijit.form.Form\">";

		print "<script type=\"dojo/method\" event=\"onSubmit\" args=\"evt\">
			evt.preventDefault();
		if (this.validate()) {
			console.log(dojo.objectToQuery(this.getValues()));
			new Ajax.Request('backend.php', {
parameters: dojo.objectToQuery(this.getValues()),
onComplete: function(transport) {
notify_info(transport.responseText);
}
});
}
</script>";

print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"op\" value=\"pluginhandler\">";
print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"method\" value=\"save\">";
print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"plugin\" value=\"wallabag\">";
print "<table width=\"100%\" class=\"prefPrefsList\">";
print "<tr><td width=\"40%\">".__("Wallabag URL")."</td>";
print "<td class=\"prefValue\"><input dojoType=\"dijit.form.ValidationTextBox\" required=\"1\" name=\"wallabag_url\" regExp='^(http|https)://.*' value=\"$wallabag_url\"></td></tr>";
	print "</table>";
	print "<p><button dojoType=\"dijit.form.Button\" type=\"submit\">".__("Save")."</button>";

	print "</form>";

	print "</div>"; #pane

	}

	function api_version() {
		return 2;
	}

}
?>
