<?php
    // functions
    require_once('class-IXR.php');

    function parse_img_tag($html){
        $img = array();
        $output = array();
        preg_match_all('/<img[^>]+>/i',$html, $result); 
        foreach( $result[0] as $img_tag){
            preg_match_all('/(alt|title|src|style)=("[^"]*")/i',$img_tag, $img[$img_tag]);
        }

        foreach($img as $imgtag => $imagedata){
            $src_index = array_search('src', $imagedata[1]);
            $alt_index = array_search('alt', $imagedata[1]);
            $title_index = array_search('title', $imagedata[1]);
            $style_index = array_search('style', $imagedata[1]);
            $tmpImgArry = array();
            if ($src_index !== false){
                $tmpImgArry['tag'] = $imgtag;
                $tmpImgArry['src'] = trim($imagedata[2][$src_index],' "');
                $tmpImgArry['mime'] = http_content_type($tmpImgArry['src']);
                if ($alt_index !== false) $tmpImgArry['alt'] = trim($imagedata[2][$alt_index], ' "');
                if ($title_index !== false) $tmpImgArry['title'] = trim($imagedata[2][$title_index], ' "');
                if ($style_index !== false) $tmpImgArry['style'] = trim($imagedata[2][$style_index], ' "');
            }
            if (! empty($tmpImgArry)) $output[] = $tmpImgArry;
        }
        return $output;
    }


    function http_content_type($url = '', $default_mime = 'application/octet-stream'){
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;  
        $content = curl_exec($ch);  
        if(!curl_errno($ch))  
        {  
            $info = curl_getinfo($ch);  
            $mime = $info['content_type'];  
            if (empty($mime)){
                $info = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);  
                $mime = $info;  
            }
        }  
        curl_close($ch);  
        if (empty($mime)) $mime = $default_mime;
        return $mime;
    }

    function xmlrpc_request($host, $user, $pass, $request, $blog, $params, &$output){
        $success = false;
        if (class_exists('IXR_Client')) :
        $XmlRpc_client = new IXR_Client ($host);
        try{
            $success = $XmlRpc_client->query(
            $request,
            $blog,
            $user,
            $pass,
            $params
            );
        }
        catch (Exception $e){
            //var_dump ( $e->getMessage ());
        }
        $output =@ ($success) ? $XmlRpc_client->message->params : $XmlRpc_client->error->message;
        endif;
        return $success;
    }

    function xmlrpc_publish($host, $user, $pass, $post_title, $content, &$output, $post_tags="", $post_categories=""){
        $success = false;
        if (! empty($content)){
            $content = stripslashes($content);
            // Publish Images
            $output_image = false;
            $imgArry = parse_img_tag($content);
            if (! empty($imgArry)){
                foreach ($imgArry as $image){
                    $remote_url = false;
                    $success_img = xmlrpc_publishMedia($host, $user, $pass, $image, $output_image);
                    if ($success_img){
                        $img_filename = basename($image['src']);
                        $img_filename_base = pathinfo($img_filename);
                        $img_filename_base = $img_filename_base['filename'];

                        $alt_text = empty($image['alt']) ? $img_filename_base : $image['alt'];
                        $title_text = empty($image['title']) ? $img_filename_base : $image['title'];
                        $style_text = empty($image['style']) ? "" : $image['style'];

                        $remote_url = $output_image[0]['url'];
                        $replacement_img_code = "<a href=\"$remote_url\"><img class=\"size-full\" title=\"$title_text\" src=\"$remote_url\" alt=\"$alt_text\" style=\"$style_text\" /></a>\n";
                        $content = str_replace($image['tag'], $replacement_img_code, $content);
                    } else {
                        $output = "[publish image " . $img_filename_base . "] " . $output_image;
                        return false;
                    }
                }
            }
            // Publish Post
            $success = xmlrpc_publishPost($host, $user, $pass, $post_title, $content, $output, $post_tags, $post_categories);
        }
        return $success;
    }

    function xmlrpc_publishMedia($host, $user, $pass, $imgArry, &$output_image){
        if (empty($imgArry)){
            return false;
        }
        if (! class_exists('IXR_Base64')){
            return false;
        }
        $imgData = file_get_contents($imgArry['src'], FILE_BINARY);
        $img_filename = basename($imgArry['src']);

        $imgArryParams = array(
        'name' => $img_filename,
        'type' => $imgArry['mime'],
        'bits' => new IXR_Base64 ($imgData)
        );

        $success_img = xmlrpc_request(
        $host, 
        $user, 
        $pass,
        'metaWeblog.newMediaObject', 
        0,
        $imgArryParams,
        $output_image
        );

        return $success_img;
    } 

    function xmlrpc_publishPost($host, $user, $pass, $post_title, $content, &$output_post, $post_tags="", $post_categories=""){
        $post_categories = explode(",", htmlspecialchars($post_categories));
        array_walk($post_categories, 'xmlrpc_trim_value');
        xmlrpc_validate_categories($host, $user, $pass, $post_categories);

        $success = false;
        $postParams = array(
        'title'=>$post_title,
        'description'=>$content,
        'mt_allow_comments'=>0,  // 1 to allow comments
        'mt_allow_pings'=>0,  // 1 to allow trackbacks
        'post_type'=>'post',
        'mt_keywords'=>$post_tags,
        'categories'=>$post_categories,
        'post_status'=>'publish',
        'post_type'=>'post'
        );

        $success_post = xmlrpc_request(
        $host, 
        $user, 
        $pass,
        'metaWeblog.newPost',
        0,
        $postParams,
        $output_post
        );
        return $success_post;
    }

    function xmlrpc_validate_categories($host, $user, $pass, $catArry){
        if (empty($catArry) || (! is_array($catArry))) return;
        $output = false;
        $categories = xmlrpc_request($host, $user, $pass, 'wp.getCategories', 0, array(), $output);
        if (is_array($output)){
            $_output = false;
            foreach($catArry as $cat_name){
                $cat_exists = xmlrpc_in_array_r($cat_name, $output);
                if (! $cat_exists){
                    $params = array('name' => ucwords($cat_name), 'description' => '');
                    $new_cat = xmlrpc_request($host, $user, $pass, 'wp.newCategory', 0, $params, $_output);
                }
            }
        }
    }

    function xmlrpc_host_script($host){
        $_host = trailingslashit($host);
        $script = $_host . 'xmlrpc.php';
        return (url_exists($script)) ? $script : false;        
    }

    if (! function_exists('xmlrpc_in_array_r')):
        function xmlrpc_in_array_r($needle, $haystack, $strict = true) {
            foreach ($haystack as $item) {
                if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && xmlrpc_in_array_r($needle, $item, $strict))) {
                    return true;
                }
            }

            return false;
        }
        endif; 

    if (! function_exists('xmlrpc_slugify')) :        
        function xmlrpc_slugify($str) {
            $str = strtolower(trim($str));
            $str = preg_replace('/[^a-z0-9-]/', '-', $str);
            $str = preg_replace('/-+/', "-", $str);
            return $str;
        }
        endif;

    if (! function_exists('xmlrpc_trim_value')) :        
        function xmlrpc_trim_value(&$value) 
        { 
            $value = trim($value); 
        }
        endif;

    if (! function_exists('trailingslashit')):
        function trailingslashit($string) {
            return untrailingslashit($string) . '/';
        }
        endif;

    if (! function_exists('untrailingslashit')):
        function untrailingslashit($string) {
            return rtrim($string, '/');
        }
        endif;

    if (! function_exists('url_exists')) :
        function url_exists($url) {
            if (!$fp = curl_init($url)) return false;
            return true;
        }
        endif;    

?>
