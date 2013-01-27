<?php
/*
*
* AWD_facebook_notifications class | AWD FCBK notifications
* (C) AHWEBDEV
*
*/
Class AWD_facebook_notifications extends AWD_facebook_plugin_abstract
{
  	
	/**
	 * The Slug of the plugin
	 * @var string
	 */
    public $plugin_slug = 'awd_fcbk_notifications';
    
    /**
     * The Name of the plugin
     * @var string
     */
    public $plugin_name = 'Facebook AWD Notifications';
    
    /**
     * The text domain of the plugin
     * @var string
     */
    public $ptd = 'AWD_facebook_notifications';
    
    /**
     * The version required for AWD_facebook object
     * @var float
     */
    public $version_requiered = "1.4.2";
    
    /**
     * The array of deps
     * @var array
     */
    public $deps = array('connect'=>0);           
    
    /**
     * plugin internal init
     */
    public function __construct($file,$AWD_facebook)
    {
        parent::__construct(__FILE__,$AWD_facebook);
    }
    /*
     * initialise the sub plugin
     */
    public function initialisation()
    {
    	parent::init();    	 
        add_action('wp_ajax_send_notifications', array(&$this, 'ajax_send_notifications'));
        if($this->AWD_facebook->options['notifications']['comment_post']){
            add_action('comment_post', array(&$this, 'listener_comment_post'));
        }
        if($this->AWD_facebook->options['notifications']['user_register']){
            add_action('user_register', array(&$this, 'listener_user_register'));
        }
        if($this->AWD_facebook->options['notifications']['insert_post']){
            add_action('wp_insert_post', array(&$this, 'listener_insert_post'));
        }
    }
    
    /**
     * get the admin menu
     */

    public function admin_menu()
    {
    	$this->plugin_admin_hook = add_submenu_page($this->AWD_facebook->plugin_slug, __('Notifications', $this->ptd),  '<img src="'.$this->plugin_url_images.'facebook-awd-notifications.png" /> '.__('Notifications', $this->ptd), 'administrator', $this->plugin_slug, array($this->AWD_facebook, 'admin_content'));
    	if($this->plugin_admin_hook != ''){
    		add_meta_box($this->plugin_slug . "_settings", __('Settings', $this->ptd).' <img src="'.$this->plugin_url_images.'facebook-awd-notifications.png" />', array(&$this, 'admin_form'), $this->plugin_admin_hook, 'normal', 'core');
    		add_meta_box($this->plugin_slug . "_send", __('Send notifications', $this->ptd).' <img src="'.$this->plugin_url_images.'facebook-awd-notifications.png" />', array(&$this, 'admin_send_notifications'), $this->plugin_admin_hook, 'normal', 'core');
    	}
    	parent::admin_menu();

    }

    /**
     * Define default $options
     * @param array $options
     */
    public function default_options($options)
    {
        $options = parent::default_options($options);
        $default_options = array();
        $default_options['comment_post'] = 0;
        $default_options['user_register'] = 0;
        $default_options['insert_post'] = 0;

        //attach options to Container
        if (!isset($options['notifications']))
            $options['notifications'] = array();
        $options['notifications'] = wp_parse_args($options['notifications'], $default_options);
        
        return $options;
    }

    /**
     * get he admin form
     */

    public function admin_form()
    {
        $form = new AWD_facebook_form('form_settings', 'POST', '', $this->AWD_facebook->plugin_option_pref);
        echo $form->start();
        echo '<div class="row">';
            echo $form->addSelect(__('Send a notification to Admin when a new comment is posted ?',$this->ptd), 'notifications[comment_post]', array(
                array('value'=>0, 'label'=>__('No',$this->ptd)),
                array('value'=>1, 'label'=>__('Yes',$this->ptd))                                    
            ), $this->AWD_facebook->options['notifications']['comment_post'], 'span4', array('class'=>'span1'));
        
            echo $form->addSelect(__('Send a notification to Admin when a user register ?',$this->ptd), 'notifications[user_register]', array(
                array('value'=>0, 'label'=>__('No',$this->ptd)),
                array('value'=>1, 'label'=>__('Yes',$this->ptd))                                    
            ), $this->AWD_facebook->options['notifications']['user_register'], 'span4', array('class'=>'span1'));
        echo '</div>';

        echo '<div class="row">';
            echo $form->addSelect(__('Send a notification to users when a new post is published ?',$this->ptd), 'notifications[insert_post]', array(
                array('value'=>0, 'label'=>__('No',$this->ptd)),
                array('value'=>1, 'label'=>__('Yes',$this->ptd))                                    
            ), $this->AWD_facebook->options['notifications']['insert_post'], 'span4', array('class'=>'span1'));
        echo '</div>';

        wp_nonce_field($this->AWD_facebook->plugin_slug . '_update_options', $this->AWD_facebook->plugin_option_pref . '_nonce_options_update_field');
        echo $form->end();
        echo '
    	<div class="form-actions">
    		<a href="#" id="submit_settings" class="btn btn-primary" data-loading-text="<i class=\'icon-time icon-white\'></i>'.__('Saving settings...', $this->ptd).'"><i class="icon-cog icon-white"></i>'.__('Save all settings', $this->ptd).'</a>
    	    <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZQ2VL33YXHJLC" class="awd_tooltip_donate btn pull-right" id="help_donate" target="_blank" class="btn pull-right"><i class="icon-heart"></i> '.__('Donate!', $this->ptd).'</a>
    	</div>';
    }
    

    /**
     * get he admin form
     */

    public function admin_send_notifications()

    {

    	$form = new AWD_facebook_form('send_notifications', 'POST', '', $this->AWD_facebook->plugin_option_pref);

    	echo $form->start();
        echo '
    		<div class="dn alert alert-error" id="send_notifications_errors"></div>
    	   	<div class="dn alert alert-success" id="send_notifications_success"></div>
            <div class="alert alert-info">'.__('You can only send notifications to facebook users linked with your blog.',$this->ptd).'</div>

    	   	<div class="row">
    			'.$form->addInputText(__('User ID', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('notifications_user_id'), 'notifications[send][user_id]', '', 'span3', array('class' => 'span2'), 'icon-user').'
                '.$form->addInputText(__('Href', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('notifications_href'), 'notifications[send][href]', '', 'span3', array('class' => 'span3')).'
    		</div>
    		<div class="row">
            ';
    			$fb_users = $this->AWD_facebook->get_all_facebook_users();
    			foreach($fb_users as $fb_user){ $options[] = array('value'=> $fb_user->meta_value, 'label'=>$fb_user->display_name.' {'.$fb_user->meta_value.'}'); }
    			echo $form->addSelect(__('Or choose users to notifiy',$this->ptd). ' ' . $this->AWD_facebook->get_the_help('notifications_users_id'), 'notifications[send][users_id][]', $options
    			, array(), 'span6', array('class'=>'span6','multiple'=>'multiple')).'
    		</div>
    		<div class="row">
    			'.$form->addInputTextArea(__('Message template', $this->ptd). ' ' . $this->AWD_facebook->get_the_help('notifications_template'), 'notifications[send][template]', '', 'span6', array('class' => 'span6')).'
    		</div>
    		<div class="form-actions">
    		   	<a href="#" id="send_notifications" class="btn btn-primary" data-loading-text="<i class=\'icon-time icon-white\'></i> '.__('Sending....', $this->ptd).'"><i class="icon-cog icon-white"></i> '.__('Send notification', $this->ptd).'</a>
    		</div>';
    		echo $form->end();
    		echo '
    		<div class="header_lightbox_help_title hidden"><img style="vertical-align:middle;" src="'.$this->AWD_facebook->plugin_url_images.'facebook-mini.png" alt="facebook logo"/> '.__('Help',$this->AWD_facebook->ptd).'</div>
			<div id="lightbox_help_notifications_href" class="hidden">
				<p>
				'.__('The relative path/GET params of the target (for example, "index.html?gift_id=123", or "?gift_id=123"). Then we will construct proper target URL based on your app settings. The logic is that, on web, if Canvas setting exists, we always show “Canvas URL + href”. If not, we show nothing. In the future (not in this version), we will also use existing URL re-writing logic to support mobile canvas and native mobile apps. We also append some special tracking params (fb_source, notif_id, notif_t) to the target URL for developers to track at their side.',$this->ptd).'
				</p>
			</div>
			<div id="lightbox_help_notifications_user_id" class="hidden">
				<p>
				'.__('You must set a user Facebook ID target',$this->ptd).'
				</p>
			</div>
			<div id="lightbox_help_notifications_users_id" class="hidden">
				<p>
				'.__('You can choose users that are registered on your site with Facebook. (Multiple)',$this->ptd).'
				</p>
			</div>
			<div id="lightbox_help_notifications_template" class="hidden">
				<p>
				'.sprintf(__('The customized text of the notification. %sSee the templating%s section below for more details.',$this->ptd),
						 '<a href="https://developers.facebook.com/docs/app_notifications/#templating" target="_blank">','</a>').'
				</p>
			</div>
            ';
	}
	
	public function ajax_send_notifications()
	{
		$notification = new AWD_facebook_notifications_base($this->AWD_facebook->fcbk);

		$return = $notification->send($_POST[$this->AWD_facebook->plugin_option_pref.'notifications']['send']);
		$response = json_encode(array('success'=> 1, 'message'=> 'The notification was send with success'));
		if(is_array($return)){
			//if is array that mean we get errors
			$error_message = '';
			foreach ($return as $error){
				if(is_wp_error($error)){
					$error_message .= $error->get_error_message().'<br />';
				}
			}
			$response = json_encode(array('success'=>0, 'error'=> $error_message));
		}else if($return === false){
			$response = json_encode(array('success'=>0, 'error'=> 'unknow error'));
		}
		echo $response;
		exit();
	}
	
	public function admin_enqueue_js()
	{
		parent::front_enqueue_js();
		wp_enqueue_script($this->plugin_slug.'-js', $this->plugin_url.'/assets/js/facebook_awd_notifications.js',array('jquery'));
	}

    public function listener_comment_post($comment_id)
    {
        $comment = get_comment( $comment_id );
        $post    = get_post( $comment->comment_post_ID );
        $author  = get_userdata( $post->post_author );
        if(isset($author->fb_uid)){
            $author_name = '{'.$author->fb_uid .'}';
        }else{
            $author_name = $author->display_name;
        }
        //notify the admin.
        $admin_fb_uid =  $this->AWD_facebook->get_admin_fbuid();
        $blog_name = get_option('blogname');
        if($admin_fb_uid){
            $notification = new AWD_facebook_notifications_base($this->AWD_facebook->fcbk);
            $return = $notification->send(array(
                'user_id' => $admin_fb_uid,
                'href' => str_replace(home_url(),'',get_permalink($post->ID)).'#comment-'.$comment_id,
                'template' => sprintf(__('%s posted a new comment on %s',$this->ptd), $author_name, $blog_name)
            ));
        }
    }

    public function listener_user_register($user_id)
    {
        $user = get_userdata($user_id);
        if(isset($user->fb_uid)){
            $user_name = '{'.$user->fb_uid .'}';
        }else{
            $user_name = $user->display_name;
        }
        //notify the admin.
        $admin_fb_uid =  $this->AWD_facebook->get_admin_fbuid();
        $blog_name = get_option('blogname');
        if($admin_fb_uid){
            $notification = new AWD_facebook_notifications_base($this->AWD_facebook->fcbk);
            $return = $notification->send(array(
                'user_id' => $admin_fb_uid,
                'template' => sprintf(__('%s was just registered on %s',$this->ptd), $user_name, $blog_name)
            ));
        }
    }

    public function listener_insert_post($post_id)
    {
        $post = get_post($post_id);
        if(!wp_is_post_revision($post_id) && $post_id > 1 && $post->post_status == 'publish'){
            $users_target = array();
            $query = 'SELECT m.meta_value as fb_uid, ID FROM '.$this->AWD_facebook->wpdb->users.' u LEFT JOIN '.$this->AWD_facebook->wpdb->usermeta.' m ON m.user_id = u.ID WHERE m.meta_key = "fb_uid" AND m.meta_value!=""';
            $fb_users = $this->AWD_facebook->wpdb->get_results($query);
            foreach($fb_users as $fb_user){
                $users_target[] = $fb_user->fb_uid;
            }
            if(count($users_target)){
                $blog_name = get_option('blogname');
                $permalink = str_replace(home_url(),'',@get_permalink($post_id));
                $post_title = html_entity_decode(get_the_title($post_id), ENT_QUOTES, 'UTF-8');
                //send notifications to users
                $notification = new AWD_facebook_notifications_base($this->AWD_facebook->fcbk);
                $return = $notification->send(array(
                    'users_id' => $users_target,
                    'href' => $permalink,
                    'template' => sprintf(__('A new post "%s" was published on %s',$this->ptd), $post_title, $blog_name)
                ));
            }
        }
    }
}
?>