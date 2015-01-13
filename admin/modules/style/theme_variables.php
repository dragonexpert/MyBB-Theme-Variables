<?php
if(!defined("IN_MYBB"))    
{
    die("Direct access not allowed.");
}

$page->output_header("Theme Variable Manager");

$page->add_breadcrumb_item("Themes", "index.php?module=style-theme_vars");
$baseurl = "index.php?module=style-theme_vars";
$table = new TABLE;
if($mybb->input['tid'] && !$mybb->input['action'])
{
    $tid = (int) $mybb->input['tid'];
    $page->add_breadcrumb_item("Variable Management", $basurl . "&amp;tid=$tid");
    $query = $db->simple_select("theme_variables", "*", "tid=$tid", array("order_by" => "name", "order_dir" => "ASC"));
    $table->construct_header("Name");
    $table->construct_header("Syntax");
    $table->construct_header("Replacement");
    $table->construct_header("Controls", array("colspan" => 3));
    $table->construct_row();
    if(!$db->num_rows($query))
    {
        $table->construct_cell("There are no results to display.", array("colspan" => 5, "style" => "text-align:center"));
    }
    while($stylevar = $db->fetch_array($query))
    {
        $table->construct_cell($stylevar['name']);
        $table->construct_cell("@{" . $stylevar['unique_name'] . "}");
        $table->construct_cell(htmlspecialchars($stylevar['content']));
        $table->construct_cell("<a href='" . $baseurl . "&amp;action=edit&amp;vid=" . $stylevar['vid'] . "'>Edit</a>");
        $table->construct_cell("<a href='" . $baseurl . "&amp;action=delete&amp;vid=" . $stylevar['vid'] . "'>Delete</a>");
        $table->construct_row();
    }
    $table->output("Available Variables");
    $form = new DefaultForm("index.php", "get");
    $form_container = new FormContainer("");
    $form_container->output_row("", "", $form->generate_hidden_field("module", "style-theme_vars"));
    $form_container->output_row("", "", $form->generate_hidden_field("action", "create"));
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Create Variable")));
    $form->end();
}
if(!$mybb->input['action'] && !$mybb->input['tid'])
{
    // No action so show a list of themes
    $themequery = $db->simple_select("themes", "tid,name,allowedgroups");
    $table->construct_header("Theme");
    $table->construct_header("Allowed Groups");
    $table->construct_header("Manage", array("colspan" => 3));
    $table->construct_row();
    while($theme = $db->fetch_array($themequery))
    {
        $table->construct_cell($theme['name']);
        $table->construct_cell($theme['allowedgroups']);
        $table->construct_cell("<a href='" . $baseurl. "&amp;tid=" . $theme['tid']. "'>Manage Variables</a>");
        $table->construct_cell("<a href='index.php?module=style-themes&amp;action=edit&amp;tid=" . $theme['tid'] . "'>Edit Properties</a>");
        $nameclause = $db->escape_string($theme['name'] . " Templates");
        $templatequery = $db->simple_select("templatesets", "*", "title='$nameclause'");
        $template = $db->fetch_array($templatequery);
        if(!$template['sid'])
        {
            // assign a default value of 1.
            $template['sid'] = 1;
        }
        $table->construct_cell("<a href='index.php?module=style-templates&amp;sid=" . $template['sid'] . "'>Edit Templates</a>");
        $table->construct_row();
    }
    $table->output("Themes");
}

if($mybb->input['action'] == "edit" && $mybb->input['vid'])
{
    $vid = (int) $mybb->get_input("vid");
    // Validate if it is a real variable
    $variablequery = $db->simple_select("theme_variables", "*", "vid=$vid");
    $info = $db->fetch_array($variablequery);
    if(!$info['vid'])
    {
        flash_message("The variable doesn't exist.", "failure");
        admin_redirect($baseurl);
    }
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        $updated_variable = array(
        "tid" => (int) $mybb->get_input("tid"),
        "name" => $db->escape_string($mybb->get_input("name")),
        "active" => (int) $mybb->get_input("active"),
        "usergroups" => $db->escape_string($mybb->get_input("usergroups")),
        "forums" => $db->escape_string($mybb->get_input("forums")),
        "content" => $db->escape_string($mybb->get_input("content"))
        );
        $db->update_query("theme_variables", $updated_variable, "vid=$vid");
        flash_message("The variable has been updated.", "success");
        admin_redirect($baseurl . "&tid=" . $updated_variable['tid']);
    }
    else
    {
        $themequery = $db->simple_select("themes", "*");
        while($result = $db->fetch_array($themequery))
        {
            $themearray[$result['tid']] = $result['name'];
        }
        // Usergroup query because no All option
        $usergroups['-1'] = "All";
        $usergroupquery = $db->simple_select("usergroups", "gid,title");
        while($group = $db->fetch_array($usergroupquery))
        {
            $usergroups[$group['gid']] = $group['title'];
        }
        $form = new DefaultForm("index.php?module=style-theme_vars&amp;action=edit&amp;vid=$vid", "post");
        $form_container = new FormContainer("Edit");
        $form_container->output_row("Name <em>*</em>", "Enter the readable name of the variable.", $form->generate_text_box("name", $info['name']), "name");
        $form_container->output_row("Theme ", "Which theme the variable belongs to.", $form->generate_select_box("tid", $themearray, $info['tid']), "tid");
        $form_container->output_row("Active", "If no, this variable will not be replaced.", $form->generate_select_box("active", array("1" => "Yes", "0" => "No"), $info['active']), "active");
        $form_container->output_row("Forums", "Select which forums this variable will work in.", $form->generate_forum_select("forums", $info['forums'], array("main_option" => "All", "multiple" => "multiple")), "forums");
        $form_container->output_row("Usergroups", "Select which usergroups will see the content.", $form->generate_select_box("usergroups", $usergroups, $info['usergroups'], array("multiple" => "multiple")), "usergroups");
        $form_container->output_row("Content <em>*</em>", "This is the content users will see.", $form->generate_text_area("content", $info['content']), "content");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Update Variable")));
        $form->end();
    }
}

if($mybb->input['action'] == "delete" && $mybb->input['vid'])
{
    $vid = (int) $mybb->get_input("vid");
    // Verify the variable exists
    $query = $db->simple_select("theme_variables", "*", "vid=$vid");
    $info = $db->fetch_array($query);
    if(!$info['vid'])
    {
        flash_message("Invalid variable specified.", "error");
        admin_redirect($baseurl);
    }
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        if($mybb->input['confirm'] == 1)
        {
            $db->delete_query("theme_variables", "vid=$vid");
            flash_message("The variable has been deleted.", "success");
            admin_redirect($baseurl . "&tid=" . $info['tid']);
        }
        else
        {
            admin_redirect($baseurl . "&tid=" . $info['tid']);
        }
    }
    $form = new DefaultForm("index.php?module=style-theme_vars&action=delete&vid=$vid", "delete");
    $form_container = new FormContainer("Delete Variable");
    $form_container->output_row("Delete this variable?", "", $form->generate_select_box("confirm", array("0" => "No", "1" => "Yes"), 0));
    $form_container->end();
    $form->output_submit_wrapper(array($form->generate_submit_button("Go")));
    $form->end();
}

if($mybb->input['action'] == "create")
{
    $page->add_breadcrumb_item("Create Variable", $baseurl . "&amp;action=create");
    if($mybb->request_method == "post" && verify_post_check($mybb->input['my_post_key']))
    {
        $new_variable = array(
        "tid" => (int) $mybb->get_input("tid"),
        "unique_name" => $db->escape_string($mybb->get_input("unique_name")),
        "name" => $db->escape_string($mybb->get_input("name")),
        "active" => (int) $mybb->get_input("active"),
        "usergroups" => $db->escape_string($mybb->get_input("usergroups")),
        "forums" => $db->escape_string($mybb->get_input("forums")),
        "content" => $db->escape_string($mybb->get_input("content"))
        );
        $db->insert_query("theme_variables", $new_variable);
        flash_message("The variable has been created.", "success");
        admin_redirect($baseurl . "&tid=" . $new_variable['tid']);
    }
    else
    {
        $themequery = $db->simple_select("themes", "*");
        while($result = $db->fetch_array($themequery))
        {
            $themearray[$result['tid']] = $result['name'];
        }
        // Usergroup query because no All option
        $usergroups['-1'] = "All";
        $usergroupquery = $db->simple_select("usergroups", "gid,title");
        while($group = $db->fetch_array($usergroupquery))
        {
            $usergroups[$group['gid']] = $group['title'];
        }
        $form = new DefaultForm("index.php?module=style-theme_vars&amp;action=create", "post");
        $form_container = new FormContainer("Create");
        $form_container->output_row("Unique Name <em>*</em>", "Enter a unique name for the variable.", $form->generate_text_box("unique_name", $mybb->get_input("unique_name")), "unique_name");
        $form_container->output_row("Name <em>*</em>", "Enter the readable name of the variable.", $form->generate_text_box("name", $mybb->get_input("name")), "name");
        $form_container->output_row("Theme ", "Which theme the variable belongs to.", $form->generate_select_box("tid", $themearray, $mybb->get_input("tid")), "tid");
        $form_container->output_row("Active", "If no, this variable will not be replaced.", $form->generate_select_box("active", array("1" => "Yes", "0" => "No"), $mybb->get_input("active")), "active");
        $form_container->output_row("Forums", "Select which forums this variable will work in.", $form->generate_forum_select("forums", -1, array("main_option" => "All", "multiple" => "multiple")), "forums");
        $form_container->output_row("Usergroups", "Select which usergroups will see the content.", $form->generate_select_box("usergroups", $usergroups, -1, array("multiple" => "multiple")), "usergroups");
        $form_container->output_row("Content <em>*</em>", "This is the content users will see.", $form->generate_text_area("content", ""), "content");
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Create Variable")));
        $form->end();
    }
}
$page->output_footer();

?>