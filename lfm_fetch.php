<?php
class LfmFetch {
	public $username;
	public $amount;


	
	// initialize new user object 
	function __construct() {
		require 'credentials.php';
		require 'lastfm_api/lastfmapi.php';
		
		// lastfmApi authentification
		$auth_vars['apiKey'] = $fm_api_key;
		$auth = new lastfmApiAuth('setsession', $auth_vars);
		
		// initialize PHP lastfmApi implementation ('user' and 'library' class initialization)
		$api_class = new lastfmApi();
		$this->userClass = $api_class->GetPackage($auth, 'user');
		$this->libraryClass = $api_class->GetPackage($auth, 'library');
		
		$this->username = $_SESSION['username'];
		$this->amount = $_SESSION['amount'];
	}



	// fetch $amount number of artist names and their total playcount
	private function GetArtists($amount) {
		// set lastfm request variables
		$method_vars = array(
			'user' => $this->username
		);
		
		// get top artists XMLs from lastfm, and restrict data to $amount number of artists
		if ($artists = $this->userClass->GetTopArtists($method_vars)) {
			$i = 0;
			foreach ($artists as $artist) {
				$artist_name[$i] = $artist['name'];
				$artist_playcount[$artist_name[$i]] = $artist['playcount'];
				$artist_image[$artist_name[$i]] = $artist['image'];
				$i++;
				if ($i == $amount)
					break;
			}
			
			// if user has less artists in library than what is chosen in form
			if ($i < $amount) {
				$_SESSION['safeamount'] = $i;
				$this->amount = $i;
			}
			else
				$_SESSION['safeamount'] = $this->amount;
		}
		else {
			die("<b>Error " . $this->userClass->error['code'] . " - </b><i>" . $this->userClass->error['desc'] . "</i>");
		}
		
		// set artist names and their playcounts for later use 
		$this->top_playcount = $artist_playcount[$artist_name[0]];
		$this->artist_name = $artist_name;
		$this->artist_playcount = $artist_playcount;
		$this->artist_image = $artist_image;
	}
	
	
	
	// fetch track names, playcounts, calculate percentage for each artist
	private function GetArtistTracks($artist_name, $artist_playcount) {
		$pages = 1;
		$i = 0;
		$tracks_array = array();
		
		// set lastfm request variables
		$method_vars = array(
			'user' => $this->username,
			'artist' => $artist_name,
			'page' => $pages
		);	
		
		// get tracks XML from lastfm (first page)
		if ($tracks = $this->libraryClass->GetTracks($method_vars)) {
			foreach ($tracks['results'] as $track) {
				$track_name[$i] = $track['name'];
				$track_playcount[$track['name']] = $track['playcount'];
				
				// fetch data for each artist
				$tracks_array[$i] = array(
					'name' => $track_name[$i],
					'playcount' => $track_playcount[$track['name']] 
				);
				$i++;				
			}
			
			$max_pages = $tracks['totalPages'];
			
			// if more than one page of tracks (more than 50 songs) fetch other pages
			if ($max_pages > 1) {
				for ($pages=2; $pages<=$max_pages; $pages++) {
					// re-set lastfm request variables (for other pages)
					$method_vars = array(
						'user' => $this->username,
						'artist' => $artist_name,
						'page' => $pages
					);				
					
					// get tracks XML from lastfm (other pages)
					if ($tracks = $this->libraryClass->GetTracks($method_vars)) {
						foreach ($tracks['results'] as $track) {
							$track_name[$i] = $track['name'];
							$track_playcount[$track['name']] = $track['playcount'];
							
							//fetch data for each artist 
							$tracks_array[$i] = array(								
								'name' => $track_name[$i], 
								'playcount' => $track_playcount[$track['name']] 
							);
							$i++;
						}
					}
				}
			}
			
			// sort songs by count (for ribbon)
			//usort($tracks_array, array($this, 'sortByCount'));
			
			// merge results from all pages
			$total_tracks = $i;
			$i = 0;
			foreach ($tracks_array as $temp) {
				$track_name[$i] = $temp['name'];
				$track_playcount[$track_name[$i]] = $temp['playcount'];
				$i++;
			}			
		}
		else {
			die("<b>Error " . $this->libraryClass->error['code'] . " - </b><i>" . $this->libraryClass->error['desc'] . "</i>");
		}	
		
		// set track names, their playcounts and percentage for further use
		$this->track_name = $track_name;
		$this->track_playcount = $track_playcount;
		$this->total_tracks = $total_tracks;
	}	
	
	
	
	// multidimensional arrays sorting
	private function SortByCount($a, $b) {
		if ($a['playcount'] == $b['playcount']) {
			return 0;
		}
		return ($a['playcount'] < $b['playcount']) ? -1 : 1;
	}	
	
	
	
	// create XML with $artistAmount number of artist names and total playcounts 
	public function XmlArtists() {
		$this->GetArtists($this->amount);
		
		$doc = new DomDocument('1.0');
		
		$root = $doc->createElement('topartists');
		$root = $doc->appendChild($root);
			
			$entity = $doc->createElement('topplaycount');
			$entity = $root->appendChild($entity);
     			$value = $doc->createTextNode($this->top_playcount);
     			$value = $entity->appendChild($value);
			
		for ($i=0; $i<$this->amount; $i++) {
			$entity = $doc->createElement('artist');
			$entity = $root->appendChild($entity);
				
				$child = $doc->createElement('name');
				$child = $entity->appendChild($child);
				$value = $doc->createTextNode($this->artist_name[$i]);
				$value = $child->appendChild($value);
				
				$child = $doc->createElement('playcount');
				$child = $entity->appendChild($child);
				$value = $doc->createTextNode($this->artist_playcount[$this->artist_name[$i]]);
				$value = $child->appendChild($value);
				
				$child = $doc->createElement('image');
				$child = $entity->appendChild($child);
				$value = $doc->createTextNode($this->artist_image[$this->artist_name[$i]]);
				$value = $child->appendChild($value);				
		}
		
		$xml_string = $doc->saveXML();
		header("Content-Type: text/xml; charset=UTF-8");
		return $xml_string; 	
	}	
	
	
	
	// create XML for each artist with its tracks, playcounts and percentage 
	public function XmlTracks($artist, $artist_playcount) {
		$this->GetArtistTracks($artist, $artist_playcount);
		$tracks = $this->track_name;
		
		$doc = new DomDocument('1.0');
		
		$root = $doc->createElement('tracks');
		$root = $doc->appendChild($root);
			
			$entity = $doc->createElement('totaltracks');
			$entity = $root->appendChild($entity);
     			$value = $doc->createTextNode($this->total_tracks);
     			$value = $entity->appendChild($value);
			
		foreach ($tracks as $track) {
			$entity = $doc->createElement('track');
			$entity = $root->appendChild($entity);
				
				$child = $doc->createElement('name');
				$child = $entity->appendChild($child);
				$value = $doc->createTextNode(htmlspecialchars($track, ENT_QUOTES));
				$value = $child->appendChild($value);
				
				$child = $doc->createElement('playcount');
				$child = $entity->appendChild($child);
				$value = $doc->createTextNode($this->track_playcount[$track]);
				$value = $child->appendChild($value);	
		}
		
		$xml_string = $doc->saveXML();
		header("Content-Type: text/xml; charset=UTF-8");
		return $xml_string; 	
	}



	// fetch users total playcount
	public function GetTotalPlaycount() {
		// set lastfm request variable
		$method_vars = array(
			'user' => $this->username
		);
		
		if ($info = $this->userClass->GetInfo($method_vars)) {
			return $info['playcount'];
		}
		else {
			die("<b>Error " . $this->userClass->error['code'] . " - </b><i>" . $this->userClass->error['desc'] . "</i>");
		}
	}
}
?>
