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
	
	require_once "lib/Booru.php";
	$site = new Booru( $_GET['site'] );
	
	$handler = $_GET['handler'];
	
	
	function dt_to_json( $arr ){
		$container = array();
		foreach( $arr as $row )
			$container[] = $row->export();
		
		echo json_encode( $container );
	}
	
	if( $handler == 'similar_tags' ){
		$query = $_GET['query'];
		$dttag = new DTTag( $site->get_api()->get_code() );
		$tags = $dttag->similar_tags( $query );
		dt_to_json( $tags );
	}
?>