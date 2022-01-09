<?php
set_time_limit(0);
//Store in DB API
function callAPI($method, $url, $data)
{
    $curl = curl_init();
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if (!$result) {
        echo "connection will fail";

        die("Connection Failure");
    }
    curl_close($curl);
    return $result;
}

//Obter data do imgur
function imgur_data($imgur_id)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, "https://api.allorigins.win/raw?url=https://imgur.com/ajaxalbums/getimages/$imgur_id/hit.json");
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

//Remover Dupes
function unique_key($array, $keyname)
{

    $new_array = array();
    foreach ($array as $key => $value) {

        if (!isset($new_array[$value[$keyname]])) {
            $new_array[$value[$keyname]] = $value;
        }

    }
    $new_array = array_values($new_array);
    return $new_array;
}

//Get the answers from Pushshift.
function get_content($URL)
{                   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

//Pushshift API to get data with Title, Images, Previews of Imgur.
function get_submission($link_id)
{
    $url = "https://api.pushshift.io/reddit/submission/search/?ids=$link_id&fields=id,title,thumbnail,media,preview,url,selftext,link_flair_text";
    return get_content($url);
}

//Pushshift API to get comments.
function get_comments($array_of_subreddits, $the_link_ids) #add the link_ids
{  
    $comments_data = [];
    foreach( $array_of_subreddits as $sub){
        $url = "https://api.pushshift.io/reddit/comment/search?&subreddit=sub";
        array_push($comments_data, get_content($url)); 
    }  
    return get_content($url);
}

 
#New results function
function results($timestamp, $subreddit, $size)
{
    echo "Results func is running";
    #added GL to improve greenlight detection
    $flairs_arr = ["LCQC", "QC/LC", "QC", "Quality Control", "QUALITY CHECK", "QC Pics (with Rule 5 format followed)", "QC Pics (with guidelines followed)", "GL", "QC CG"];
    $final_arr = [];

    #Added for red light counter
    $flairs_arr_rl = ["RL"]; 
    $final_arr_rl = []; 

    
    
    for ($i = 0; $i < 1; $i++) { 
 
        $URL = "https://api.pushshift.io/reddit/search/comment/?q=GL&subreddit=$subreddit&before=$timestamp&sort=desc&size=$size&fields=link_id,created_utc,permalink,id";
        
        $data = json_decode(get_content($URL), true);
        #echo $data;

        foreach ($data['data'] as $datum) {
            $datum['permalink'] = str_replace($datum['id'] . '/', '', $datum['permalink']);
            $titulo = explode('/', $datum['permalink']);
            $titulo = str_replace('/', '', str_replace('_', ' ', $titulo[5]));


            $no_dupes[] = array("reddit_link_id" => $datum['link_id'], "reddit_link" => $datum['permalink'], "reddit_title" => $titulo, "reddit_created_utc" => $datum['created_utc'], "imgur_iframe" => null, "thumbnail_link" => null, "w2c_link" => null);
            $timestamp = $datum['created_utc'];
            unset($titulo);
        }
        unset($data, $datum);

        if (!empty($no_dupes)) {
            $unique_arr = unique_key($no_dupes, 'reddit_link_id');
            $ids_arr = [];
            foreach ($unique_arr as $ids) {
                $ids_arr[] = $ids['reddit_link_id'];
            }
            unset($ids);

            $link_ids = implode(",", $ids_arr);

            #Calling of the get_submission function
            $submissions_arr = json_decode(get_submission($link_ids), true);

            foreach ($submissions_arr['data'] as $submissions_title) {

                $submissions_title['id'] = "t3_" . $submissions_title['id'];

                foreach ($unique_arr as $key => $value) { 
                    if ($value['reddit_link_id'] == $submissions_title['id']) {

                        #Red light logic
                        if (in_array($submissions_title['link_flair_text'], $flairs_arr_rl)) {
                            $rl_counter = 0;
                            foreach ($no_dupes as $rl_count) {
                                if ($rl_count['reddit_link_id'] == $value['reddit_link_id']) {
                                    $rl_counter++;
                                }
                            }
                            unset($rl_count);

                            $unique_arr[$key]['rl_counter'] = $rl_counter;
                            $unique_arr[$key]['reddit_title'] = $submissions_title['title'];
                        }

                        #Green light logic
                        if (in_array($submissions_title['link_flair_text'], $flairs_arr)) {
 
                            $gl_counter = 0;
                            foreach ($no_dupes as $gl_count) {
                                if ($gl_count['reddit_link_id'] == $value['reddit_link_id']) {
                                    $gl_counter++;
                                }
                            }
                            unset($gl_count);

                            $unique_arr[$key]['gl_counter'] = $gl_counter;
                            $unique_arr[$key]['reddit_title'] = $submissions_title['title'];

                            #Get submission (media) section
                            if (isset($submissions_title['media'])) {
                                $unique_arr[$key]['imgur_iframe'] = $submissions_title['media']['oembed']['url'];
                            }

                            #Get submission (Imgur) section
                            if (isset($submissions_title['media']['oembed']['url'])) {
                                
                                if (stripos($submissions_title['media']['oembed']['url'], 'weidian.com') !== false) { 
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url']; 
                                }

                                if (stripos($submissions_title['media']['oembed']['url'], 'taobao.com') !== false) { 
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url']; 
                                }

                                if (stripos($submissions_title['media']['oembed']['url'], 'wegobuy.com') !== false) { 
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url']; 
                                }

                                if (stripos($submissions_title['media']['oembed']['url'], 'x.yupoo.com') !== false) { 
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url']; 
                                }

                            }

                            #Get submission (URL) section
                            if (isset($submissions_title['url'])) {
                                if (stripos($submissions_title['url'], 'i.redd.it') !== false) {
                                    $unique_arr[$key]['thumbnail_link'] = $submissions_title['url'];
                                }

                                #Thumbnail not available
                                if (stripos($submissions_title['url'], 'imgur.com') !== false) {
                                    $unique_arr[$key]['thumbnail_link'] = $submissions_title['url'];
                                } 
                                #End of thumbnail not available

                                if (stripos($submissions_title['url'], 'imgur.com') !== false) {
                                    $unique_arr[$key]['imgur_iframe'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'taobao.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'weidian.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                } 
                                #Added for WC2 link better detection.
                                if (stripos($submissions_title['url'], 'wegobuy.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'x.yupoo.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                }
   
                            }

                            #Get submission (Selftext) / (Actual Post) Section.
                            if (isset($submissions_title['selftext'])) {
                                $re = '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
                                preg_match_all($re, $submissions_title['selftext'], $matches, PREG_SET_ORDER, 0);

                                if (!empty($matches)) {
                                    foreach ($matches as $match) {
                                        if (stripos($match[0], 'imgur.com') !== false) {
                                            $unique_arr[$key]['imgur_iframe'] = $match[0];
                                        }

                                        if (stripos($match[0], 'taobao.com') !== false) {
                                            $unique_arr[$key]['w2c_link'] = $match[0];
                                        }

                                        if (stripos($match[0], 'weidian.com') !== false) {
                                            $unique_arr[$key]['w2c_link'] = $match[0];
                                        }
                                        #Added for W2C better detection.
                                        if (stripos($match[0], 'wegobuy.com') !== false) {
                                            $unique_arr[$key]['w2c_link'] = $match[0];
                                        }

                                        if (stripos($match[0], 'x.yupoo.com') !== false) {
                                            $unique_arr[$key]['w2c_link'] = $match[0];
                                        }
                                    }
                                    unset($matches, $match);
                                }

                            }


                            #Get submission (Preview)
                            if (isset($submissions_title['preview'])) { 
                                $unique_arr[$key]['thumbnail_link'] = $submissions_title['preview']['images'][0]['source']['url'];
                            } elseif (isset($submissions_title['thumbnail'])) {

                                switch ($submissions_title['thumbnail']) {
                                    case "nsfw":
                                    case "self":
                                    case "default":
                                        $submissions_title['thumbnail'] = '';
                                        break;
                                }
                                $unique_arr[$key]['thumbnail_link'] = $submissions_title['thumbnail'];
                            }

                            if (isset($unique_arr[$key]['imgur_iframe'])) {

                                $id = pathinfo($unique_arr[$key]['imgur_iframe'], PATHINFO_FILENAME);
                                $imgur_data = json_decode(imgur_data($id), true);

                                if (!empty($imgur_data["data"]["images"])) {

                                    if ($unique_arr[$key]['thumbnail_link'] == null) {
                                        $imgur_url = $imgur_data["data"]["images"][0]["hash"] . $imgur_data["data"]["images"][0]["ext"];
                                        $unique_arr[$key]['thumbnail_link'] = "https://i.imgur.com/$imgur_url";
                                    }

                                    if (strtolower(pathinfo($unique_arr[$key]['thumbnail_link'], PATHINFO_EXTENSION)) == "mp4") {
                                        $unique_arr[$key]['thumbnail_link'] = null;
                                    }
                                
                                
                                    if (!isset($unique_arr[$key]['w2c_link']) || $unique_arr[$key]['w2c_link'] == null) {
                                        foreach ($imgur_data["data"]["images"] as $images) {
                                            if ($images['description'] != null || $images['description'] != "") {
                                                $image_description = str_replace(' ', '', $images['description']);

                                                $re = '/(http|ftp|https):\/\/([\w+?\.\w+])+([a-zA-Z0-9\~\!\@\#\$\%\^\&\*\(\)_\-\=\+\\\\\/\?\.\:\;\'\,]*)?/m';
                                                preg_match_all($re, $image_description, $matches, PREG_SET_ORDER, 0);

                                                if (!empty($matches)) {
                                                    foreach ($matches as $match) {
                                                        $unique_arr[$key]['w2c_link'] = $match[0];
                                                    }
                                                    unset($matches);
                                                }
                                                unset($image_description);
                                            }
                                        }
                                    }
                                }

                            }

                        } else {
                            unset($unique_arr[$key]);
                        }

                    }
                }
            }
            unset($submissions_arr, $submissions_title, $no_dupes);

            $final_arr[] = $unique_arr;
        }
    }

    # Comments for W2C better detection
    for ($i = 0; $i < 1; $i++) { 
    
        $URL = "https://api.pushshift.io/reddit/search/comment/?q=GL&subreddit=$subreddit&before=$timestamp&sort=desc&size=$size&fields=link_id,created_utc,permalink,id";
        
        $data = json_decode(get_content($URL), true);

        #Subreddit array to extract comments
        $array_of_subreddits = ["fashionreps", "repsneakers", "flexicas", "designerreps", "reptime", "repladies"]; 
        
        foreach ($data['data'] as $datum) {
            $datum['permalink'] = str_replace($datum['id'] . '/', '', $datum['permalink']);
            $titulo = explode('/', $datum['permalink']);
            $titulo = str_replace('/', '', str_replace('_', ' ', $titulo[5]));


            $no_dupes[] = array("reddit_link_id" => $datum['link_id'], "reddit_link" => $datum['permalink'], "reddit_title" => $titulo, "reddit_created_utc" => $datum['created_utc'], "imgur_iframe" => null, "thumbnail_link" => null, "w2c_link" => null);
            $timestamp = $datum['created_utc'];
            unset($titulo);
        }
        unset($data, $datum);

        if (!empty($no_dupes)) {
            $unique_arr = unique_key($no_dupes, 'reddit_link_id');
            $ids_arr = [];
            foreach ($unique_arr as $ids) {
                $ids_arr[] = $ids['reddit_link_id'];
            }
            unset($ids);

            $link_ids = implode(",", $ids_arr);
            $the_link_ids = $ids_arr;

            $comments_arr = json_decode(get_comments($array_of_subreddits, $link_ids), true); # only pass the link_ids
            foreach ($comments_arr['data'] as $comments){
                foreach ($unique_arr as $key => $value) {
                    if(!empty($value['reddit_title'])){ 
                        if (isset($comments['body'])) {

                            if (stripos($comments['body'], 'weidian.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }

                            if (stripos($comments['body'], 'taobao.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }

                            if (stripos($comments['body'], 'wegobuy.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }    
                        }
                    }
                    #W2C link detection (Original post)
                    if(in_array($comments['link_id'], $the_link_ids)){ 
                        if (isset($comments['body'])) {

                            if (stripos($comments['body'], 'weidian.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }

                            if (stripos($comments['body'], 'taobao.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }

                            if (stripos($comments['body'], 'wegobuy.com') !== false) {
                                $unique_arr[$key]['w2c_link'] = $comments['w2c_link'];
                            }
                        }
                        
                    }
                }
            }
        }  
    }

    echo "Data was pushed succesfuly";   
}  


$timestamp = time();
$subreddits_array = ["fashionreps", "repsneakers", "flexicas", "designerreps", "reptime", "repladies"];
#$subreddits_array = ["fashionreps"];
$size = 1000;

foreach ($subreddits_array as $subreddits) {
    
    $result = results($timestamp, $subreddits, $size);

    // foreach (results($timestamp, $subreddits, $size) as $result) {
        
    callAPI('PUT', "http://glfinder-api:8080/feeds/$subreddits", json_encode($result, JSON_UNESCAPED_SLASHES));
    #callAPI('PUT', "http://127.0.0.1:8080/feeds/$subreddits", json_encode($result, JSON_UNESCAPED_SLASHES));
        
    // }
    unset($result);
}
unset($subreddits);


?>
