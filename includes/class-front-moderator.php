<?php

if (!class_exists('Front_Moderator')) {
    class Front_Moderator
    {
        public $flag_icon =  '<span class="dashicons dashicons-flag"></span>';
        public function __construct()
        {
            $this->flag_icon = apply_filters('buddypress_moderator_flag_icon', $this->flag_icon);
        }
       
        public function set_hooks()
        {
            if (!is_admin()) {
                //add moderation flags
                
                //replies:
                add_action('bbp_theme_before_reply_admin_links', array($this, 'add_moderation_flag'));
                //add moderated content to moderated replies
                add_filter('bbp_get_reply_content', array($this, 'moderate_replies'));
            }
            
            add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'));
         
            //AJAX actions
            add_action('wp_ajax_mark_for_moderation', array($this, 'mark_for_moderation'));
        }
        /*
         * enqueue front facing scripts
         */
        public function enqueue_front_scripts()
        {
          
            //allow filter for others to change url to fontawesome but icons will still need to be the same if they
            //switch versions
            wp_enqueue_style('dashicons');
            wp_register_style('buddypress-moderator-settings-style', plugins_url()."/buddypress-moderator/css/front.css");
            wp_enqueue_style('buddypress-moderator-settings-style');
            
            //script for sending AJAX calls to moderate a post
            wp_register_script("buddypress-moderator-settings-script", plugins_url()."/buddypress-moderator/js/flag.js", ['jquery']);
            wp_enqueue_script('buddypress-moderator-settings-script');
            //localize for ajax calls
            wp_localize_script(
                    'buddypress-moderator-settings-script',
                    'flag_object',
                    array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce'    => wp_create_nonce('moderation_nonce'),
                    )
                );
        }
      
        /*
         * mark post for moderation
         */
        public function mark_for_moderation()
        {
            //verify nonce
            if (!wp_verify_nonce($_REQUEST['nonce'], 'moderation_nonce')) {
                exit('Invalid AJAX call');
            }
            $id = intval($_REQUEST['id']);
            //update the post meat of this reply to moderate
            $status = get_post_meta($id, 'moderate_status');
            if ($status === false) {
                $status = add_post_meta($id, 'moderate_status', 'moderate');
            } else {
                $status = update_post_meta(intval($_REQUEST['id']), 'moderate_status', 'moderate');
            }
            //add moderated by
            update_post_meta($id, 'flagged_by', get_current_user_id());
            update_post_meta($id, 'flagged_date', date('m/d/Y'));
            wp_send_json_success();
        }
        /*
         * adds a moderation icon to topics
         */
        public function add_moderation_flag()
        {
            $reply_id = bbp_get_reply_ancestor_id();
            echo "<p><a class='flag_for_moderation' href='#' data-type='reply' data-id='$reply_id'>".$this->flag_icon."</a></p>";
        }
        /*
         * moderate replies called from applied filter
         */
        public function moderate_replies($content, $reply_id)
        {
            $status = get_post_meta(bbp_get_reply_ancestor_id(), 'moderate_status', true);
            if ($status == 'moderate') {
                $content = '<p class="moderated">This content has been flagged for moderation.</p>';
            } elseif ($status == 'archived_moderated') {
                $content = '<p class="moderated">This content has been found to be offensive and will no longer be displayed</p>';
            }
            return $content;
        }
    }
    
    $moderator = new Front_Moderator();
    $moderator->set_hooks();
}
