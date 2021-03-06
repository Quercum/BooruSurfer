<?php
	/*	This file is part of BooruSurfer.

		BooruSurfer is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		BooruSurfer is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with BooruSurfer.  If not, see <http://www.gnu.org/licenses/>.
	*/
	
	require_once "plugins/sites/dan.php";
	
	abstract class GelApi extends DanApi{
		//No related tags support : \
		public function supports_related_tags(){ return false; }
		
		protected function get_url( $handler, $action, $format, $parameters=array(), $login=false ){
			//Format is ignored, only XML available
			if( $login ){
				//TODO: add autorisation
			}
			
			//Add base requirements
			$parameters['page'] = 'dapi';
			$parameters['s'] = $handler;
			$parameters['q'] = $action;
			
			//make parameters
			$para = "?";
			foreach( $parameters as $key => $value )
				$para .= "$key=" . rawurlencode( $value ) . '&';
			$para = rtrim( $para, "&" );
			
			//Create URL
			return $this->url . "index.php$para";
		}
		
		public function index( $search=NULL, $page=1, $limit=NULL ){
			//Format parameters
			$para = array();
			if( $search != NULL && $search != "" )
				$para['tags'] = $search;
			if( $limit )
				$para['limit'] = $limit;
			if( $page > 1 )
				$para['pid'] = $page - 1;
			
			//Retrive raw data from the server
			$url = $this->get_url( 'post', 'index', 'xml', $para );
			$data = $this->get_xml( $url );
			if( !$data )	//Kill if failed
				return NULL;
			
			//Parse array of posts
			$posts = array();
			foreach( $data->post as $post_data )
				$posts[] = $this->parse_post( $post_data );
			
			//Get amount of posts total
			$posts['count'] = (string)$data['count'];
			
			//Return posts
			return $posts;
		}
		
		public function related_tags( $tags ){
			return array(); //Not working : \
		}
	}
	
	class GelbooruApi extends GelApi{
		public function __construct(){
			$this->url = "http://gelbooru.com/";
		}
		
		protected function get_post_mapping(){ return GelbooruApi::$post_mapping; }
		public static $post_mapping = array(
			'id'	=>	'id',
			'hash'	=>	'md5',
			'author'	=>	'creator_id',
			'creation_date'	=>	'created_at',
			
			'parent_id'	=>	'parent_id',
			'has_children'	=>	'has_children',
			'has_notes'	=>	'has_notes',
			'has_comments'	=>	'has_comments',
			'source'	=>	'source',
			
			'tags'	=>	'tags',
			'score'	=>	'score',
			'rating'	=>	'rating',
			'status'	=>	'status',
			
			'url'	=>	'file_url',
			'width'	=>	'width',
			'height'	=>	'height',
		//	'filesize'	=>	'file_size',
			
			'thumb_url'	=>	'preview_url',
			'thumb_width'	=>	'preview_width',
			'thumb_height'	=>	'preview_height',
		//	'thumb_filesize'	=>	NULL,
			
			'preview_url'	=>	'sample_url',
			'preview_width'	=>	'sample_width',
			'preview_height'	=>	'sample_height',
		//	'preview_filesize'	=>	'sample_file_size',
			
		//	'reduced_url'	=>	'jpeg_url',
		//	'reduced_width'	=>	'jpeg_width',
		//	'reduced_height'	=>	'jpeg_height',
		//	'reduced_filesize'	=>	'jpeg_file_size'
		);
		
		private static $tag_mapping = array(
			'id' => 'name',
			'name' => 'name',
			'type' => 'type',
			'count' => 'count'
		);
		
		public function get_name(){ return "Gelbooru"; }
		public function get_code(){ return "gel"; }
		
		public function get_front_color(){ return 0x006FFA; }
		public function get_back_color(){ return 0xFFFFFF; }
	}
	
	
?>