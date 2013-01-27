<?php
class AWD_facebook_notifications_base 
{
	protected $api;
	protected $model = array(
				'user_id'=>'',
				'users_id'=>'',
				'href'=>'',
				'template'=>'',
			);
	
	public function __construct($fcbk)
	{
		$this->fcbk = $fcbk;
	}
	
	public function sendNotification($user_id, $href, $template)
	{
		$return = true;
		try{
			$this->fcbk->api('/'.$user_id.'/notifications','post', array(
					'href' => $href,
					'template' => $template,
					'access_token' => $this->fcbk->getApplicationAccessToken()
			));
		}catch(FacebookApiException $e){
			$return = new WP_Error(__CLASS__.':'.$e->getType(), $e->getMessage());
		}
		return $return;
		
	}
	
	public function send($options)
	{
		$errors = array();
		$options = wp_parse_args($options, $this->model);
		if($options['user_id'] == '' && !is_array($options['users_id'])){
			$errors[] = new WP_Error(__CLASS__.':Missing params', 'You must enter a user ID OR select users in the list.');
		}
		if($options['template'] == ''){
			$errors[] =  new WP_Error(__CLASS__.':Missing params', 'You must enter a message.');
		}
		if(count($errors))
			return $errors;
		
		if(is_array($options['users_id'])){
			foreach($options['users_id'] as $user_id){
				$return = $this->sendNotification($user_id, $options['href'], $options['template']);
				if(is_wp_error($return))
					$errors[] = $return;
			}
		}else{
			$return = $this->sendNotification($options['user_id'], $options['href'], $options['template']);
			if(is_wp_error($return))
				$errors[] = $return;
		}
		if(count($errors))
			return $errors;
		return true;
	}
}