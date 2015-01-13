<?php

// Hooks
$plugins->add_hook("admin_style_menu", "theme_vars_admin_style_menu");
$plugins->add_hook("admin_style_action_handler", "theme_vars_admin_style_action_handler");
$plugins->add_hook("admin_style_permissions", "theme_vars_admin_style_permissions");
$plugins->add_hook("pre_output_page", "theme_vars_pre_output_page", 100); /* Use the priority so it executes last. */

function theme_vars_info()
{
    return array(
    "name" => "Theme Variables",
    "description" => "Lets you create variables to improve your themes.",
    "author" => "Mark Janssen",
    "codename" => "theme_vars",
    "version" => "1.0",
    "compatibility" => "18*"
    );
}

function theme_vars_install()
{
    global $db;
    $charset = $db->build_create_table_collation();
    $db->write_query("CREATE TABLE " . TABLE_PREFIX . "theme_variables (
    vid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tid INT NOT NULL DEFAULT 1,
    unique_name TEXT,
    name TEXT,
    active SMALLINT NOT NULL DEFAULT 1,
    usergroups TEXT,
    forums TEXT,
    content TEXT
    ) ENGINE=Innodb {$charset}");
}

function theme_vars_is_installed()
{
    global $db;
    if($db->table_exists("theme_variables"))
    {
        return TRUE;
    }
    return FALSE;
}

function theme_vars_activate()
{
    // Nothing here
}

function theme_vars_deactivate()
{
    // Nothing here
}

function theme_vars_uninstall()
{
    global $db;
    if($db->table_exists("theme_variables"))
    {
        $db->drop_table("theme_variables");
    }
}

function theme_vars_admin_style_menu(&$sub_menu)
{
    $key = count($sub_menu) *10 + 10;
    $sub_menu[$key] = array(
    "id" => "theme_vars",
    "title" => "Theme Variables",
    "link" => "index.php?module=style-theme_vars"
    );
}

function theme_vars_admin_style_action_handler(&$actions)
{
    $actions['theme_vars'] = array(
    "active" => "theme_vars",
    "file" => "theme_variables.php"
    );
}

function theme_vars_admin_style_permissions(&$admin_permissions)
{
       $admin_permissions['theme_vars'] = "Can Manage Theme Variables";
}

function theme_vars_pre_output_page(&$contents)
{
    global $mybb, $db;
    $themeid = $mybb->user['style'];
    if(!$themeid)
    {
        $defaultthemequery = $db->simple_select("themes", "tid", "def=1");
        $themeid = $db->fetch_field($defaultthemequery, "tid");
    }
    $stylequery = $db->simple_select("theme_variables", "*", "tid=$themeid AND active=1");
    $usergroups = $mybb->user['usergroup'];
    if($mybb->user['additionalgroups'])
    {
        $usergroups .= "," . $mybb->user['usergroups']; 
    }
    $mygroups = explode(",", $usergroups);
    while($stylevar = $db->fetch_array($stylequery))
    {
        $apply = FALSE;
        if($stylevar['usergroups'] == -1)
        {
            $apply = TRUE;
        }
        else
        {
            $ruleusergroups = explode(",", $stylevar['usergroups']);
            foreach($mygroups as $group)
            {
                if(in_array($group, $ruleusergroups))
                {
                    $apply = TRUE;
                }
            }
        }
        if($stylevar['forums'] != -1)
        {
            if(THIS_SCRIPT == "forumdisplay.php")
            {
                $fid = (int) $mybb->get_input("fid");
                $allowedforums = explode(",", $stylevar['forums']);
                if(in_array($fid, $allowedforums))
                {
                    $apply = TRUE;
                }
                else
                {
                    $apply = FALSE;
                }
            }
            else
            {
                $apply = FALSE;
            }
        }
        if($apply)
        {
                $contents = str_replace("@{" . $stylevar['unique_name'] . "}", $stylevar['content'], $contents);
        }
        else
        {
            $contents = str_replace("@{" . $stylevar['unique_name'] ."}", "", $contents);
        }
    }
}
?>
