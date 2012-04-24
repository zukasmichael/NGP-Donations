<?php

wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-core', dirname(dirname(dirname(dirname(__FILE__)))).'/wp-includes/js/jquery/ui.core.js');
wp_enqueue_script('jquery-ui-widget', dirname(dirname(dirname(dirname(__FILE__)))).'/wp-includes/js/jquery/ui.widget.js');
wp_enqueue_script('jquery-ui-mouse', dirname(dirname(dirname(dirname(__FILE__)))).'/wp-includes/js/jquery/ui.mouse.js');
wp_enqueue_script('jquery-ui-sortable', dirname(dirname(dirname(dirname(__FILE__)))).'/wp-includes/js/jquery/ui.sortable.js');
wp_enqueue_script('ngp_admin', plugins_url('/ngp_js.js', __FILE__));
wp_enqueue_style('ngp_admin', plugins_url('/ngp_admin.css', __FILE__));

/*
 * Manage the NGP Forms
 *
 * @global object $wpdb
 * @global object $ngp
 * @returns bool
 */
function ngp_manage_forms() {
	if(isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'save_captcha_info':
				if(wp_verify_nonce($_POST['ngp_catch_info'], 'ngp_nonce_field')) {
					ngp_save_api_info($_POST);
				}
				ngp_admin_index();
				break;
			case 'edit_form':
				if(isset($_GET['id'])) {
					if(wp_verify_nonce($_POST['ngp_save'], 'ngp_nonce_field') && $_POST['ngp_id']==$_GET['id']) {
						ngp_save_form($_POST);
					}
					ngp_edit_form($_GET['id']);
				} else {
					echo 'Error.';
				}
				break;
			case 'new_form':
				if(wp_verify_nonce($_POST['ngp_save'], 'ngp_nonce_field')) {
					$id = ngp_save_form($_POST);
				}
				if(isset($id)) {
					ngp_edit_form($id);
				} else {
					ngp_edit_form();
				}
				break;
		}
	} else {
		ngp_admin_index();
	}
	return true;
}

function ngp_save_api_info($data) {
	if(isset($data['public_key']) && !empty($data['public_key'])) {
		update_option('ngp_recaptch_public_key', $data['public_key']);
	}
	
	if(isset($data['private_key']) && !empty($data['private_key'])) {
		update_option('ngp_recaptch_private_key', $data['private_key']);
	}
}

function ngp_admin_index() {
	// global $wpdb, $psc;
	
	// $forms = $wpdb->get_results('SELECT id, name, slug, thanks_url, captcha, default_category, default_status FROM '.$psc->forms);
	
	echo ngp_admin_index_header();
	
	echo '<tbody id="the-list">';
	foreach($forms as $form) {
		// $default_category = $wpdb->get_results('SELECT term_id, name FROM '.$wpdb->prefix.'terms WHERE term_id="'.$form->default_category.'"');
		echo '<tr id="post-'.$form->id.'" class="alternate author-self status-publish format-default" valign="top">
				<td class="post-title page-title column-title">
					<strong>
						<a class="row-title" href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=edit_form&id='.$form->id.'" title="Edit &#8220;'.stripslashes(str_replace("\"", "'", $form->name)).'&#8221;">'.
							stripslashes($form->name).
						'</a>
					</strong>
					<div class="row-actions">
						<span class="edit">
							<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=edit_form&id='.$form->id.'" title="Edit this item">Edit</a> | 
						</span>
						<span class="trash"><a href="'.wp_nonce_url('/wp-admin/admin.php?page=publicly-submitted-content/admin&id='.$form->id.'&action=delete_form', 'delete_form').'">Trash</a></span>
					</div>
				</td>
				<td class="form-id column-title">
				'.$form->id.'
				</td>
				<td class="column-status">
					'.$form->default_status.'
				</td>
				<td class="column-category">
					';
					if(count($default_category)==0)
						echo 'No default category.';
					else
						echo $default_category[0]->name;
				echo '
				</td>
				<td class="column-captcha">
					';
					if($form->captcha==0) {
						echo 'no';
					} else {
						echo 'yes';
					}
				echo '
				</td>
				<td class="column-thanksurl">
					<a href="'.get_bloginfo('siteurl').$form->thanks_url.'">'.$form->thanks_url.'</a>
				</td>
			</tr>
					';
	}
	echo '</tbody></table>
		<h2>Use re:Captcha</h2>
		<form name="post" action="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=save_captcha_info'.$form_id.'" method="post" id="catpchaInfoForm" class="widget">';
		wp_nonce_field('ngp_nonce_field', 'ngp_catch_info');
		echo '<div class="text">
				<label for="public_key">Public Key</label>
				<input type="text" name="public_key" id="api_key" value="'.get_option('ngp_recaptch_public_key').'" />
			</div>
			<div class="text">
				<label for="private_key">Private Key</label>
				<input type="text" name="private_key" id="private_key" value="'.get_option('ngp_recaptch_private_key').'" />
			</div>
			<div class="submit">
				<input type="submit" value="Save Captcha Info" />
			</div>
		</form>
	</div>';
}

/*
 * Save a PSC Form
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function ngp_save_form($data) {
	global $wpdb, $psc;
	
	$old_fields = $data['data'];
	$fields = array();
	foreach($old_fields as $field) {
		$fields[] = $field;
	}
	
	foreach($fields as $key => $value) {
		if(empty($value['options']) || !in_array($value['type'], array('select', 'multiselect', 'radio', 'checkbox'))) {
			unset($fields[$key]['options']);
		}
		
		if(isset($fields[$key]['options'])) {
			$fields[$key]['options'] = explode(',', $fields[$key]['options']);
			$fields[$key]['options'] = array_map('trim', $fields[$key]['options']);
		}
		
		$fields[$key]['slug'] = 'ngp_'.str_replace('ngp_', '', $value['slug']);
		if(isset($value['required']) && $value['required']=='on') {
			$fields[$key]['required'] = 'true';
		} else {
			$fields[$key]['required'] = 'false';
		}
		
		if(isset($value['maps_as']) && $value['maps_as']=='on') {
			$fields[$key]['maps_as'] = 'content';
		} else {
		}
	}
	
 	if(isset($data['captcha']) && $data['captcha']=='on') {
		// nothing
		$data['captcha'] = 1;
	} else {
		$data['captcha'] = 0;
	}
	
	if(isset($data['thanks_url']) && !empty($data['thanks_url'])) {
		// nothing
	} else {
		$data['thanks_url'] = null;
	}
	
	// echo '<br /><br />';
	// var_dump('SET data=\''.mysql_escape_string(serialize($fields)).'\', name="'.mysql_escape_string($data['title']).'", slug="'.mysql_escape_string($data['slug']).'", thanks_url="'.mysql_escape_string($data['thanks_url']).'", default_status="'.mysql_escape_string($data['default_status']).'", default_category="'.mysql_escape_string($data['default_category']).'", captcha="'.mysql_escape_string($data['captcha']).'"');
	
	if(isset($data['ngp_id'])) {
		// $wpdb->query('UPDATE '.$psc->forms.' SET data=\''.serialize($fields).'\', name="'.$data['title'].'", slug="'.$data['slug'].'", thanks_url="'.$data['thanks_url'].'", default_status="'.$data['default_status'].'", default_category="'.$data['default_category'].'", captcha="'.$data['captcha'].'" WHERE id="'.$data['ngp_id'].'"');
		return true;
	} else {
		// $insert_array = array('data' => serialize($fields), 'name' => $data['title'], 'slug' => $data['slug'], 'thanks_url' => $data['thanks_url'], 'default_status' => $data['default_status'], 'default_category' => $data['default_category'], 'captcha' => $data['catpcha']);
		// $wpdb->insert($psc->forms, $insert_array);
		// return $wpdb->insert_id;
	}
}

/*
 * View/edit a PSC Form
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function ngp_edit_form($form_id=null) {
	global $wpdb, $psc;
	
	if($form_id) {
		// $form = $wpdb->get_results('SELECT * FROM '.$psc->forms.' WHERE id="'.$form_id.'"');
	}
	
	// $categories = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'terms');
	$categories = array();
	$stati = array('pending', 'draft', 'published');
	$field_types = array('text', 'textarea', 'hidden', 'select', 'multiselect', 'radio', 'checkbox', 'file');
	
	if(isset($form) && count($form)===0) {
		echo 'Sorry, but a form with that ID does not exist.';
	} else {
		if(isset($form[0]->data)) {
			$fields = unserialize($form[0]->data);
		} else {
			$form = array(
				(object) array(
					'name' => '',
					'slug' => '',
					'default_category' => '',
					'default_status' => 'pending',
					'thanks_url' => '',
					'captcha' => 0
				)
			);
			$fields = array();
		}
		// Edit the form!!
		echo '
		<div class="wrap">
			<div id="icon-edit" class="icon32 icon32-posts-post"><br /></div>
			<h2>Edit Form</h2>';
			if($form_id) {
				echo '<form name="post" action="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=edit_form&id='.$form_id.'" method="post" id="post">';
			} else {
				echo '<form name="post" action="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=new_form" method="post" id="post">';
			}
			wp_nonce_field('ngp_nonce_field', 'ngp_save');
			echo '
				<div id="post-body">
					<div id="post-body-content">
						<div id="titlediv">';
						if($form_id) { echo '<input type="hidden" name="ngp_id" value="'.$form_id.'" />'; }
						echo '<div id="titlewrap">
								<label class="hide-if-no-js" style="visibility:hidden" id="title-prompt-text" for="title">Enter name here</label>';
								if($form_id) {
									echo '<input type="text" name="title" size="30" tabindex="1" value="'.$form[0]->name.'" id="title" autocomplete="off" />';
								} else {
									echo '<input type="text" name="title" size="30" tabindex="1" value="" id="title" autocomplete="off" />';
								}
							echo '</div>
							<div class="inside">
								<div id="edit-slug-box">
									<strong>Slug:</strong> ';
									if($form_id) {
										echo '<input type="hidden" id="form_slug" name="slug" value="'.$form[0]->slug.'" /><span id="sample-permalink">'.$form[0]->slug.'</span>';
									} else {
										echo '<input type="hidden" id="form_slug" name="slug" value="" /><span id="sample-permalink"></span>';
									}
								echo '</div>
							</div>
						</div>
						<div id="form_edit_options" class="postbox fieldItem">
							<h3 class="hndle"><span>Form Options</span></h3>
							<div class="inside">
								<div class="select">
									<label for="default_category">Default Category</label>
									<select name="default_category" id="default_category">';
									foreach($categories as $category) {
										echo '<option value="'.$category->term_id.'"';
										if($form_id) {
											if($category->term_id==$form[0]->default_category) { echo ' selected="selected"'; }
										} else {
											if($category->slug=='publicly_submitted_content') { echo ' selected="selected"'; }
										}
										echo '>'.$category->name.'</option>'."\r\n";
									}
								
									echo '</select>
								</div>
								<div class="select">
									<label for="default_status">Default Status</label>
									<select name="default_status" id="default_status">';
									foreach($stati as $state) {
										echo '<option value="'.$state.'"';
										if($form_id) {
											if($state==$form[0]->default_status) { echo ' selected="selected"'; }
										}
										echo '>'.ucfirst($state).'</option>'."\r\n";
									}
								
									echo '</select>
								</div>
								<div class="checkbox">
									<input type="checkbox"';
									if($form_id) {
										if($form[0]->captcha==1) { echo ' checked'; }
									}
									echo ' name="captcha" id="captcha_option" />';
									echo '<label for="captcha_option">Use Captcha?</label>
								</div>';
								echo '<div id="ngp_catcha_info" style="display:none">
									<p>You must enter a re:Captcha public &amp; private keys in order to utilize captcha.</p>
								</div>
								<input type="hidden" name="public_key" id="api_key" value="'.get_option('ngp_recaptch_public_key').'" />
								<input type="hidden" name="private_key" id="private_key" value="'.get_option('ngp_recaptch_private_key').'" />
								<div class="text">
									<label for="thanks_url">Thanks Redirect <span class="small">(leave blank to not use a redirect or if the redirect is causing a blank page.)</span></label>
									<div class="thanksLink">
										'.get_bloginfo('siteurl').'<input type="text" name="thanks_url" id="thanks_url" value="';
										if($form_id) { echo $form[0]->thanks_url; }
										echo '" />
									</div>
								</div>
							</div>
							<div id="major-publishing-actions">
							<div id="delete-action">
								<a class="submitdelete deletion" href="#">Delete Form</a>
							</div>
							
							<div id="publishing-action">
								<input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="Save Form">
							</div>
							<div class="clear"></div>
							</div>
						</div>
						';
							
						echo '</div>
						<div id="normal-sortables" class="meta-box-sortables ui-sortable fieldItems">
							';
						if(!$form_id) {
							$key = 0;
							echo '<div id="field'.$key.'item" class="postbox fieldItem">
							<div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>[Label]</span></h3>
							<div class="inside" style="display:block;">
								<a href="#" class="deleteFieldItem">Delete Field</a>
								<div class="text"><label for="field'.$key.'label">Label</label><input type="text" name="data['.$key.'][label]" class="fieldlabel" id="field'.$key.'label" value="" /></div>';
								echo '<div class="text"><label for="field'.$key.'slug">Slug/ID/Name</label><input type="text" name="data['.$key.'][slug]" class="slugify" id="field'.$key.'slug" value="" /></div>';
								echo '<div class="select"><label for="field'.$key.'type">Type</label><select name="data['.$key.'][type]" class="fieldtype" id="field'.$key.'type">';
									foreach($field_types as $field_type) {
										echo '<option value="'.$field_type.'">';
										if($field_type=='file') { echo 'Image'; } else { echo ucfirst($field_type); }
										echo '</option>';
									}
								echo '</select></div>';
								echo '<div class="text options" style="display:none;"><label for="field'.$key.'options">Options</label>
								<input type="text" name="data['.$key.'][options]" id="field'.$key.'options" value="" />
								<p class="small">(comma separated list of options for select, multiselect, checkbox, and radio types)</p></div>';
								echo '<div class="checkbox"><input type="checkbox" name="data['.$key.'][required]" id="field'.$key.'required" /><label for="field'.$key.'required">Required</label></div>';
								echo '<div class="checkbox"><input type="checkbox" name="data['.$key.'][maps_as]" id="field'.$key.'maps_as" /><label for="field'.$key.'maps_as">Use this as the "post content"</label></div>
								</div>
							</div>';
						} else {
							foreach($fields as $key => $field) {
								echo '<div id="field'.$key.'item" class="postbox fieldItem closed">
								<div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>'.$field['label'].'</span></h3>
								<div class="inside">
									<a href="#" class="deleteFieldItem">Delete Field</a>
									<div class="text"><label for="field'.$key.'label">Label</label><input type="text" name="data['.$key.'][label]" class="fieldlabel" id="field'.$key.'label" value="'.$field['label'].'" /></div>';
									echo '<div class="text"><label for="field'.$key.'slug">Slug/ID/Name</label><input type="text" name="data['.$key.'][slug]" class="slugify" id="field'.$key.'slug" value="'.str_replace('ngp_', '', $field['slug']).'" /></div>';
									echo '<div class="select"><label for="field'.$key.'type">Type</label><select name="data['.$key.'][type]" class="fieldtype" id="field'.$key.'type">';
										foreach($field_types as $field_type) {
											echo '<option value="'.$field_type.'"';
											if($field_type==$field['type']) { echo ' selected="selected"'; }
											echo '>';
											if($field_type=='file') { echo 'Image'; } else { echo ucfirst($field_type); }
											echo '</option>';
										}
									echo '</select></div>';
									echo '<div class="text options"';
									if(!in_array($field['type'], array('select', 'multiselect', 'radio', 'checkbox'))) {
										echo ' style="display:none;"';
									}
									echo '><label for="field'.$key.'options">Options</label>
										<input type="text" name="data['.$key.'][options]" id="field'.$key.'options" value="';
									if(isset($field['options']) && !empty($field['options'])) { echo implode(',', $field['options']); }
									echo '" />
										<p class="small">(comma separated list of options for select, multiselect, checkbox, and radio types)</p>
									</div>';
									echo '<div class="checkbox"><input type="checkbox" name="data['.$key.'][required]" id="field'.$key.'required"';
									if($field['required']=='true') {
										echo ' checked';
									}
									echo ' /><label for="field'.$key.'required">Required</label></div>';
									echo '<div class="checkbox"><input type="checkbox"';
									if(isset($field['maps_as']) && $field['maps_as']=='content') {
										echo ' checked';
									}
									echo ' name="data['.$key.'][maps_as]" id="field'.$key.'maps_as" /><label for="field'.$key.'maps_as">Use this as the "post content"</label></div>
									</div>
								</div>';
							}
						}
						echo '
						</div><input type="hidden" id="psckeycount" value="'.($key+1).'" />
						<a id="add_form_field" class="preview button" href="#">Add form field</a>
					</div>
				</div>
			</form>
		</div>';
	}
}

function ngp_admin_index_header() {
	return '<div class="wrap"><div id="icon-edit" class="icon32 icon32-posts-post"><br /></div> 
	<h2>Forms <a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin&action=new_form" class="button add-new-h2">Add New</a></h2>
	<table class="wp-list-table widefat fixed posts" cellspacing="0">
		<thead>
			<tr> 
				<!--<th scope="col" id="cb" class="manage-column column-cb check-column"  style=""><input type="checkbox" /></th>-->
				<th scope="col" id="title" class="manage-column column-title sortable desc"  style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin/?orderby=title&#038;order=asc">
						<span>Title</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" id="status" class="manage-column column-title sortable" style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin/?orderby=id&#038;order=asc">
						<span>ID</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" id="status" class="manage-column column-title sortable" style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin/?orderby=default_status&#038;order=asc">
						<span>Default Status</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" id="category" class="manage-column column-title sortable"  style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin/?orderby=default_category&#038;order=asc">
						<span>Default Category</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" class="manage-column column-tags" style="">Captcha</th>
				<th scope="col" class="manage-column column-tags" style="">Thanks URL</th>
			</tr>
		</thead>
		<tfoot> 
			<tr>
				<!--<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>-->
				<th scope="col"  class="manage-column column-title sortable desc" style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/admin.php?page=publicly-submitted-content/admin/?orderby=title&#038;order=asc">
						<span>Title</span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<!--<th scope="col"  class="manage-column column-author sortable desc"  style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/edit.php?orderby=author&#038;order=asc">
						<span>Author</span><span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" class="manage-column column-categories" style="">Categories</th>
				<th scope="col" class="manage-column column-tags" style="">Tags</th>
				<th scope="col"  class="manage-column column-comments num sortable desc" style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/edit.php?orderby=comment_count&#038;order=asc">
						<span><div class="vers">
							<img alt="Comments" src="'.get_bloginfo('siteurl').'/wp-admin/images/comment-grey-bubble.png" />
						</div></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col"  class="manage-column column-date sortable asc"  style="">
					<a href="'.get_bloginfo('siteurl').'/wp-admin/edit.php?orderby=date&#038;order=desc">
						<span>Date</span><span class="sorting-indicator"></span>
					</a>
				</th>-->
			</tr>
		</tfoot>';
}