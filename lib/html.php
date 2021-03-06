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
	
	class htmlPage{
		private $html_type;
		
		public $html;
		
		function __construct(){
			$this->html_type = "xhtml";
			$this->html = new htmlBody();
		}
		
		function write(){
			//TODO: html5
			$this->html->writeXhtml();
		}
	}
	
	class htmlBody extends htmlObject{
		public $head;
		public $title;
		
		public $body;
		
		function __construct(){
			parent::__construct( "html" );
			$this->attributes['lang'] = 'en';
			$this->attributes['dir'] = 'ltr';
			//$this->attributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
			$this->content[] = new htmlObject( "head" );
			$this->content[] = new htmlObject( "body" );
			$this->head =& $this->content[0];
			$this->body =& $this->content[1];
			
			$this->head->content[] = new htmlObject( "title" );
			$this->title =& $this->head->content[0]->content;
		}
		
		function writeXhtml(){
			//header('Content-type: application/xhtml+xml; charset=utf-8');
			echo '<?xml version="1.0" encoding="UTF-8"?>', "\r\n";
			echo "<!DOCTYPE html>\r\n";
			parent::writeXhtml();
		}
		
		function addStylesheet( $href, $media=NULL ){
			$link = new htmlObject( "link" );
			$link->attributes['href'] = $href;
			$link->attributes['rel'] = "stylesheet";
			$link->attributes['media'] = $media;
			
			$this->head->content[] = $link;
		}
		
		function addSequence( $type, $url ){
			$link = new htmlObject( "link" );
			$link->attributes['rel'] = $type;
			$link->attributes['href'] = $url;
			
			$this->head->content[] = $link;
		}
	}
	
	class htmlObject{
		private $type;
		public $attributes;
		public $content;
		
		
		function __construct( $type, $content=NULL, $attributes=NULL ){
			$this->type = $type;
			$this->attributes = $attributes;
			$this->content = $content;
		}
		
		public function writeHtml(){
		
		}
		public function writeXhtml(){
			if( $this->type ){
				//Write type and attributes
				echo '<', $this->type;
				if( $this->attributes ){
					foreach( $this->attributes as $attribute=>$value )
						if( $value )
							echo ' ', $attribute, '="', htmlspecialchars( $value, ENT_QUOTES ), '"';
				}
				
				//Write body of element
				if( $this->content ){
					echo '>';
					
					if( is_object( $this->content ) )
						$this->content->writeXhtml();
					else if( is_array( $this->content ) )
						array_walk_recursive( $this->content, function( $item, $key ){
							if( is_object( $item ) )
								$item->writeXhtml();
							else
								echo htmlspecialchars( $item, ENT_NOQUOTES );
						} );
							else
								echo htmlspecialchars( $this->content, ENT_NOQUOTES );
					
					echo '</', $this->type, '>';
				}
				else
					echo '/>';
			}
		}
		
		function addClass( $class ){
			if( isset( $this->attributes['class'] ) )
				$this->attributes['class'] .= " $class";
			else
				$this->attributes['class'] = $class;
		}
		function setID( $id ){
			$this->attributes['id'] = $id;
		}
	}
	
	class htmlLink extends htmlObject{
		public $href;
		
		function __construct( $link, $title ){
			parent::__construct( "a" );
			$this->attributes['href'] = $link;
			$this->href =& $this->attributes['href'];
			
			$this->content = $title;
		}
	}
	
	class htmlImage extends htmlObject{
		public $src;
		public $alt;
		
		function __construct( $link, $alt = NULL ){
			parent::__construct( "img" );
			$this->attributes['src'] = $link;
			$this->attributes['alt'] = $alt;
			$this->src =& $this->attributes['src'];
			$this->alt =& $this->attributes['alt'];
		}
	}
	
	class htmlList extends htmlObject{
		function __construct( $ordered=false ){
			if( $ordered )
				parent::__construct( "ol" );
			else
				parent::__construct( "ul" );
		}
		
		function addItem( $item ){
			return $this->content[] = new htmlObject( "li", $item );
		}
	}
	
	function toClass( $title ){
		return array( 'class'=>$title );
	}
	function toId( $id ){
		return array( 'id'=>$id );
	}
	
	
	class fakeObject{
		private $data;
		function __construct( $data ){
			$this->data = $data;
			
			//TODO: actually parse this...
		}
		
		
		public function writeHtml(){
			echo $this->data;
		}
		public function writeXhtml(){
			echo $this->data;
		}
	}
?>