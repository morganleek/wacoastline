<?php
  /*
    * Routing Custom API URL to WP Ajax Request
  */

  $api_query_vars = array(
    'wac_api_route',
    'snap_site',
    'from_date',
    'encoding'
  );

  function wac_register_rewrites() {
    global $wp_rewrite;

    add_rewrite_rule( 'wac-api/(.+?)/site/(.+?)/from/?([0-9]{1,})/?/(.+?)$', 'index.php?wac_api_route=$matches[1]&snap_site=$matches[2]&from_date=$matches[3]&encoding=$matches[4]', 'top' );
  }

  function wac_api_init() {
    global $api_query_vars;
    wac_register_rewrites();

    global $wp;
    foreach($api_query_vars as $var) {
      $wp->add_query_var( $var );  
    }
  }

  function wac_api_loaded() {
    if ( 
      empty( $GLOBALS['wp']->query_vars['wac_api_route'] ) && 
      empty( $GLOBALS['wp']->query_vars['snap_site'] ) && 
      empty( $GLOBALS['wp']->query_vars['from_date'] ) &&
      empty( $GLOBALS['wp']->query_vars['encoding'] )
    ) {
      return;
    }

    global $wpdb;

    $errors = array();
    
    extract($GLOBALS['wp']->query_vars);

    // Check Site Exits and get Post ID
		$post_site_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}posts` 
				WHERE `post_title` = '%s' 
				AND `post_status` = 'publish'
				LIMIT 1",
				$snap_site	
			)
		);

		if( $post_site_id == null ) {
			print 'Invalid "snap_site"';
			die();
		}

		$date = date('Y-m-d H:i:s', $from_date);

		$images = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}posts` 
				WHERE `post_date` >= '%s' 
				AND `post_parent` = '%d'
				AND `post_type` = 'attachment'",
				$date, $post_site_id
			)
		);

    $images_array = array();

    if($wpdb->num_rows > 0) {
      foreach($images as $k => $image) {
				$image_meta = array();

	      // S3 File
				$s3_path = $wpdb->get_row(
					$wpdb->prepare(
			      "SELECT * FROM `{$wpdb->prefix}as3cf_items` 
						WHERE `source_id` = %d",
						$image->ID
					) 
				);

				if($s3_path !== null) {
					$image_meta['AmazonS3'] = $s3_path->bucket . '/' . $s3_path->path;
				}
				else {
					$image_meta['AmazonS3'] = $image->ID;
				}

        $meta = $wpdb->get_results(
          $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}postmeta`
            WHERE `post_id` = %d",
            $image->ID 
          )
        );
        
        
        foreach($meta as $m) {
          // if($m->meta_key === "amazonS3_info") {
          //   // Deserialize
          //   $s3 = unserialize($m->meta_value);
          //   $image_meta['AmazonS3'] = $s3['bucket'] . '/' . $s3['key'];
          // }
          if(strpos($m->meta_key, 'bh_exif_') !== false) {
            $image_meta[str_replace('bh_exif_', '', $m->meta_key)] = $m->meta_value;
          }
          // else if(strpos($m->meta_key, '_wp_attached_file') !== false) {
	        //  $image_meta['ServerURL'] = wp_get_upload_dir()['url'] . $m->meta_value;
          // }
        }
        $image_meta['Author'] = $image->post_excerpt;
        $image_meta['CoastSnaps_Post_Date'] = $image->post_date;
        ksort($image_meta);
        $images_array[] = $image_meta;
      }
    }

    if($encoding == "json" || $encoding == "JSON") {
      header("Content-type: application/json; charset=utf-8");
      print json_encode($images_array);
    }
    else {
      header("Content-type: text/csv; charset=utf-8");
      $output = fopen('php://output', 'w');
      if(!empty($images_array)) {
        fputcsv($output, array_keys($images_array[0]));
      }
      
      foreach($images_array as $i) {
        fputcsv($output, $i);
      }
      fclose($output);
    }

    die();
  }

  add_action( 'init', 'wac_api_init' );
  add_action( 'parse_request', 'wac_api_loaded' );