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
	
	require_once "lib/Database.php";
	require_once "lib/DTPost.php";
	
	class Index{
		//Table names
		private $post;
		private $list;
		private $prefix;
		
		//Search parameters
		private $site;
		private $search;
		
		//Current seach info
		private $id = NULL; //For index_list
		private $ordered; //TODO: ...
		private $index; //hmm, don't like this one...
		
		//Other
		const limit = 100; //Maximal fetch limit
		
		public function __construct( $site, $search ){
			$this->site = $site;
			$this->search = $search;
			
			$this->prefix = $site->get_api()->get_code();
			$this->list = $this->prefix . "_index_list";
			$this->post = $this->prefix . "_index_post";
			
			//Create tables if missing
			$db = Database::get_instance();
			if( !$db->table_exists( $this->list ) )
				$db->db->query( "CREATE TABLE $this->list ( "
					.	"id INTEGER PRIMARY KEY AUTOINCREMENT, "
					.	"search TEXT, "
					.	"count INT, "
					.	"next_update INT, "
					.	"ordered INT, "
					.	"related_tags TEXT, "
					.	"related_counts TEXT, "
					.	"locked INT )" 
					);
			if( !$db->table_exists( $this->post ) )
				$db->db->query( "CREATE TABLE $this->post ( "
					.	"list INT NOT NULL, "
					.	"offset INT, "
					.	"post INT, "
					.	"PRIMARY KEY( list, offset ), "
					.	"FOREIGN KEY(list) REFERENCES $this->list (id) ON DELETE CASCADE )" 
					);
			
			//TODO: standalize search
			
			//Get search, or create it
			$this->lookup_search();
			if( $this->id === NULL )
				$this->create_search();
		}
		
		public function get_page( $page ){
			//Update if too old
			if( $this->next_update() <= time() ){
				//TODO: with sankaku, check count before fetching
				
				$this->fetch_and_save( 1, Index::limit );
			}
			
			//Get it from the DB
			return $this->fetch_from_db( $page );
		}
		
		private function update_offsets( $diff ){
			if( $diff != 0 ){
				$db = Database::get_instance()->db;
				$db->query(
						"UPDATE $this->post SET offset = offset + "
					.	(int)$diff . " WHERE list = "
					.	(int)$this->id
					);
			}
		}
		
		private function fetch_and_save( $page, $limit ){
			$data = $this->site->get_api()->index( $this->search, $page, $limit );
			
			//Fix offsets
			if( isset( $data['count'] ) ){
				$offset = $data['count'] - $this->get_count();
				$this->update_offsets( $offset );
				$this->set_count( $data['count'] );
				
				$this->set_next_update( time() + 5 * 60 ); //TODO:
			}
			
			$this->save_posts( $data, ($page-1) * $limit );
		}
		private function fetch_from_db( $page, $limit=NULL ){
		//Grap everything from the database
			//Prepare the query
			$db = Database::get_instance()->db;
			$stmt = $db->prepare( "SELECT * FROM $this->post "
				.	"LEFT JOIN " . $this->prefix . '_post '
				.	"ON post = id "
				.	"WHERE offset >= :range_min AND offset < :range_max "
				.	"AND list = :id"
				);
			
			//Get default fetch amount if unset
			if( $limit == NULL )
				$limit = $this->site->get_fetch_amount();
			
			//Finish the query and execute
			$stmt->execute( array(
					'range_min'	=>	($page-1) * $limit
				,	'range_max'	=>	$page * $limit
				,	'id'	=>	$this->id
				) );
			$data = $stmt->fetchAll();
			
		//Try to retrive the data
			if( count( $data ) == $limit ){
				//All post are known, convert and return them
				$posts = array();
				foreach( $data as $post )
					$posts[] = new DTPost( $this->prefix, $post );
				return $posts;
			}
			else{
				//Not all are fetched, fetch and try again
				$this->fetch_and_save(
						(int)(($page-1)/3) + 1
					,	$limit * 3
					);
				return $this->fetch_from_db( $page, $limit );
			}
		}
		
		//Get basic information
		private function lookup_search(){
			//Get data
			$db = Database::get_instance()->db;
			$stmt = $db->query( "SELECT * FROM $this->list WHERE search = " . $db->quote( $this->search ) );
			$this->index = $stmt ? $stmt->fetch() : NULL;
			
			//Save ID
			$this->id = $this->index ? $this->index['id'] : NULL;
		}
		
		private function create_search(){
			//Prepare values to insert
			$search = $this->search;
			$ordered = false; //TODO: check this
			
			//Save in db
			$db = Database::get_instance()->db;
			$stmt = $db->query(
					"INSERT INTO $this->list ( "
				.	"search, ordered"
				.	" ) VALUES ("
				.	$db->quote( $search ) . ", "
				.	(int)$ordered
				.	" )"
				);
				
			//Find it again and fetch values
			//Stupid, but whatever...
			$this->lookup_search();
		}
		
		private function save_posts( $data, $offset ){
			$db = Database::get_instance()->db;
			$db->beginTransaction();
			foreach( $data as $post )
				if( gettype( $post ) == "array" ){ //Do not convert extra properties like 'count'
					$p = new DTPost( $this->prefix, $post );
					$p->db_save();
					
					$db->query( "REPLACE INTO $this->post VALUES ( "
						.	(int)$this->id . ", "
						.	(int)$offset . ", "
						.	(int)$p->id() . " )"
						);
					
					$offset++;
				}
			$db->commit();
		}
		
		public function get_search(){ return $this->search; }
		public function next_update(){
			return $this->index['next_update'];
		}
		private function set_next_update( $time ){
			$this->index['next_update'] = $time;
			
			//Update db
			$db = Database::get_instance()->db;
			$db->query(
					"UPDATE $this->list SET next_update = "
				.	$time . " WHERE id = "
				.	(int)$this->id
				);
		}
		public function get_count(){
			return $this->index['count'];
		}
		public function get_page_amount(){
			return $this->get_count() ? ceil( $this->get_count() / $this->site->get_fetch_amount() ) : NULL;
		}
		private function set_count( $count ){
			$this->index['count'] = $count;
			
			//Update db
			$db = Database::get_instance()->db;
			$db->query(
					"UPDATE $this->list SET count = "
				.	(int)$count . " WHERE id = "
				.	(int)$this->id
				);
		}
	}
	
?>