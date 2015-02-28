<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
class Issuu {



	public $apiKey;

	public $secret;

	public $url;
	
	public $request_url;
	
	public $upload_url;

	public $session_key;

	public $my_user_id;

	public $error;

	public $errormsg;

	public $docresult;



	function __construct($apiKey, $secret) {

		$this->apiKey = $apiKey;

		$this->secret = $secret;

		$this->url = "http://api.issuu.com/1_0";
		$this->upload_url = "http://upload.issuu.com/1_0";

	 }






	function upload($params, $file = NULL, $entity) {

		$action = "issuu.document.upload";
		
		$this->url = $this->upload_url;

		$result = $this->postRequest($action, $params, $file= NULL, $entity);

		return $result;

	}





	function uploadFromUrl($url, $doc_type = null, $access = null, $rev_id = null) {

		$action = "issuu.document.upload";

		$params['url'] = $url;

		$params['access'] = $access;

		$params['rev_id'] = $rev_id;

		$params['doc_type'] = $doc_type;

        $data_array = $this->postRequest($action, $params);

		return $data_array;

	}



	function getList(){

		$action = "issuu.documents.list";
		
		$this->url = $this->request_url;

		$result = $this->postRequest($action, $params);

		return $result['resultset'];

	}


	function getConversionStatus($doc_id) {

		$action = "docs.getConversionStatus";

		$params['doc_id'] = $doc_id;



		$result = $this->postRequest($action, $params);

		return $result['conversion_status'];

	}



	function getSettings($doc_id) {

		$action = "docs.getSettings";

		$params['doc_id'] = $doc_id;



		$result = $this->postRequest($action, $params);

		return $result;

	}



	function changeSettings($doc_ids, $params) {

		$action = "docs.changeSettings";

		$params['doc_ids'] = $doc_ids;



		$result = $this->postRequest($action, $params);

		return $result;

	}


	function delete($doc_name) {

		$action = "issuu.document.delete";
		
		$this->url = $this->url."?";
		
		$params['names'] = $doc_name;
		
		$result = $this->postRequest($action, $params);

		return true;

	}

	function search($query, $num_results = null, $num_start = null, $scope = null) {

		$action = "docs.search";

		$params['query'] = $query;

		$params['num_results'] = $num_results;

		$params['num_start'] = $num_start;

		$params['scope'] = $scope;



		$result = $this->postRequest($action, $params);



		return $result['result_set'];

	}

	function getDownloadURL($doc_id, $doc_type) {



		$action = "docs.getDownloadURL";

		$params['doc_id'] = $doc_id;

		$params['doc_type'] = $doc_type;

		$result = $this->postRequest($action, $params);



		return $result['download_link'];



	}



	function login($username, $password) {

		$action = "user.login";

		$params['username'] = $username;

		$params['password'] = $password;



		$result = $this->postRequest($action, $params);

		$this->session_key = $result['session_key'];

		return $result;

	}


	function signup($username, $password, $email, $name = null) {

		$action = "user.signup";

		$params['username'] = $username;

		$params['password'] = $password;

		$params['name'] = $name;

		$params['email'] = $email;

		$result = $this->postRequest($action, $params);

		return $result;

	}

   
	
	
	
	

	function postRequest($action, $params, $file= array(), $entity) {

		$params['apiKey'] = $this->apiKey;

		$params['action'] = $action;

		$params['session_key'] = $this->session_key;

		$params['my_user_id'] = $this->my_user_id;



		foreach ($params as $key => $val) {

			if ($val == null)

				unset($params[$key]);

		}	



		$post_params = array();

		foreach ($params as $key => $val) {

			if (is_array($val)) {

			  $val = implode(',', $val);

			}

			if ($key != 'file' && substr($val, 0, 1) == "@") {

				$val = chr(32).$val;

			}

			$post_params[$key] = $val;

		}

     

		$secret = $this->secret;

		//add

		if ($secret) {

		  $post_params['signature'] = $this->generate_sig($params, $secret);

		}

		$request_url = $this->url;

		if (isset($post_params['file'])) {	
	    	$xml = _issuu_request($request_url, $post_params, "POST");
         
		}

		else {

		  $xml = _issuu_request($request_url, $post_params, "GET");

		}
       
	    
	   
		$xml_parse = @simplexml_load_string($xml);
		$json = json_encode($xml_parse);
		$result = @json_decode($json,TRUE);

		
		if(!empty($result))
		{
			
			$result = array(
			'doc_id' => array(
				0 => @$result['document']['@attributes']['documentId'],
				)
			
			);
			
			
			
			return $result;
			
		}
			
		return $result;

	}



	function generate_sig($params_array, $secret) {

		$str = '';



		ksort($params_array);

		foreach ($params_array as $k=>$v) {

		  if ($k != 'file') {

		    $str .= $k . $v;

		  }

		}

		$str = $secret . $str;



		return md5($str);

	}



	function convert_simplexml_to_array($sxml) {

		$arr = array();

		if ($sxml) {

		  foreach ($sxml as $k => $v) {

				if($arr[$k]) {

					$arr[$k." ".(count($arr) + 1)] = self::convert_simplexml_to_array($v);

				}

				else{

					$arr[$k] = self::convert_simplexml_to_array($v);

				}

			}

		}

		if (sizeof($arr) > 0) {

		  return $arr;

		}

		else {

		  return (string)$sxml;

		}

	}



	function issuu_parse_xml($xml) {

		return;



		$encoding = 'UTF-8';

		$values = array();

		$index = array();

		$parser = xml_parser_create($encoding);

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);

		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

		$ok = xml_parse_into_struct($parser, $xml, $values, $index);



		if (!$ok) {

			watchdog("issuu", "Error parsing XML");

		}



		xml_parser_free($parser);

		$result = array();

		foreach($values as $key => $node){

			$result[$node['tag']] = $node['value'];

		}



		$result = $values;

		return $result;

	}



	function getdocresult() {

		return $this->docresult;

	}

	function geterror() {

		return $this->error;

	}

	function geterrormsg() {

		return $this->errormsg;

	}

}

function xml2array($fname){

  $sxi = new SimpleXmlIterator($fname, null, false);

  return sxiToArray($sxi);

}



function sxiToArray($sxi){

  $a = array();

  for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {

    if(!array_key_exists($sxi->key(), $a)){

      $a[$sxi->key()] = array();

    }

    if($sxi->hasChildren()){

      $a[$sxi->key()][] = sxiToArray($sxi->current());

    }

    else{

      $a[$sxi->key()][] = strval($sxi->current());

    }

  }

  return $a;

}



function _issuu_request($request_url, $params = NULL, $action = 'GET') {



  if (variable_get('issuu_log_requests', 0) && $params) {

    $apiaction = $params['action'];

    $doc_id = $params['doc_id'] ? $params['doc_id'] : $params['doc_ids'];

    $logparams = $params;

    if($logparams['apiKey']) $logparams['apiKey']='xxx';

    if($logparams['signature']) $logparams['signature']='xxx';

    $link = $request_url . issuu_list_params($logparams);

    watchdog('issuu', "Action: %apiaction, Document ID: %doc_id \n URL: %link", array('%apiaction' => $apiaction, '%doc_id' => $doc_id, '%link' => $link), WATCHDOG_NOTICE);

  }



  $platform = variable_get('issuu_request_framework', ISSUU_PLATFORM_EITHER);

  if ($platform == ISSUU_PLATFORM_EITHER) {

    if (function_exists("curl_init")) {

      $platform = ISSUU_PLATFORM_CURL;

    }

    else {

      $platform = ISSUU_PLATFORM_FOPEN;

    }

  }

  if ($platform == ISSUU_PLATFORM_CURL) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if ($action == 'POST') {

      curl_setopt($ch, CURLOPT_POST, 1 );

      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

      curl_setopt($ch, CURLOPT_URL, $request_url);

    }

    else {

      curl_setopt($ch, CURLOPT_URL, $request_url . issuu_list_params($params));

    }    

    $data = curl_exec($ch);

    if (curl_errno($ch)) {

      $link = $request_url . issuu_list_params($params);

      drupal_set_message(t("Request to issuu.com failed. See the event log for more details."), 'error');

      watchdog("issuu", "Request failed (CURL) - %err - %link", array('%err' => curl_error($ch), '%link' => $link), WATCHDOG_ERROR);

    }

    curl_close($ch);

    return $data;

  }

  else {

    $headers = array();

    $data = NULL;

    if ($action == "POST") {

      require_once drupal_get_path('module', 'issuu') . '/includes/multipart.inc';

      $boundary = 'A0sFSD';

      $headers = array("Content-Type" => "multipart/form-data; boundary=$boundary");

      $data = multipart_encode($boundary, $params);

      $request = drupal_http_request($request_url, $headers, $action, $data);

    }

    else {

      $request = drupal_http_request($request_url . issuu_list_params($params), $headers, $action, $data);

    }

    if ($request->error) {

      $link = $request_url . issuu_list_params($params);

      drupal_set_message(t("Request to issuu.com failed. See the event log for more details."), 'error');

      watchdog("issuu", t("Request failed (FOPEN) - %err - %link"), array('%err' => $request->error, '%link' => $link), WATCHDOG_ERROR);

      if ($request->code < 0) {

        watchdog("issuu", t("fsockopen might not be supported on your server. CURL must be installed or fsockopen enabled in order for the issuu module to work"), NULL, WATCHDOG_ERROR);

      }

    }

    return $request->data;

  }

}


function issuu_list_params($params) {



  if ($params == NULL) {

    return;

  }

  $output = '';

  foreach ($params as $key => $value) {

    $output .= "&$key=". urlencode($value);

  }

  return $output;

}

function db_last_insert_id($table, $field) {
  return db_result(db_query("SELECT CURRVAL('{" . db_escape_table($table) . "}_" . db_escape_table($field) . "_seq')"));
}

