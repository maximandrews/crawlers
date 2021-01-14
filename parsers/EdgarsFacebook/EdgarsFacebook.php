<?php
/**
 * @name		Facebook App: Post on users wall
 * @author		Edgars Neimanis
 */

// include Facebook class
require_once('api/facebook.php');

class Wall {
	// access token
	private $token;
	// facebook api object
	private $facebook;

	/**
	 * Constructor
	 */
	public function __construct($token, $appData)
	{
		// access to facebook API Object
		$this->facebook = new Facebook($appData);
		// enable upload feature
		$this->facebook->setFileUploadSupport(true);
		
		// define access token
		$this->token = $token;
		
		// set access token
		$this->facebook->setAccessToken($this->token);
		
		// get album
		$this->albumData = array(
			'name'			=> 'Summercamp',
			'description' 	=> 'Summercamp augšupielādētie attēli',
		);
		
		// select albums
		$album = $this->facebook->api('/me/albums?fields=name,count,can_upload', 'get');
		// count albums
		$album_count = count($album['data']);
		
		// is there any album?
		if($album_count)
		{
			// are we found?
			$found = false;
			
			for($i = 0; $i < $album_count; $i++)
			{
				// search by album name & upload permission for app
				if( $album['data'][$i]['name'] == $this->albumData['name'] && 
					$album['data'][$i]['can_upload'] === true
					)
				{
					// YEES We found it
					$found = true;
					$this->albumData['id'] 		= $album['data'][$i]['id'];
					$this->albumData['count']	= $album['data'][$i]['count'];
					break;
				}
			}
			
			// we can't find that album
			if($found === false)
			{
				$this->createAlbum();
			}
		}
		else {
			$this->createAlbum();
		}
	}
	
	/**
	 * Creates album
	 */
	private function createAlbum()
	{
		// post on facebook
		$album = $this->facebook->api('/me/albums', 'post', $this->albumData);
		// write in album data array
		$this->albumData['id'] 		= $album['id'];
		$this->albumData['count'] 	= 1;
	}
	
	/**
	 * Post on wall
	 
	 */
	public function post($message, $picture='', $name='')
	{
		$postData = array(
			'message'		=> $message,
		);
		
		// add picture, to post data
		if($picture != '')
		{
			// about photo
			$photoData = array(
				'message'		=> 'Summercamp augšupielādētais attēls',
				'name'			=> $name,
				'image'			=> '@'. __DIR__ . '\images\\'.$picture,
			);
			// firstly upload photo
			$uploaded 	= $this->facebook->api('/'.$this->albumData['id'].'/photos', 'post', $photoData);
			$photo 		= $this->facebook->api('/'.$uploaded['id'], 'get');
			
			// pass some things to feed too
			$postData['picture']		= $photo['source'];
			$postData['link']			= $photo['link'];
		}
		
		// send to facebook
		$this->facebook->api('/me/feed', 'post', $postData);
		// show message
		echo '[OK]: Image uploaded & posted to wall!';
	}
}

// token
$token = 'CAAC0F8YSptIBAAnbNontZAVhSFy5zVWSJ1VFKYiO89dBP8lUNGCSVDW4TaNOd6sZAoIvWeOCinksrE8zJBvTl6NMNnU87aiq9bkKRDuJLhAXQCCgONrvyGjTBZB0HgFFgAERTRYD5Hhch8I3KMK59Xp6mDtrU8ZD';

// app data
$appData = array(
	'appId'  => '198014200358610',
	'secret' => '781d0958b0b36f7e8358cbfc3036d582',
);

$wall = new Wall($token, $appData);
// do posting
$wall->post('Whatss upp', 'l_2257385.jpg', 'Weekendiņš');