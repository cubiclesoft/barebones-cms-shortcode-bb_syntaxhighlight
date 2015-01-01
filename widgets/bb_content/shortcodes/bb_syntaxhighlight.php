<?php
	// Barebones CMS Content Widget Shortcode Handler for Syntax Highlighted code
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	$g_bb_content_shortcodes["bb_syntaxhighlight"] = array(
		"name" => "Syntax Highlighter",
		"toolbaricon" => $g_fullurl . "/bb_syntaxhighlight_small.png",
		"mainicon" => $g_fullurl . "/bb_syntaxhighlight_large.png",
		"cache" => true,
		"security" => array(
			"" => array("Syntax Highlighter", "Defines who can add and edit syntax highlighted code."),
			"preview" => array("Preview Highlighted Code", "Defines who can preview syntax highlighted code.")
		)
	);

	class bb_content_shortcode_bb_syntaxhighlight extends BB_ContentShortcodeBase
	{
		private function GetInfo($sid)
		{
			global $bb_widget;

			$info = $bb_widget->shortcodes[$sid];
			if (!isset($info["code"]))  $info["code"] = "";
			if (!isset($info["type"]) || !file_exists(ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrush" . $info["type"] . ".js"))  $info["type"] = "Plain";
			if (!isset($info["opt-auto-links"]))  $info["opt-auto-links"] = true;
			if (!isset($info["opt-collapse"]))  $info["opt-collapse"] = false;
			if (!isset($info["opt-first-line"]))  $info["opt-first-line"] = 1;
			if (!isset($info["opt-gutter"]))  $info["opt-gutter"] = true;
			if (!isset($info["opt-highlight-prefix"]))  $info["opt-highlight-prefix"] = "";
			if (!isset($info["opt-html-script"]))  $info["opt-html-script"] = false;
			if (!isset($info["opt-smart-tabs"]))  $info["opt-smart-tabs"] = true;
			if (!isset($info["opt-tab-size"]))  $info["opt-tab-size"] = 4;
			if (!isset($info["opt-toolbar"]))  $info["opt-toolbar"] = true;
			if (!isset($info["opt-wrap-lines"]))  $info["opt-wrap-lines"] = true;

			return $info;
		}

		private function GetAliases($filename)
		{
			$result = array();
			$data = file_get_contents($filename);
			if (preg_match('/SyntaxHighlighter\.brushes\..*?\.aliases\s*=\s*(\[.*?\]);/', $data, $matches))
			{
				$result = json_decode(str_replace("'", "\"", $matches[1]));
			}

			return $result;
		}

		public function GenerateShortcode($parent, $sid, $depth)
		{
			global $bb_widget;

			$info = $this->GetInfo($sid);
			if ($info["code"] == "")  return "";

			$info["code"] = Str::ReplaceNewlines("\n", $info["code"]);

			if ($parent !== false && !$parent->IsShortcodeAllowed("bb_syntaxhighlight", "preview"))
			{
				if ($info["opt-highlight-prefix"] != "")  $info["code"] = str_replace("\n" . $info["opt-highlight-prefix"], "\n", $info["code"]);

				return "<pre>" . htmlspecialchars($info["code"]) . "</pre>";
			}

			$filename = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrush" . $info["type"] . ".js";

			$css = $bb_widget->css;
			$css[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/styles/shCore.css"] = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/styles/shCore.css";
			$css[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/styles/shThemeBarebones.css"] = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/styles/shThemeBarebones.css";
			$bb_widget->css = $css;

			$bb_widget->use_premainjs = true;

			$js = $bb_widget->js;
			$js[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shCore.js"] = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shCore.js";
			$js[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrush" . $info["type"] . ".js"] = $filename;
			if ($info["opt-html-script"])  $js[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrushHTML.js"] = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrushHTML.js";
			$js[ROOT_URL . "/" . SUPPORT_PATH . "/syntaxhighlighter/barebones.js"] = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/barebones.js";
			$bb_widget->js = $js;

			$opts = array();
			$aliases = $this->GetAliases($filename);
			if (count($aliases))  $opts[] = "brush: " . $aliases[0] . ";";
			if (!$info["opt-auto-links"])  $opts[] = "auto-links: false;";
			if ($info["opt-collapse"])  $opts[] = "collapse: true;";
			if ($info["opt-first-line"] != 1)  $opts[] = "first-line: " . (int)$info["opt-first-line"] . ";";
			if (!$info["opt-gutter"])  $opts[] = "gutter: false;";
			if ($info["opt-html-script"])  $opts[] = "html-script: true;";
			if (!$info["opt-smart-tabs"])  $opts[] = "smart-tabs: false;";
			if ($info["opt-tab-size"] != 4)  $opts[] = "tab-size: " . (int)$info["opt-tab-size"] . ";";
			if (!$info["opt-toolbar"])  $opts[] = "toolbar: false;";
			if (!$info["opt-wrap-lines"])  $opts[] = "wrap-lines: false;";
			if ($info["opt-highlight-prefix"] != "")
			{
				$highlight = array();
				$lines = explode("\n", $info["code"]);
				foreach ($lines as $num => $line)
				{
					if (substr($line, 0, strlen($info["opt-highlight-prefix"])) == $info["opt-highlight-prefix"])
					{
						$highlight[] = $num + (int)$info["opt-first-line"];
						$lines[$num] = substr($line, strlen($info["opt-highlight-prefix"]));
					}
				}
				if (count($highlight))
				{
					$info["code"] = implode("\n", $lines);
					$opts[] = "highlight: [" . implode(",", $highlight) . "];";
				}
			}

			return "<div class=\"syntaxhighlight-wrap\"><pre class=\"" . htmlspecialchars(implode(" ", $opts)) . "\">\n" . htmlspecialchars($info["code"]) . "\n</pre></div>";
		}

		public function ProcessShortcodeBBAction($parent)
		{
			global $bb_widget, $bb_widget_id, $bb_def_extmap;

			$info = $this->GetInfo($parent->GetSID());

			if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_upload_ajaxupload")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_upload_ajaxupload");

				$msg = BB_ValidateAJAXUpload();
				if ($msg != "")
				{
					echo htmlspecialchars(BB_Translate($msg));
					exit();
				}

				$info["code"] = file_get_contents($_FILES["Filedata"]["tmp_name"]);
				if (!$parent->SaveShortcode($info))
				{
					echo htmlspecialchars(BB_Translate("Unable to save the shortcode."));
					exit();
				}

				echo "OK";

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_upload_ajaxupload");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_upload_submit")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_upload_submit");

				$fileinfo = BB_IsValidURL($_REQUEST["url"], array("protocol" => "http"));
				if (!$fileinfo["success"])  BB_PropertyFormError($fileinfo["error"]);

				$info["code"] = $fileinfo["data"];
				if (!$parent->SaveShortcode($info))  BB_PropertyFormError("Unable to save the shortcode.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Image transferred.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo $parent->CreateShortcodePropertiesJS(""); ?>);
ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_upload_submit");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_upload")
			{
				$parent->CreateShortcodeUploader("", array(), "Configure Syntax Highlighter", "Code", "code", "*.*", "All Files");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_edit_load")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_edit_load");

				echo rawurlencode($info["code"]);

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_edit_load");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_edit_save")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_edit_save");

				$info["code"] = $_REQUEST["content"];
				if (!$parent->SaveShortcode($info))  echo htmlspecialchars(BB_Translate("Unable to save content.  Try again."));
				else
				{
					echo "OK\n";
					echo "<script type=\"text/javascript\">ReloadIFrame();</script>";
				}

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_edit_save");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_edit")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_edit");

				// Attempt to map the Syntax Highlighter selection with an Edit Area syntax highlighter selector.
				$filename = ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/shBrush" . $info["type"] . ".js";
				$aliases = $this->GetAliases($filename);
				$syntax = "html";
				foreach ($aliases as $alias)
				{
					if (isset($bb_def_extmap["." . $alias]) && $bb_def_extmap["." . $alias]["edit"] == "ea")
					{
						$syntax = $bb_def_extmap["." . $alias]["syntax"];
						break;
					}
				}

?>
<script type="text/javascript">
LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/editfile.js?_=20140418', true, function(loaded) {
		return ((!loaded && typeof(window.CreateEditAreaInstance) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function(params) {
		$('#fileeditor').show();

		var fileopts = {
			loadurl : Gx__URLBase,
			loadparams : <?php echo $parent->CreateShortcodePropertiesJS("bb_syntaxhighlight_edit_load", array(), true); ?>,
			id : 'wid_<?php echo BB_JSSafe($bb_widget_id); ?>_sc_<?php echo BB_JSSafe($parent->GetSID()); ?>',
			display : '<?php echo BB_JSSafe($bb_widget->_f . " (" . $parent->GetSID() . ") - Syntax Highlight"); ?>',
			saveurl : Gx__URLBase,
			saveparams : <?php echo $parent->CreateShortcodePropertiesJS("bb_syntaxhighlight_edit_save", array(), true); ?>,
			syntax : '<?php echo BB_JSSafe($syntax); ?>',
			aceopts : {
				'focus' : true,
				'theme' : 'crimson_editor'
			}
		};

		var editopts = {
			ismulti : true,
			closelast : ClosedAllFiles,
			width : '100%',
			height : '500px'
		};

		CreateEditAreaInstance('fileeditor', fileopts, editopts);
});
CloseProperties();
</script>
<?php

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_edit");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_configure_submit")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_configure_submit");

				$found = false;
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts");
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".js" && substr($name, 0, 7) == "shBrush" && substr($name, 7, $pos - 7) == $_REQUEST["type"])
					{
						$found = true;
						break;
					}
				}
				if (!$found)  BB_PropertyFormError("Invalid type specified.");

				$info["type"] = $_REQUEST["type"];
				$info["opt-auto-links"] = ($_REQUEST["opt-auto-links"] == "enable");
				$info["opt-collapse"] = ($_REQUEST["opt-collapse"] == "enable");
				$info["opt-gutter"] = ($_REQUEST["opt-gutter"] == "enable");
				$info["opt-first-line"] = (int)$_REQUEST["opt-first-line"];
				$info["opt-highlight-prefix"] = Str::ReplaceNewlines("", $_REQUEST["opt-highlight-prefix"]);
				$info["opt-html-script"] = ($_REQUEST["opt-html-script"] == "enable");
				$info["opt-smart-tabs"] = ($_REQUEST["opt-smart-tabs"] == "enable");
				$info["opt-tab-size"] = (int)$_REQUEST["opt-tab-size"];
				$info["opt-toolbar"] = ($_REQUEST["opt-toolbar"] == "enable");
				$info["opt-wrap-lines"] = ($_REQUEST["opt-wrap-lines"] == "enable");

				if (!$parent->SaveShortcode($info))  BB_PropertyFormError("Unable to save the shortcode.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Options saved.")); ?></div>
<script type="text/javascript">
CloseProperties();
ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_configure_submit");
			}
			else if ($_REQUEST["sc_action"] == "bb_syntaxhighlight_configure")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_syntaxhighlight_configure");

				$desc = "<br />";
				$desc .= $parent->CreateShortcodePropertiesLink(BB_Translate("Upload/Transfer Code"), "bb_syntaxhighlight_upload");
				$desc .= " | " . $parent->CreateShortcodePropertiesLink(BB_Translate("Edit"), "bb_syntaxhighlight_edit");

				$types = array();
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts");
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".js" && substr($name, 0, 7) == "shBrush")
					{
						$type = substr($name, 7, $pos - 7);
						$aliases = $this->GetAliases(ROOT_PATH . "/" . SUPPORT_PATH . "/syntaxhighlighter/scripts/" . $name);
						$types[$type] = $type . " (" . implode(", ", $aliases) . ")";
					}
				}

				$options = array(
					"title" => "Configure Syntax Highlighter",
					"desc" => "Configure the syntax highlighter display options or upload/transfer/edit code.",
					"htmldesc" => $desc,
					"bb_action" => $_REQUEST["bb_action"],
					"hidden" => array(
						"sid" => $parent->GetSID(),
						"sc_action" => "bb_syntaxhighlight_configure_submit"
					),
					"fields" => array(
						array(
							"title" => "Type",
							"type" => "select",
							"name" => "type",
							"options" => $types,
							"select" => $info["type"],
							"desc" => "The syntax highlighter (brush) to use for the code."
						),
						array(
							"title" => "Detect Links",
							"type" => "select",
							"name" => "opt-auto-links",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-auto-links"] ? "enable" : "disable"),
							"desc" => "Automatically detect hyperlinks to let users click them."
						),
						array(
							"title" => "Initially Collapsed",
							"type" => "select",
							"name" => "opt-collapse",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-collapse"] ? "enable" : "disable"),
							"desc" => "Collapsed code is initially hidden until users click on an expansion link."
						),
						array(
							"title" => "Gutter",
							"type" => "select",
							"name" => "opt-gutter",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-gutter"] ? "enable" : "disable"),
							"desc" => "The gutter shows a vertical line and line numbers."
						),
						array(
							"title" => "First Line Number",
							"type" => "text",
							"name" => "opt-first-line",
							"value" => $info["opt-first-line"],
							"desc" => "The first line number to display in the gutter."
						),
						array(
							"title" => "Highlight Prefix",
							"type" => "text",
							"name" => "opt-highlight-prefix",
							"value" => $info["opt-highlight-prefix"],
							"desc" => "Highlight specific lines with a different background color by using a unique line prefix."
						),
						array(
							"title" => "HTML Script",
							"type" => "select",
							"name" => "opt-html-script",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-html-script"] ? "enable" : "disable"),
							"desc" => "The code is a mixture of HTML and some other language (e.g. PHP)."
						),
						array(
							"title" => "Smart Tabs",
							"type" => "select",
							"name" => "opt-smart-tabs",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-smart-tabs"] ? "enable" : "disable"),
							"desc" => "Smart tabs attempts to keep code aligned when converting tabs to spaces."
						),
						array(
							"title" => "Tab Size",
							"type" => "text",
							"name" => "opt-tab-size",
							"value" => $info["opt-tab-size"],
							"desc" => "Specifies how many spaces to convert each tab to."
						),
						array(
							"title" => "Toolbar",
							"type" => "select",
							"name" => "opt-toolbar",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-toolbar"] ? "enable" : "disable"),
							"desc" => "A toolbar displays when hovering the mouse over the code."
						),
						array(
							"title" => "Wrap Lines",
							"type" => "select",
							"name" => "opt-wrap-lines",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-wrap-lines"] ? "enable" : "disable"),
							"desc" => "Automatically wrap the lines so the code is entirely visible on the page."
						)
					),
					"submit" => "Save",
					"focus" => true
				);

				BB_RunPluginActionInfo("bb_content_shortcode_bb_syntaxhighlight_configure_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_content_shortcode_bb_syntaxhighlight_configure");
			}
		}
	}
?>