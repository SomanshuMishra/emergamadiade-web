<?php

error_reporting(E_ERROR);

set_time_limit(0);
// Store in DB by API Request
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
        die("$current_date - Could not connect to glfinder-api.");
    } else {
        echo "$current_date - Sending data to the database - request successful.";
    }
    curl_close($curl);
    return $result;
}

// Get data from imgur
function imgur_data($imgur_id)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, "https://api.allorigins.win/raw?url=https://imgur.com/ajaxalbums/getimages/$imgur_id/hit.json");
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $result = curl_exec($ch);
    if (!$result) {
        die("$current_date - Could not connect to api.allorigin.win (CDN to IMGUR).");
    } else {
        echo "$current_date - Retrieving data from AllOrigins (IMGUR CDN) - request successful";
    }
    curl_close($ch);
    return $result;
}

// Remove Duplicates
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

// Get PushShift API Responses
function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $data = curl_exec($ch); 
    if (!$data) {
        die("$current_date - Could not connect to PushShift.");
    } else {
        echo "$current_date - Retrieving data from PushShift - request successful";
    }
    curl_close($ch);
    return $data;
}

// Get IMGUR data with title, images and previews
function get_submission($link_id)
{
    $url = "https://api.pushshift.io/reddit/submission/search/?ids=$link_id&fields=id,title,thumbnail,media,preview,url,selftext,link_flair_text";
    return get_content($url);
}

// Get everything that has been GL'd by keywords (line 102)
function results($timestamp, $subreddit, $size)
{
    $flairs_arr = ["LCQC", "QC/LC", "QC", "Quality Control", "QUALITY CHECK", "QC Pics (with guidelines followed)", "QC Pics (with Rule 5 format followed)"];
    $final_arr = [];

    for ($i = 0; $i < 10; $i++) {

        $URL = "https://api.pushshift.io/reddit/search/comment/?q=GL&subreddit=$subreddit&before=$timestamp&sort=desc&size=$size&fields=link_id,created_utc,permalink,id";

        $data = json_decode(get_content($URL), true);

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

            $submissions_arr = json_decode(get_submission($link_ids), true);

            foreach ($submissions_arr['data'] as $submissions_title) {

                $submissions_title['id'] = "t3_" . $submissions_title['id'];

                foreach ($unique_arr as $key => $value) {
                    if ($value['reddit_link_id'] == $submissions_title['id']) {
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

                            if (isset($submissions_title['media'])) {
                                $unique_arr[$key]['imgur_iframe'] = $submissions_title['media']['oembed']['url'];
                            }

                            if (isset($submissions_title['url'])) {
                                if (stripos($submissions_title['url'], 'i.redd.it') !== false) {
                                    $unique_arr[$key]['thumbnail_link'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'imgur.com') !== false) {
                                    $unique_arr[$key]['imgur_iframe'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'taobao.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                }

                                if (stripos($submissions_title['url'], 'weidian.com') !== false) {
                                    $unique_arr[$key]['w2c_link'] = $submissions_title['url'];
                                }
                            }

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
                                    }
                                    unset($matches, $match);
                                }

                            }

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

    return array_reduce($final_arr, 'array_merge', array());
}

$timestamp = time();
$subreddits_array = ["fashionreps", "repsneakers", "flexicas", "designerreps", "reptime", "repladies","couturereps"];
$size = 1000;

foreach ($subreddits_array as $subreddits) {
    foreach (results($timestamp, $subreddits, $size) as $result) {

        // callAPI('PUT', "http://glfinder-api:8080/feeds/$subreddits", json_encode($result, JSON_UNESCAPED_SLASHES));
        callAPI('PUT', "http://127.0.0.1:5000/feeds/$subreddits", json_encode($result, JSON_UNESCAPED_SLASHES));

    }
    unset($result);
}
unset($subreddits);


?>

