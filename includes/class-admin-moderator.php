<?php

if (!class_exists('Admin_Moderator')) {
    class Admin_Moderator
    {
        public $flag_icon =  '<span class="dashicons dashicons-flag"></span>';
        public function __construct()
        {
            $this->flag_icon = apply_filters('buddypress_moderator_flag_icon', $this->flag_icon);
        }
       
        public function set_hooks()
        {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            //register moderator role
            add_action('init', array( $this, 'registger_moderator' ));
            //create admin page
            add_action('admin_menu', array($this, 'register_moderator_page'));
            //AJAX actions
            add_action('wp_ajax_moderate_action', array($this, 'moderate_action'));
        }
       
        /*
         * enqueue admin facing scripts
         */
        public function enqueue_admin_scripts($hook_suffix)
        {
            //allow filter for others to change url to fontawesome but icons will still need to be the same if they
            //switch versions
            wp_enqueue_style('dashicons');
            wp_enqueue_style('buddypress-moderator-admin-css', plugins_url().'/buddypress-moderator/css/admin.css');
            
            //add tablesorter to admin page
            if ($hook_suffix == 'toplevel_page_moderate') {
                wp_enqueue_script("buddypress_moderator_tablesorter", plugins_url()."/buddypress-moderator/js/tablesorter.min.js", ['jquery']);
                wp_enqueue_script("buddypress_moderator_admin", plugins_url()."/buddypress-moderator/js/admin.js", ['jquery','buddypress_moderator_tablesorter']);
                
                //localize for ajax calls
                wp_localize_script(
                    'buddypress_moderator_admin',
                    'admin_object',
                    array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce'    => wp_create_nonce('moderation_nonce'),
                    )
                );
            }
        }
        public function registger_moderator()
        {
            $result = add_role(
                    'moderator_basic',
                    'Moderator',
                    array(
                        // "create_sites" => true,
                                                // "delete_sites" => true,
                                                // "manage_network" => true,
                                                // "manage_sites" => true,
                                                // "manage_network_users" => true,
                                                // "manage_network_plugins" => true,
                                                // "manage_network_themes" => true,
                                                // "manage_network_options" => true,
                                                // "upload_plugins" => true,
                                                // "upload_themes" => true,
                                                // "upgrade_network" => true,
                                                // "setup_network" => true,
                                                // "activate_plugins" => true,
                                                // "create_users" => true,
                                                // "delete_plugins" => true,
                                                // "delete_themes" => true,
                                                // "delete_users" => true,
                                                'edit_files' => true,
                        // "edit_plugins" => true,
                        // "edit_theme_options" => true,
                        // "edit_themes" => true,
                        'edit_users'           => true,
                        // "export" => true,
                        // "import" => true,
                        // "install_plugins" => true,
                        // "install_themes" => true,
                        'list_users'           => true,
                        // "manage_options" => true,
                        'promote_users'        => true,
                        'remove_users'         => true,
                        // "switch_themes" => true,
                        // "update_core" => true,
                        // "update_plugins" => true,
                        // "update_themes" => true,
                        // "edit_dashboard" => true,
                        // "customize" => true,
                        // "delete_site" => true,
                        "moderate_comments" => true,
                        'manage_categories'    => true,
                        'manage_links'         => true,
                        'edit_others_posts'    => true,
                        'edit_pages'           => true,
                        'edit_others_pages'    => true,
                        'edit_published_pages' => true,
                        // "publish_pages" => true,
                        // "delete_pages" => true,
                        // "delete_others_pages" => true,
                        // "delete_published_pages" => true,
                        // "delete_others_posts" => true,
                        // "delete_private_posts" => true,
                        'edit_private_posts'   => true,
                        'read_private_posts'   => true,
                        // "delete_private_pages" => true,
                        'edit_private_pages'   => true,
                        'read_private_pages'   => true,
                        'unfiltered_html'      => true,
                        'edit_published_posts' => true,
                        'upload_files'         => true,
                        // "publish_posts" => true,
                        // "delete_published_posts" => true,
                        'edit_posts'           => true,
                        // "delete_posts" => true,
                        'read',
                    )
                );
        }
        public function register_moderator_page()
        {
            add_menu_page('Moderate Page', 'Moderate', 'moderate_comments', 'moderate', array($this, 'admin_page'), 'dashicons-flag', 90);
        }
        /*
         * AJAX moderator action
         */
        public function moderate_action()
        {
            //verify nonce
            if (!wp_verify_nonce($_REQUEST['nonce'], 'moderation_nonce')) {
                exit('Invalid AJAX call');
            }
            
            //upadate status
            $id = intval($_REQUEST['id']);
            $status = $_REQUEST['directive'];
            
            update_post_meta($id, 'moderate_status', $status);
            update_post_meta($id, 'moderated_by', get_current_user_id());
            update_post_meta($id, 'moderated_date', date('m/d/Y'));
            wp_send_json_success();
        }
        /*
         * WP_Query and get_posts were returning a post of id 1 that didn't have the meta key
         * that moderator tagged.  I couldn't find in the database how this was generating this so I
         * built this to query the postmeta db directly and return an array of post objects
         * @input $key
         * @input $value
         * @return array of post objects
         */
        private function get_posts_by_meta($key, $value)
        {
            global $wpdb;
            
            $sql = "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='$key' AND meta_value='$value'";
            $res = $wpdb->get_results($sql);
            
            $results = array();
            foreach ($res as $p) {
                $results[] = get_post($p->post_id);
            }
            
            return $results;
        }
        /*
         * render admin page
         */
        public function admin_page()
        {
            //get posts of all possible moderate statuses

            $moderate = $this->get_posts_by_meta('moderate_status', 'moderate');
            $archived_moderated = $this->get_posts_by_meta('moderate_status', 'archived_moderated');
            $archived_released = $this->get_posts_by_meta('moderate_status', 'archived_released');
            
            //create a table with all of them in it, but mark rows with which are not moderate with class hidden and hide them?>
<div class="wrap">
    <h1>Moderate Content</h1>
    <div class="filter">
        <label>Filter: <br>
            <input type="radio" class="view" name="view" value="moderate" checked /> Moderate <br>
            <input type="radio" class="view"  name="view" value="archived_moderated"  /> Archived Moderated <br>
             <input type="radio" class="view"  name="view" value="archived_released"  /> Archived Released
        </label>
    </div>
     <table class="wp-list-table widefat moderator_table">
         <thead>
             <tr>
                 <th>ID</th>
                 <th>Title</th>
                 <th>Author</th>
                 <th>Flagged By</th>
                 <th>Moderated By</th>
                 <th>Content</th>
                 <th>Actions</th>
             </tr>
         </thead>
         <tbody>
           <?php foreach ($moderate as $post) {
                $flagged_by = get_post_meta(intval($post->ID), 'flagged_by', true);
                $flagged_by = ($flagged_by) ? get_user_by('id', $flagged_by) : '';
                $flagged_by = (!empty($flagged_by))? "<a href='".get_edit_user_link($flagged_by->ID)."'>".$flagged_by->display_name.'</a>' : 'Unknown';
                
                if (!empty($flagged_by)) {
                    $flagged_date = get_post_meta(intval($post->ID), 'flagged_date', true);
                  
                    $flagged_by = ($flagged_date) ? $flagged_by." on $flagged_date" : $flagged_by;
                } ?>
             <tr class="moderate">
                 <td><?php echo $post->ID; ?></td>
                 <td><?php echo $post->post_title; ?></td>
                 <td><?php bbp_reply_author_link($post->ID); ?></td>
                 <td><?php echo $flagged_by; ?></td>
                 <td></td>
                 <td><?php echo $post->post_content; ?></td>
                 <td class="actions" data-id="<?php echo $post->ID; ?>">
                     <a class="archived_released" >Archive Release</a> 
                     <a class="archived_moderated" >Archive Moderate</a>
                 </td>
             </tr>
          <?php
            } ?>
             <?php foreach ($archived_moderated as $post) {
                $flagged_by = get_post_meta(intval($post->ID), 'flagged_by', true);
                $flagged_by = ($flagged_by) ? get_user_by('id', $flagged_by) : '';
                $flagged_by = (!empty($flagged_by))? "<a href='".get_edit_user_link($flagged_by->ID)."'>".$flagged_by->display_name.'</a>' : 'Unknown';
                
                $moderated_by = get_post_meta(intval($post->ID), 'moderated_by', true);
                $moderated_by = ($moderated_by) ? get_user_by('id', $moderated_by) : '';
                $moderated_by = (!empty($moderated_by))? "<a href='".get_edit_user_link($moderated_by->ID)."'>".$moderated_by->display_name.'</a>' : 'Unknown';
                
                if (!empty($moderated_by)) {
                    $moderated_date = get_post_meta(intval($post->ID), 'moderated_date', true);
                  
                    $moderated_by = ($moderated_date) ? $moderated_by." on $moderated_date" : $moderated_by;
                } ?>
             <tr class="archived_moderated hide">
                 <td><?php echo $post->ID; ?></td>
                 <td><?php echo $post->post_title; ?></td>
                 <td><?php bbp_reply_author_link($post->ID); ?></td>
                 <td><?php echo $flagged_by; ?></td>
                 <td><?php echo $moderated_by; ?></td>
                 <td><?php echo $post->post_content; ?></td>
                 <td class="actions" data-id="<?php echo $post->ID; ?>">
                     <a class="archived_released" >Archive Release</a>
                     <a class="moderate" >Moderate</a> 
                 </td>
             </tr>
          <?php
            } ?>
             <?php foreach ($archived_released as $post) {
                $flagged_by = get_post_meta(intval($post->ID), 'flagged_by', true);
                $flagged_by = ($flagged_by) ? get_user_by('id', $flagged_by) : '';
                $flagged_by = (!empty($flagged_by))? "<a href='".get_edit_user_link($flagged_by->ID)."'>".$flagged_by->display_name.'</a>' : 'Unknown';
                
                $moderated_by = get_post_meta(intval($post->ID), 'moderated_by', true);
                $moderated_by = ($moderated_by) ? get_user_by('id', $moderated_by) : '';
                $moderated_by = (!empty($moderated_by))? "<a href='".get_edit_user_link($moderated_by->ID)."'>".$moderated_by->display_name.'</a>' : 'Unknown';
                
                if (!empty($moderated_by)) {
                    $moderated_date = get_post_meta(intval($post->ID), 'moderated_date', true);
                  
                    $moderated_by = ($moderated_date) ? $moderated_by." on $moderated_date" : $moderated_by;
                } ?>
             <tr class="archived_released hide">
                 <td><?php echo $post->ID; ?></td>
                 <td><?php echo $post->post_title; ?></td>
                 <td><?php bbp_reply_author_link($post->ID); ?></td>
                 <td><?php echo $flagged_by; ?></td>
                 <td><?php echo $moderated_by; ?></td>
                 <td><?php echo $post->post_content; ?></td>
                 <td class="actions" data-id="<?php echo $post->ID; ?>">
                     <a class="archived_moderated" >Archive Moderate</a>
                     <a class="moderate" >Moderate</a> 
                 </td>
             </tr>
          <?php
            } ?>
         </tbody>
    </table>
</div>
            <?php
        }
        /*
         * mark post for moderation
         */
//        public function mark_for_moderation()
//        {
//            //verify nonce
//            if (!wp_verify_nonce($_REQUEST['nonce'], 'moderation_nonce')) {
//                wp_send_json_error();
//                exit('Invalid AJAX call');
//            }
//        }
    }
    
    $moderator = new Admin_Moderator();
    $moderator->set_hooks();
}
